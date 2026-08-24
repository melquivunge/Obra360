@php
    // O separador das plantas segue o tipo de projecto: onde o desenho é o
    // produto está sempre lá, e logo a seguir ao geral; numa obra é material de
    // apoio, aparece ao pé dos documentos e só quando já existem plantas — ou a
    // quem as pode criar, senão a primeira nunca poderia ser feita.
    $canManagePlans = auth()->user()->can('manage-projects');
    // OBRA-02: uma obra sem plantas próprias mas com plantas herdadas do
    // projecto de origem tem mesmo de abrir o separador — é lá que elas estão.
    $showPlansTab = $project->showsPlans() || $canManagePlans || $inheritedPlans->isNotEmpty();
    $plansTab = $showPlansTab ? ['plantas' => 'Plantas'] : [];

    $tabs = array_merge(
        ['geral' => 'Geral'],
        $project->isSiteWork() ? [] : $plansTab,
        ['fases' => 'Fases', 'fotos' => 'Fotos', 'despesas' => 'Despesas', 'pagamentos' => 'Pagamentos', 'documentos' => 'Documentos'],
        $project->isSiteWork() ? $plansTab : [],
        ['historico' => 'Histórico', 'mensagens' => 'Mensagens'],
    );

    // Os filtros das fotos recarregam a página por GET; sem isto o separador activo voltava sempre a "geral".
    $requestedTab = request('tab');
    $activeTab = is_string($requestedTab) && isset($tabs[$requestedTab]) ? $requestedTab : 'geral';

    // Um erro de validação volta por back() e a página recarrega no separador
    // "geral" — a mensagem ficava num separador que ninguém tinha aberto, e o
    // modal que a originou fechava-se pelo caminho. Cada formulário desta
    // página leva um marcador `_form`; o marcador diz de onde veio o erro, e
    // daqui sai o separador que tem de estar aberto quando a página volta.
    $submittedForm = is_string(old('_form')) ? old('_form') : null;

    // Os modais das despesas e das versões servem vários registos e escolhem o
    // destino em JS, por isso o marcador leva o id do registo. Estes dois
    // guardam o marcador que voltou — ou o valor de repouso, que não coincide
    // com marcador nenhum e mantém o resumo escondido.
    $expenseForm = $submittedForm !== null && str_starts_with($submittedForm, 'despesa_') ? $submittedForm : 'despesa_nova';
    $versionForm = $submittedForm !== null && str_starts_with($submittedForm, 'nova_versao') ? $submittedForm : 'nova_versao';

    // Marcador → separador onde o formulário vive.
    $formTabs = [
        'nova_fase' => 'fases',
        'editar_fase' => 'fases',
        'enviar_fotos' => 'fotos',
        'despesa_nova' => 'despesas',
        'despesa_editar' => 'despesas',
        'nova_cobranca' => 'pagamentos',
        'novo_documento' => 'documentos',
        'nova_planta' => 'plantas',
        'editar_planta' => 'plantas',
        'nova_versao' => 'plantas',
        'nova_atualizacao' => 'historico',
    ];

    if ($submittedForm !== null && $errors->any()) {
        foreach ($formTabs as $marker => $tab) {
            if (($submittedForm === $marker || str_starts_with($submittedForm, $marker.'_')) && isset($tabs[$tab])) {
                $activeTab = $tab;
                break;
            }
        }
    }

    // Rede de segurança para um pedido sem marcador — o limite de upload das
    // plantas (LIM-02) é o erro que mais custa perder de vista.
    $planErrorKeys = ['file', 'external_url', 'change_note', 'version', 'discipline'];
    if ($submittedForm === null && $showPlansTab && $errors->hasAny($planErrorKeys)) {
        $activeTab = 'plantas';
    }
@endphp
<x-admin-layout>
<div class="p-6 md:p-8 space-y-6" x-data="projectShow({{ $project->id }}, '{{ $activeTab }}')">

    {{-- Header --}}
    <div>
        <a href="{{ route('admin.projects.index') }}" style="font-size:0.875rem;color:var(--muted-foreground)">← Obras e Projetos</a>
        <div class="flex flex-wrap items-start justify-between gap-3 mt-2">
            <div class="flex items-start gap-4">
                {{-- Capa da obra ao lado do título. Sem capa própria — ou com o ficheiro em
                     falta no disco, que faz a rota devolver 404 — entra a imagem padrão, para
                     o cabeçalho não mudar de forma consoante a obra. --}}
                <x-project-cover :src="$project->cover_image_path ? route('admin.projects.cover', $project) : null"
                                 alt="Capa d{{ $project->nounWithArticle() }}"
                                 style="width:4.5rem;height:4.5rem;border-radius:0.75rem;object-fit:cover;border:1px solid var(--border);flex-shrink:0;" />
                <div>
                    <div class="flex items-center gap-3">
                        <h1 style="font-size:1.5rem;font-weight:700;">{{ $project->name }}</h1>
                        <span class="badge badge-{{ $project->status }}">{{ \App\Models\Project::STATUSES[$project->status] ?? $project->status }}</span>
                        <x-project-type :project="$project" />
                    </div>
                    <p style="color:var(--muted-foreground);font-size:0.875rem;margin-top:0.25rem;">{{ $project->client?->name }} · {{ $project->location ?: '—' }}</p>
                </div>
            </div>
            @can('manage-projects')
            <div class="flex gap-2">
                <a href="{{ route('admin.projects.edit', $project) }}" class="btn-secondary">Editar</a>
                <form method="POST" action="{{ route('admin.projects.destroy', $project) }}" data-confirm="Excluir {{ $project->isSiteWork() ? 'esta obra' : 'este projeto' }}?">
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

    {{-- Erros de uma submissão sem marcador: nenhum dos formulários abaixo os
         reclama, e sem isto não apareceriam em lado nenhum. --}}
    <x-form-errors />

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
                    'Tipo'=>$project->typeLabel(),
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
            @php
                // OBRA-03: a continuidade nos dois sentidos. O gestor só abre o
                // que gere, por isso o que ele não pode abrir aparece como texto
                // e não como uma ligação que lhe daria 403.
                $isManager = auth()->user()->isManager();
                $canOpen = fn ($other) => ! $isManager || (int) $other->manager_id === auth()->id();
            @endphp
            @if($project->sourceProject || $project->hasMissingSource() || $project->derivedProjects->isNotEmpty())
            <div style="border-top:1px solid var(--border);padding-top:1rem;margin-bottom:1rem;">
                <p style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.06em;color:var(--muted-foreground);margin-bottom:0.5rem;">Continuidade</p>
                @if($project->sourceProject)
                <p style="font-size:0.875rem;">
                    Nasceu do projeto
                    @if($canOpen($project->sourceProject))
                    <a href="{{ route('admin.projects.show', $project->sourceProject) }}" style="color:var(--primary);font-weight:600;">{{ $project->sourceProject->name }}</a>
                    @else
                    <strong>{{ $project->sourceProject->name }}</strong>
                    @endif
                    <span style="color:var(--muted-foreground)">· {{ $project->sourceProject->typeLabel() }}</span>
                </p>
                @elseif($project->hasMissingSource())
                <p style="font-size:0.875rem;color:var(--muted-foreground)">
                    O projeto de origem foi removido. O vínculo fica salvo e volta a valer se ele for restaurado.
                </p>
                @endif
                @foreach($project->derivedProjects as $derived)
                <p style="font-size:0.875rem;margin-top:0.25rem;">
                    Deu origem a
                    @if($canOpen($derived))
                    <a href="{{ route('admin.projects.show', $derived) }}" style="color:var(--primary);font-weight:600;">{{ $derived->name }}</a>
                    @else
                    <strong>{{ $derived->name }}</strong>
                    @endif
                    <span style="color:var(--muted-foreground)">· {{ $derived->typeLabel() }}</span>
                </p>
                @endforeach
            </div>
            @endif
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
        {{-- O modelo de etapas só aparece enquanto não houver fase nenhuma:
             depois de o trabalho começar, criar cinco de uma vez atrapalha. --}}
        @if(\App\Support\PhaseTemplates::availableFor($project))
        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;padding:0.875rem 1rem;border:1px solid var(--border);border-radius:0.5rem;background-color:var(--surface);">
            <div>
                <p style="font-size:0.875rem;font-weight:600;">Começar pelas etapas habituais deste tipo de trabalho</p>
                <p style="font-size:0.75rem;color:var(--muted-foreground);margin-top:0.125rem;">
                    Cria {{ count(\App\Support\PhaseTemplates::for($project->type)) }} etapas em 0%. São um ponto de partida — pode editar, reordenar ou excluir qualquer uma.
                </p>
            </div>
            <form method="POST" action="{{ route('admin.projects.phases.template', $project) }}">
                @csrf
                <button type="submit" class="btn-secondary" style="white-space:nowrap;">Criar etapas</button>
            </form>
        </div>
        @endif

        <div class="flex justify-end">
            <button type="button" data-modal-open="modal-fase" class="btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Nova fase
            </button>
        </div>
        @endcan

        @forelse($phases as $phase)
        @php $phaseForm = 'editar_fase_'.$phase->id; @endphp
        <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:1.25rem;"
             x-data="{ editing: {{ old('_form') === $phaseForm ? 'true' : 'false' }} }">
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
                    <button type="button" @click="editing=!editing" class="hover-surface-bg" style="padding:0.375rem;border-radius:0.375rem;color:var(--muted-foreground);background:none;border:none;cursor:pointer;">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                    </button>
                    <form method="POST" action="{{ route('admin.projects.phases.destroy', [$project, $phase]) }}" data-confirm="Remover fase?">
                        @csrf @method('DELETE')
                        <button type="submit" class="hover-danger" style="padding:0.375rem;border-radius:0.375rem;color:var(--muted-foreground);background:none;border:none;cursor:pointer;">
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
                <input type="hidden" name="_form" value="{{ $phaseForm }}">

                <x-form-errors :form="$phaseForm" />

                <div class="grid gap-3 sm:grid-cols-2">
                    <div><label class="field-label">Nome</label><input type="text" name="name" value="{{ old('name', $phase->name) }}" required><x-field-error field="name" :form="$phaseForm" /></div>
                    <div><label class="field-label">Status</label><select name="status">@foreach(\App\Models\ProjectPhase::STATUSES as $k=>$v)<option value="{{ $k }}" {{ old('status', $phase->status)===$k?'selected':'' }}>{{ $v }}</option>@endforeach</select><x-field-error field="status" :form="$phaseForm" /></div>
                    <div><label class="field-label">%</label><input type="number" name="percentage" min="0" max="100" value="{{ old('percentage', $phase->percentage) }}"><x-field-error field="percentage" :form="$phaseForm" /></div>
                    <div><label class="field-label">Ordem</label><input type="number" name="sort_order" value="{{ old('sort_order', $phase->sort_order) }}"><x-field-error field="sort_order" :form="$phaseForm" /></div>
                </div>
                <div><label class="field-label">Descrição</label><textarea name="description" rows="2" style="resize:vertical;">{{ old('description', $phase->description) }}</textarea><x-field-error field="description" :form="$phaseForm" /></div>
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
                <input type="hidden" name="_form" value="enviar_fotos">

                <x-form-errors form="enviar_fotos" />

                <div>
                    <label class="field-label">Arquivos (JPG, PNG, WebP, MP4…)</label>
                    <input type="file" name="photos[]" multiple accept="image/*,video/*" style="padding:0.375rem;" required>
                    <x-field-error field="photos" form="enviar_fotos" />
                </div>
                <div class="grid gap-3 sm:grid-cols-3">
                    <div>
                        <label class="field-label">Fase</label>
                        <select name="phase_id">
                            <option value="">Sem fase</option>
                            @foreach($phases as $ph)<option value="{{ $ph->id }}" {{ old('phase_id')==$ph->id?'selected':'' }}>{{ $ph->name }}</option>@endforeach
                        </select>
                        <x-field-error field="phase_id" form="enviar_fotos" />
                    </div>
                    <div><label class="field-label">Legenda</label><input type="text" name="caption" value="{{ old('caption') }}"><x-field-error field="caption" form="enviar_fotos" /></div>
                    <div><label class="field-label">Data</label><input type="date" name="taken_at" value="{{ old('taken_at', now()->toDateString()) }}"><x-field-error field="taken_at" form="enviar_fotos" /></div>
                </div>
                <button type="submit" class="btn-primary">Enviar</button>
            </form>
        </div>
        @endcan
        {{-- Filtros da galeria (FOTO-04) --}}
        <form method="GET" action="{{ route('admin.projects.show', $project) }}" class="flex flex-wrap gap-2" style="margin-bottom:0.75rem;">
            <input type="hidden" name="tab" value="fotos">
            <select name="photo_phase" style="max-width:14rem;" data-auto-submit>
                <option value="">Todas as fases</option>
                @foreach($phases as $ph)
                    <option value="{{ $ph->id }}" {{ (int) request('photo_phase') === $ph->id ? 'selected' : '' }}>{{ $ph->name }}</option>
                @endforeach
            </select>
            <input type="date" name="photo_from" value="{{ request('photo_from') }}" style="max-width:11rem;" data-auto-submit title="De">
            <input type="date" name="photo_to" value="{{ request('photo_to') }}" style="max-width:11rem;" data-auto-submit title="Até">
            <select name="photo_order" style="max-width:11rem;" data-auto-submit>
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
                <img src="{{ $thumbUrl }}" alt="{{ $photo->caption }}" loading="lazy" style="cursor:pointer;"
                     data-lightbox-src="{{ $photoUrl }}" data-lightbox-caption="{{ $photo->caption ?? '' }}">
                @endif
                @if($photo->caption)<div style="position:absolute;bottom:0;left:0;right:0;background:linear-gradient(to top,rgba(0,0,0,0.8),transparent);padding:0.5rem;font-size:0.75rem;color:#fff;">{{ $photo->caption }}</div>@endif
                @can('manage-projects')
                <form method="POST" action="{{ route('admin.projects.photos.destroy', [$project, $photo]) }}" data-confirm="Remover foto?" style="position:absolute;top:4px;right:4px;">
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
        {{-- A galeria é paginada: os filtros e o separador viajam nos links. --}}
        @if($photos->hasPages())
        <div>{{ $photos->links() }}</div>
        @endif
    </div>

    {{-- TAB: Despesas --}}
    <div x-show="tab==='despesas'" x-cloak class="space-y-4">

        <x-budget-plan :project="$project" :can-edit="auth()->user()->can('access-financial')" />
        @can('access-financial')
        <div class="flex justify-end">
            <button type="button" data-despesa-nova class="btn-primary">
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
                        @forelse($expenses as $exp)
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
                                        data-despesa-editar="{{ json_encode($expData) }}"
                                        class="hover-text-primary"
                                        style="padding:0.25rem;border-radius:0.375rem;background:none;border:none;cursor:pointer;color:var(--muted-foreground)">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
                                </button>
                                <form method="POST" action="{{ route('admin.projects.expenses.destroy', [$project, $exp]) }}" data-confirm="Remover despesa?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="hover-text-danger" style="padding:0.25rem;border-radius:0.375rem;background:none;border:none;cursor:pointer;color:var(--muted-foreground)">
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
        @if($expenses->hasPages())
        <div>{{ $expenses->links() }}</div>
        @endif
    </div>

    {{-- TAB: Pagamentos --}}
    <div x-show="tab==='pagamentos'" x-cloak class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            {{-- Os totais vêm somados em SQL, do controlador: a lista abaixo é
                 paginada e somar só a página visível daria o número errado. --}}
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
            <button type="button" data-modal-open="modal-pagamento" class="btn-primary">
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
                                    <form method="POST" action="{{ route('admin.projects.payments.destroy', [$project, $pay]) }}" data-confirm="Remover cobrança?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="hover-text-danger" style="padding:0.25rem;border-radius:0.375rem;background:none;border:none;cursor:pointer;color:var(--muted-foreground)">
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
        @if($payments->hasPages())
        <div>{{ $payments->links() }}</div>
        @endif
    </div>

    {{-- TAB: Documentos --}}
    <div x-show="tab==='documentos'" x-cloak class="space-y-4">
        @can('manage-projects')
        <div class="flex justify-end">
            <button type="button" data-modal-open="modal-documento" class="btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Enviar documento
            </button>
        </div>
        @endcan
        <div class="space-y-2">
            @forelse($documents as $doc)
            <div class="flex items-center gap-3 p-3 rounded-lg" style="background-color:var(--card);border:1px solid var(--border);">
                <svg class="w-5 h-5 shrink-0" style="color:var(--primary)" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                <div class="min-w-0 flex-1">
                    <p class="font-medium truncate" style="font-size:0.875rem;">{{ $doc->name }}</p>
                    <p style="font-size:0.75rem;color:var(--muted-foreground)">{{ \App\Models\Document::TYPES[$doc->type]??$doc->type }} ·
                        <span style="color:{{ $doc->visibility==='cliente'?'var(--success)':'var(--muted-foreground)' }}">{{ $doc->visibility==='cliente'?'Visível ao cliente':'Interno' }}</span>
                        · {{ $doc->created_at->format('d/m/Y') }}
                    </p>
                </div>
                <a href="{{ route('admin.projects.documents.download', [$project, $doc]) }}" target="_blank" rel="noopener" class="hover-text-primary" style="padding:0.5rem;color:var(--muted-foreground)">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                </a>
                @can('manage-projects')
                <form method="POST" action="{{ route('admin.projects.documents.destroy', [$project, $doc]) }}" data-confirm="Remover documento?">
                    @csrf @method('DELETE')
                    <button type="submit" class="hover-text-danger" style="padding:0.5rem;background:none;border:none;cursor:pointer;color:var(--muted-foreground)">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                    </button>
                </form>
                @endcan
            </div>
            @empty
            <p style="color:var(--muted-foreground);font-size:0.875rem;">Nenhum documento enviado.</p>
            @endforelse
        </div>
        @if($documents->hasPages())
        <div>{{ $documents->links() }}</div>
        @endif
    </div>

    {{-- TAB: Plantas --}}
    @if($showPlansTab)
    <div x-show="tab==='plantas'" x-cloak class="space-y-4">

        <x-revision-counter :project="$project" />

        @if($canManagePlans)
        <div class="flex justify-end">
            <button type="button" data-modal-open="modal-planta" class="btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Nova planta
            </button>
        </div>
        @endif

        {{-- OBRA-02: as plantas aprovadas do projecto de origem.
             Não são plantas desta obra e a interface não finge que são: nada
             aqui se edita, envia ou apaga. A planta continua a ser do projecto
             que a desenhou — corrigida lá, muda aqui no pedido seguinte, porque
             não há cópia nenhuma pelo meio. Só entram versões aprovadas pelo
             cliente: um desenho por aprovar não é o que se leva para a obra. --}}
        @if($inheritedPlans->isNotEmpty())
        <div style="background-color:var(--card);border:1px solid var(--border);border-left:3px solid oklch(0.6 0.18 220);border-radius:0.75rem;padding:1.25rem;">
            <div class="flex flex-wrap items-baseline justify-between gap-2" style="margin-bottom:0.25rem;">
                <h4 style="font-weight:600;">Plantas do projeto de origem</h4>
                <a href="{{ route('admin.projects.show', $project->sourceProject) }}" style="font-size:0.8125rem;color:var(--primary);">{{ $project->sourceProject->name }} ↗</a>
            </div>
            <p style="font-size:0.8125rem;color:var(--muted-foreground);margin-bottom:1rem;">
                Versões aprovadas pelo cliente no projeto de origem. Não são plantas dest{{ $project->isSiteWork() ? 'a obra' : 'e projeto' }}: vivem lá e são gerenciadas lá.
            </p>
            <div class="space-y-2">
                @foreach($inheritedPlans as $inherited)
                @php $approvedVersion = $inherited->versions->first(); @endphp
                <div class="flex flex-wrap items-center gap-3" style="font-size:0.8125rem;padding:0.625rem 0.75rem;border-radius:0.5rem;background-color:var(--surface);">
                    <span style="font-weight:600;">{{ $inherited->name }}</span>
                    <span style="font-size:0.6875rem;font-weight:500;padding:0.125rem 0.5rem;border-radius:9999px;background-color:var(--card);color:var(--muted-foreground);border:1px solid var(--border);">{{ $inherited->disciplineLabel() }}</span>
                    <span class="badge badge-concluida">Aprovada {{ $approvedVersion->label() }}</span>
                    <span style="color:var(--muted-foreground)" class="min-w-0 truncate">
                        {{ $approvedVersion->isExternal() ? 'Endereço externo' : ($approvedVersion->original_name ?: strtoupper($approvedVersion->format)) }}
                        @if($approvedVersion->humanSize()) · {{ $approvedVersion->humanSize() }} @endif
                    </span>
                    <span style="color:var(--muted-foreground)">aprovada a {{ $approvedVersion->approved_at->format('d/m/Y') }}</span>
                    <span class="ml-auto">
                        @if($approvedVersion->isExternal())
                            <a href="{{ $approvedVersion->external_url }}" target="_blank" rel="noopener noreferrer" style="color:var(--primary);">Abrir endereço ↗</a>
                        @else
                            <a href="{{ route('admin.projects.plans.versions.download', [$project->sourceProject, $inherited, $approvedVersion]) }}" style="color:var(--primary);">Baixar</a>
                        @endif
                    </span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @forelse($project->plans as $plan)
        @php
            $current = $plan->versions->first();
            $badge = ['em_elaboracao' => 'planejada', 'enviada' => 'aguardando_aprovacao', 'aprovada' => 'concluida', 'substituida' => 'pausada'][$plan->status] ?? 'planejada';
        @endphp
        @php $planForm = 'editar_planta_'.$plan->id; @endphp
        <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:1.25rem;"
             x-data="{ editing: {{ old('_form') === $planForm ? 'true' : 'false' }}, history: false }">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h4 style="font-weight:600;">{{ $plan->name }}</h4>
                        <span class="badge badge-{{ $badge }}">{{ $plan->statusLabel() }}</span>
                        <span style="font-size:0.6875rem;font-weight:500;padding:0.125rem 0.5rem;border-radius:9999px;background-color:var(--surface);color:var(--muted-foreground);border:1px solid var(--border);">{{ $plan->disciplineLabel() }}</span>
                    </div>
                    <p style="font-size:0.8125rem;color:var(--muted-foreground);margin-top:0.25rem;">
                        @if($current)
                            Versão atual {{ $current->label() }} ·
                            @if($current->isExternal()) endereço externo @else {{ strtoupper($current->format) }}{{ $current->humanSize() ? ' · '.$current->humanSize() : '' }} @endif
                            · {{ $current->created_at->format('d/m/Y') }}
                            @if($current->wasSentToClient()) · enviada ao cliente a {{ $current->sent_to_client_at->format('d/m/Y') }} @endif
                        @else
                            Sem versões. Envie a primeira.
                        @endif
                    </p>
                    @if($plan->notes)<p style="font-size:0.8125rem;color:var(--muted-foreground);margin-top:0.25rem;white-space:pre-wrap;">{{ $plan->notes }}</p>@endif
                </div>
                <div class="flex flex-wrap gap-2 shrink-0">
                    @if($plan->versions->count() > 1)
                    <button type="button" @click="history=!history" class="btn-secondary" style="font-size:0.8125rem;">
                        <span x-text="history ? 'Ocultar histórico' : 'Histórico ({{ $plan->versions->count() }} versões)'"></span>
                    </button>
                    @endif
                    @if($canManagePlans)
                    @if($current && ! $current->isSuperseded())
                    <form method="POST" action="{{ route('admin.projects.plans.versions.send', [$project, $plan, $current]) }}">
                        @csrf
                        <button type="submit" class="btn-secondary" style="font-size:0.8125rem;">{{ $current->wasSentToClient() ? 'Reenviar ao cliente' : 'Enviar ao cliente' }}</button>
                    </form>
                    @endif
                    <button type="button" class="btn-primary" style="font-size:0.8125rem;"
                            data-versao-planta="{{ $plan->id }}"
                            data-versao-nome="{{ $plan->name }}"
                            data-versao-numero="{{ $plan->versions->count() + 1 }}">
                        Nova versão
                    </button>
                    <button type="button" @click="editing=!editing" style="padding:0.375rem;border-radius:0.375rem;color:var(--muted-foreground);background:none;border:none;cursor:pointer;" title="Editar planta">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                    </button>
                    <form method="POST" action="{{ route('admin.projects.plans.destroy', [$project, $plan]) }}" data-confirm="Remover a planta &quot;{{ $plan->name }}&quot;? O histórico de versões é mantido.">
                        @csrf @method('DELETE')
                        <button type="submit" class="hover-text-danger" style="padding:0.375rem;border-radius:0.375rem;background:none;border:none;cursor:pointer;color:var(--muted-foreground)" title="Remover planta">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            {{-- PLAN-04: o histórico completo. A versão corrente está sempre
                 visível; as substituídas abrem a pedido para a lista não crescer
                 sem fim, mas continuam todas descarregáveis. --}}
            <div class="mt-4 space-y-2" style="border-top:1px solid var(--border);padding-top:1rem;">
                @foreach($plan->versions as $i => $version)
                <div x-show="{{ $i === 0 ? 'true' : 'history' }}" @if($i > 0) x-cloak @endif
                     class="flex flex-wrap items-center gap-3" style="font-size:0.8125rem;padding:0.5rem 0.75rem;border-radius:0.5rem;background-color:var(--surface);">
                    <span style="font-weight:600;min-width:2.5rem;">{{ $version->label() }}</span>
                    @if($version->isSuperseded())
                        <span class="badge badge-pausada">Substituída</span>
                    @else
                        <span class="badge badge-em_andamento">Atual</span>
                    @endif
                    <span style="color:var(--muted-foreground)" class="min-w-0 truncate">
                        {{ $version->isExternal() ? 'Endereço externo' : ($version->original_name ?: strtoupper($version->format)) }}
                        @if($version->humanSize()) · {{ $version->humanSize() }} @endif
                    </span>
                    <span style="color:var(--muted-foreground)">{{ $version->created_at->format('d/m/Y H:i') }}</span>
                    @if($version->uploader)<span style="color:var(--muted-foreground)">{{ $version->uploader->name }}</span>@endif
                    @if($version->change_note)<span style="color:var(--muted-foreground)" class="w-full">{{ $version->change_note }}</span>@endif
                    <span class="ml-auto">
                        @if($version->isExternal())
                            <a href="{{ $version->external_url }}" target="_blank" rel="noopener noreferrer" style="color:var(--primary);">Abrir endereço ↗</a>
                        @else
                            <a href="{{ route('admin.projects.plans.versions.download', [$project, $plan, $version]) }}" style="color:var(--primary);">Baixar</a>
                        @endif
                    </span>

                    @if($version->isViewable3d())
                        <x-model-viewer :src="route('admin.projects.plans.versions.download', [$project, $plan, $version])"
                                        :label="$plan->name.' — '.$version->label()" />
                    @endif

                    <x-plan-comments :version="$version"
                                     :action="route('admin.projects.plans.versions.comments.store', [$project, $plan, $version])"
                                     :form="'comentario_versao_'.$version->id" />
                </div>
                @endforeach
            </div>

            @if($canManagePlans)
            <form x-show="editing" x-cloak method="POST" action="{{ route('admin.projects.plans.update', [$project, $plan]) }}" class="mt-4 space-y-3 pt-4" style="border-top:1px solid var(--border)">
                @csrf @method('PUT')
                <input type="hidden" name="_form" value="{{ $planForm }}">

                <x-form-errors :form="$planForm" />

                <div class="grid gap-3 sm:grid-cols-2">
                    <div><label class="field-label">Nome</label><input type="text" name="name" value="{{ old('name', $plan->name) }}" maxlength="200" required><x-field-error field="name" :form="$planForm" /></div>
                    <div><label class="field-label">Disciplina</label><select name="discipline">@foreach(\App\Models\Plan::DISCIPLINES as $k=>$v)<option value="{{ $k }}" {{ old('discipline', $plan->discipline)===$k?'selected':'' }}>{{ $v }}</option>@endforeach</select><x-field-error field="discipline" :form="$planForm" /></div>
                </div>
                <div><label class="field-label">Notas</label><textarea name="notes" rows="2" style="resize:vertical;">{{ old('notes', $plan->notes) }}</textarea><x-field-error field="notes" :form="$planForm" /></div>
                <div class="flex gap-2"><button type="submit" class="btn-primary">Salvar</button><button type="button" @click="editing=false" class="btn-secondary">Cancelar</button></div>
            </form>
            @endif
        </div>
        @empty
        @if($inheritedPlans->isEmpty())
        <p style="color:var(--muted-foreground);font-size:0.875rem;">Nenhuma planta cadastrada.</p>
        @else
        <p style="color:var(--muted-foreground);font-size:0.875rem;">Est{{ $project->isSiteWork() ? 'a obra' : 'e projeto' }} não tem plantas próprias.</p>
        @endif
        @endforelse
    </div>
    @endif

    {{-- TAB: Histórico --}}
    <div x-show="tab==='historico'" x-cloak class="space-y-4">
        @can('manage-projects')
        <div class="flex justify-end">
            <button type="button" data-modal-open="modal-historico" class="btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Nova atualização
            </button>
        </div>
        @endcan
        <div class="space-y-2">
            @forelse($updates as $upd)
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
        @if($updates->hasPages())
        <div>{{ $updates->links() }}</div>
        @endif
    </div>

    {{-- TAB: Mensagens --}}
    <div x-show="tab==='mensagens'" x-cloak>
        <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;display:flex;flex-direction:column;height:min(70vh,520px);">
            <div style="padding:1rem;border-bottom:1px solid var(--border);flex-shrink:0;">
                <p style="font-weight:700;">{{ $project->name }}</p>
                <p style="font-size:0.75rem;color:var(--muted-foreground)">Conversa em tempo real</p>
            </div>
            <div id="msg-list" style="flex:1;overflow-y:auto;padding:1rem;display:flex;flex-direction:column;gap:0.75rem;">
                {{-- A conversa carrega as mensagens mais recentes; as anteriores
                     ficam a um clique, na «página seguinte» deste paginador. --}}
                @if($messages->hasMorePages())
                <div style="text-align:center;">
                    <a href="{{ $messages->nextPageUrl() }}" style="font-size:0.8125rem;color:var(--primary);">Ver mensagens anteriores</a>
                </div>
                @endif
                @if($messages->currentPage() > 1)
                <div style="text-align:center;">
                    <a href="{{ $messages->previousPageUrl() }}" style="font-size:0.8125rem;color:var(--muted-foreground);">Voltar às mensagens recentes</a>
                </div>
                @endif
                @forelse($messages as $msg)
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
        <script @cspNonce>
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

<script @cspNonce>
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
// Abrir, fechar, clicar fora e Escape vivem em resources/js/app.js para
// qualquer `[data-modal-overlay]`; esta página chama por `window.obra360`
// apenas onde é código a decidir: editar uma despesa, começar uma versão nova,
// reabrir o modal cujo formulário falhou a validação.
</script>

@can('manage-projects')
{{-- Modal: Nova Fase --}}
<div id="modal-fase" class="modal-overlay" data-modal-overlay style="display:none;">
  <div class="modal-box">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
      <h2 class="modal-title" style="margin:0;">Nova fase</h2>
      <button type="button" data-modal-close="modal-fase" class="hover-surface-bg" style="padding:0.375rem;border-radius:0.375rem;border:none;background:none;cursor:pointer;color:var(--muted-foreground);"><svg style="width:1.25rem;height:1.25rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('admin.projects.phases.store', $project) }}" class="space-y-4">
      @csrf
      <input type="hidden" name="_form" value="nova_fase">

      <x-form-errors form="nova_fase" />

      <div class="grid gap-3 sm:grid-cols-2">
        <div><label class="field-label">Nome da fase <span style="color:var(--destructive)">*</span></label><input type="text" name="name" value="{{ old('name') }}" required placeholder="Ex: Fundação, Estrutura, Revestimento..."><x-field-error field="name" form="nova_fase" /></div>
        <div><label class="field-label">Status da fase</label><select name="status">@foreach(\App\Models\ProjectPhase::STATUSES as $k=>$v)<option value="{{ $k }}" {{ old('status')===$k?'selected':'' }}>{{ $v }}</option>@endforeach</select><x-field-error field="status" form="nova_fase" /></div>
        <div>
          <label class="field-label">% Concluída <span class="field-tip" data-tip="Porcentagem de conclusão desta fase. 0 = não iniciada, 100 = concluída.">?</span></label>
          <input type="number" name="percentage" min="0" max="100" value="{{ old('percentage', 0) }}" placeholder="0">
          <span style="display:block;font-size:0.7rem;color:var(--muted-foreground);margin-top:0.25rem;">0 = não iniciada &nbsp;·&nbsp; 100 = concluída</span>
          <x-field-error field="percentage" form="nova_fase" />
        </div>
        <div>
          <label class="field-label">Ordem na lista <span class="field-tip" data-tip="Posição da fase na lista. Ex: 1 = primeira fase, 2 = segunda fase.">?</span></label>
          <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $phases->count()) }}">
          <span style="display:block;font-size:0.7rem;color:var(--muted-foreground);margin-top:0.25rem;">Deixe em 0 para adicionar ao fim da lista</span>
          <x-field-error field="sort_order" form="nova_fase" />
        </div>
        <div><label class="field-label">Data de início</label><input type="date" name="start_date" value="{{ old('start_date') }}"><x-field-error field="start_date" form="nova_fase" /></div>
        <div><label class="field-label">Previsão de término</label><input type="date" name="expected_end_date" value="{{ old('expected_end_date') }}"><x-field-error field="expected_end_date" form="nova_fase" /></div>
      </div>
      <div><label class="field-label">Descrição da fase</label><textarea name="description" rows="2" style="resize:vertical;" placeholder="Descreva o que será feito nesta fase...">{{ old('description') }}</textarea><x-field-error field="description" form="nova_fase" /></div>
      <div style="display:flex;gap:0.5rem;"><button type="submit" class="btn-primary">Adicionar fase</button><button type="button" data-modal-close="modal-fase" class="btn-secondary">Cancelar</button></div>
    </form>
  </div>
</div>
@endcan

@can('access-financial')
{{-- Modal: Registrar Despesa --}}
<div id="modal-despesa" class="modal-overlay" data-modal-overlay style="display:none;">
  <div class="modal-box" style="max-width:40rem;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
      <h2 class="modal-title" id="titulo-despesa" style="margin:0;">Registrar despesa</h2>
      <button type="button" data-modal-close="modal-despesa" class="hover-surface-bg" style="padding:0.375rem;border-radius:0.375rem;border:none;background:none;cursor:pointer;color:var(--muted-foreground);"><svg style="width:1.25rem;height:1.25rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></button>
    </div>
    <form id="form-despesa" method="POST" action="{{ route('admin.projects.expenses.store', $project) }}" enctype="multipart/form-data" class="space-y-4">
      @csrf
      <input type="hidden" name="_method" id="despesa-method" disabled value="PUT">
      {{-- O modal serve criar e editar: o valor do marcador é posto em JS, e é
           ele que na volta diz qual dos dois reabrir e com que despesa. --}}
      <input type="hidden" name="_form" id="despesa-form" value="despesa_nova">

      <x-form-errors :form="$expenseForm" />

      <div class="grid gap-3 sm:grid-cols-2">
        <div><label class="field-label">Categoria</label><select name="category">@foreach(\App\Models\Expense::CATEGORIES as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select><x-field-error field="category" :form="$expenseForm" /></div>
        <div><label class="field-label">Data</label><input type="date" name="spent_at" value="{{ now()->toDateString() }}" required><x-field-error field="spent_at" :form="$expenseForm" /></div>
        <div class="sm:col-span-2"><label class="field-label">Descrição <span style="color:var(--destructive)">*</span></label><input type="text" name="description" required><x-field-error field="description" :form="$expenseForm" /></div>
        <div><label class="field-label">Valor ({{ currency_symbol() }})</label><input type="number" name="amount" step="0.01" min="0" required><x-field-error field="amount" :form="$expenseForm" /></div>
        <div><label class="field-label">Fornecedor</label><input type="text" name="supplier"><x-field-error field="supplier" :form="$expenseForm" /></div>
        <div><label class="field-label">Comprovante</label><input type="file" name="receipt" style="padding:0.375rem;"><x-field-error field="receipt" :form="$expenseForm" /></div>
        <div><label class="field-label">Notas</label><input type="text" name="notes"><x-field-error field="notes" :form="$expenseForm" /></div>
      </div>
      <div style="display:flex;gap:0.5rem;"><button type="submit" class="btn-primary" id="despesa-submit">Registrar despesa</button><button type="button" data-modal-close="modal-despesa" class="btn-secondary">Cancelar</button></div>
    </form>
  </div>
</div>
@endcan

@can('manage-projects')
{{-- Modal: Enviar Documento --}}
<div id="modal-documento" class="modal-overlay" data-modal-overlay style="display:none;">
  <div class="modal-box">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
      <h2 class="modal-title" style="margin:0;">Enviar documento</h2>
      <button type="button" data-modal-close="modal-documento" class="hover-surface-bg" style="padding:0.375rem;border-radius:0.375rem;border:none;background:none;cursor:pointer;color:var(--muted-foreground);"><svg style="width:1.25rem;height:1.25rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('admin.projects.documents.store', $project) }}" enctype="multipart/form-data" class="space-y-4">
      @csrf
      <input type="hidden" name="_form" value="novo_documento">

      <x-form-errors form="novo_documento" />

      <div><label class="field-label">Arquivo <span style="color:var(--destructive)">*</span></label><input type="file" name="file" required style="padding:0.375rem;" accept=".pdf,.docx,.xlsx,.xls,.doc,.jpg,.jpeg,.png,.webp"><x-field-error field="file" form="novo_documento" /></div>
      <div class="grid gap-3 sm:grid-cols-3">
        <div class="sm:col-span-2"><label class="field-label">Nome do documento <span style="color:var(--destructive)">*</span></label><input type="text" name="name" value="{{ old('name') }}" required><x-field-error field="name" form="novo_documento" /></div>
        <div><label class="field-label">Tipo</label><select name="type">@foreach(\App\Models\Document::TYPES as $k=>$v)<option value="{{ $k }}" {{ old('type')===$k?'selected':'' }}>{{ $v }}</option>@endforeach</select><x-field-error field="type" form="novo_documento" /></div>
      </div>
      <div class="grid gap-3 sm:grid-cols-2">
        <div><label class="field-label">Visibilidade</label><select name="visibility"><option value="interno" {{ old('visibility')==='interno'?'selected':'' }}>Interno (só equipe)</option><option value="cliente" {{ old('visibility')==='cliente'?'selected':'' }}>Cliente (visível no portal)</option></select><x-field-error field="visibility" form="novo_documento" /></div>
        <div><label class="field-label">Notas</label><input type="text" name="notes" value="{{ old('notes') }}"><x-field-error field="notes" form="novo_documento" /></div>
      </div>
      <div style="display:flex;gap:0.5rem;"><button type="submit" class="btn-primary">Enviar documento</button><button type="button" data-modal-close="modal-documento" class="btn-secondary">Cancelar</button></div>
    </form>
  </div>
</div>
@endcan

@can('access-financial')
{{-- Modal: Nova Cobrança --}}
<div id="modal-pagamento" class="modal-overlay" data-modal-overlay style="display:none;">
  <div class="modal-box" style="max-width:40rem;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
      <h2 class="modal-title" style="margin:0;">Nova cobrança</h2>
      <button type="button" data-modal-close="modal-pagamento" class="hover-surface-bg" style="padding:0.375rem;border-radius:0.375rem;border:none;background:none;cursor:pointer;color:var(--muted-foreground);"><svg style="width:1.25rem;height:1.25rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('admin.projects.payments.store', $project) }}" class="space-y-4" x-data="{ type: @js(old('type', 'pix')) }">
      @csrf
      <input type="hidden" name="_form" value="nova_cobranca">

      <x-form-errors form="nova_cobranca" />

      <div class="grid gap-3 sm:grid-cols-2">
        <div class="sm:col-span-2"><label class="field-label">Título <span style="color:var(--destructive)">*</span></label><input type="text" name="title" value="{{ old('title') }}" required placeholder="Ex: Parcela 1, Sinal d{{ $project->nounWithArticle() }}…"><x-field-error field="title" form="nova_cobranca" /></div>
        <div><label class="field-label">Valor ({{ currency_symbol() }}) <span style="color:var(--destructive)">*</span></label><input type="number" name="amount" step="0.01" min="0.01" value="{{ old('amount') }}" required><x-field-error field="amount" form="nova_cobranca" /></div>
        <div><label class="field-label">Vencimento</label><input type="date" name="due_date" value="{{ old('due_date') }}"><x-field-error field="due_date" form="nova_cobranca" /></div>
        <div>
          <label class="field-label">Tipo</label>
          <select name="type" x-model="type">
            @foreach(\App\Models\Payment::TYPES as $k=>$v)<option value="{{ $k }}" {{ old('type')===$k?'selected':'' }}>{{ $v }}</option>@endforeach
          </select>
          <x-field-error field="type" form="nova_cobranca" />
        </div>
        <div>
          <label class="field-label">Status</label>
          <select name="status">
            @foreach(\App\Models\Payment::STATUSES as $k=>$v)<option value="{{ $k }}" {{ old('status')===$k?'selected':'' }}>{{ $v }}</option>@endforeach
          </select>
          <x-field-error field="status" form="nova_cobranca" />
        </div>
        <div x-show="type==='boleto'" class="sm:col-span-2"><label class="field-label">Linha digitável (boleto)</label><input type="text" name="barcode" value="{{ old('barcode') }}" placeholder="000000000 …" maxlength="60"><x-field-error field="barcode" form="nova_cobranca" /></div>
        <div x-show="type==='pix'" class="sm:col-span-2"><label class="field-label">Chave PIX</label><input type="text" name="pix_key" value="{{ old('pix_key') }}" placeholder="email, CPF, telefone ou chave aleatória" maxlength="100"><x-field-error field="pix_key" form="nova_cobranca" /></div>
        <div x-show="type==='pix'" class="sm:col-span-2"><label class="field-label">Código copia-e-cola / QR</label><textarea name="pix_qrcode" rows="2" style="resize:vertical;" placeholder="00020126…">{{ old('pix_qrcode') }}</textarea><x-field-error field="pix_qrcode" form="nova_cobranca" /></div>
        <div class="sm:col-span-2"><label class="field-label">Descrição / observações</label><textarea name="description" rows="2" style="resize:vertical;">{{ old('description') }}</textarea><x-field-error field="description" form="nova_cobranca" /></div>
      </div>
      <div style="display:flex;gap:0.5rem;"><button type="submit" class="btn-primary">Criar cobrança</button><button type="button" data-modal-close="modal-pagamento" class="btn-secondary">Cancelar</button></div>
    </form>
  </div>
</div>
@endcan

@can('manage-projects')
{{-- Modal: Nova Atualização --}}
<div id="modal-historico" class="modal-overlay" data-modal-overlay style="display:none;">
  <div class="modal-box">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
      <h2 class="modal-title" style="margin:0;">Nova atualização</h2>
      <button type="button" data-modal-close="modal-historico" class="hover-surface-bg" style="padding:0.375rem;border-radius:0.375rem;border:none;background:none;cursor:pointer;color:var(--muted-foreground);"><svg style="width:1.25rem;height:1.25rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('admin.projects.updates.store', $project) }}" class="space-y-4">
      @csrf
      <input type="hidden" name="_form" value="nova_atualizacao">

      <x-form-errors form="nova_atualizacao" />

      <div><label class="field-label">Título <span style="color:var(--destructive)">*</span></label><input type="text" name="title" value="{{ old('title') }}" required><x-field-error field="title" form="nova_atualizacao" /></div>
      <div><label class="field-label">Descrição</label><textarea name="description" rows="3" style="resize:vertical;">{{ old('description') }}</textarea><x-field-error field="description" form="nova_atualizacao" /></div>
      <div style="display:flex;gap:0.5rem;"><button type="submit" class="btn-primary">Registrar</button><button type="button" data-modal-close="modal-historico" class="btn-secondary">Cancelar</button></div>
    </form>
  </div>
</div>
@endcan

@if($showPlansTab)
@can('manage-projects')
{{-- Modal: Nova Planta --}}
<div id="modal-planta" class="modal-overlay" data-modal-overlay style="display:none;">
  <div class="modal-box">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
      <h2 class="modal-title" style="margin:0;">Nova planta</h2>
      <button type="button" data-modal-close="modal-planta" style="padding:0.375rem;border-radius:0.375rem;border:none;background:none;cursor:pointer;color:var(--muted-foreground);"><svg style="width:1.25rem;height:1.25rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('admin.projects.plans.store', $project) }}" class="space-y-4">
      @csrf
      <input type="hidden" name="_form" value="nova_planta">

      <x-form-errors form="nova_planta" />

      <div>
        <label class="field-label">Nome da planta <span style="color:var(--destructive)">*</span></label>
        <input type="text" name="name" value="{{ old('name') }}" required maxlength="200" placeholder="Ex: Planta baixa — piso 0">
        <span style="display:block;font-size:0.7rem;color:var(--muted-foreground);margin-top:0.25rem;">O desenho, não o arquivo. As revisões entram depois como versões desta mesma planta.</span>
        <x-field-error field="name" form="nova_planta" />
      </div>
      <div><label class="field-label">Disciplina</label><select name="discipline">@foreach(\App\Models\Plan::DISCIPLINES as $k=>$v)<option value="{{ $k }}" {{ old('discipline')===$k?'selected':'' }}>{{ $v }}</option>@endforeach</select><x-field-error field="discipline" form="nova_planta" /></div>
      <div><label class="field-label">Notas</label><textarea name="notes" rows="2" style="resize:vertical;" maxlength="1000">{{ old('notes') }}</textarea><x-field-error field="notes" form="nova_planta" /></div>
      <div style="display:flex;gap:0.5rem;"><button type="submit" class="btn-primary">Criar planta</button><button type="button" data-modal-close="modal-planta" class="btn-secondary">Cancelar</button></div>
    </form>
  </div>
</div>

{{-- Modal: Nova Versão (o mesmo modal serve todas as plantas; o destino é
     definido em JS, como no modal das despesas) --}}
<div id="modal-versao" class="modal-overlay" data-modal-overlay style="display:none;">
  <div class="modal-box">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
      <h2 class="modal-title" style="margin:0;">Nova versão <span id="versao-numero" style="color:var(--muted-foreground)"></span></h2>
      <button type="button" data-modal-close="modal-versao" style="padding:0.375rem;border-radius:0.375rem;border:none;background:none;cursor:pointer;color:var(--muted-foreground);"><svg style="width:1.25rem;height:1.25rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></button>
    </div>
    <p style="font-size:0.8125rem;color:var(--muted-foreground);margin-bottom:1rem;" id="versao-planta"></p>
    <form id="form-versao" method="POST" enctype="multipart/form-data" class="space-y-4">
      @csrf
      {{-- Como no modal das despesas, o destino é escolhido em JS: o marcador
           leva o id da planta para a volta saber qual reabrir. --}}
      <input type="hidden" name="_form" id="versao-form" value="nova_versao">

      <x-form-errors :form="$versionForm" />

      <div>
        <label class="field-label">Arquivo</label>
        <input type="file" name="file" style="padding:0.375rem;" accept=".pdf,.jpg,.jpeg,.png,.webp,.dwg,.dxf,.skp,.rvt,.ifc,.glb,.gltf,.zip">
        {{-- LIM-01: o limite é dito antes de alguém gastar dez minutos a enviar
             um Revit de 120 MB para ver o envio falhar no fim. --}}
        <span style="display:block;font-size:0.7rem;color:var(--muted-foreground);margin-top:0.375rem;">
          Formatos aceitos: {{ strtoupper(implode(', ', \App\Models\PlanVersion::FORMATS)) }}.
        </span>
        <span style="display:block;font-size:0.7rem;color:var(--muted-foreground);margin-top:0.25rem;">
          <strong>Limite deste servidor: {{ \App\Models\PlanVersion::maxUploadLabel() }} por arquivo.</strong>
          Um desenho maior do que isto não chega a ser recebido — nesse caso deixe o arquivo em branco e use o endereço externo abaixo.
        </span>
        <x-field-error field="file" :form="$versionForm" />
      </div>
      <div>
        <label class="field-label">Endereço externo (em alternativa ao arquivo)</label>
        <input type="url" name="external_url" value="{{ old('external_url') }}" maxlength="2048" placeholder="https://drive.google.com/…">
        <span style="display:block;font-size:0.7rem;color:var(--muted-foreground);margin-top:0.25rem;">Google Drive, WeTransfer, OneDrive. A versão fica registrada do mesmo jeito, com o histórico completo — só o arquivo é que vive fora daqui.</span>
        <x-field-error field="external_url" :form="$versionForm" />
      </div>
      <div><label class="field-label">Nota de alterações</label><textarea name="change_note" rows="2" style="resize:vertical;" maxlength="1000" placeholder="O que mudou em relação à versão anterior">{{ old('change_note') }}</textarea><x-field-error field="change_note" :form="$versionForm" /></div>
      <div style="display:flex;gap:0.5rem;"><button type="submit" class="btn-primary">Enviar versão</button><button type="button" data-modal-close="modal-versao" class="btn-secondary">Cancelar</button></div>
    </form>
  </div>
</div>

<script @cspNonce>
@php $versaoUrlTemplate = route('admin.projects.plans.versions.store', [$project, '__ID__']); @endphp
const versaoStoreUrl = id => @json($versaoUrlTemplate).replace('__ID__', id);

function novaVersao(planId, planName, nextVersion) {
    const form = document.getElementById('form-versao');
    form.reset();
    form.action = versaoStoreUrl(planId);
    document.getElementById('versao-form').value = 'nova_versao_' + planId;
    document.getElementById('versao-planta').textContent = planName;
    document.getElementById('versao-numero').textContent = 'v' + nextVersion;
    window.obra360.openModal('modal-versao');
}

// O botão "Nova versão" de cada planta traz os três valores em atributos; era
// uma chamada a novaVersao() num handler de clique construído em Blade.
document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-versao-planta]');
    if (!btn) return;
    novaVersao(Number(btn.dataset.versaoPlanta), btn.dataset.versaoNome, Number(btn.dataset.versaoNumero));
});
</script>
@endcan
@endif

{{-- Lightbox --}}
<div id="lightbox-overlay"
     style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.92);align-items:center;justify-content:center;flex-direction:column;gap:1rem;">
    <button type="button" data-lightbox-close class="hover-opaque"
            style="position:absolute;top:1rem;right:1rem;background:none;border:none;color:white;cursor:pointer;padding:0.5rem;border-radius:0.5rem;opacity:0.7;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:1.5rem;height:1.5rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
    {{-- Sem `event.stopPropagation()`: o fecho só reage ao próprio fundo e ao
         botão de fechar, portanto carregar na imagem já não fecha nada. --}}
    <img id="lightbox-img" src="" alt="" style="max-width:90vw;max-height:85vh;object-fit:contain;border-radius:0.5rem;box-shadow:0 25px 60px rgba(0,0,0,0.6);">
    <p id="lightbox-caption" style="color:rgba(255,255,255,0.7);font-size:0.875rem;text-align:center;max-width:60ch;"></p>
</div>

{{-- O lightbox (abrir, fechar, Escape) vive em resources/js/app.js: era o
     mesmo código aqui e em client/projects/show.blade.php. A miniatura declara
     `data-lightbox-src` e `data-lightbox-caption`. --}}
<script @cspNonce>
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

function preencherDespesa(despesa) {
    const form = document.getElementById('form-despesa');
    form.category.value = despesa.category ?? '';
    form.spent_at.value = despesa.spent_at ?? '';
    form.description.value = despesa.description ?? '';
    form.amount.value = despesa.amount ?? '';
    form.supplier.value = despesa.supplier ?? '';
    form.notes.value = despesa.notes ?? '';
    form.receipt.value = '';
}

function editarDespesa(despesa) {
    const form = document.getElementById('form-despesa');
    form.action = despesaUpdateUrl(despesa.id);
    document.getElementById('despesa-method').disabled = false;
    document.getElementById('despesa-form').value = 'despesa_editar_' + despesa.id;
    document.getElementById('despesa-submit').textContent = 'Salvar alterações';
    document.getElementById('titulo-despesa').textContent = 'Editar despesa';

    preencherDespesa(despesa);

    window.obra360.openModal('modal-despesa');
}

function novaDespesa() {
    const form = document.getElementById('form-despesa');
    form.reset();
    form.action = despesaStoreUrl;
    document.getElementById('despesa-method').disabled = true;
    document.getElementById('despesa-form').value = 'despesa_nova';
    document.getElementById('despesa-submit').textContent = 'Registrar despesa';
    document.getElementById('titulo-despesa').textContent = 'Registrar despesa';
    form.spent_at.value = @json(now()->toDateString());

    window.obra360.openModal('modal-despesa');
}

// Os botões da tabela de despesas. O de editar traz a linha inteira em JSON no
// atributo, tal como trazia no `onclick` que aqui estava.
document.addEventListener('click', function (e) {
    if (e.target.closest('[data-despesa-nova]')) {
        novaDespesa();
        return;
    }
    const editar = e.target.closest('[data-despesa-editar]');
    if (editar) editarDespesa(JSON.parse(editar.dataset.despesaEditar));
});
</script>

{{-- ─────────────────────────────────────────────────────────────────────────
     Um erro de validação de um formulário que vive num modal chegava aqui como
     uma página recarregada e um modal fechado: a mensagem existia, mas ficava
     por detrás de um botão que ninguém voltava a carregar. O marcador `_form`
     que veio no `old()` diz qual era o modal — e o modal reabre já com o
     resumo, os erros por campo e o que a pessoa tinha escrito.
     ───────────────────────────────────────────────────────────────────────── --}}
@php
    $modaisPorMarcador = [
        'nova_fase' => 'modal-fase',
        'novo_documento' => 'modal-documento',
        'nova_cobranca' => 'modal-pagamento',
        'nova_atualizacao' => 'modal-historico',
        'nova_planta' => 'modal-planta',
    ];

    $despesaAntiga = [
        'category' => old('category'),
        'spent_at' => old('spent_at'),
        'description' => old('description'),
        'amount' => old('amount'),
        'supplier' => old('supplier'),
        'notes' => old('notes'),
    ];

    $despesaEditada = $submittedForm !== null && str_starts_with($submittedForm, 'despesa_editar_')
        ? (int) substr($submittedForm, strlen('despesa_editar_'))
        : null;

    $plantaDaVersao = $submittedForm !== null && str_starts_with($submittedForm, 'nova_versao_')
        ? $project->plans->firstWhere('id', (int) substr($submittedForm, strlen('nova_versao_')))
        : null;
@endphp
@if($errors->any() && $submittedForm !== null)
<script @cspNonce>
document.addEventListener('DOMContentLoaded', function () {
@if(isset($modaisPorMarcador[$submittedForm]))
    window.obra360.openModal(@json($modaisPorMarcador[$submittedForm]));
@elseif($submittedForm === 'despesa_nova')
    if (typeof novaDespesa === 'function') {
        novaDespesa();
        preencherDespesa(@json($despesaAntiga));
    }
@elseif($despesaEditada)
    if (typeof editarDespesa === 'function') {
        editarDespesa(@json(['id' => $despesaEditada] + $despesaAntiga));
    }
@elseif($plantaDaVersao)
    if (typeof novaVersao === 'function') {
        novaVersao({{ $plantaDaVersao->id }}, @json($plantaDaVersao->name), {{ $plantaDaVersao->versions->count() + 1 }});
        var versao = document.getElementById('form-versao');
        versao.external_url.value = @json(old('external_url', ''));
        versao.change_note.value = @json(old('change_note', ''));
    }
@endif
});
</script>
@endif
</x-admin-layout>
