<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'client_id', 'manager_id', 'name', 'code', 'description', 'location',
    'start_date', 'expected_end_date', 'completed_at', 'budget', 'status',
    'progress', 'show_financials', 'cover_image_path', 'notes',
])]
class Project extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUSES = [
        'planejada' => 'Planejada',
        'em_andamento' => 'Em andamento',
        'pausada' => 'Pausada',
        'aguardando_aprovacao' => 'Aguardando aprovação',
        'concluida' => 'Concluída',
        'cancelada' => 'Cancelada',
    ];

    protected function casts(): array
    {
        return [
            'show_financials' => 'boolean',
            'budget' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function phases(): HasMany
    {
        return $this->hasMany(ProjectPhase::class)->orderBy('sort_order');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class)->latest();
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class)->latest('spent_at');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class)->latest();
    }

    public function updates(): HasMany
    {
        return $this->hasMany(ProjectUpdate::class)->latest();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->oldest();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest();
    }

    public function recalcProgress(): void
    {
        $avg = $this->phases()->avg('percentage') ?? 0;
        $this->update(['progress' => (int) round($avg)]);
    }

    public function totalSpent(): float
    {
        return (float) $this->expenses()->sum('amount');
    }
}
