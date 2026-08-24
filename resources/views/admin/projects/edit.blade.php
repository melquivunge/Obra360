<x-admin-layout>
<div class="p-6 md:p-8 max-w-3xl">
    <div class="mb-6 flex items-start justify-between">
        <div>
            <a href="{{ route('admin.projects.show', $project) }}" style="font-size:0.875rem;color:var(--muted-foreground)">← {{ $project->isSiteWork() ? 'Obra' : 'Projeto' }}</a>
            <h1 style="font-size:1.5rem;font-weight:700;margin-top:0.5rem;">Editar {{ $project->noun() }}</h1>
        </div>
        <a href="{{ route('admin.projects.show', $project) }}" class="btn-secondary">Ver {{ $project->noun() }}</a>
    </div>
    <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:1.5rem;">
        {{-- OBRA-01: ver o comentário do formulário de criação. --}}
        <form method="POST" action="{{ route('admin.projects.update', $project) }}" enctype="multipart/form-data" class="space-y-4"
              x-data="{
                  client: @js((string) old('client_id', $project->client_id)),
                  source: '',
                  sources: @js($sourceProjects),
              }"
              x-init="$nextTick(() => source = @js((string) old('source_project_id', $project->source_project_id)))">
            @csrf @method('PUT')

            <x-form-errors />
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="field-label">Cliente <span style="color:var(--destructive)">*</span></label>
                    <select name="client_id" required x-model="client" @change="source = ''">
                        @foreach($clients as $c)<option value="{{ $c->id }}" {{ old('client_id', $project->client_id)==$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach
                    </select>
                    <x-field-error field="client_id" />
                </div>
                <div>
                    <label class="field-label">Código interno</label>
                    <input type="text" name="code" value="{{ old('code', $project->code) }}">
                </div>
            </div>
            @unless($project->hasMissingSource())
            <div x-show="(sources[client] ?? []).length" x-cloak>
                <label class="field-label">Projeto de origem</label>
                <select name="source_project_id" x-model="source">
                    <option value="">Nenhum</option>
                    <template x-for="source_option in (sources[client] ?? [])" :key="source_option.id">
                        <option :value="source_option.id" x-text="source_option.label"></option>
                    </template>
                </select>
                <p style="font-size:0.75rem;color:var(--muted-foreground);margin-top:0.25rem;">
                    {{ ucfirst($project->nounWithArticle()) }} mostra as plantas aprovadas do projeto de origem, sem cópias.
                </p>
                <x-field-error field="source_project_id" />
            </div>
            @else
            {{-- A ligação existe mas o projecto de origem está na reciclagem. O
                 campo não é desenhado de todo: um select sem a opção certa
                 apagaria a ligação ao guardar qualquer outra alteração, e o que
                 se quer é o contrário — restaurar o projecto devolve tudo. --}}
            <p style="font-size:0.75rem;color:var(--muted-foreground);">
                O projeto de origem dest{{ $project->isSiteWork() ? 'a obra' : 'e projeto' }} foi removido. O vínculo fica salvo e volta a valer se ele for restaurado.
            </p>
            @endunless
            <div>
                <label class="field-label">Nome d{{ $project->nounWithArticle() }} <span style="color:var(--destructive)">*</span></label>
                <input type="text" name="name" value="{{ old('name', $project->name) }}" required>
                <x-field-error field="name" />
            </div>
            <div>
                <label class="field-label">Localização</label>
                <input type="text" name="location" value="{{ old('location', $project->location) }}">
            </div>
            <div>
                <label class="field-label">Descrição</label>
                <textarea name="description" rows="3" style="resize:vertical;">{{ old('description', $project->description) }}</textarea>
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="field-label">Início</label>
                    <input type="date" name="start_date" value="{{ old('start_date', $project->start_date) }}">
                </div>
                <div>
                    <label class="field-label">Previsão</label>
                    <input type="date" name="expected_end_date" value="{{ old('expected_end_date', $project->expected_end_date) }}">
                </div>
                <div>
                    <label class="field-label">Orçamento ({{ currency_symbol() }})</label>
                    <input type="number" step="0.01" min="0" name="budget" value="{{ old('budget', $project->budget) }}">
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="field-label">Tipo <span style="color:var(--destructive)">*</span></label>
                    <select name="type" required>
                        @foreach(\App\Models\Project::TYPES as $k => $v)
                        <option value="{{ $k }}" {{ old('type', $project->type)===$k?'selected':'' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                    <x-field-error field="type" />
                </div>
                <div>
                    <label class="field-label">Status</label>
                    <select name="status">
                        @foreach(\App\Models\Project::STATUSES as $k => $v)
                        <option value="{{ $k }}" {{ old('status', $project->status)===$k?'selected':'' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">Gestor</label>
                    <select name="manager_id">
                        <option value="">Nenhum</option>
                        @foreach($managers as $m)<option value="{{ $m->id }}" {{ old('manager_id', $project->manager_id)==$m->id?'selected':'' }}>{{ $m->name }}</option>@endforeach
                    </select>
                </div>
                @if(\App\Models\Project::progressIsManual())
                <div>
                    <label class="field-label">Progresso (%)</label>
                    <input type="number" name="progress" min="0" max="100" value="{{ old('progress', $project->progress) }}">
                    <x-field-error field="progress" />
                </div>
                @endif
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="show_financials" value="1" id="sf" {{ old_checked('show_financials', (bool) $project->show_financials)?'checked':'' }} style="width:auto;">
                <label for="sf" style="font-size:0.875rem;color:var(--foreground)">Exibir financeiro para o cliente</label>
            </div>
            <div>
                <label class="field-label">Nova imagem de capa (opcional)</label>
                @if($project->cover_image_path)
                <img src="{{ route('admin.projects.cover', $project) }}" alt="Capa atual" style="height:80px;border-radius:0.5rem;margin-bottom:0.5rem;object-fit:cover;">
                @endif
                <input type="file" name="cover_image" accept="image/*" style="padding:0.375rem;">
            </div>
            <div>
                <label class="field-label">Notas internas</label>
                <textarea name="notes" rows="2" style="resize:vertical;">{{ old('notes', $project->notes) }}</textarea>
            </div>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="btn-primary">Salvar alterações</button>
                <a href="{{ route('admin.projects.show', $project) }}" class="btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</x-admin-layout>
