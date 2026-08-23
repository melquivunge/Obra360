@php
    $tabs = ['geral' => 'Geral', 'fases' => 'Fases', 'fotos' => 'Fotos', 'despesas' => 'Despesas', 'pagamentos' => 'Pagamentos', 'documentos' => 'Documentos', 'historico' => 'Histórico', 'mensagens' => 'Mensagens'];
    // Os filtros das fotos recarregam a página por GET; sem isto o separador activo voltava sempre a "geral".
    $requestedTab = request('tab');
    $activeTab = is_string($requestedTab) && isset($tabs[$requestedTab]) ? $requestedTab : 'geral';
@endphp
<x-admin-layout>
<div class="p-6 md:p-8 space-y-6" x-data="projectShow({{ $project->id }}, '{{ $activeTab }}')">

    {{-- Header --}}
    <div>
        <a href="{{ route('admin.projects.index') }}" style="font-size:0.875rem;color:var(--muted-foreground)">← Obras</a>
        <div class="flex flex-wrap items-start justify-between gap-3 mt-2">
            <div class="flex items-start gap-4">
                {{-- Capa da obra ao lado do título. Sem capa própria — ou com o ficheiro em
                     falta no disco, que faz a rota devolver 404 — entra a imagem padrão, para
                     o cabeçalho não mudar de forma consoante a obra. --}}
                <x-project-cover :src="$project->cover_image_path ? route('admin.projects.cover', $project) : null"
                                 alt="Capa da obra"
                                 style="width:4.5rem;height:4.5rem;border-radius:0.75rem;object-fit:cover;border:1px solid var(--border);flex-shrink:0;" />
                <div>
                    <div class="flex items-center gap-3">
                        <h1 style="font-size:1.5rem;font-weight:700;">{{ $project->name }}</h1>
                        <span class="badge badge-{{ $project->status }}">{{ \App\Models\Project::STATUSES[$project->status] ?? $project->status }}</span>
                    </div>
                    <p style="color:var(--muted-foreground);font-size:0.875rem;margin-top:0.25rem;">{{ $project->client?->name }} · {{ $project->location ?: '—' }}</p>
                </div>
            </div>
            @can('manage-projects')
            <div class="flex gap-2">
                <a href="{{ route('admin.projects.edit', $project) }}" class="btn-secondary">Editar</a>
                <form method="POST" action="{{ route('admin.projects.destroy', $project) }}" onsubmit="return confirm('Excluir esta obra?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-danger">Excluir</button>
                </form>
            </div>
            @endcan
        </div>
        <div class="mt-4">
            <div class="flex justify-between" style="font-size:0.8125rem;color:var(--muted-foreground);margin-bottom:0.375rem;">
                <span>Progresso geral</span><span>{{ $project->progress }}%</span>
            </div>
            <div class="progress-bar"><div class="progress-fill" style="width:{{ $project->progress }}%"></div></div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="tab-nav">
        @foreach($tabs as $key=>$label)
        <button type="button" class="tab-btn" :class="tab==='{{ $key }}' ? 'active' : ''" @click="select('{{ $key }}')">{{ $label }}</button>
        @endforeach
    </div>

    {{-- TAB: Geral --}}
    <div x-show="tab==='geral'" x-cloak>
        <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:1.25rem;">
            <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3 mb-4">
                @php $infos = [
                    'Cliente'=>$project->client?->name??'—',
                    'Código'=>$project->code??'—',
                    'Localização'=>$project->location??'—',
                    'Início'=>$project->start_date?\Carbon\Carbon::parse($project->start_date)->format('d/m/Y'):'—',
                    'Previsão'=>$project->expected_end_date?\Carbon\Carbon::parse($project->expected_end_date)->format('d/m/Y'):'—',
                    'Gestor'=>$project->manager?->name??'—',
                ]; @endphp
                @foreach($infos as $label=>$val)
                <div>
                    <p style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.06em;color:var(--muted-foreground)">{{ $label }}</p>
                    <p style="font-weight:600;margin-top:0.25rem;">{{ $val }}</p>
                </div>
                @endforeach
            </div>
            @if($project->description)
            <div style="border-top:1px solid var(--border);padding-top:1rem;">
                <p style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.06em;color:var(--muted-foreground);margin-bottom:0.5rem;">Descrição</p>
                <p style="font-size:0.875rem;white-space:pre-wrap;">{{ $project->description }}</p>
            </div>
            @endif
        </div>
        <div class="grid gap-3 sm:grid-cols-3 mt-4">
            @php $spent=$totalSpent; $budget=floatval($project->budget??0); @endphp
            <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:1rem;">
                <p style="font-size:0.75rem;color:var(--muted-foreground)">Orçamento</p>
                <p style="font-size:1.25rem;font-weight:700;margin-top:0.25rem;">{{ money($budget) }}</p>
            </div>
            <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:1rem;">
                <p style="font-size:0.75rem;color:var(--muted-foreground)">Total gasto</p>
                <p style="font-size:1.25rem;font-weight:700;margin-top:0.25rem;">{{ money($spent) }}</p>
            </div>
            <div style="background-color:oklch(0.74 0.135 78 / 0.08);border:1px solid oklch(0.74 0.135 78 / 0.35);border-radius:0.75rem;padding:1rem;">
                <p style="font-size:0.75rem;color:var(--muted-foreground)">Saldo restante</p>
                <p style="font-size:1.25rem;font-weight:700;color:var(--primary);margin-top:0.25rem;">{{ money($budget-$spent) }}</p>
            </div>
        </div>
    </div>

    {{-- TAB: Fases --}}
    <div x-show="tab==='fases'" x-cloak class="space-y-4">
        @can('manage-projects')
        <div class="flex justify-end">
            <button type="button" onclick="openModal('modal-fase')" class="btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Nova fase
            </button>
        </div>
        @endcan

        @forelse($phases as $phase)
        <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:1.25rem;" x-data="{ editing: false }">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <h4 style="font-weight:600;">{{ $phase->name }}</h4>
                        <span class="badge badge-{{ $phase->status }}">{{ \App\Models\ProjectPhase::STATUSES[$phase->status] ?? $phase->status }}</span>
                    </div>
                    @if($phase->description)<p style="font-size:0.8125rem;color:var(--muted-foreground);margin-top:0.25rem;">{{ $phase->description }}</p>@endif
                </div>
                @can('manage-projects')
                <div class="flex gap-1 shrink-0">
                    <button type="button" @click="editing=!editing" style="padding:0.375rem;border-radius:0.375rem;color:var(--muted-foreground);background:none;border:none;cursor:pointer;" onmouseover="this.style.backgroundColor='var(--surface)'" onmouseout="this.style.backgroundColor=''">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                    </button>
                    <form method="POST" action="{{ route('admin.projects.phases.destroy', [$project, $phase]) }}" onsubmit="return confirm('Remover fase?')">
                        @csrf @method('DELETE')
                        <button type="submit" style="padding:0.375rem;border-radius:0.375rem;color:var(--muted-foreground);background:none;border:none;cursor:pointer;" onmouseover="this.style.backgroundColor='oklch(0.62 0.21 25 / 0.15)';this.style.color='var(--destructive)'" onmouseout="this.style.backgroundColor='';this.style.color='var(--muted-foreground)'">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                        </button>
                    </form>
                </div>
                @endcan
            </div>
            <div class="mt-3">
                <div class="flex justify-between" style="font-size:0.75rem;color:var(--muted-foreground);margin-bottom:0.375rem;"><span>Progresso</span><span>{{ $phase->percentage }}%</span></div>
                <div class="progress-bar"><div class="progress-fill" style="width:{{ $phase->percentage }}%"></div></div>
            </div>
            @can('manage-projects')
            <form x-show="editing" x-cloak method="POST" action="{{ route('admin.projects.phases.update', [$project, $phase]) }}" class="mt-4 space-y-3 pt-4" style="border-top:1px solid var(--border)">
                @csrf @method('PUT')
                <div class="grid gap-3 sm:grid-cols-2">
                    <div><label class="field-label">Nome</label><input type="text" name="name" value="{{ $phase->name }}" required></div>
                    <div><label class="field-label">Status</label><select name="status">@foreach(\App\Models\ProjectPhase::STATUSES as $k=>$v)<option value="{{ $k }}" {{ $phase->status===$k?'selected':'' }}>{{ $v }}</option>@endforeach</select></div>
                    <div><label class="field-label">%</label><input type="number" name="percentage" min="0" max="100" value="{{ $phase->percentage }}"></div>
                    <div><label class="field-label">Ordem</label><input type="number" name="sort_order" value="{{ $phase->sort_order }}"></div>
                </div>
                <div><label class="field-label">Descrição</label><textarea name="description" rows="2" style="resize:vertical;">{{ $phase->description }}</textarea></div>
                <div class="flex gap-2"><button type="submit" class="btn-primary">Salvar</button><button type="button" @click="editing=false" class="btn-secondary">Cancelar</button></div>
            </form>
            @endcan
        </div>
        @empty
        <p style="color:var(--muted-foreground);font-size:0.875rem;">Nenhuma fase cadastrada.</p>
        @endforelse
    </div>

    {{-- TAB: Fotos --}}
    <div x-show="tab==='fotos'" x-cloak class="space-y-4">
        @can('manage-projects')
        <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:1.25rem;">
            <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;">Enviar fotos / vídeos</h3>
            <form method="POST" action="{{ route('admin.projects.photos.store', $project) }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <div>
                    <label class="field-label">Arquivos (JPG, PNG, WebP, MP4…)</label>
                    <input type="file" name="photos[]" multiple accept="image/*,video/*" style="padding:0.375rem;" required>
                </div>
                <div class="grid gap-3 sm:grid-cols-3">
                    <div>
                        <label class="field-label">Fase</label>
                        <select name="phase_id">
                            <option value="">Sem fase</option>
                            @foreach($phases as $ph)<option value="{{ $ph->id }}">{{ $ph->name }}</option>@endforeach
                        </select>
                    </div>
                    <div><label class="field-label">Legenda</label><input type="text" name="caption"></div>
                    <div><label class="field-label">Data</label><input type="date" name="taken_at" value="{{ now()->toDateString() }}"></div>
                </div>
                <button type="submit" class="btn-primary">Enviar</button>
            </form>
        </div>
        @endcan
        {{-- Filtros da galeria (FOTO-04) --}}
        <form method="GET" action="{{ route('admin.projects.show', $project) }}" class="flex flex-wrap gap-2" style="margin-bottom:0.75rem;">
            <input type="hidden" name="tab" value="fotos">
            <select name="photo_phase" style="max-width:14rem;" onchange="this.form.submit()">
                <option value="">Todas as fases</option>
                @foreach($phases as $ph)
                    <option value="{{ $ph->id }}" {{ (int) request('photo_phase') === $ph->id ? 'selected' : '' }}>{{ $ph->name }}</option>
                @endforeach
            </select>
            <input type="date" name="photo_from" value="{{ request('photo_from') }}" style="max-width:11rem;" onchange="this.form.submit()" title="De">
            <input type="date" name="photo_to" value="{{ request('photo_to') }}" style="max-width:11rem;" onchange="this.form.submit()" title="Até">
            <select name="photo_order" style="max-width:11rem;" onchange="this.form.submit()">
                <option value="recentes" {{ request('photo_order') !== 'antigas' ? 'selected' : '' }}>Mais recentes</option>
                <option value="antigas" {{ request('photo_order') === 'antigas' ? 'selected' : '' }}>Mais antigas</option>
            </select>
            @if(request()->hasAny(['photo_phase','photo_from','photo_to','photo_order']))
                <a href="{{ route('admin.projects.show', [$project, 'tab' => 'fotos']) }}" style="align-self:center;font-size:0.8125rem;color:var(--muted-foreground);">Limpar filtros</a>
            @endif
        </form>

        <div class="photo-grid">
            @forelse($photos as $photo)
            @php
                $photoUrl = route('admin.projects.photos.show', [$project, $photo]);
                $thumbUrl = $photo->thumbnail_path ? $photoUrl.'?thumb=1' : $photoUrl;
            @endphp
            <div class="photo-item" style="position:relative;">
                @if($photo->media_type === 'video')
                <video src="{{ $photoUrl }}" style="width:100%;height:100%;object-fit:cover;" muted></video>
                @else
                <img src="{{ $thumbUrl }}" alt="{{ $photo->caption }}" loading="lazy" style="cursor:pointer;" onclick="openLightbox(@js($photoUrl), @js($photo->caption ?? ''))">
                @endif
                @if($photo->caption)<div style="position:absolute;bottom:0;left:0;right:0;background:linear-gradient(to top,rgba(0,0,0,0.8),transparent);padding:0.5rem;font-size:0.75rem;color:#fff;">{{ $photo->caption }}</div>@endif
                @can('manage-projects')
                <form method="POST" action="{{ route('admin.projects.photos.destroy', [$project, $photo]) }}" onsubmit="return confirm('Remover foto?')" style="position:absolute;top:4px;right:4px;">
                    @csrf @method('DELETE')
                    <button type="submit" style="background:rgba(0,0,0,0.6);border:none;border-radius:0.25rem;color:#fff;cursor:pointer;padding:0.25rem;">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </form>
                @endcan
            </div>
            @empty
            <p style="color:var(--muted-foreground);font-size:0.875rem;grid-column:1/-1;">
                {{ request()->hasAny(['photo_phase','photo_from','photo_to']) ? 'Nenhuma foto corresponde aos filtros.' : 'Nenhuma foto enviada.' }}
            </p>
            @endforelse
        </div>
    </div>

    {{-- TAB: Despesas --}}
    <div x-show="tab==='despesas'" x-cloak class="space-y-4">
        @can('access-financial')
        <div class="flex justify-end">
            <button type="button" onclick="novaDespesa()" class="btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Registrar despesa
            </button>
        </div>
        @endcan
        @php $budget=floatval($project->budget??0);$pct=$budget>0?min(100,round($totalSpent/$budget*100)):0; @endphp
        <div class="grid gap-3 sm:grid-cols-3">
            <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:1rem;"><p style="font-size:0.75rem;color:var(--muted-foreground)">Orçamento</p><p style="font-size:1.125rem;font-weight:700;margin-top:0.25rem;">{{ money($budget) }}</p></div>
            <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:1rem;"><p style="font-size:0.75rem;color:var(--muted-foreground)">Total gasto</p><p style="font-size:1.125rem;font-weight:700;margin-top:0.25rem;">{{ money($totalSpent) }}</p></div>
            <div style="background-color:oklch(0.74 0.135 78 / 0.08);border:1px solid oklch(0.74 0.135 78 / 0.3);border-radius:0.75rem;padding:1rem;"><p style="font-size:0.75rem;color:var(--muted-foreground)">Saldo</p><p style="font-size:1.125rem;font-weight:700;color:var(--primary);margin-top:0.25rem;">{{ money($budget-$totalSpent) }}</p></div>
        </div>
        <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;overflow:hidden;">
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead style="background-color:var(--surface)"><tr><th>Data</th><th>Categoria</th><th>Descrição</th><th>Fornecedor</th><th style="text-align:right">Valor</th><th></th></tr></thead>
                    <tbody>
                        @forelse($project->expenses as $exp)
                        <tr>
                            <td style="color:var(--muted-foreground);white-space:nowrap;">{{ $exp->spent_at?->format('d/m/Y') }}</td>
                            <td>{{ \App\Models\Expense::CATEGORIES[$exp->category]??$exp->category }}</td>
                            <td>{{ $exp->description }}</td>
                            <td style="color:var(--muted-foreground)">{{ $exp->supplier??'—' }}</td>
                            <td style="text-align:right;font-weight:600;">{{ money($exp->amount) }}</td>
                            <td style="white-space:nowrap;display:flex;gap:0.25rem;">
                                @php
                                    $expData = [
                                        'id' => $exp->id,
                                        'category' => $exp->category,
                                        'spent_at' => $exp->spent_at?->toDateString(),
                                        'description' => $exp->description,
                                        'amount' => (float) $exp->amount,
                                        'supplier' => $exp->supplier,
                                        'notes' => $exp->notes,
                                    ];
                                @endphp
                                @can('access-financial')
                                <button type="button" title="Editar despesa"
                                        onclick='editarDespesa(@json($expData))'
                                        style="padding:0.25rem;border-radius:0.375rem;background:none;border:none;cursor:pointer;color:var(--muted-foreground)" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--muted-foreground)'">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
                                </button>
                                <form method="POST" action="{{ route('admin.projects.expenses.destroy', [$project, $exp]) }}" onsubmit="return confirm('Remover despesa?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" style="padding:0.25rem;border-radius:0.375rem;background:none;border:none;cursor:pointer;color:var(--muted-foreground)" onmouseover="this.style.color='var(--destructive)'" onmouseout="this.style.color='var(--muted-foreground)'">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                    </button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" style="text-align:center;color:var(--muted-foreground);padding:2rem;">Nenhuma despesa registrada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB: Pagamentos --}}
    <div x-show="tab==='pagamentos'" x-cloak class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            @php
                $totalPaid    = $payments->where('status','paid')->sum('amount');
                $totalPending = $payments->where('status','pending')->sum('amount');
            @endphp
            <div class="flex gap-3 flex-wrap">
                <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:0.75rem 1rem;">
                    <p style="font-size:0.7rem;text-transform:uppercase;color:var(--muted-foreground)">Pendente</p>
                    <p style="font-size:1rem;font-weight:700;color:oklch(0.6 0.18 40)">{{ money($totalPending) }}</p>
                </div>
                <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:0.75rem 1rem;">
                    <p style="font-size:0.7rem;text-transform:uppercase;color:var(--muted-foreground)">Recebido</p>
                    <p style="font-size:1rem;font-weight:700;color:oklch(0.55 0.16 145)">{{ money($totalPaid) }}</p>
                </div>
            </div>
            @can('access-financial')
            <button type="button" onclick="openModal('modal-pagamento')" class="btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Nova cobrança
            </button>
            @endcan
        </div>

        <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;overflow:hidden;">
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead style="background-color:var(--surface)">
                        <tr><th>Título</th><th>Tipo</th><th>Vencimento</th><th style="text-align:right">Valor</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $pay)
                        <tr>
                            <td>
                                <p style="font-weight:500;">{{ $pay->title }}</p>
                                @if($pay->description)<p style="font-size:0.75rem;color:var(--muted-foreground)">{{ $pay->description }}</p>@endif
                            </td>
                            <td>{{ \App\Models\Payment::TYPES[$pay->type] ?? $pay->type }}</td>
                            <td style="white-space:nowrap;color:var(--muted-foreground)">{{ $pay->due_date?->format('d/m/Y') ?? '—' }}</td>
                            <td style="text-align:right;font-weight:600;">{{ money($pay->amount) }}</td>
                            <td>
                                @if($pay->status === 'paid')
                                    <span style="display:inline-flex;padding:0.2rem 0.6rem;border-radius:9999px;font-size:0.7rem;font-weight:500;background:oklch(0.55 0.16 145 / 0.12);color:oklch(0.55 0.16 145)">Pago</span>
                                @elseif($pay->status === 'cancelled')
                                    <span style="display:inline-flex;padding:0.2rem 0.6rem;border-radius:9999px;font-size:0.7rem;font-weight:500;background:var(--surface);color:var(--muted-foreground)">Cancelado</span>
                                @else
                                    <span style="display:inline-flex;padding:0.2rem 0.6rem;border-radius:9999px;font-size:0.7rem;font-weight:500;background:oklch(0.6 0.18 40 / 0.12);color:oklch(0.6 0.18 40)">Pendente</span>
                                @endif
                            </td>
                            <td>
                                @can('access-financial')
                                <div class="flex gap-1">
                                    @if($pay->isPending())
                                    <form method="POST" action="{{ route('admin.projects.payments.mark-paid', [$project, $pay]) }}">
                                        @csrf
                                        <button type="submit" title="Marcar como pago" style="padding:0.25rem 0.5rem;border-radius:0.375rem;background:none;border:1px solid var(--border);cursor:pointer;font-size:0.7rem;color:oklch(0.55 0.16 145)">✓ Pago</button>
                                    </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.projects.payments.destroy', [$project, $pay]) }}" onsubmit="return confirm('Remover cobrança?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" style="padding:0.25rem;border-radius:0.375rem;background:none;border:none;cursor:pointer;color:var(--muted-foreground)" onmouseover="this.style.color='var(--destructive)'" onmouseout="this.style.color='var(--muted-foreground)'">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                        </button>
                                    </form>
                                </div>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" style="text-align:center;color:var(--muted-foreground);padding:2rem;">Nenhuma cobrança registrada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB: Documentos --}}
    <div x-show="tab==='documentos'" x-cloak class="space-y-4">
        @can('manage-projects')
        <div class="flex justify-end">
            <button type="button" onclick="openModal('modal-documento')" class="btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Enviar documento
            </button>
        </div>
        @endcan
        <div class="space-y-2">
            @forelse($project->documents as $doc)
            <div class="flex items-center gap-3 p-3 rounded-lg" style="background-color:var(--card);border:1px solid var(--border);">
                <svg class="w-5 h-5 shrink-0" style="color:var(--primary)" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                <div class="min-w-0 flex-1">
                    <p class="font-medium truncate" style="font-size:0.875rem;">{{ $doc->name }}</p>
                    <p style="font-size:0.75rem;color:var(--muted-foreground)">{{ \App\Models\Document::TYPES[$doc->type]??$doc->type }} ·
                        <span style="color:{{ $doc->visibility==='cliente'?'var(--success)':'var(--muted-foreground)' }}">{{ $doc->visibility==='cliente'?'Visível ao cliente':'Interno' }}</span>
                        · {{ $doc->created_at->format('d/m/Y') }}
                    </p>
                </div>
                <a href="{{ route('admin.projects.documents.download', [$project, $doc]) }}" target="_blank" rel="noopener" style="padding:0.5rem;color:var(--muted-foreground)" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--muted-foreground)'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                </a>
                @can('manage-projects')
                <form method="POST" action="{{ route('admin.projects.documents.destroy', [$project, $doc]) }}" onsubmit="return confirm('Remover documento?')">
                    @csrf @method('DELETE')
                    <button type="submit" style="padding:0.5rem;background:none;border:none;cursor:pointer;color:var(--muted-foreground)" onmouseover="this.style.color='var(--destructive)'" onmouseout="this.style.color='var(--muted-foreground)'">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                    </button>
                </form>
                @endcan
            </div>
            @empty
            <p style="color:var(--muted-foreground);font-size:0.875rem;">Nenhum documento enviado.</p>
            @endforelse
        </div>
    </div>

    {{-- TAB: Histórico --}}
    <div x-show="tab==='historico'" x-cloak class="space-y-4">
        @can('manage-projects')
        <div class="flex justify-end">
            <button type="button" onclick="openModal('modal-historico')" class="btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Nova atualização
            </button>
        </div>
        @endcan
        <div class="space-y-2">
            @forelse($project->updates as $upd)
            <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:1rem;">
                <div class="flex items-center justify-between" style="font-size:0.75rem;color:var(--muted-foreground);margin-bottom:0.25rem;">
                    <span class="badge badge-planejada">{{ \App\Models\ProjectUpdate::TYPES[$upd->type]??$upd->type }}</span>
                    <span>{{ $upd->happened_at?->format('d/m/Y H:i') }}</span>
                </div>
                <p style="font-size:0.875rem;font-weight:500;">{{ $upd->title }}</p>
                @if($upd->description)<p style="font-size:0.8125rem;color:var(--muted-foreground);margin-top:0.25rem;">{{ $upd->description }}</p>@endif
            </div>
            @empty
            <p style="color:var(--muted-foreground);font-size:0.875rem;">Nenhuma atualização registrada.</p>
            @endforelse
        </div>
    </div>

    {{-- TAB: Mensagens --}}
    <div x-show="tab==='mensagens'" x-cloak>
        <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;display:flex;flex-direction:column;height:min(70vh,520px);">
            <div style="padding:1rem;border-bottom:1px solid var(--border);flex-shrink:0;">
                <p style="font-weight:700;">{{ $project->name }}</p>
                <p style="font-size:0.75rem;color:var(--muted-foreground)">Conversa em tempo real</p>
            </div>
            <div id="msg-list" style="flex:1;overflow-y:auto;padding:1rem;display:flex;flex-direction:column;gap:0.75rem;">
                @forelse($project->messages as $msg)
                @php $mine = $msg->user_id === auth()->id(); @endphp
                <div style="display:flex;justify-content:{{ $mine?'flex-end':'flex-start' }};">
                    <div style="max-width:80%;border-radius:1rem;padding:0.625rem 0.875rem;font-size:0.875rem;background:{{ $mine?'var(--primary)':'var(--surface)' }};color:{{ $mine?'var(--primary-foreground)':'var(--foreground)' }};">
                        @if(!$mine)<p style="font-size:0.75rem;font-weight:600;opacity:0.8;margin-bottom:0.125rem;">{{ $msg->user?->name }}</p>@endif
                        <p style="white-space:pre-wrap;">{{ $msg->body }}</p>
                        <p style="font-size:0.7rem;margin-top:0.25rem;opacity:0.7;">{{ $msg->created_at->format('d/m H:i') }}</p>
                    </div>
                </div>
                @empty
                <p style="color:var(--muted-foreground);font-size:0.875rem;text-align:center;margin:auto;">Nenhuma mensagem. Seja o primeiro!</p>
                @endforelse
            </div>
            <div style="padding:0.75rem;border-top:1px solid var(--border);">
                <div class="flex gap-2 items-end">
                    <textarea id="msg-input" rows="2" placeholder="Escreva uma mensagem… (Enter envia, Shift+Enter nova linha)" style="flex:1;resize:none;"></textarea>
                    <button id="msg-send" class="btn-primary">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
                        Enviar
                    </button>
                </div>
            </div>
        </div>
        <script>
        (function(){
            const projectId = {{ $project->id }};
            const myId = {{ auth()->id() }};
            const csrfToken = document.querySelector('meta[name=csrf-token]').content;
            const list = document.getElementById('msg-list');
            const input = document.getElementById('msg-input');
            const btn = document.getElementById('msg-send');

            function scrollBottom(){ list.scrollTop = list.scrollHeight; }
            function appendMsg(body, userName, isMine, time){
                const d = document.createElement('div');
                d.style.cssText = `display:flex;justify-content:${isMine?'flex-end':'flex-start'}`;
                const bubble = document.createElement('div');
                bubble.style.cssText = `max-width:80%;border-radius:1rem;padding:0.625rem 0.875rem;font-size:0.875rem;background:${isMine?'var(--primary)':'var(--surface)'};color:${isMine?'var(--primary-foreground)':'var(--foreground)'}`;
                if (!isMine) {
                    const name = document.createElement('p');
                    name.style.cssText = 'font-size:0.75rem;font-weight:600;opacity:0.8;margin-bottom:0.125rem;';
                    name.textContent = userName || 'Usuário';
                    bubble.appendChild(name);
                }
                const text = document.createElement('p');
                text.style.whiteSpace = 'pre-wrap';
                text.textContent = body;
                bubble.appendChild(text);
                const sentAt = document.createElement('p');
                sentAt.style.cssText = 'font-size:0.7rem;margin-top:0.25rem;opacity:0.7;';
                sentAt.textContent = time;
                bubble.appendChild(sentAt);
                d.appendChild(bubble);
                list.appendChild(d);
                scrollBottom();
            }

            async function send(){
                const body = input.value.trim();
                if(!body) return;
                btn.disabled = true;
                try {
                    const r = await fetch(`/admin/obras/${projectId}/mensagens`, {
                        method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrfToken,'Accept':'application/json'},
                        body: JSON.stringify({body})
                    });
                    const data = await r.json();
                    if(data.ok){ input.value=''; appendMsg(body,'Eu',true,new Date().toLocaleString('pt-BR',{day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'})); }
                } finally { btn.disabled=false; }
            }

            btn.addEventListener('click', send);
            input.addEventListener('keydown', e => { if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();send();} });

            if(window.Echo){
                window.Echo.private(`project.${projectId}`).listen('.message.sent', data => {
                    if(data.user_id !== myId) appendMsg(data.body, data.user_name, false, data.created_at);
                });
            }
            scrollBottom();
        })();
        </script>
    </div>
</div>

<script>
function projectShow(id, initialTab){ return {
    tab: initialTab || 'geral',
    // Reflectir o separador no URL: os POST desta página (fotos, fases, despesas,
    // documentos…) voltam por back(), que usa o referer — sem isto cairiam em "geral".
    select(key){
        this.tab = key;
        if(!window.history || !window.history.replaceState) return;
        var url = new URL(window.location.href);
        url.searchParams.set('tab', key);
        // replaceState (e não pushState) para não encher o histórico do browser.
        window.history.replaceState(window.history.state, '', url);
    },
}; }
function openModal(id){ var m=document.getElementById(id);m.style.display='flex';var f=m.querySelector('select,input:not([type=hidden]):not([type=file]):not([type=checkbox]),textarea');if(f)setTimeout(function(){f.focus();},50); }
function closeModal(id){ document.getElementById(id).style.display='none'; }
document.addEventListener('keydown',function(e){if(e.key==='Escape')document.querySelectorAll('.modal-overlay').forEach(function(m){m.style.display='none';});});
</script>

@can('manage-projects')
{{-- Modal: Nova Fase --}}
<div id="modal-fase" class="modal-overlay" style="display:none;" onclick="if(event.target===this)closeModal('modal-fase')">
  <div class="modal-box">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
      <h2 class="modal-title" style="margin:0;">Nova fase</h2>
      <button type="button" onclick="closeModal('modal-fase')" style="padding:0.375rem;border-radius:0.375rem;border:none;background:none;cursor:pointer;color:var(--muted-foreground);" onmouseover="this.style.backgroundColor='var(--surface)'" onmouseout="this.style.backgroundColor=''"><svg style="width:1.25rem;height:1.25rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('admin.projects.phases.store', $project) }}" class="space-y-4">
      @csrf
      <div class="grid gap-3 sm:grid-cols-2">
        <div><label class="field-label">Nome da fase <span style="color:var(--destructive)">*</span></label><input type="text" name="name" required placeholder="Ex: Fundação, Estrutura, Revestimento..."></div>
        <div><label class="field-label">Status da fase</label><select name="status">@foreach(\App\Models\ProjectPhase::STATUSES as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
        <div>
          <label class="field-label">% Concluída <span class="field-tip" data-tip="Percentagem de conclusão desta fase. 0 = não iniciada, 100 = concluída.">?</span></label>
          <input type="number" name="percentage" min="0" max="100" value="0" placeholder="0">
          <span style="display:block;font-size:0.7rem;color:var(--muted-foreground);margin-top:0.25rem;">0 = não iniciada &nbsp;·&nbsp; 100 = concluída</span>
        </div>
        <div>
          <label class="field-label">Ordem na lista <span class="field-tip" data-tip="Posição da fase na lista. Ex: 1 = primeira fase, 2 = segunda fase.">?</span></label>
          <input type="number" name="sort_order" min="0" value="{{ $phases->count() }}">
          <span style="display:block;font-size:0.7rem;color:var(--muted-foreground);margin-top:0.25rem;">Deixe em 0 para adicionar ao fim da lista</span>
        </div>
        <div><label class="field-label">Data de início</label><input type="date" name="start_date"></div>
        <div><label class="field-label">Previsão de término</label><input type="date" name="expected_end_date"></div>
      </div>
      <div><label class="field-label">Descrição da fase</label><textarea name="description" rows="2" style="resize:vertical;" placeholder="Descreva o que será feito nesta fase..."></textarea></div>
      <div style="display:flex;gap:0.5rem;"><button type="submit" class="btn-primary">Adicionar fase</button><button type="button" onclick="closeModal('modal-fase')" class="btn-secondary">Cancelar</button></div>
    </form>
  </div>
</div>
@endcan

@can('access-financial')
{{-- Modal: Registrar Despesa --}}
<div id="modal-despesa" class="modal-overlay" style="display:none;" onclick="if(event.target===this)closeModal('modal-despesa')">
  <div class="modal-box" style="max-width:40rem;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
      <h2 class="modal-title" id="titulo-despesa" style="margin:0;">Registrar despesa</h2>
      <button type="button" onclick="closeModal('modal-despesa')" style="padding:0.375rem;border-radius:0.375rem;border:none;background:none;cursor:pointer;color:var(--muted-foreground);" onmouseover="this.style.backgroundColor='var(--surface)'" onmouseout="this.style.backgroundColor=''"><svg style="width:1.25rem;height:1.25rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></button>
    </div>
    <form id="form-despesa" method="POST" action="{{ route('admin.projects.expenses.store', $project) }}" enctype="multipart/form-data" class="space-y-4">
      @csrf
      <input type="hidden" name="_method" id="despesa-method" disabled value="PUT">
      <div class="grid gap-3 sm:grid-cols-2">
        <div><label class="field-label">Categoria</label><select name="category">@foreach(\App\Models\Expense::CATEGORIES as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
        <div><label class="field-label">Data</label><input type="date" name="spent_at" value="{{ now()->toDateString() }}" required></div>
        <div class="sm:col-span-2"><label class="field-label">Descrição <span style="color:var(--destructive)">*</span></label><input type="text" name="description" required></div>
        <div><label class="field-label">Valor ({{ currency_symbol() }})</label><input type="number" name="amount" step="0.01" min="0" required></div>
        <div><label class="field-label">Fornecedor</label><input type="text" name="supplier"></div>
        <div><label class="field-label">Comprovante</label><input type="file" name="receipt" style="padding:0.375rem;"></div>
        <div><label class="field-label">Notas</label><input type="text" name="notes"></div>
      </div>
      <div style="display:flex;gap:0.5rem;"><button type="submit" class="btn-primary" id="despesa-submit">Registrar despesa</button><button type="button" onclick="fecharDespesa()" class="btn-secondary">Cancelar</button></div>
    </form>
  </div>
</div>
@endcan

@can('manage-projects')
{{-- Modal: Enviar Documento --}}
<div id="modal-documento" class="modal-overlay" style="display:none;" onclick="if(event.target===this)closeModal('modal-documento')">
  <div class="modal-box">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
      <h2 class="modal-title" style="margin:0;">Enviar documento</h2>
      <button type="button" onclick="closeModal('modal-documento')" style="padding:0.375rem;border-radius:0.375rem;border:none;background:none;cursor:pointer;color:var(--muted-foreground);" onmouseover="this.style.backgroundColor='var(--surface)'" onmouseout="this.style.backgroundColor=''"><svg style="width:1.25rem;height:1.25rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('admin.projects.documents.store', $project) }}" enctype="multipart/form-data" class="space-y-4">
      @csrf
      <div><label class="field-label">Arquivo <span style="color:var(--destructive)">*</span></label><input type="file" name="file" required style="padding:0.375rem;" accept=".pdf,.docx,.xlsx,.xls,.doc,.jpg,.jpeg,.png,.webp"></div>
      <div class="grid gap-3 sm:grid-cols-3">
        <div class="sm:col-span-2"><label class="field-label">Nome do documento <span style="color:var(--destructive)">*</span></label><input type="text" name="name" required></div>
        <div><label class="field-label">Tipo</label><select name="type">@foreach(\App\Models\Document::TYPES as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
      </div>
      <div class="grid gap-3 sm:grid-cols-2">
        <div><label class="field-label">Visibilidade</label><select name="visibility"><option value="interno">Interno (só equipa)</option><option value="cliente">Cliente (visível no portal)</option></select></div>
        <div><label class="field-label">Notas</label><input type="text" name="notes"></div>
      </div>
      <div style="display:flex;gap:0.5rem;"><button type="submit" class="btn-primary">Enviar documento</button><button type="button" onclick="closeModal('modal-documento')" class="btn-secondary">Cancelar</button></div>
    </form>
  </div>
</div>
@endcan

@can('access-financial')
{{-- Modal: Nova Cobrança --}}
<div id="modal-pagamento" class="modal-overlay" style="display:none;" onclick="if(event.target===this)closeModal('modal-pagamento')">
  <div class="modal-box" style="max-width:40rem;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
      <h2 class="modal-title" style="margin:0;">Nova cobrança</h2>
      <button type="button" onclick="closeModal('modal-pagamento')" style="padding:0.375rem;border-radius:0.375rem;border:none;background:none;cursor:pointer;color:var(--muted-foreground);" onmouseover="this.style.backgroundColor='var(--surface)'" onmouseout="this.style.backgroundColor=''"><svg style="width:1.25rem;height:1.25rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('admin.projects.payments.store', $project) }}" class="space-y-4" x-data="{ type: 'pix' }">
      @csrf
      <div class="grid gap-3 sm:grid-cols-2">
        <div class="sm:col-span-2"><label class="field-label">Título <span style="color:var(--destructive)">*</span></label><input type="text" name="title" required placeholder="Ex: Parcela 1, Sinal de obra…"></div>
        <div><label class="field-label">Valor ({{ currency_symbol() }}) <span style="color:var(--destructive)">*</span></label><input type="number" name="amount" step="0.01" min="0.01" required></div>
        <div><label class="field-label">Vencimento</label><input type="date" name="due_date"></div>
        <div>
          <label class="field-label">Tipo</label>
          <select name="type" x-model="type">
            @foreach(\App\Models\Payment::TYPES as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
          </select>
        </div>
        <div>
          <label class="field-label">Status</label>
          <select name="status">
            @foreach(\App\Models\Payment::STATUSES as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
          </select>
        </div>
        <div x-show="type==='boleto'" class="sm:col-span-2"><label class="field-label">Linha digitável (boleto)</label><input type="text" name="barcode" placeholder="000000000 …" maxlength="60"></div>
        <div x-show="type==='pix'" class="sm:col-span-2"><label class="field-label">Chave PIX</label><input type="text" name="pix_key" placeholder="email, CPF, telefone ou chave aleatória" maxlength="100"></div>
        <div x-show="type==='pix'" class="sm:col-span-2"><label class="field-label">Código copia-e-cola / QR</label><textarea name="pix_qrcode" rows="2" style="resize:vertical;" placeholder="00020126…"></textarea></div>
        <div class="sm:col-span-2"><label class="field-label">Descrição / observações</label><textarea name="description" rows="2" style="resize:vertical;"></textarea></div>
      </div>
      <div style="display:flex;gap:0.5rem;"><button type="submit" class="btn-primary">Criar cobrança</button><button type="button" onclick="closeModal('modal-pagamento')" class="btn-secondary">Cancelar</button></div>
    </form>
  </div>
</div>
@endcan

@can('manage-projects')
{{-- Modal: Nova Atualização --}}
<div id="modal-historico" class="modal-overlay" style="display:none;" onclick="if(event.target===this)closeModal('modal-historico')">
  <div class="modal-box">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
      <h2 class="modal-title" style="margin:0;">Nova atualização</h2>
      <button type="button" onclick="closeModal('modal-historico')" style="padding:0.375rem;border-radius:0.375rem;border:none;background:none;cursor:pointer;color:var(--muted-foreground);" onmouseover="this.style.backgroundColor='var(--surface)'" onmouseout="this.style.backgroundColor=''"><svg style="width:1.25rem;height:1.25rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('admin.projects.updates.store', $project) }}" class="space-y-4">
      @csrf
      <div><label class="field-label">Título <span style="color:var(--destructive)">*</span></label><input type="text" name="title" required></div>
      <div><label class="field-label">Descrição</label><textarea name="description" rows="3" style="resize:vertical;"></textarea></div>
      <div style="display:flex;gap:0.5rem;"><button type="submit" class="btn-primary">Registrar</button><button type="button" onclick="closeModal('modal-historico')" class="btn-secondary">Cancelar</button></div>
    </form>
  </div>
</div>
@endcan

{{-- Lightbox --}}
<div id="lightbox-overlay"
     style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.92);align-items:center;justify-content:center;flex-direction:column;gap:1rem;"
     onclick="closeLightbox()">
    <button onclick="closeLightbox();event.stopPropagation()"
            style="position:absolute;top:1rem;right:1rem;background:none;border:none;color:white;cursor:pointer;padding:0.5rem;border-radius:0.5rem;opacity:0.7;"
            onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.7'">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:1.5rem;height:1.5rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
    <img id="lightbox-img" src="" alt="" style="max-width:90vw;max-height:85vh;object-fit:contain;border-radius:0.5rem;box-shadow:0 25px 60px rgba(0,0,0,0.6);" onclick="event.stopPropagation()">
    <p id="lightbox-caption" style="color:rgba(255,255,255,0.7);font-size:0.875rem;text-align:center;max-width:60ch;"></p>
</div>

<script>
function openLightbox(src, caption) {
    const overlay = document.getElementById('lightbox-overlay');
    const img = document.getElementById('lightbox-img');
    const cap = document.getElementById('lightbox-caption');
    if (!overlay || !img) return;
    img.src = src;
    img.alt = caption || '';
    if (cap) cap.textContent = caption || '';
    overlay.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    const overlay = document.getElementById('lightbox-overlay');
    if (overlay) overlay.style.display = 'none';
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('lightbox-overlay')?.style.display === 'flex') closeLightbox();
});

// ── Despesas: o mesmo modal serve para criar e editar (DESP-03) ──────────────
@php
    // Atribuídos primeiro de propósito: @json divide a expressão pela vírgula,
    // por isso um route() de dois argumentos faria o segundo passar por
    // opções de json_encode e perderia as flags de escape.
    $despesaStoreUrl = route('admin.projects.expenses.store', $project);
    $despesaUpdateUrlTemplate = route('admin.projects.expenses.update', [$project, '__ID__']);
@endphp
const despesaStoreUrl = @json($despesaStoreUrl);
const despesaUpdateUrl = id => @json($despesaUpdateUrlTemplate).replace('__ID__', id);

function editarDespesa(despesa) {
    const form = document.getElementById('form-despesa');
    form.action = despesaUpdateUrl(despesa.id);
    document.getElementById('despesa-method').disabled = false;
    document.getElementById('despesa-submit').textContent = 'Guardar alterações';
    document.getElementById('titulo-despesa').textContent = 'Editar despesa';

    form.category.value = despesa.category ?? '';
    form.spent_at.value = despesa.spent_at ?? '';
    form.description.value = despesa.description ?? '';
    form.amount.value = despesa.amount ?? '';
    form.supplier.value = despesa.supplier ?? '';
    form.notes.value = despesa.notes ?? '';
    form.receipt.value = '';

    openModal('modal-despesa');
}

function novaDespesa() {
    const form = document.getElementById('form-despesa');
    form.reset();
    form.action = despesaStoreUrl;
    document.getElementById('despesa-method').disabled = true;
    document.getElementById('despesa-submit').textContent = 'Registrar despesa';
    document.getElementById('titulo-despesa').textContent = 'Registrar despesa';
    form.spent_at.value = @json(now()->toDateString());

    openModal('modal-despesa');
}

function fecharDespesa() {
    closeModal('modal-despesa');
}
</script>
</x-admin-layout>
