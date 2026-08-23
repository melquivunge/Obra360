<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PLAN-01: um desenho identificado dentro de um projecto — «Planta baixa —
 * piso 0» — e não um ficheiro. Os ficheiros são as versões (PlanVersion).
 */
#[Fillable(['project_id', 'name', 'discipline', 'status', 'notes', 'created_by'])]
class Plan extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * 2D e 3D não são só uma etiqueta: nenhum formato 3D é pré-visualizável no
     * browser, por isso a disciplina governa o que a interface promete mostrar.
     */
    public const DISCIPLINES = [
        '2d' => 'Desenho 2D',
        '3d' => 'Modelo 3D',
    ];

    /**
     * PLAN-05. `substituida` não significa «apagada»: significa que o cliente
     * já viu — ou aprovou — uma versão que deixou de ser a corrente. Voltar a
     * `em_elaboracao` nesse momento apagaria essa informação.
     */
    public const STATUSES = [
        'em_elaboracao' => 'Em elaboração',
        'enviada' => 'Enviada ao cliente',
        'aprovada' => 'Aprovada',
        'substituida' => 'Substituída',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'discipline' => '2d',
        'status' => 'em_elaboracao',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Da mais recente para a mais antiga — é assim que o histórico se lê. */
    public function versions(): HasMany
    {
        return $this->hasMany(PlanVersion::class)->orderByDesc('version');
    }

    /** PLAN-04: a versão de número mais alto é a corrente. */
    public function currentVersion(): HasOne
    {
        return $this->hasOne(PlanVersion::class)->ofMany('version', 'max');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function disciplineLabel(): string
    {
        return self::DISCIPLINES[$this->discipline] ?? $this->discipline;
    }

    /**
     * O número que a próxima versão vai receber (PLAN-02). Lê o máximo em vez
     * de contar as linhas: contar daria o número errado assim que uma versão
     * fosse removida à mão da base de dados.
     */
    public function nextVersionNumber(): int
    {
        // reorder() porque `versions()` traz um ORDER BY que não pertence a uma
        // agregação — o MySQL em ONLY_FULL_GROUP_BY recusa-o.
        return (int) $this->versions()->reorder()->max('version') + 1;
    }
}
