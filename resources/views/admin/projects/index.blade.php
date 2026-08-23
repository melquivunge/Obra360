<x-admin-layout>
<div class="space-y-6 p-6 md:p-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 style="font-size:1.5rem;font-weight:700;">Obras</h1>
            <p style="color:var(--muted-foreground);font-size:0.875rem;margin-top:0.25rem;">Cadastre e acompanhe todas as obras.</p>
        </div>
        @can('manage-projects')
        <button type="button" onclick="openModal('modal-nova-obra')" class="btn-primary">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Nova obra
        </button>
        @endcan
    </div>

    @if(session('success'))
    <div style="background-color:oklch(0.6 0.18 145 / 0.12);border:1px solid oklch(0.6 0.18 145 / 0.4);color:oklch(0.7 0.15 145);border-radius:0.5rem;padding:0.75rem 1rem;font-size:0.875rem;">
        {{ session('success') }}
    </div>
    @endif

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.projects.index') }}" class="flex flex-wrap gap-3">
        <select name="client_id" style="max-width:16rem;" onchange="this.form.submit()">
            <option value="">Todos os clientes</option>
            @foreach($clients as $c)
            <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>
        <select name="type" style="max-width:14rem;" onchange="this.form.submit()">
            <option value="">Todos os tipos</option>
            @foreach(\App\Models\Project::TYPES as $k => $v)
            <option value="{{ $k }}" {{ request('type') === $k ? 'selected' : '' }}>{{ $v }}</option>
            @endforeach
        </select>
        <select name="status" style="max-width:14rem;" onchange="this.form.submit()">
            <option value="">Todos os status</option>
            @foreach(\App\Models\Project::STATUSES as $k => $v)
            <option value="{{ $k }}" {{ request('status') === $k ? 'selected' : '' }}>{{ $v }}</option>
            @endforeach
        </select>
        @if(request('client_id') || request('status') || request('type'))
        <a href="{{ route('admin.projects.index') }}" class="btn-secondary">Limpar</a>
        @endif
    </form>

    {{-- Cards --}}
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($projects as $project)
        <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:1.25rem;display:flex;flex-direction:column;transition:border-color 0.15s;"
             onmouseover="this.style.borderColor='oklch(0.74 0.135 78 / 0.4)'"
             onmouseout="this.style.borderColor='var(--border)'">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3 min-w-0">
                    {{-- Capa: miniatura discreta. Sem capa própria — ou com o ficheiro em
                         falta no disco, que faz a rota devolver 404 — entra a imagem padrão,
                         de modo que a miniatura ocupa sempre o mesmo espaço. --}}
                    <x-project-cover :src="$project->cover_image_path ? route('admin.projects.cover', $project) : null"
                                     alt="" loading="lazy"
                                     style="width:3rem;height:3rem;border-radius:0.5rem;object-fit:cover;border:1px solid var(--border);flex-shrink:0;" />
                    <div class="min-w-0">
                        @if($project->code)<p style="font-size:0.75rem;color:var(--muted-foreground)">{{ $project->code }}</p>@endif
                        <h3 style="font-size:1rem;font-weight:700;" class="truncate">{{ $project->name }}</h3>
                        <p style="font-size:0.8125rem;color:var(--muted-foreground)" class="truncate">{{ $project->client?->name }} · {{ $project->location ?: '—' }}</p>
                    </div>
                </div>
                <div class="flex flex-col items-end gap-1 shrink-0">
                    <span class="badge badge-{{ $project->status }} shrink-0">{{ \App\Models\Project::STATUSES[$project->status] ?? $project->status }}</span>
                    <x-project-type :project="$project" />
                </div>
            </div>
            <div class="mt-4">
                <div class="flex justify-between" style="font-size:0.75rem;color:var(--muted-foreground);margin-bottom:0.375rem;">
                    <span>Progresso</span><span>{{ $project->progress }}%</span>
                </div>
                <div class="progress-bar"><div class="progress-fill" style="width:{{ $project->progress }}%"></div></div>
            </div>
            <div class="grid grid-cols-2 gap-2 mt-4" style="font-size:0.75rem;">
                <div style="background-color:var(--surface);border:1px solid var(--border);border-radius:0.5rem;padding:0.5rem;">
                    <div style="color:var(--muted-foreground)">Orçamento</div>
                    <div style="font-weight:600;margin-top:0.125rem;">{{ money($project->budget ?? 0) }}</div>
                </div>
                <div style="background-color:var(--surface);border:1px solid var(--border);border-radius:0.5rem;padding:0.5rem;">
                    <div style="color:var(--muted-foreground)">Início</div>
                    <div style="font-weight:600;margin-top:0.125rem;">{{ $project->start_date ? \Carbon\Carbon::parse($project->start_date)->format('d/m/Y') : '—' }}</div>
                </div>
            </div>
            <div class="flex items-center justify-between mt-4 pt-3" style="border-top:1px solid var(--border);">
                <a href="{{ route('admin.projects.show', $project) }}" style="font-size:0.875rem;color:var(--primary);display:inline-flex;align-items:center;gap:0.25rem;">
                    Abrir
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                </a>
                <div class="flex gap-1">
                    <a href="{{ route('admin.projects.edit', $project) }}"
                       style="padding:0.375rem;border-radius:0.375rem;color:var(--muted-foreground);display:inline-flex;"
                       onmouseover="this.style.backgroundColor='var(--surface)'" onmouseout="this.style.backgroundColor=''">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
                    </a>
                    <form method="POST" action="{{ route('admin.projects.destroy', $project) }}" onsubmit="return confirm('Excluir {{ $project->isSiteWork() ? 'esta obra' : 'este projecto' }} e todos os dados?')">
                        @csrf @method('DELETE')
                        <button type="submit" style="padding:0.375rem;border-radius:0.375rem;color:var(--muted-foreground);background:none;border:none;cursor:pointer;display:inline-flex;"
                                onmouseover="this.style.backgroundColor='oklch(0.62 0.21 25 / 0.15)';this.style.color='var(--destructive)'"
                                onmouseout="this.style.backgroundColor='';this.style.color='var(--muted-foreground)'">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="md:col-span-2 xl:col-span-3" style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:3rem;text-align:center;color:var(--muted-foreground);">
            Nenhuma obra encontrada.
        </div>
        @endforelse
    </div>

    {{ $projects->links() }}
</div>

{{-- Modal: Nova Obra --}}
@can('manage-projects')
<div id="modal-nova-obra" class="modal-overlay" style="display:none;" onclick="if(event.target===this)closeModal('modal-nova-obra')">
    <div class="modal-box" style="max-width:42rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
            <h2 class="modal-title" style="margin:0;">Nova obra</h2>
            <button type="button" onclick="closeModal('modal-nova-obra')" style="padding:0.375rem;border-radius:0.375rem;border:none;background:none;cursor:pointer;color:var(--muted-foreground);" onmouseover="this.style.backgroundColor='var(--surface)';this.style.color='var(--foreground)'" onmouseout="this.style.backgroundColor='';this.style.color='var(--muted-foreground)'" aria-label="Fechar">
                <svg style="width:1.25rem;height:1.25rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.projects.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="_form" value="nova_obra">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="field-label">Cliente <span style="color:var(--destructive)">*</span></label>
                    <select name="client_id" required>
                        <option value="">Selecione…</option>
                        @foreach($clients as $c)<option value="{{ $c->id }}" {{ old('client_id')==$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach
                    </select>
                    @error('client_id')<p style="color:var(--destructive);font-size:0.75rem;margin-top:0.25rem;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="field-label">Código interno</label>
                    <input type="text" name="code" value="{{ old('code') }}">
                </div>
            </div>
            <div>
                <label class="field-label">Nome da obra <span style="color:var(--destructive)">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required id="obra-nome">
                @error('name')<p style="color:var(--destructive);font-size:0.75rem;margin-top:0.25rem;">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="field-label">Localização</label>
                <input type="text" name="location" value="{{ old('location') }}">
            </div>
            <div>
                <label class="field-label">Descrição</label>
                <textarea name="description" rows="2" style="resize:vertical;">{{ old('description') }}</textarea>
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="field-label">Data de início</label>
                    <input type="date" name="start_date" value="{{ old('start_date') }}">
                </div>
                <div>
                    <label class="field-label">Previsão de término</label>
                    <input type="date" name="expected_end_date" value="{{ old('expected_end_date') }}">
                </div>
                <div>
                    <label class="field-label">Orçamento ({{ currency_symbol() }})</label>
                    <input type="number" step="0.01" min="0" name="budget" value="{{ old('budget', 0) }}">
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="field-label">Tipo <span style="color:var(--destructive)">*</span></label>
                    <select name="type" required>
                        @foreach(\App\Models\Project::TYPES as $k => $v)
                        <option value="{{ $k }}" {{ old('type','construcao')===$k?'selected':'' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                    @error('type')<p style="color:var(--destructive);font-size:0.75rem;margin-top:0.25rem;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="field-label">Status</label>
                    <select name="status">
                        @foreach(\App\Models\Project::STATUSES as $k => $v)
                        <option value="{{ $k }}" {{ old('status','planejada')===$k?'selected':'' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">Gestor responsável</label>
                    <select name="manager_id">
                        <option value="">Nenhum</option>
                        @foreach($managers as $m)<option value="{{ $m->id }}" {{ old('manager_id')==$m->id?'selected':'' }}>{{ $m->name }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:0.5rem;">
                <input type="checkbox" name="show_financials" value="1" id="sf_modal" {{ old('show_financials','1')?'checked':'' }} style="width:1rem;height:1rem;border-radius:0.25rem;">
                <label for="sf_modal" style="font-size:0.875rem;color:var(--foreground);cursor:pointer;">Exibir financeiro para o cliente</label>
            </div>
            <div>
                <label class="field-label">Imagem de capa</label>
                <input type="file" name="cover_image" accept="image/*" style="padding:0.375rem;">
            </div>
            <div>
                <label class="field-label">Notas internas</label>
                <textarea name="notes" rows="2" style="resize:vertical;">{{ old('notes') }}</textarea>
            </div>
            <div style="display:flex;gap:0.5rem;padding-top:0.25rem;">
                <button type="submit" class="btn-primary">Criar obra</button>
                <button type="button" onclick="closeModal('modal-nova-obra')" class="btn-secondary">Cancelar</button>
            </div>
        </form>
    </div>
</div>
@endcan

<script>
function openModal(id) {
    var m = document.getElementById(id);
    m.style.display = 'flex';
    var first = m.querySelector('select,input:not([type=hidden]):not([type=file]):not([type=checkbox]),textarea');
    if (first) setTimeout(function(){ first.focus(); }, 50);
}
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}
document.addEventListener('keydown', function(e){ if(e.key==='Escape') document.querySelectorAll('.modal-overlay').forEach(function(m){ m.style.display='none'; }); });
@if(old('_form') === 'nova_obra')
document.addEventListener('DOMContentLoaded', function(){ openModal('modal-nova-obra'); });
@endif
</script>
</x-admin-layout>
