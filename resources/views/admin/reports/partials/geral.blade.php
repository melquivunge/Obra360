<table class="rep-table">
    <tbody>
        <tr><th>Cliente</th><td>{{ $project->client?->name ?? '—' }}</td></tr>
        <tr><th>Responsável</th><td>{{ $project->manager?->name ?? '—' }}</td></tr>
        <tr><th>Localização</th><td>{{ $project->location ?: '—' }}</td></tr>
        <tr><th>Status</th><td>{{ \App\Models\Project::STATUSES[$project->status] ?? $project->status }}</td></tr>
        <tr><th>Progresso</th><td>{{ (int) $project->progress }}%</td></tr>
        <tr><th>Início</th><td>{{ $project->start_date?->format('d/m/Y') ?? '—' }}</td></tr>
        <tr><th>Previsão de fim</th><td>{{ $project->expected_end_date?->format('d/m/Y') ?? '—' }}</td></tr>
        <tr><th>Orçamento</th><td>{{ money($project->budget ?? 0) }}</td></tr>
        <tr><th>Total gasto</th><td>{{ money($totalSpent) }}</td></tr>
        <tr><th>Saldo</th><td>{{ money($project->remainingBalance()) }}</td></tr>
        <tr><th>Orçamento usado</th><td>{{ $project->budgetUsedPercent() }}%</td></tr>
    </tbody>
</table>

@if($project->description)
    <h3 class="rep-h3">Descrição</h3>
    <p>{{ $project->description }}</p>
@endif

<h3 class="rep-h3">Fases ({{ $phases->count() }})</h3>
@include('admin.reports.partials.fases-table', ['phases' => $phases])

<h3 class="rep-h3">Últimas atualizações</h3>
@if($updates->isEmpty())
    <p class="rep-empty">Sem atualizações registadas.</p>
@else
    <table class="rep-table">
        <thead><tr><th>Data</th><th>Título</th><th>Por</th></tr></thead>
        <tbody>
        @foreach($updates as $u)
            <tr>
                <td>{{ $u->created_at?->format('d/m/Y H:i') }}</td>
                <td>{{ $u->title }}</td>
                <td>{{ $u->user?->name ?? 'Sistema' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif
