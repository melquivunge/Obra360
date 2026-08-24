<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Um comentário sobre uma versão de planta. Ver a migração para o motivo de
 * estar preso à versão e não à planta.
 */
#[Fillable(['plan_version_id', 'user_id', 'body'])]
class PlanComment extends Model
{
    use HasFactory;

    public function version(): BelongsTo
    {
        return $this->belongsTo(PlanVersion::class, 'plan_version_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** O autor saiu da equipa: o comentário fica, sem dono identificado. */
    public function authorName(): string
    {
        return $this->author?->name ?? 'Utilizador removido';
    }

    /** Distingue o lado do cliente do lado da equipa na conversa. */
    public function isFromClient(): bool
    {
        return $this->author?->isClient() ?? false;
    }
}
