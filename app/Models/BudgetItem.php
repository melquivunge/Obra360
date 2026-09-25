<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uma linha do orçamento: quanto se planeia gastar numa categoria.
 *
 * Partilha as categorias com as despesas de propósito — é essa partilha que
 * permite pôr o planeado e o gasto lado a lado sem ninguém ter de mapear nada
 * à mão.
 */
#[Fillable(['project_id', 'category', 'description', 'amount', 'sort_order'])]
class BudgetItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function categoryLabel(): string
    {
        return __(Expense::CATEGORIES[$this->category] ?? $this->category);
    }
}
