<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'client_id', 'manager_id', 'name', 'code', 'type', 'source_project_id', 'description', 'location',
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

    /**
     * OBRA-01: os tipos que podem ser origem de uma obra. Uma obra não nasce de
     * outra obra — nasce do desenho que a antecedeu.
     */
    public const SOURCE_TYPES = ['arquitetura', 'design_interiores'];

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

    /** PLAN-01: as plantas deste projecto, da mais recente para a mais antiga. */
    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class)->latest();
    }

    /**
     * OBRA-01: o projecto de arquitectura ou de design que deu origem a esta
     * obra. Nulo na esmagadora maioria das obras.
     *
     * A relação respeita o SoftDeletes do projecto de origem de propósito: uma
     * origem na reciclagem devolve `null` e tudo o que dela depende — as
     * plantas herdadas, o cartão de continuidade no portal — simplesmente
     * deixa de aparecer, em vez de rebentar a página com um `->name` sobre
     * nulo. A coluna não é limpa: restaurar o projecto devolve a ligação
     * inteira. Para dizer ao gestor que a origem existiu e foi removida há
     * hasMissingSource().
     */
    public function sourceProject(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_project_id');
    }

    /**
     * OBRA-03: a obra (ou obras) que nasceram deste projecto. É plural porque
     * um projecto de arquitectura pode ser construído por fases, cada uma sua
     * obra, e o inverso — recusar a segunda — não traria nada a ninguém.
     */
    public function derivedProjects(): HasMany
    {
        return $this->hasMany(self::class, 'source_project_id')->latest();
    }

    /** OBRA-01: só um projecto de arquitectura ou de design pode ser origem. */
    public function canBeSource(): bool
    {
        return in_array($this->type, self::SOURCE_TYPES, true);
    }

    /**
     * A ligação existe mas a origem já não está visível — foi para a
     * reciclagem. Vale a pena dizê-lo na área interna: sem isto as plantas
     * herdadas desapareciam da obra sem explicação nenhuma.
     */
    public function hasMissingSource(): bool
    {
        return $this->source_project_id !== null && $this->sourceProject === null;
    }

    /**
     * OBRA-02: as plantas aprovadas do projecto de origem, sem cópia dos
     * ficheiros — a planta continua a ser do projecto que a desenhou.
     *
     * «Aprovada» é lido na versão e não no estado da planta: o estado passa a
     * `substituida` mal entre uma revisão nova, e uma planta assinada pelo
     * cliente não deixa de estar assinada só porque o estúdio começou a mexer
     * na revisão seguinte. O que a obra mostra é a versão aprovada mais
     * recente — o último desenho que o cliente aceitou levar para a construção.
     * Um desenho por aprovar nunca entra aqui.
     *
     * @return Collection<int, Plan>
     */
    public function inheritedApprovedPlans(): Collection
    {
        $source = $this->sourceProject;

        if ($source === null) {
            return new Collection;
        }

        $approved = fn ($query) => $query->whereNotNull('approved_at')->orderByDesc('version');

        return $source->plans()
            ->whereHas('versions', $approved)
            ->with(['versions' => $approved, 'versions.approver'])
            ->get();
    }

    /**
     * Uma obra não pode ser origem de si própria, nem fechar um ciclo com quem
     * já a aponta. Sobe a cadeia a partir do candidato: se lá encontrar este
     * projecto, a ligação fecharia o círculo. O registo dos visitados trava o
     * ciclo que já exista nos dados, para a verificação não correr para sempre.
     */
    public function wouldCycleThroughSource(int $candidateId): bool
    {
        $target = (int) $this->getKey();
        $seen = [];
        $currentId = $candidateId;

        while ($currentId !== null) {
            if ($currentId === $target || isset($seen[$currentId])) {
                return true;
            }

            $seen[$currentId] = true;

            $next = self::withTrashed()->whereKey($currentId)->value('source_project_id');
            $currentId = $next === null ? null : (int) $next;
        }

        return false;
    }

    /**
     * Se o separador «Plantas» faz sentido neste projecto.
     *
     * Num projecto de arquitectura ou de design de interiores as plantas são o
     * produto: está sempre lá. Numa obra de construção ou remodelação são
     * material de apoio — existe muitas vezes, mas nem sempre — por isso só
     * aparece quando já há plantas. Quem as pode criar vê-o de qualquer forma
     * (a vista junta o `manage-projects` a esta condição), senão a primeira
     * planta de uma obra nunca poderia ser criada.
     */
    public function showsPlans(): bool
    {
        if (! $this->isSiteWork()) {
            return true;
        }

        return $this->relationLoaded('plans')
            ? $this->plans->isNotEmpty()
            : $this->plans()->exists();
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
     * Como nomear um conjunto de projectos do mesmo cliente — no título do
     * painel e na navegação do portal. Quem só contratou arquitectura ou
     * design de interiores não tem obra nenhuma para ver ali (TIPO-04).
     */
    public static function collectionLabel(bool $hasSiteWork, bool $hasDesignWork): string
    {
        return match (true) {
            $hasSiteWork && $hasDesignWork => 'Minhas Obras e Projectos',
            $hasDesignWork => 'Meus Projectos',
            default => 'Minhas Obras',
        };
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
