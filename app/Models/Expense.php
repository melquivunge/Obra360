<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'project_id', 'category', 'description', 'amount',
    'spent_at', 'supplier', 'receipt_path', 'notes', 'created_by',
])]
class Expense extends Model
{
    use SoftDeletes;

    public const CATEGORIES = [
        'material' => 'Material',
        'mao_de_obra' => 'Mão de obra',
        'transporte' => 'Transporte',
        'equipamento' => 'Equipamento',
        'documentacao' => 'Documentação',
        'licenca' => 'Licença',
        'terceirizado' => 'Terceirizado',
        'alimentacao' => 'Alimentação',
        'outro' => 'Outro',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
