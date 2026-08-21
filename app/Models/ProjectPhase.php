<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'project_id', 'name', 'description', 'status', 'percentage',
    'sort_order', 'start_date', 'expected_end_date', 'real_end_date',
])]
class ProjectPhase extends Model
{
    use HasFactory;

    public const STATUSES = [
        'pendente' => 'Pendente',
        'em_andamento' => 'Em andamento',
        'concluida' => 'Concluída',
        'pausada' => 'Pausada',
        'aguardando_aprovacao' => 'Aguardando aprovação',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'expected_end_date' => 'date',
            'real_end_date' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class, 'phase_id');
    }

    protected static function booted(): void
    {
        static::saved(function (ProjectPhase $phase) {
            $phase->project->recalcProgress();
        });
        static::deleted(function (ProjectPhase $phase) {
            $phase->project->recalcProgress();
        });
    }
}
