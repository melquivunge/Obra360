<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['project_id', 'title', 'description', 'type', 'happened_at', 'user_id'])]
class ProjectUpdate extends Model
{
    public const TYPES = [
        'geral' => 'Atualização geral',
        'foto' => 'Foto adicionada',
        'despesa' => 'Despesa registrada',
        'fase' => 'Mudança de fase',
        'documento' => 'Documento adicionado',
        'status' => 'Mudança de status',
    ];

    protected function casts(): array
    {
        return ['happened_at' => 'datetime'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(int $projectId, string $title, string $type = 'geral', ?string $description = null): void
    {
        static::create([
            'project_id' => $projectId,
            'title' => $title,
            'description' => $description,
            'type' => $type,
            'happened_at' => now(),
            'user_id' => auth()->id(),
        ]);
    }
}
