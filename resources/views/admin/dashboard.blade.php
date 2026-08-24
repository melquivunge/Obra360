<x-admin-layout>
<div class="space-y-6 p-6 md:p-8">

    {{-- Header --}}
    <div>
        <h1 style="font-size:1.5rem;font-weight:700;letter-spacing:-0.01em;">Dashboard</h1>
        <p style="color:var(--muted-foreground);font-size:0.875rem;margin-top:0.25rem;">Visão geral do portfólio de obras e projetos · {{ now()->format('d/m/Y') }}</p>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @php
            $cards = [
                ['label'=>'Obras e projetos', 'value'=>$totalProjects, 'accent'=>false],
                ['label'=>'Em andamento',   'value'=>$inProgress,     'accent'=>true],
                ['label'=>'Concluídas',     'value'=>$completed,      'accent'=>false],
                ['label'=>'Pausadas',       'value'=>$paused,         'accent'=>false],
                ['label'=>'Atrasadas',      'value'=>$lateCount,      'accent'=>false],
                ['label'=>'Perto do fim',   'value'=>$nearCompletionCount, 'accent'=>false],
                ['label'=>'Clientes',       'value'=>$totalClients,   'accent'=>false],
                ['label'=>'Total orçado',   'value'=>money($totalBudget), 'accent'=>false],
                ['label'=>'Total gasto',    'value'=>money($totalSpent),  'accent'=>false],
                ['label'=>'Saldo restante', 'value'=>money($totalBudget-$totalSpent), 'accent'=>true],
            ];
        @endphp
        @foreach($cards as $card)
        <div style="background-color:{{ $card['accent'] ? 'oklch(0.74 0.135 78 / 0.08)' : 'var(--card)' }};border:1px solid {{ $card['accent'] ? 'oklch(0.74 0.135 78 / 0.35)' : 'var(--border)' }};border-radius:0.75rem;padding:1rem;{{ $card['accent'] ? 'box-shadow:var(--shadow-gold);' : '' }}">
            <p style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.08em;color:var(--muted-foreground);">{{ $card['label'] }}</p>
            <p style="font-size:1.5rem;font-weight:700;margin-top:0.5rem;color:{{ $card['accent'] ? 'var(--primary)' : 'var(--foreground)' }}">{{ $card['value'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Alertas: obras atrasadas e perto da conclusão (DASH-01) --}}
    @if($lateProjects->isNotEmpty() || $nearCompletionProjects->isNotEmpty())
    <div class="grid gap-6 lg:grid-cols-2">
        @if($lateProjects->isNotEmpty())
        <div style="background-color:var(--card);border:1px solid oklch(0.6 0.18 40 / 0.4);border-radius:0.75rem;padding:1.25rem;">
            <h2 style="font-size:1rem;font-weight:700;color:oklch(0.7 0.16 40);">Em atraso ({{ $lateCount }})</h2>
            <p style="font-size:0.75rem;color:var(--muted-foreground);margin:0.25rem 0 0.75rem;">Previsão de término já passou e o trabalho não está concluído.</p>
            <div class="space-y-2">
                @foreach($lateProjects as $late)
                <a href="{{ route('admin.projects.show', $late) }}" class="flex items-center justify-between"
                   style="padding:0.625rem 0.75rem;border-radius:0.5rem;background-color:var(--surface);">
                    <span>
                        <span style="font-weight:600;font-size:0.875rem;">{{ $late->name }}</span>
                        <span style="display:block;font-size:0.75rem;color:var(--muted-foreground);">{{ $late->client?->name ?? '—' }}</span>
                    </span>
                    <span style="font-size:0.75rem;color:oklch(0.7 0.16 40);white-space:nowrap;">
                        {{ $late->expected_end_date?->format('d/m/Y') }}
                        · {{ (int) $late->expected_end_date?->diffInDays(now()) }} dias
                    </span>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        @if($nearCompletionProjects->isNotEmpty())
        <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:1.25rem;">
            <h2 style="font-size:1rem;font-weight:700;">Perto da conclusão ({{ $nearCompletionCount }})</h2>
            <p style="font-size:0.75rem;color:var(--muted-foreground);margin:0.25rem 0 0.75rem;">Em andamento com 80% ou mais de progresso.</p>
            <div class="space-y-2">
                @foreach($nearCompletionProjects as $near)
                <a href="{{ route('admin.projects.show', $near) }}" class="flex items-center justify-between"
                   style="padding:0.625rem 0.75rem;border-radius:0.5rem;background-color:var(--surface);">
                    <span>
                        <span style="font-weight:600;font-size:0.875rem;">{{ $near->name }}</span>
                        <span style="display:block;font-size:0.75rem;color:var(--muted-foreground);">{{ $near->client?->name ?? '—' }}</span>
                    </span>
                    <span style="font-size:0.75rem;font-weight:600;color:var(--primary);">{{ (int) $near->progress }}%</span>
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- Recent projects + updates --}}
    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Recent projects --}}
        <div class="lg:col-span-2" style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:1.25rem;">
            <div class="flex items-center justify-between mb-4">
                <h2 style="font-size:1rem;font-weight:700;">Obras e projetos recentes</h2>
                <a href="{{ route('admin.projects.index') }}" style="font-size:0.8rem;color:var(--primary);">Ver todos →</a>
            </div>
            <div class="space-y-2">
                @forelse($recentProjects as $project)
                <a href="{{ route('admin.projects.show', $project) }}"
                   class="flex items-center justify-between p-3 rounded-lg transition-colors"
                   style="border:1px solid var(--border)"
                   onmouseover="this.style.borderColor='oklch(0.74 0.135 78 / 0.4)'"
                   onmouseout="this.style.borderColor='var(--border)'">
                    <div class="min-w-0">
                        <p class="font-medium truncate" style="font-size:0.875rem;">{{ $project->name }}</p>
                        <p style="font-size:0.75rem;color:var(--muted-foreground)" class="truncate">{{ $project->client?->name }} · {{ $project->location ?: '—' }}</p>
                    </div>
                    <div class="flex items-center gap-3 ml-3 shrink-0">
                        <span class="badge badge-{{ $project->status }}">{{ \App\Models\Project::STATUSES[$project->status] ?? $project->status }}</span>
                        <span style="font-size:0.875rem;font-weight:600;color:var(--primary)">{{ $project->progress }}%</span>
                    </div>
                </a>
                @empty
                <p style="color:var(--muted-foreground);font-size:0.875rem;text-align:center;padding:2rem 0;">Nenhuma obra ou projeto cadastrado.</p>
                @endforelse
            </div>
        </div>

        {{-- Recent updates --}}
        <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:1.25rem;">
            <h2 style="font-size:1rem;font-weight:700;margin-bottom:1rem;">Atualizações recentes</h2>
            <div class="space-y-3">
                @forelse($recentUpdates as $update)
                <div class="flex gap-3">
                    <div class="shrink-0 w-7 h-7 rounded-md flex items-center justify-center"
                         style="background:oklch(0.74 0.135 78 / 0.15);color:var(--primary)">
                        @if($update->type === 'foto')
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z"/></svg>
                        @elseif($update->type === 'documento')
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                        @else
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate" style="font-size:0.8125rem;">{{ $update->title }}</p>
                        <p class="truncate" style="font-size:0.75rem;color:var(--muted-foreground)">{{ $update->project?->name }} · {{ $update->happened_at?->format('d/m/Y') }}</p>
                    </div>
                </div>
                @empty
                <p style="color:var(--muted-foreground);font-size:0.875rem;text-align:center;padding:2rem 0;">Sem atualizações.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
</x-admin-layout>
