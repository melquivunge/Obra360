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
    'client_id', 'manager_id', 'name', 'code', 'type', 'description', 'location',
    'start_date', 'expected_end_date', 'completed_at', 'budget', 'status',
    'progress', 'show_financials', 'cover_image_path', 'notes',
])]
class Project extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * TIPO-01: o que a empresa vende. Governa o vocabulário da interface e,
     * mais tarde, o que cada página mostra — não uma tabela à parte.
     */
    public const TYPES = [
        'construcao' => 'Construção',
        'remodelacao' => 'Remodelação',
        'arquitetura' => 'Projecto de arquitectura',
        'design_interiores' => 'Design de interiores',
    ];

    /**
     * Tipos que correspondem a obra física. Os restantes são serviços de
     * projecto: chamar-lhes «obra» ao cliente estaria errado.
     */
    public const SITE_TYPES = ['construcao', 'remodelacao'];

    public const STATUSES = [
        'planejada' => 'Planejada',
        'em_andamento' => 'Em andamento',
        'pausada' => 'Pausada',
        'aguardando_aprovacao' => 'Aguardando aprovação',
        'concluida' => 'Concluída',
        'cancelada' => 'Cancelada',
    ];

    /**
     * TIPO-02: um projecto sem tipo declarado é uma obra de construção — o
     * mesmo valor com que a migração preencheu as linhas já existentes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => 'construcao',
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

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /** Verdadeiro para construção e remodelação; falso para os serviços de projecto. */
    public function isSiteWork(): bool
    {
        return in_array($this->type, self::SITE_TYPES, true);
    }

    /**
     * O substantivo a usar na interface: «obra» para quem contratou construção
     * ou remodelação, «projecto» para quem contratou só arquitectura ou design
     * de interiores.
     */
    public function noun(): string
    {
        return $this->isSiteWork() ? 'obra' : 'projecto';
    }

    /**
     * O mesmo substantivo com o artigo concordado — «a obra», «o projecto» —
     * para as frases da interface que o antecedem de uma preposição.
     */
    public function nounWithArticle(): string
    {
        return ($this->isSiteWork() ? 'a ' : 'o ').$this->noun();
    }

    /**
     * O progresso é derivado da média das fases, salvo quando a definição
     * `progress_mode` está em `manual` — aí o valor é do gestor e as fases
     * não lhe tocam (FASE-04).
     */
    public function recalcProgress(): void
    {
        if (static::progressIsManual()) {
            return;
        }

        $avg = $this->phases()->avg('percentage') ?? 0;
        $this->update(['progress' => (int) round($avg)]);
    }

    public static function progressIsManual(): bool
    {
        return Setting::get('progress_mode', 'derivado') === 'manual';
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
