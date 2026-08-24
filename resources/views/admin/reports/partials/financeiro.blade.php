<table class="rep-table">
    <tbody>
        <tr><th>Orçamento</th><td>{{ money($project->budget ?? 0) }}</td></tr>
        <tr><th>Total gasto</th><td>{{ money($totalSpent) }}</td></tr>
        <tr><th>Saldo</th><td>{{ money($project->remainingBalance()) }}</td></tr>
        <tr><th>Orçamento usado</th><td>{{ $project->budgetUsedPercent() }}%</td></tr>
    </tbody>
</table>

<h3 class="rep-h3">Despesas por categoria</h3>
@if(empty($byCategory))
    <p class="rep-empty">Sem despesas registradas.</p>
@else
    <table class="rep-table">
        <thead><tr><th>Categoria</th><th class="num">Total</th><th class="num">% do gasto</th></tr></thead>
        <tbody>
        @foreach($byCategory as $row)
            <tr>
                <td>{{ $row['label'] }}</td>
                <td class="num">{{ money($row['total']) }}</td>
                <td class="num">{{ $row['percent'] }}%</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot><tr><th>Total</th><th class="num">{{ money($totalSpent) }}</th><th class="num">100%</th></tr></tfoot>
    </table>
@endif

<h3 class="rep-h3">Despesas por mês</h3>
@if(empty($byMonth))
    <p class="rep-empty">Sem despesas registradas.</p>
@else
    <table class="rep-table">
        <thead><tr><th>Mês</th><th class="num">Total</th></tr></thead>
        <tbody>
        @foreach($byMonth as $row)
            <tr><td>{{ $row['label'] }}</td><td class="num">{{ money($row['total']) }}</td></tr>
        @endforeach
        </tbody>
    </table>
@endif

<h3 class="rep-h3">Lançamentos ({{ $expenses->count() }})</h3>
@if($expenses->isEmpty())
    <p class="rep-empty">Sem despesas registradas.</p>
@else
    <table class="rep-table">
        <thead><tr><th>Data</th><th>Descrição</th><th>Categoria</th><th>Fornecedor</th><th class="num">Valor</th></tr></thead>
        <tbody>
        @foreach($expenses as $e)
            <tr>
                <td>{{ $e->spent_at?->format('d/m/Y') }}</td>
                <td>{{ $e->description }}</td>
                <td>{{ \App\Models\Expense::CATEGORIES[$e->category] ?? $e->category }}</td>
                <td>{{ $e->supplier ?: '—' }}</td>
                <td class="num">{{ money($e->amount) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<h3 class="rep-h3">Pagamentos ({{ $payments->count() }})</h3>
@if($payments->isEmpty())
    <p class="rep-empty">Sem pagamentos registrados.</p>
@else
    <table class="rep-table">
        <thead><tr><th>Vencimento</th><th>Título</th><th>Tipo</th><th>Status</th><th class="num">Valor</th></tr></thead>
        <tbody>
        @foreach($payments as $p)
            <tr>
                <td>{{ $p->due_date?->format('d/m/Y') ?? '—' }}</td>
                <td>{{ $p->title }}</td>
                <td>{{ \App\Models\Payment::TYPES[$p->type] ?? $p->type }}</td>
                <td>{{ \App\Models\Payment::STATUSES[$p->status] ?? $p->status }}</td>
                <td class="num">{{ money($p->amount) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif
