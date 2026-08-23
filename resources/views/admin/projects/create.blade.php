<x-admin-layout>
<div class="p-6 md:p-8 max-w-3xl">
    <div class="mb-6">
        <a href="{{ route('admin.projects.index') }}" style="font-size:0.875rem;color:var(--muted-foreground)">← Obras</a>
        <h1 style="font-size:1.5rem;font-weight:700;margin-top:0.5rem;">Nova obra</h1>
    </div>
    <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:1.5rem;">
        <form method="POST" action="{{ route('admin.projects.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
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
                <input type="text" name="name" value="{{ old('name') }}" required>
                @error('name')<p style="color:var(--destructive);font-size:0.75rem;margin-top:0.25rem;">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="field-label">Localização</label>
                <input type="text" name="location" value="{{ old('location') }}">
            </div>
            <div>
                <label class="field-label">Descrição</label>
                <textarea name="description" rows="3" style="resize:vertical;">{{ old('description') }}</textarea>
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
                    <label class="field-label">Orçamento total ({{ currency_symbol() }})</label>
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
            <div class="flex items-center gap-2">
                <input type="checkbox" name="show_financials" value="1" id="sf" {{ old('show_financials','1')?'checked':'' }} style="width:auto;">
                <label for="sf" style="font-size:0.875rem;color:var(--foreground)">Exibir financeiro para o cliente</label>
            </div>
            <div>
                <label class="field-label">Imagem de capa</label>
                <input type="file" name="cover_image" accept="image/*" style="padding:0.375rem;">
            </div>
            <div>
                <label class="field-label">Notas internas</label>
                <textarea name="notes" rows="2" style="resize:vertical;">{{ old('notes') }}</textarea>
            </div>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="btn-primary">Criar obra</button>
                <a href="{{ route('admin.projects.index') }}" class="btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</x-admin-layout>
