<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
            'start_date' => 'date',
            'expected_end_date' => 'date',
            'completed_at' => 'datetime',
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

    public function latestImage(): HasOne
    {
        return $this->hasOne(Photo::class)
            ->where('media_type', 'image')
            ->latestOfMany();
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

    public function remainingBalance(): float
    {
        return (float) ($this->budget ?? 0) - $this->totalSpent();
    }

    /**
     * Percentage of the budget already spent. Returns 0 when there is no budget
     * so the financial section renders instead of dividing by zero (FIN-04).
     */
    public function budgetUsedPercent(): float
    {
        $budget = (float) ($this->budget ?? 0);

        return $budget > 0 ? round($this->totalSpent() / $budget * 100, 1) : 0.0;
    }

    /**
     * Expense totals per category, ordered by amount (FIN-02).
     *
     * @return array<int, array{key: string, label: string, total: float, percent: float}>
     */
    public function expensesByCategory(): array
    {
        $rows = $this->expenses()
            ->reorder()
            ->selectRaw('category, SUM(amount) AS total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->pluck('total', 'category');

        $spent = (float) $rows->sum();

        return $rows->map(fn ($total, $category) => [
            'key' => $category,
            'label' => Expense::CATEGORIES[$category] ?? $category,
            'total' => (float) $total,
            'percent' => $spent > 0 ? round((float) $total / $spent * 100, 1) : 0.0,
        ])->values()->all();
    }

    /**
     * Expense totals per month, oldest first (FIN-03).
     *
     * @return array<int, array{month: string, label: string, total: float}>
     */
    public function expensesByMonth(): array
    {
        // ponytail: substr on the date column works the same on MySQL and SQLite,
        // unlike DATE_FORMAT/strftime which differ between the two.
        return $this->expenses()
            ->reorder()
            ->selectRaw('SUBSTR(spent_at, 1, 7) AS month, SUM(amount) AS total')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'month' => $row->month,
                'label' => Carbon::createFromFormat('Y-m', $row->month)->translatedFormat('M/Y'),
                'total' => (float) $row->total,
            ])
            ->all();
    }
}
