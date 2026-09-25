<table class="rep-table">
    <tbody>
        <tr><th>{{ __('Cliente') }}</th><td>{{ $project->client?->name ?? '—' }}</td></tr>
        <tr><th>{{ __('Responsável') }}</th><td>{{ $project->manager?->name ?? '—' }}</td></tr>
        <tr><th>{{ __('Localização') }}</th><td>{{ $project->location ?: '—' }}</td></tr>
        <tr><th>{{ __('Status') }}</th><td>{{ __(\App\Models\Project::STATUSES[$project->status] ?? $project->status) }}</td></tr>
        <tr><th>{{ __('Progresso') }}</th><td>{{ (int) $project->progress }}%</td></tr>
        <tr><th>{{ __('Início') }}</th><td>{{ $project->start_date?->format(app()->getLocale() === 'en' ? 'm/d/Y' : 'd/m/Y') ?? '—' }}</td></tr>
        <tr><th>{{ __('Previsão de fim') }}</th><td>{{ $project->expected_end_date?->format(app()->getLocale() === 'en' ? 'm/d/Y' : 'd/m/Y') ?? '—' }}</td></tr>
        <tr><th>{{ __('Orçamento') }}</th><td>{{ money($project->budget ?? 0) }}</td></tr>
        <tr><th>{{ __('Total gasto') }}</th><td>{{ money($totalSpent) }}</td></tr>
        <tr><th>{{ __('Saldo') }}</th><td>{{ money($project->remainingBalance()) }}</td></tr>
        <tr><th>{{ __('Orçamento usado') }}</th><td>{{ $project->budgetUsedPercent() }}%</td></tr>
    </tbody>
</table>

@if($project->description)
    <h3 class="rep-h3">{{ __('Descrição') }}</h3>
    <p>{{ $project->description }}</p>
@endif

<h3 class="rep-h3">{{ __('Fases (') }}{{ $phases->count() }})</h3>
@include('admin.reports.partials.fases-table', ['phases' => $phases])

<h3 class="rep-h3">{{ __('Últimas atualizações') }}</h3>
@if($updates->isEmpty())
    <p class="rep-empty">{{ __('Sem atualizações registradas.') }}</p>
@else
    <table class="rep-table">
        <thead><tr><th>{{ __('Data') }}</th><th>{{ __('Título') }}</th><th>{{ __('Por') }}</th></tr></thead>
        <tbody>
        @foreach($updates as $u)
            <tr>
                <td>{{ $u->created_at?->format(app()->getLocale() === 'en' ? 'm/d/Y H:i' : 'd/m/Y H:i') }}</td>
                <td>{{ $u->title }}</td>
                <td>{{ $u->user?->name ?? __('Sistema') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif
