<x-admin-layout>
<div class="space-y-6 p-6 md:p-8">
    <div>
        <h1 style="font-size:1.5rem;font-weight:700;">Relatórios</h1>
        <p style="color:var(--muted-foreground);font-size:0.875rem;margin-top:0.25rem;">Escolha uma obra para ver e exportar os relatórios.</p>
    </div>

    <form method="GET" action="{{ route('admin.reports.index') }}" class="flex gap-3">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Procurar obra..." style="max-width:20rem;">
        <button type="submit" class="btn-primary">Procurar</button>
    </form>

    @if($projects->isEmpty())
        <p class="rep-empty" style="color:var(--muted-foreground);font-style:italic;">Nenhuma obra encontrada.</p>
    @else
        <div class="space-y-3">
        @foreach($projects as $project)
            <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:1rem;">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div style="font-weight:600;">{{ $project->name }}</div>
                        <div style="font-size:0.8125rem;color:var(--muted-foreground);">
                            {{ $project->client?->name ?? '—' }}@if($project->code) · {{ $project->code }}@endif
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($types as $key => $label)
                            <a href="{{ route('admin.reports.show', [$project, $key]) }}"
                               style="padding:0.35rem 0.7rem;border-radius:0.5rem;font-size:0.8125rem;border:1px solid var(--border);color:var(--muted-foreground);">
                                {{ str_replace('Relatório ', '', $label) }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
        </div>
        {{ $projects->links() }}
    @endif
</div>
</x-admin-layout>
