<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'project_id', 'name', 'type', 'visibility', 'path', 'notes', 'uploaded_by',
])]
class Document extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPES = [
        'contrato' => 'Contrato',
        'orcamento' => 'Orçamento',
        'planta' => 'Planta',
        'recibo' => 'Recibo',
        'licenca' => 'Licença',
        'relatorio' => 'Relatório',
        'nota_fiscal' => 'Nota fiscal',
        'boleto' => 'Boleto',
        'outro' => 'Outro',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isClientVisible(): bool
    {
        return $this->visibility === 'cliente';
    }
}
