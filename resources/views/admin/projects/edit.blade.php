<x-admin-layout>
<div class="p-6 md:p-8 max-w-3xl">
    <div class="mb-6 flex items-start justify-between">
        <div>
            <a href="{{ route('admin.projects.show', $project) }}" style="font-size:0.875rem;color:var(--muted-foreground)">← Obra</a>
            <h1 style="font-size:1.5rem;font-weight:700;margin-top:0.5rem;">Editar obra</h1>
        </div>
        <a href="{{ route('admin.projects.show', $project) }}" class="btn-secondary">Ver obra</a>
    </div>
    <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:1.5rem;">
        <form method="POST" action="{{ route('admin.projects.update', $project) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf @method('PUT')
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="field-label">Cliente <span style="color:var(--destructive)">*</span></label>
                    <select name="client_id" required>
                        @foreach($clients as $c)<option value="{{ $c->id }}" {{ $project->client_id==$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">Código interno</label>
                    <input type="text" name="code" value="{{ old('code', $project->code) }}">
                </div>
            </div>
            <div>
                <label class="field-label">Nome da obra <span style="color:var(--destructive)">*</span></label>
                <input type="text" name="name" value="{{ old('name', $project->name) }}" required>
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
                    <label class="field-label">Status</label>
                    <select name="status">
                        @foreach(\App\Models\Project::STATUSES as $k => $v)
                        <option value="{{ $k }}" {{ $project->status===$k?'selected':'' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">Gestor</label>
                    <select name="manager_id">
                        <option value="">Nenhum</option>
                        @foreach($managers as $m)<option value="{{ $m->id }}" {{ $project->manager_id==$m->id?'selected':'' }}>{{ $m->name }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="show_financials" value="1" id="sf" {{ $project->show_financials?'checked':'' }} style="width:auto;">
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
