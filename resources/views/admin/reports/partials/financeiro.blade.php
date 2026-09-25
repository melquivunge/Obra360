<table class="rep-table">
    <tbody>
        <tr><th>{{ __('Orçamento') }}</th><td>{{ money($project->budget ?? 0) }}</td></tr>
        <tr><th>{{ __('Total gasto') }}</th><td>{{ money($totalSpent) }}</td></tr>
        <tr><th>{{ __('Saldo') }}</th><td>{{ money($project->remainingBalance()) }}</td></tr>
        <tr><th>{{ __('Orçamento usado') }}</th><td>{{ $project->budgetUsedPercent() }}%</td></tr>
    </tbody>
</table>

<h3 class="rep-h3">{{ __('Despesas por categoria') }}</h3>
@if(empty($byCategory))
    <p class="rep-empty">{{ __('Sem despesas registradas.') }}</p>
@else
    <table class="rep-table">
        <thead><tr><th>{{ __('Categoria') }}</th><th class="num">{{ __('Total') }}</th><th class="num">{{ __('% do gasto') }}</th></tr></thead>
        <tbody>
        @foreach($byCategory as $row)
            <tr>
                <td>{{ __($row['label']) }}</td>
                <td class="num">{{ money($row['total']) }}</td>
                <td class="num">{{ $row['percent'] }}%</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot><tr><th>{{ __('Total') }}</th><th class="num">{{ money($totalSpent) }}</th><th class="num">100%</th></tr></tfoot>
    </table>
@endif

<h3 class="rep-h3">{{ __('Despesas por mês') }}</h3>
@if(empty($byMonth))
    <p class="rep-empty">{{ __('Sem despesas registradas.') }}</p>
@else
    <table class="rep-table">
        <thead><tr><th>{{ __('Mês') }}</th><th class="num">{{ __('Total') }}</th></tr></thead>
        <tbody>
        @foreach($byMonth as $row)
            <tr><td>{{ __($row['label']) }}</td><td class="num">{{ money($row['total']) }}</td></tr>
        @endforeach
        </tbody>
    </table>
@endif

<h3 class="rep-h3">{{ __('Lançamentos (') }}{{ $expenses->count() }})</h3>
@if($expenses->isEmpty())
    <p class="rep-empty">{{ __('Sem despesas registradas.') }}</p>
@else
    <table class="rep-table">
        <thead><tr><th>{{ __('Data') }}</th><th>{{ __('Descrição') }}</th><th>{{ __('Categoria') }}</th><th>{{ __('Fornecedor') }}</th><th class="num">{{ __('Valor') }}</th></tr></thead>
        <tbody>
        @foreach($expenses as $e)
            <tr>
                <td>{{ $e->spent_at?->format(app()->getLocale() === 'en' ? 'm/d/Y' : 'd/m/Y') }}</td>
                <td>{{ $e->description }}</td>
                <td>{{ __(\App\Models\Expense::CATEGORIES[$e->category] ?? $e->category) }}</td>
                <td>{{ $e->supplier ?: '—' }}</td>
                <td class="num">{{ money($e->amount) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<h3 class="rep-h3">{{ __('Pagamentos (') }}{{ $payments->count() }})</h3>
@if($payments->isEmpty())
    <p class="rep-empty">{{ __('Sem pagamentos registrados.') }}</p>
@else
    <table class="rep-table">
        <thead><tr><th>{{ __('Vencimento') }}</th><th>{{ __('Título') }}</th><th>{{ __('Tipo') }}</th><th>{{ __('Status') }}</th><th class="num">{{ __('Valor') }}</th></tr></thead>
        <tbody>
        @foreach($payments as $p)
            <tr>
                <td>{{ $p->due_date?->format(app()->getLocale() === 'en' ? 'm/d/Y' : 'd/m/Y') ?? '—' }}</td>
                <td>{{ $p->title }}</td>
                <td>{{ __(\App\Models\Payment::TYPES[$p->type] ?? $p->type) }}</td>
                <td>{{ __(\App\Models\Payment::STATUSES[$p->status] ?? $p->status) }}</td>
                <td class="num">{{ money($p->amount) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif
