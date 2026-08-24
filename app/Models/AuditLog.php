<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'user_name', 'action', 'auditable_type', 'auditable_id',
    'description', 'changes', 'ip_address',
])]
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    public const ACTIONS = [
        'created' => 'Criação',
        'updated' => 'Alteração',
        'deleted' => 'Remoção',
        'restored' => 'Restauração',
    ];

    /** Human labels for the models we audit. */
    public const LABELS = [
        Project::class => 'Obra',
        Client::class => 'Cliente',
        User::class => 'Usuário',
        Expense::class => 'Despesa',
        Payment::class => 'Pagamento',
        Photo::class => 'Foto',
        Document::class => 'Documento',
        ProjectPhase::class => 'Fase',
        Setting::class => 'Configuração',
    ];

    protected function casts(): array
    {
        return ['changes' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subjectLabel(): string
    {
        return self::LABELS[$this->auditable_type] ?? class_basename($this->auditable_type);
    }

    public function actionLabel(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }
}
