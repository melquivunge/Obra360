<x-client-layout>
@php
    // Uma aprovação de planta volta por back(); sem isto o portal reabriria no
    // Resumo e a mensagem de confirmação ficaria num separador fechado.
    $activeTab = session('active_tab') === 'plantas' && $plans->isNotEmpty() ? 'plantas' : 'resumo';
@endphp
<div class="p-4 md:p-8 space-y-6" x-data="{ tab: @js($activeTab) }">

    {{-- Back + Header --}}
    <div>
        <a href="{{ route('portal.dashboard') }}" class="hover-text-primary" style="display:inline-flex;align-items:center;gap:0.375rem;font-size:0.875rem;color:var(--color-muted-foreground);text-decoration:none;margin-bottom:1rem;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1rem;height:1rem;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            Voltar
        </a>

        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <h1 style="font-size:1.5rem;font-weight:700;letter-spacing:-0.01em;">{{ $project->name }}</h1>
                <p style="color:var(--color-muted-foreground);font-size:0.875rem;margin-top:0.25rem;">{{ $project->location ?: '—' }}</p>
            </div>
            <div class="flex flex-col items-end gap-1 shrink-0">
                <span class="badge badge-{{ $project->status }} shrink-0">
                    {{ \App\Models\Project::STATUSES[$project->status] ?? $project->status }}
                </span>
                <x-project-type :project="$project" />
            </div>
        </div>
    </div>

    {{-- Capa da obra. Sem capa própria — ou com o ficheiro em falta no disco, que faz a
         rota devolver 404 — entra a imagem padrão: a faixa é sempre a mesma e nunca sobra
         uma moldura vazia nem o ícone de imagem partida. --}}
    <div style="border:1px solid var(--color-border);border-radius:0.875rem;overflow:hidden;background-color:var(--color-surface);">
        <x-project-cover :src="$project->cover_image_path ? route('portal.projects.cover', $project) : null"
                         alt="Capa d{{ $project->nounWithArticle() }}"
                         style="width:100%;height:180px;object-fit:cover;display:block;" />
    </div>

    {{-- Progress card --}}
    <div style="background-color:var(--color-card);border:1px solid var(--color-border);border-radius:0.875rem;padding:1.5rem;">
        <div class="flex justify-between mb-2">
            <span style="font-size:0.875rem;font-weight:600;">Progresso geral</span>
            <span style="font-size:0.875rem;font-weight:700;color:var(--color-primary);">{{ $project->progress }}%</span>
        </div>
        <div class="progress-bar" style="height:0.625rem;">
            <div class="progress-fill" style="width:{{ $project->progress }}%"></div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-5" style="padding-top:1.25rem;border-top:1px solid var(--color-border);">
            @php
                $currentPhase = $project->phases->firstWhere('status', 'em_andamento')
                    ?? $project->phases->firstWhere('status', 'pendente');
            @endphp
            <div>
                <p style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.06em;color:var(--color-muted-foreground);">Fase atual</p>
                <p style="font-size:0.9375rem;font-weight:600;margin-top:0.25rem;">{{ $currentPhase?->name ?? '—' }}</p>
            </div>
            <div>
                <p style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.06em;color:var(--color-muted-foreground);">Início</p>
                <p style="font-size:0.9375rem;font-weight:600;margin-top:0.25rem;">{{ $project->start_date ? \Carbon\Carbon::parse($project->start_date)->format('d/m/Y') : '—' }}</p>
            </div>
            <div>
                <p style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.06em;color:var(--color-muted-foreground);">Previsão de término</p>
                <p style="font-size:0.9375rem;font-weight:600;margin-top:0.25rem;">{{ $project->expected_end_date ? \Carbon\Carbon::parse($project->expected_end_date)->format('d/m/Y') : '—' }}</p>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div>
        <div class="tab-nav" style="overflow-x:auto;-webkit-overflow-scrolling:touch;flex-wrap:nowrap;scrollbar-width:none;">
            <button class="tab-btn" :class="{ active: tab === 'resumo' }" @click="tab = 'resumo'">Resumo</button>
            <button class="tab-btn" :class="{ active: tab === 'galeria' }" @click="tab = 'galeria'">
                Galeria
                @if($photos->count()) <span style="font-size:0.7rem;margin-left:0.25rem;opacity:0.7;">({{ $photos->count() }})</span> @endif
            </button>
            <button class="tab-btn" :class="{ active: tab === 'fases' }" @click="tab = 'fases'">Fases</button>
            @if($canViewFinancials)
            <button class="tab-btn" :class="{ active: tab === 'financeiro' }" @click="tab = 'financeiro'">Financeiro</button>
            @endif
            @if($payments->isNotEmpty())
            <button class="tab-btn" :class="{ active: tab === 'cobranças' }" @click="tab = 'cobranças'">
                Cobranças
                @php $pendingCount = $payments->where('status','pending')->count(); @endphp
                @if($pendingCount) <span style="font-size:0.7rem;margin-left:0.25rem;background:oklch(0.6 0.18 40 / 0.15);color:oklch(0.6 0.18 40);border-radius:9999px;padding:0.1rem 0.4rem;">{{ $pendingCount }}</span> @endif
            </button>
            @endif
            @if($plans->isNotEmpty())
            <button class="tab-btn" :class="{ active: tab === 'plantas' }" @click="tab = 'plantas'">
                Plantas
                <span style="font-size:0.7rem;margin-left:0.25rem;opacity:0.7;">({{ $plans->count() }})</span>
            </button>
            @endif
            <button class="tab-btn" :class="{ active: tab === 'documentos' }" @click="tab = 'documentos'">Documentos</button>
            @if($allowComments)
            <button class="tab-btn" :class="{ active: tab === 'mensagens' }" @click="tab = 'mensagens'">Mensagens</button>
            @endif
        </div>

        {{-- Tab: Resumo --}}
        <div x-show="tab === 'resumo'" style="display:none;">
            {{-- OBRA-04: a continuidade entre o projecto e a obra. É o que o
                 cliente quer saber ao abrir a obra que contratou a seguir ao
                 projecto: que é a mesma casa, o mesmo trabalho, o mesmo fio. --}}
            @if($sourceProject || $derivedProjects->isNotEmpty())
            <div style="background-color:oklch(0.6 0.18 220 / 0.08);border:1px solid oklch(0.6 0.18 220 / 0.28);border-radius:0.875rem;padding:1.125rem 1.25rem;margin-bottom:1rem;">
                @if($sourceProject)
                <p style="font-size:0.9375rem;">
                    {{ $project->isSiteWork() ? 'Esta obra nasceu do projeto' : 'Este projeto nasceu de' }}
                    <a href="{{ route('portal.projects.show', $sourceProject) }}" style="color:var(--color-primary);font-weight:600;text-decoration:none;">{{ $sourceProject->name }}</a>.
                </p>
                <p style="font-size:0.8125rem;color:var(--color-muted-foreground);margin-top:0.25rem;">
                    As plantas que você aprovou continuam lá, na página desse projeto.
                </p>
                @endif
                @foreach($derivedProjects as $derived)
                <p style="font-size:0.9375rem;{{ $sourceProject || ! $loop->first ? 'margin-top:0.75rem;' : '' }}">
                    {{ $derived->isSiteWork() ? 'Deu origem à obra' : 'Deu origem ao projeto' }}
                    <a href="{{ route('portal.projects.show', $derived) }}" style="color:var(--color-primary);font-weight:600;text-decoration:none;">{{ $derived->name }}</a>.
                </p>
                @endforeach
            </div>
            @endif
            <div style="background-color:var(--color-card);border:1px solid var(--color-border);border-radius:0.875rem;padding:1.5rem;">
                @if($project->description)
                <div style="margin-bottom:1.5rem;">
                    <h3 style="font-size:0.875rem;font-weight:600;margin-bottom:0.5rem;color:var(--color-muted-foreground);text-transform:uppercase;letter-spacing:0.05em;">Descrição</h3>
                    <p style="font-size:0.9375rem;white-space:pre-wrap;line-height:1.6;">{{ $project->description }}</p>
                </div>
                @endif

                <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;">Atualizações recentes</h3>
                @php $updates = $project->updates->take(10); @endphp
                @if($updates->isEmpty())
                    <p style="color:var(--color-muted-foreground);font-size:0.875rem;text-align:center;padding:1.5rem 0;">Nenhuma atualização ainda.</p>
                @else
                <div class="space-y-2">
                    @foreach($updates as $update)
                    @php
                        $typeColors = [
                            'foto'      => ['bg' => 'oklch(0.74 0.135 78 / 0.12)', 'color' => 'var(--color-primary)'],
                            'despesa'   => ['bg' => 'oklch(0.5 0.15 220 / 0.15)',  'color' => 'oklch(0.6 0.18 220)'],
                            'fase'      => ['bg' => 'oklch(0.65 0.16 145 / 0.15)', 'color' => 'oklch(0.65 0.16 145)'],
                            'documento' => ['bg' => 'oklch(0.6 0.18 280 / 0.15)',  'color' => 'oklch(0.7 0.18 280)'],
                            'planta'    => ['bg' => 'oklch(0.6 0.16 200 / 0.15)',  'color' => 'oklch(0.62 0.16 200)'],
                            'planta_aprovada' => ['bg' => 'oklch(0.65 0.16 145 / 0.15)', 'color' => 'oklch(0.65 0.16 145)'],
                            'status'    => ['bg' => 'oklch(0.78 0.15 75 / 0.15)',  'color' => 'oklch(0.78 0.15 75)'],
                            'geral'     => ['bg' => 'var(--color-surface)',         'color' => 'var(--color-muted-foreground)'],
                        ];
                        $tc = $typeColors[$update->type] ?? $typeColors['geral'];
                    @endphp
                    <div style="background-color:var(--color-surface);border:1px solid var(--color-border);border-radius:0.5rem;padding:0.875rem;display:flex;gap:0.875rem;align-items:flex-start;">
                        <span style="display:inline-flex;padding:0.25rem 0.625rem;border-radius:9999px;font-size:0.7rem;font-weight:500;white-space:nowrap;background-color:{{ $tc['bg'] }};color:{{ $tc['color'] }};flex-shrink:0;margin-top:0.125rem;">
                            {{ \App\Models\ProjectUpdate::TYPES[$update->type] ?? $update->type }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p style="font-size:0.875rem;font-weight:500;">{{ $update->title }}</p>
                            @if($update->description)
                            <p style="font-size:0.8125rem;color:var(--color-muted-foreground);margin-top:0.25rem;">{{ $update->description }}</p>
                            @endif
                        </div>
                        <span style="font-size:0.75rem;color:var(--color-muted-foreground);white-space:nowrap;flex-shrink:0;">{{ $update->happened_at->diffForHumans() }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        {{-- Tab: Galeria --}}
        <div x-show="tab === 'galeria'" style="display:none;">
            <div style="background-color:var(--color-card);border:1px solid var(--color-border);border-radius:0.875rem;padding:1.5rem;">
                <h2 style="font-size:1rem;font-weight:700;margin-bottom:1.25rem;">Galeria de fotos</h2>
                @if($photos->isEmpty())
                    <p style="color:var(--color-muted-foreground);font-size:0.875rem;text-align:center;padding:2rem 0;">Nenhuma foto disponível.</p>
                @else
                @php
                    $phaseMap = $project->phases->keyBy('id');
                    $byPhase = $photos->groupBy('phase_id');
                    $noPhase = $byPhase->pull('') ?? collect();
                @endphp
                <div class="space-y-6">
                    @foreach($project->phases as $phase)
                        @if(isset($byPhase[$phase->id]) && $byPhase[$phase->id]->isNotEmpty())
                        <div>
                            <h4 style="font-size:0.875rem;font-weight:600;margin-bottom:0.75rem;color:var(--color-foreground);">
                                {{ $phase->name }}
                                <span style="color:var(--color-muted-foreground);font-weight:400;">({{ $byPhase[$phase->id]->count() }})</span>
                            </h4>
                            <div class="photo-grid">
                                @foreach($byPhase[$phase->id] as $photo)
                                @php $photoUrl = route('portal.projects.photos.show', [$project, $photo]); @endphp
                                <div class="photo-item">
                                    @if($photo->media_type === 'video')
                                        <video src="{{ $photoUrl }}" style="width:100%;height:100%;object-fit:cover;" muted playsinline></video>
                                    @else
                                        <img src="{{ $photoUrl }}" alt="{{ $photo->caption ?: $photo->title }}"
                                             class="hover-zoom-lg"
                                             style="width:100%;height:100%;object-fit:cover;transition:transform 0.2s;">
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    @endforeach
                    @if($noPhase->isNotEmpty())
                    <div>
                        <h4 style="font-size:0.875rem;font-weight:600;margin-bottom:0.75rem;color:var(--color-foreground);">
                            Sem fase <span style="color:var(--color-muted-foreground);font-weight:400;">({{ $noPhase->count() }})</span>
                        </h4>
                        <div class="photo-grid">
                            @foreach($noPhase as $photo)
                            @php $photoUrl = route('portal.projects.photos.show', [$project, $photo]); @endphp
                            <div class="photo-item">
                                @if($photo->media_type === 'video')
                                    <video src="{{ $photoUrl }}" style="width:100%;height:100%;object-fit:cover;" muted playsinline></video>
                                @else
                                    <img src="{{ $photoUrl }}" alt="{{ $photo->caption ?: $photo->title }}"
                                         class="hover-zoom-lg"
                                         style="width:100%;height:100%;object-fit:cover;transition:transform 0.2s;cursor:pointer;"
                                         data-lightbox-src="{{ $photoUrl }}"
                                         data-lightbox-caption="{{ $photo->caption ?: $photo->title ?? '' }}">
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>

        {{-- Tab: Fases --}}
        <div x-show="tab === 'fases'" style="display:none;">
            <div style="background-color:var(--color-card);border:1px solid var(--color-border);border-radius:0.875rem;padding:1.5rem;">
                <h2 style="font-size:1rem;font-weight:700;margin-bottom:1.25rem;">Fases d{{ $project->nounWithArticle() }}</h2>
                @if($project->phases->isEmpty())
                    <p style="color:var(--color-muted-foreground);font-size:0.875rem;text-align:center;padding:2rem 0;">Nenhuma fase cadastrada.</p>
                @else
                <div class="space-y-3">
                    @foreach($project->phases as $phase)
                    <div style="background-color:var(--color-surface);border:1px solid var(--color-border);border-radius:0.5rem;padding:1rem;">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <h4 style="font-size:0.9375rem;font-weight:600;">{{ $phase->name }}</h4>
                            <span class="badge badge-{{ $phase->status }}">
                                {{ \App\Models\ProjectPhase::STATUSES[$phase->status] ?? $phase->status }}
                            </span>
                        </div>
                        @if($phase->description)
                        <p style="font-size:0.8125rem;color:var(--color-muted-foreground);margin-top:0.375rem;">{{ $phase->description }}</p>
                        @endif
                        <div style="margin-top:0.875rem;">
                            <div class="flex justify-between mb-1">
                                <span style="font-size:0.75rem;color:var(--color-muted-foreground);">Conclusão</span>
                                <span style="font-size:0.75rem;font-weight:600;color:var(--color-primary);">{{ $phase->percentage }}%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width:{{ $phase->percentage }}%"></div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-3" style="font-size:0.8125rem;">
                            @if($phase->start_date)
                            <div>
                                <span style="color:var(--color-muted-foreground);">Início: </span>
                                <span>{{ \Carbon\Carbon::parse($phase->start_date)->format('d/m/Y') }}</span>
                            </div>
                            @endif
                            @if($phase->expected_end_date)
                            <div>
                                <span style="color:var(--color-muted-foreground);">Previsão: </span>
                                <span>{{ \Carbon\Carbon::parse($phase->expected_end_date)->format('d/m/Y') }}</span>
                            </div>
                            @endif
                            @if($phase->real_end_date)
                            <div>
                                <span style="color:var(--color-muted-foreground);">Concluída: </span>
                                <span style="color:var(--color-success)">{{ \Carbon\Carbon::parse($phase->real_end_date)->format('d/m/Y') }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        {{-- Tab: Financeiro --}}
        @if($canViewFinancials)
        <div x-show="tab === 'financeiro'" style="display:none;">
            <div class="space-y-4">
                {{-- Summary cards --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div style="background-color:var(--color-card);border:1px solid var(--color-border);border-radius:0.75rem;padding:1rem;">
                        <p style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.06em;color:var(--color-muted-foreground);">Total orçado</p>
                        <p style="font-size:1.375rem;font-weight:700;margin-top:0.375rem;">{{ money($project->budget ?? 0) }}</p>
                    </div>
                    <div style="background-color:var(--color-card);border:1px solid var(--color-border);border-radius:0.75rem;padding:1rem;">
                        <p style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.06em;color:var(--color-muted-foreground);">Total gasto</p>
                        <p style="font-size:1.375rem;font-weight:700;margin-top:0.375rem;">{{ money($totalSpent) }}</p>
                    </div>
                    <div style="background-color:oklch(0.74 0.135 78 / 0.08);border:1px solid oklch(0.74 0.135 78 / 0.3);border-radius:0.75rem;padding:1rem;">
                        <p style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.06em;color:var(--color-muted-foreground);">Saldo restante</p>
                        <p style="font-size:1.375rem;font-weight:700;margin-top:0.375rem;color:var(--color-primary);">{{ money(($project->budget ?? 0) - $totalSpent) }}</p>
                    </div>
                </div>

                @if($expenses->isNotEmpty())
                {{-- Breakdown by category --}}
                @php
                    $byCat = $expenses->groupBy('category')->map(fn($g) => $g->sum('amount'))->sortDesc();
                @endphp
                <div style="background-color:var(--color-card);border:1px solid var(--color-border);border-radius:0.875rem;padding:1.5rem;">
                    <h3 style="font-size:0.9375rem;font-weight:700;margin-bottom:1rem;">Gastos por categoria</h3>
                    <div class="space-y-3">
                        @foreach($byCat as $cat => $val)
                        @php $pct = $totalSpent > 0 ? round(($val / $totalSpent) * 100) : 0; @endphp
                        <div>
                            <div class="flex justify-between mb-1">
                                <span style="font-size:0.8125rem;color:var(--color-muted-foreground);">{{ \App\Models\Expense::CATEGORIES[$cat] ?? $cat }}</span>
                                <span style="font-size:0.8125rem;font-weight:600;">{{ money($val) }} <span style="color:var(--color-muted-foreground);font-weight:400;">({{ $pct }}%)</span></span>
                            </div>
                            <div class="progress-bar" style="height:0.375rem;">
                                <div class="progress-fill" style="width:{{ $pct }}%"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Expense table --}}
                <div style="background-color:var(--color-card);border:1px solid var(--color-border);border-radius:0.875rem;padding:1.5rem;overflow-x:auto;">
                    <h3 style="font-size:0.9375rem;font-weight:700;margin-bottom:1rem;">Lançamentos</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Categoria</th>
                                <th>Descrição</th>
                                <th>Fornecedor</th>
                                <th style="text-align:right;">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expenses->take(50) as $expense)
                            <tr>
                                <td style="white-space:nowrap;color:var(--color-muted-foreground);">{{ $expense->spent_at?->format('d/m/Y') }}</td>
                                <td>{{ \App\Models\Expense::CATEGORIES[$expense->category] ?? $expense->category }}</td>
                                <td>{{ $expense->description ?: '—' }}</td>
                                <td style="color:var(--color-muted-foreground);">{{ $expense->supplier ?: '—' }}</td>
                                <td style="text-align:right;font-weight:600;white-space:nowrap;">{{ money($expense->amount) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div style="background-color:var(--color-card);border:1px solid var(--color-border);border-radius:0.875rem;padding:2rem;text-align:center;">
                    <p style="color:var(--color-muted-foreground);font-size:0.875rem;">Nenhuma despesa registrada.</p>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Tab: Cobranças --}}
        @if($payments->isNotEmpty())
        <div x-show="tab === 'cobranças'" style="display:none;">
            <div class="space-y-3">
                @foreach($payments as $pay)
                @php
                    $isPaid      = $pay->status === 'paid';
                    $isCancelled = $pay->status === 'cancelled';
                @endphp
                <div style="background-color:var(--color-card);border:1px solid var(--color-border);border-radius:0.875rem;padding:1.25rem;">
                    <div class="flex items-start justify-between gap-3 flex-wrap">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p style="font-weight:600;font-size:0.9375rem;">{{ $pay->title }}</p>
                                @if($isPaid)
                                    <span style="display:inline-flex;padding:0.15rem 0.55rem;border-radius:9999px;font-size:0.7rem;font-weight:500;background:oklch(0.55 0.16 145 / 0.12);color:oklch(0.55 0.16 145)">Pago</span>
                                @elseif($isCancelled)
                                    <span style="display:inline-flex;padding:0.15rem 0.55rem;border-radius:9999px;font-size:0.7rem;font-weight:500;background:var(--color-surface);color:var(--color-muted-foreground)">Cancelado</span>
                                @else
                                    <span style="display:inline-flex;padding:0.15rem 0.55rem;border-radius:9999px;font-size:0.7rem;font-weight:500;background:oklch(0.6 0.18 40 / 0.12);color:oklch(0.6 0.18 40)">Pendente</span>
                                @endif
                            </div>
                            @if($pay->description)<p style="font-size:0.8125rem;color:var(--color-muted-foreground);margin-top:0.25rem;">{{ $pay->description }}</p>@endif
                            <p style="font-size:0.75rem;color:var(--color-muted-foreground);margin-top:0.375rem;">
                                {{ \App\Models\Payment::TYPES[$pay->type] ?? $pay->type }}
                                @if($pay->due_date) · Vencimento: {{ $pay->due_date->format('d/m/Y') }} @endif
                                @if($pay->paid_at) · Pago em: {{ $pay->paid_at->format('d/m/Y') }} @endif
                            </p>
                        </div>
                        <p style="font-size:1.25rem;font-weight:700;white-space:nowrap;{{ $isPaid ? 'color:oklch(0.55 0.16 145)' : '' }}">{{ money($pay->amount) }}</p>
                    </div>
                    @if(!$isPaid && !$isCancelled)
                        @if($pay->type === 'pix' && ($pay->pix_key || $pay->pix_qrcode))
                        <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--color-border);">
                            @if($pay->pix_key)<p style="font-size:0.8125rem;margin-bottom:0.25rem;"><strong>Chave PIX:</strong> <span style="font-family:monospace;">{{ $pay->pix_key }}</span></p>@endif
                            @if($pay->pix_qrcode)<div style="margin-top:0.5rem;"><p style="font-size:0.75rem;color:var(--color-muted-foreground);margin-bottom:0.25rem;">Código copia-e-cola:</p><textarea readonly rows="2" data-select-all style="width:100%;font-family:monospace;font-size:0.7rem;resize:none;background:var(--color-surface);border:1px solid var(--color-border);border-radius:0.375rem;padding:0.5rem;">{{ $pay->pix_qrcode }}</textarea></div>@endif
                        </div>
                        @elseif($pay->type === 'boleto' && $pay->barcode)
                        <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--color-border);">
                            <p style="font-size:0.75rem;color:var(--color-muted-foreground);margin-bottom:0.25rem;">Linha digitável:</p>
                            <p data-copy="{{ $pay->barcode }}" style="font-family:monospace;font-size:0.8125rem;cursor:pointer;padding:0.5rem;background:var(--color-surface);border-radius:0.375rem;border:1px solid var(--color-border);" title="Clique para copiar">{{ $pay->barcode }}</p>
                        </div>
                        @endif
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Tab: Documentos --}}
        <div x-show="tab === 'documentos'" style="display:none;">
            <div style="background-color:var(--color-card);border:1px solid var(--color-border);border-radius:0.875rem;padding:1.5rem;">
                <h2 style="font-size:1rem;font-weight:700;margin-bottom:1.25rem;">Documentos disponíveis</h2>
                @if($documents->isEmpty())
                    <p style="color:var(--color-muted-foreground);font-size:0.875rem;text-align:center;padding:2rem 0;">Sem documentos disponíveis.</p>
                @else
                <div class="space-y-2">
                    @foreach($documents as $doc)
                    <div class="flex items-center gap-3" style="padding:0.875rem;border:1px solid var(--color-border);border-radius:0.5rem;background-color:var(--color-surface);">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                             class="w-5 h-5 shrink-0" style="color:var(--color-primary);">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                        </svg>
                        <div class="min-w-0 flex-1">
                            <p style="font-size:0.9375rem;font-weight:500;" class="truncate">{{ $doc->name }}</p>
                            <p style="font-size:0.75rem;color:var(--color-muted-foreground);margin-top:0.125rem;">
                                {{ \App\Models\Document::TYPES[$doc->type] ?? $doc->type }} · {{ $doc->created_at->format('d/m/Y') }}
                            </p>
                        </div>
                        <a href="{{ route('portal.projects.documents.download', [$project, $doc]) }}" target="_blank" rel="noopener"
                           class="hover-border-primary"
                           style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.375rem 0.75rem;border:1px solid var(--color-border);border-radius:0.375rem;font-size:0.8125rem;color:var(--color-primary);text-decoration:none;flex-shrink:0;transition:border-color 0.15s;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                            </svg>
                            Baixar
                        </a>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        {{-- Tab: Plantas (PORT-01 a PORT-06) --}}
        @if($plans->isNotEmpty())
        <div x-show="tab === 'plantas'" style="display:none;">
            <div style="background-color:var(--color-card);border:1px solid var(--color-border);border-radius:0.875rem;padding:1.5rem;">
                <h2 style="font-size:1rem;font-weight:700;margin-bottom:0.35rem;">Plantas e desenhos</h2>
                <p style="font-size:0.8125rem;color:var(--color-muted-foreground);margin-bottom:1.25rem;">
                    Estão aqui as versões que foram enviadas para você. Aprove a versão atual quando ela estiver conforme.
                </p>

                {{-- O cliente vê o mesmo contador que a equipe: é isso que evita
                     a discussão sobre quantas alterações já foram feitas. --}}
                <div style="margin-bottom:1.25rem;">
                    <x-revision-counter :project="$project" />
                </div>

                @if(session('success'))
                <div style="background-color:oklch(0.65 0.16 145 / 0.12);border:1px solid oklch(0.65 0.16 145 / 0.35);border-radius:0.5rem;padding:0.75rem 0.875rem;margin-bottom:1rem;font-size:0.875rem;color:oklch(0.65 0.16 145);">
                    {{ session('success') }}
                </div>
                @endif
                @error('plan_version')
                <div style="background-color:oklch(0.62 0.21 25 / 0.12);border:1px solid oklch(0.62 0.21 25 / 0.35);border-radius:0.5rem;padding:0.75rem 0.875rem;margin-bottom:1rem;font-size:0.875rem;color:oklch(0.62 0.21 25);">
                    {{ $message }}
                </div>
                @enderror

                <div class="space-y-3">
                    @foreach($plans as $plan)
                    @php
                        // A colecção vem filtrada às versões enviadas e ordenada
                        // da mais recente para a mais antiga: a primeira é a que
                        // o cliente tem em mãos.
                        $current = $plan->versions->first();
                        $history = $plan->versions->slice(1);
                    @endphp
                    <div x-data="{ history: false }" style="border:1px solid var(--color-border);border-radius:0.75rem;padding:1.125rem;background-color:var(--color-surface);">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 style="font-size:0.9375rem;font-weight:600;">{{ $plan->name }}</h3>
                                    <span style="font-size:0.6875rem;font-weight:500;padding:0.125rem 0.5rem;border-radius:9999px;background-color:var(--color-card);color:var(--color-muted-foreground);border:1px solid var(--color-border);">{{ $plan->disciplineLabel() }}</span>
                                    @if($current->approved_at)
                                    <span style="font-size:0.6875rem;font-weight:600;padding:0.125rem 0.5rem;border-radius:9999px;background-color:oklch(0.65 0.16 145 / 0.15);color:oklch(0.65 0.16 145);">Aprovada</span>
                                    @elseif($current->isSuperseded())
                                    <span style="font-size:0.6875rem;font-weight:600;padding:0.125rem 0.5rem;border-radius:9999px;background-color:var(--color-card);color:var(--color-muted-foreground);border:1px solid var(--color-border);">Substituída</span>
                                    @endif
                                </div>
                                <p style="font-size:0.8125rem;color:var(--color-muted-foreground);margin-top:0.3rem;">
                                    Versão {{ $current->label() }} ·
                                    {{ $current->isExternal() ? 'endereço externo' : strtoupper($current->format) }}{{ $current->humanSize() ? ' · '.$current->humanSize() : '' }}
                                    · enviada a {{ $current->sent_to_client_at->format('d/m/Y') }}
                                </p>
                                @if($current->change_note)
                                <p style="font-size:0.8125rem;color:var(--color-muted-foreground);margin-top:0.25rem;white-space:pre-wrap;">{{ $current->change_note }}</p>
                                @endif
                                @if($current->approved_at)
                                <p style="font-size:0.75rem;color:oklch(0.65 0.16 145);margin-top:0.35rem;">
                                    Aprovada por {{ $current->approver?->name ?? 'você' }} em {{ $current->approved_at->format('d/m/Y \à\s H:i') }}.
                                </p>
                                @elseif($current->isSuperseded())
                                {{-- Diz porque não há botão de aprovar, sem
                                     descrever a revisão que ainda não foi
                                     enviada: o cliente fica a saber o estado da
                                     cópia que tem, e não o que está em cima da
                                     mesa do estúdio. --}}
                                <p style="font-size:0.75rem;color:var(--color-muted-foreground);margin-top:0.35rem;">
                                    Esta não é mais a versão atual da planta e por isso não pode ser aprovada.
                                </p>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center gap-2 shrink-0">
                                @include('client.projects.partials.plan-version-links', ['project' => $project, 'plan' => $plan, 'version' => $current])

                                {{-- PORT-04/PORT-06: só a versão corrente e só uma vez. --}}
                                @if(! $current->isSuperseded() && ! $current->approved_at)
                                <form method="POST" action="{{ route('portal.projects.plans.versions.approve', [$project, $plan, $current]) }}"
                                      x-data="{ sent: false }" @submit="sent = true"
                                      data-confirm="Aprovar a versão {{ $current->label() }} da planta &quot;{{ $plan->name }}&quot;? A aprovação fica registrada e não é reversível.">
                                    @csrf
                                    {{-- O `disabled` no envio evita o segundo pedido do duplo clique;
                                         o servidor não confia nele — a aprovação é idempotente lá. --}}
                                    <button type="submit" :disabled="sent"
                                            style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.4rem 0.85rem;border-radius:0.375rem;border:none;font-size:0.8125rem;font-weight:600;cursor:pointer;background-color:oklch(0.65 0.16 145);color:white;"
                                            :style="sent ? 'opacity:0.6;cursor:progress;' : ''">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                        </svg>
                                        <span x-text="sent ? 'Aprovando…' : 'Aprovar versão'">Aprovar versão</span>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </div>

                        {{-- PORT-02: as versões anteriores continuam disponíveis. --}}
                        @if($history->isNotEmpty())
                        <div style="margin-top:0.875rem;padding-top:0.875rem;border-top:1px solid var(--color-border);">
                            <button type="button" @click="history = !history"
                                    style="background:none;border:none;padding:0;cursor:pointer;font-size:0.8125rem;color:var(--color-primary);"
                                    x-text="history ? 'Ocultar versões anteriores' : 'Ver versões anteriores ({{ $history->count() }})'">Ver versões anteriores ({{ $history->count() }})</button>

                            <div x-show="history" style="display:none;margin-top:0.75rem;" class="space-y-2">
                                @foreach($history as $old)
                                <div class="flex flex-wrap items-center gap-3" style="font-size:0.8125rem;padding:0.5rem 0.75rem;border-radius:0.5rem;background-color:var(--color-card);border:1px solid var(--color-border);">
                                    <span style="font-weight:600;min-width:2.25rem;">{{ $old->label() }}</span>
                                    <span style="color:var(--color-muted-foreground);" class="min-w-0 truncate">
                                        {{ $old->isExternal() ? 'Endereço externo' : strtoupper($old->format) }}{{ $old->humanSize() ? ' · '.$old->humanSize() : '' }}
                                    </span>
                                    <span style="color:var(--color-muted-foreground);">enviada a {{ $old->sent_to_client_at->format('d/m/Y') }}</span>
                                    @if($old->approved_at)
                                    <span style="color:oklch(0.65 0.16 145);">aprovada a {{ $old->approved_at->format('d/m/Y') }}</span>
                                    @endif
                                    <span class="ml-auto flex flex-wrap items-center gap-2">
                                        @include('client.projects.partials.plan-version-links', ['project' => $project, 'plan' => $plan, 'version' => $old])
                                    </span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- Tab: Mensagens --}}
        @if($allowComments)
        <div x-show="tab === 'mensagens'" style="display:none;">
            <div style="background-color:var(--color-card);border:1px solid var(--color-border);border-radius:0.875rem;overflow:hidden;"
                 x-data="chatClient({{ $project->id }})" x-init="init()">
                <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--color-border);">
                    <p style="font-size:0.9375rem;font-weight:700;">Conversa com a equipe</p>
                    <p style="font-size:0.75rem;color:var(--color-muted-foreground);margin-top:0.125rem;">Mensagens em tempo real</p>
                </div>

                <div x-ref="msgArea" style="height:min(400px,55vh);overflow-y:auto;padding:1rem;display:flex;flex-direction:column;gap:0.75rem;">
                    <template x-for="m in messages" :key="m.id">
                        <div :style="m.is_mine ? 'display:flex;justify-content:flex-end' : 'display:flex;justify-content:flex-start'">
                            <div :style="m.is_mine
                                ? 'background-color:var(--color-primary);color:var(--color-primary-foreground);max-width:80%;padding:0.5rem 0.875rem;border-radius:1rem 1rem 0.25rem 1rem;font-size:0.875rem;'
                                : 'background-color:var(--color-surface);border:1px solid var(--color-border);color:var(--color-foreground);max-width:80%;padding:0.5rem 0.875rem;border-radius:1rem 1rem 1rem 0.25rem;font-size:0.875rem;'">
                                <p x-show="!m.is_mine" style="font-size:0.75rem;font-weight:600;margin-bottom:0.25rem;opacity:0.8;" x-text="m.user_name"></p>
                                <p x-text="m.body" style="white-space:pre-wrap;word-break:break-word;"></p>
                                <p style="font-size:0.65rem;opacity:0.6;margin-top:0.25rem;text-align:right;" x-text="m.created_at"></p>
                            </div>
                        </div>
                    </template>
                    <div x-show="messages.length === 0 && !loading" style="display:flex;flex-direction:column;align-items:center;justify-content:center;flex:1;gap:0.5rem;padding:2rem 0;color:var(--color-muted-foreground);">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:2rem;height:2rem;opacity:0.4;"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/></svg>
                        <p style="font-size:0.875rem;">Nenhuma mensagem ainda. Seja o primeiro a escrever!</p>
                    </div>
                    <div x-show="loading" style="text-align:center;padding:2rem 0;color:var(--color-muted-foreground);font-size:0.875rem;">Carregando…</div>
                </div>

                <div style="border-top:1px solid var(--color-border);padding:0.875rem;display:flex;gap:0.625rem;align-items:flex-end;">
                    <textarea x-model="newMsg"
                              @keydown.enter.prevent="!$event.shiftKey && send()"
                              rows="2" maxlength="1000"
                              placeholder="Escreva uma mensagem para a equipe… (Enter envia, Shift+Enter quebra linha)"
                              style="flex:1;resize:none;background-color:var(--color-surface);border:1px solid var(--color-border);border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.875rem;color:var(--color-foreground);outline:none;transition:border-color 0.15s;max-height:100px;overflow-y:auto;"
                              @focus="$el.style.borderColor='var(--color-primary)'"
                              @blur="$el.style.borderColor='var(--color-border)'"></textarea>
                    <button @click="send()" :disabled="sending || !newMsg.trim()" class="btn-primary" style="white-space:nowrap;flex-shrink:0;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1rem;height:1rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/>
                        </svg>
                        <span x-text="sending ? 'Enviando…' : 'Enviar'"></span>
                    </button>
                </div>
                <p style="text-align:right;font-size:0.7rem;color:var(--color-muted-foreground);padding:0 0.875rem 0.5rem;">
                    <span x-text="newMsg.length"></span>/1000
                </p>
            </div>
        </div>
        @endif
    </div>
</div>

<script @cspNonce>
function chatClient(projectId) {
    return {
        messages: [],
        newMsg: '',
        sending: false,
        loading: true,
        init() {
            this.load();
            if (window.Echo) {
                window.Echo.private('project.' + projectId)
                    .listen('.message.sent', (e) => {
                        if (!this.messages.find(m => m.id === e.id)) {
                            this.messages.push({ ...e, is_mine: false });
                            this.$nextTick(() => {
                                if (this.$refs.msgArea) this.$refs.msgArea.scrollTop = this.$refs.msgArea.scrollHeight;
                            });
                        }
                    });
            }
        },
        async load() {
            this.loading = true;
            try {
                const r = await fetch('/portal/obras/' + projectId + '/mensagens', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                this.messages = await r.json();
            } catch(e) { console.error(e); }
            this.loading = false;
            this.$nextTick(() => {
                if (this.$refs.msgArea) this.$refs.msgArea.scrollTop = this.$refs.msgArea.scrollHeight;
            });
        },
        async send() {
            const text = this.newMsg.trim();
            if (!text || this.sending) return;
            this.sending = true;
            try {
                const r = await fetch('/portal/obras/' + projectId + '/mensagens', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({ body: text })
                });
                const data = await r.json();
                if (data.ok) {
                    this.messages.push({
                        id: data.id,
                        body: text,
                        is_mine: true,
                        user_name: 'Eu',
                        created_at: new Date().toLocaleString('pt-BR', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' })
                    });
                    this.newMsg = '';
                    this.$nextTick(() => {
                        if (this.$refs.msgArea) this.$refs.msgArea.scrollTop = this.$refs.msgArea.scrollHeight;
                    });
                }
            } catch(e) { console.error(e); }
            this.sending = false;
        }
    };
}
</script>

{{-- Lightbox --}}
<div id="lightbox-overlay"
     style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.92);align-items:center;justify-content:center;flex-direction:column;gap:1rem;">
    <button type="button" data-lightbox-close class="hover-opaque"
            style="position:absolute;top:1rem;right:1rem;background:none;border:none;color:white;cursor:pointer;padding:0.5rem;border-radius:0.5rem;opacity:0.7;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:1.5rem;height:1.5rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
    <img id="lightbox-img" src="" alt="" style="max-width:90vw;max-height:85vh;object-fit:contain;border-radius:0.5rem;box-shadow:0 25px 60px rgba(0,0,0,0.6);">
    <p id="lightbox-caption" style="color:rgba(255,255,255,0.7);font-size:0.875rem;text-align:center;max-width:60ch;"></p>
</div>

{{-- Abrir, fechar e a tecla Escape vivem em resources/js/app.js: era o mesmo
     código aqui e em admin/projects/show.blade.php. A miniatura declara o que
     mostrar em `data-lightbox-src` e `data-lightbox-caption`. --}}
</x-client-layout>
