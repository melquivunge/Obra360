<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['project_id', 'client_id', 'title', 'description', 'amount', 'type', 'status', 'due_date', 'barcode', 'pix_key', 'pix_qrcode', 'paid_at'])]
class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'amount'  => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    public const TYPES = ['boleto' => 'Boleto Bancário', 'pix' => 'PIX'];
    public const STATUSES = ['pending' => 'Pendente', 'paid' => 'Pago', 'cancelled' => 'Cancelado'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
