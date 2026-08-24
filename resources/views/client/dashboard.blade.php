<x-client-layout>
<div class="space-y-6 p-4 md:p-8">

    {{-- Header. O título segue o que o cliente contratou: quem só tem projecto de
         arquitectura ou de design de interiores não tem obra nenhuma (TIPO-04). --}}
    @php
        $hasSiteWork = $projects->contains(fn ($p) => $p->isSiteWork());
        $hasDesignWork = $projects->contains(fn ($p) => ! $p->isSiteWork());
        // A mesma regra que nomeia a entrada do menu, no modelo — aqui sobre a
        // colecção real, sem passar pela cache do layout.
        $heading = \App\Models\Project::collectionLabel($hasSiteWork, $hasDesignWork);
    @endphp
    <div>
        <h1 style="font-size:1.5rem;font-weight:700;letter-spacing:-0.01em;">{{ $heading }}</h1>
        <p style="color:var(--color-muted-foreground);font-size:0.875rem;margin-top:0.25rem;">
            Bem-vindo, {{ auth()->user()->name }}. Acompanhe o andamento abaixo.
        </p>
    </div>

    @if($projects->isEmpty())
        <div style="background-color:var(--color-card);border:1px solid var(--color-border);border-radius:0.875rem;padding:3rem;text-align:center;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                 class="w-12 h-12 mx-auto mb-4" style="color:var(--color-muted-foreground);opacity:0.4;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21" />
            </svg>
            <p style="color:var(--color-muted-foreground);font-size:0.9375rem;">Você ainda não tem obras ou projetos vinculados.</p>
            <p style="color:var(--color-muted-foreground);font-size:0.8125rem;margin-top:0.375rem;opacity:0.7;">Entre em contato com a equipe para liberar seu acesso.</p>
        </div>
    @else
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @foreach($projects as $project)
            <a href="{{ route('portal.projects.show', $project) }}"
               class="hover-card-lift group overflow-hidden flex flex-col"
               style="background-color:var(--color-card);border:1px solid var(--color-border);border-radius:1rem;text-decoration:none;transition:border-color 0.2s,box-shadow 0.2s;">

                {{-- Capa: capa própria, última foto ou imagem padrão — o cartão mostra
                     sempre uma imagem, em vez do antigo estado vazio com o ícone de
                     edifício. Quem escolhe entre elas é o componente. --}}
                <div style="height:180px;overflow:hidden;background-color:var(--color-surface);flex-shrink:0;">
                    <x-project-cover :src="$project->cover_url" alt="{{ $project->name }}"
                                     class="hover-zoom"
                                     style="width:100%;height:180px;object-fit:cover;transition:transform 0.3s;" />
                </div>

                {{-- Info --}}
                <div style="padding:1.25rem;flex:1;display:flex;flex-direction:column;gap:0.875rem;">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <h3 style="font-size:1.0625rem;font-weight:700;letter-spacing:-0.01em;" class="truncate">{{ $project->name }}</h3>
                            <p style="font-size:0.8125rem;color:var(--color-muted-foreground);margin-top:0.125rem;" class="truncate">
                                {{ $project->location ?: '—' }}
                            </p>
                        </div>
                        <div class="flex flex-col items-end gap-1 shrink-0">
                            <span class="badge badge-{{ $project->status }} shrink-0">
                                {{ \App\Models\Project::STATUSES[$project->status] ?? $project->status }}
                            </span>
                            <x-project-type :project="$project" />
                        </div>
                    </div>

                    {{-- Progress --}}
                    <div>
                        <div class="flex justify-between mb-1">
                            <span style="font-size:0.75rem;color:var(--color-muted-foreground);">Progresso</span>
                            <span style="font-size:0.75rem;font-weight:600;color:var(--color-primary);">{{ $project->progress }}%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width:{{ $project->progress }}%"></div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-between mt-auto" style="padding-top:0.75rem;border-top:1px solid var(--color-border);">
                        @if($canViewFinancials && $project->show_financials && $project->budget)
                            <div>
                                <p style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.06em;color:var(--color-muted-foreground);">Orçamento</p>
                                <p style="font-size:0.875rem;font-weight:600;margin-top:0.125rem;">{{ money($project->budget) }}</p>
                            </div>
                        @else
                            <span></span>
                        @endif
                        <p style="font-size:0.75rem;color:var(--color-muted-foreground);">{{ $project->updated_at->diffForHumans() }}</p>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    @endif
</div>
</x-client-layout>
