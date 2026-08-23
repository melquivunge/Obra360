<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PLAN-02/PLAN-03: uma revisão numerada de uma planta. O histórico nunca é
 * apagado — daí não haver SoftDeletes aqui: uma versão substituída continua a
 * ser uma linha viva, marcada em `superseded_at` (PLAN-04).
 */
#[Fillable([
    'plan_id', 'version', 'path', 'original_name', 'format', 'size',
    'external_url', 'change_note', 'uploaded_by', 'superseded_at',
    'sent_to_client_at', 'approved_by', 'approved_at',
])]
class PlanVersion extends Model
{
    use HasFactory;

    /**
     * PLAN-07. Nota sobre a regra de validação: `mimes:` resolve a extensão
     * contra o mapa de tipos MIME do Symfony, que não conhece dwg, skp, rvt,
     * ifc nem glb — um DWG legítimo chega como `application/octet-stream` e
     * seria recusado. A validação usa `extensions:` (Laravel 11+), que compara
     * a extensão declarada e mantém o bloqueio de uploads PHP.
     */
    public const FORMATS = [
        'pdf', 'jpg', 'jpeg', 'png', 'webp',
        'dwg', 'dxf', 'skp', 'rvt', 'ifc',
        'glb', 'gltf', 'zip',
    ];

    /**
     * O tecto usado quando o PHP diz «sem limite» nas duas directivas. É o
     * valor de `docker/php/local.ini`, que é também o do alojamento partilhado.
     */
    public const FALLBACK_MAX_KILOBYTES = 65536;

    /** Formatos que o browser mostra sem descarregar (PORT-03, fatia seguinte). */
    public const PREVIEWABLE_FORMATS = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'size' => 'integer',
            'superseded_at' => 'datetime',
            'sent_to_client_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isSuperseded(): bool
    {
        return $this->superseded_at !== null;
    }

    /** PLAN-08: a versão vive num endereço externo em vez de um ficheiro nosso. */
    public function isExternal(): bool
    {
        return $this->path === null && $this->external_url !== null;
    }

    public function wasSentToClient(): bool
    {
        return $this->sent_to_client_at !== null;
    }

    public function label(): string
    {
        return 'v'.$this->version;
    }

    /** O tamanho em unidades legíveis; nulo quando a versão é um endereço externo. */
    public function humanSize(): ?string
    {
        if (! $this->size) {
            return null;
        }

        return $this->size >= 1048576
            ? number_format($this->size / 1048576, 1, ',', ' ').' MB'
            : number_format(max($this->size / 1024, 1), 0, ',', ' ').' KB';
    }

    /**
     * LIM-01: o tecto real deste servidor, em kilobytes — o mesmo número que
     * alimenta a regra `max:` e o texto do formulário. Lê o `php.ini` em vez de
     * repetir um valor à mão: um servidor mal configurado tem de dizer a verdade
     * sobre o que aceita, não o que gostaríamos que aceitasse.
     *
     * `post_max_size` entra no cálculo porque é ele que trava o pedido inteiro:
     * um ficheiro dentro de `upload_max_filesize` mas acima de `post_max_size`
     * nunca chega ao Laravel.
     */
    public static function maxUploadKilobytes(): int
    {
        $limits = array_filter([
            self::iniBytes((string) ini_get('upload_max_filesize')),
            self::iniBytes((string) ini_get('post_max_size')),
        ], fn (int $bytes): bool => $bytes > 0);

        return $limits === []
            ? self::FALLBACK_MAX_KILOBYTES
            : max(1, intdiv((int) min($limits), 1024));
    }

    /** O mesmo tecto em texto para a interface e para as mensagens de erro. */
    public static function maxUploadLabel(): string
    {
        $kilobytes = self::maxUploadKilobytes();

        return $kilobytes >= 1024
            ? rtrim(rtrim(number_format($kilobytes / 1024, 1, ',', ' '), '0'), ',').' MB'
            : $kilobytes.' KB';
    }

    /**
     * LIM-02: o ficheiro chegou ao servidor e é grande de mais. As mensagens do
     * limite vivem aqui, e não espalhadas pelos controladores, porque as duas
     * têm de citar o mesmo número que o formulário anuncia.
     */
    public static function overLimitMessage(): string
    {
        return sprintf(
            'O ficheiro excede o limite de %s do servidor. Escolha um ficheiro mais pequeno '
            .'ou guarde esta versão com um endereço externo (Google Drive, WeTransfer, '
            .'OneDrive) em vez do ficheiro.',
            self::maxUploadLabel(),
        );
    }

    /** LIM-02: o pedido era tão grande que o PHP o descartou antes de o entregar. */
    public static function truncatedUploadMessage(): string
    {
        return sprintf(
            'O envio excedeu o limite de %s do servidor e não chegou a ser recebido. '
            .'Escolha um ficheiro mais pequeno ou guarde esta versão com um endereço externo '
            .'(Google Drive, WeTransfer, OneDrive) em vez do ficheiro.',
            self::maxUploadLabel(),
        );
    }

    /** Converte «64M», «2G», «512K» ou «0» em bytes. */
    private static function iniBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '') {
            return 0;
        }

        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
