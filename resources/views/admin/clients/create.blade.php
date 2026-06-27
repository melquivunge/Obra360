<x-admin-layout>
<div class="p-6 md:p-8 max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('admin.clients.index') }}" style="font-size:0.875rem;color:var(--muted-foreground);">← Clientes</a>
        <h1 style="font-size:1.5rem;font-weight:700;margin-top:0.5rem;">Novo cliente</h1>
    </div>
    <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:1.5rem;">
        <form method="POST" action="{{ route('admin.clients.store') }}" class="space-y-4">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="field-label">Nome <span style="color:var(--destructive)">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required>
                    @error('name')<p style="color:var(--destructive);font-size:0.75rem;margin-top:0.25rem;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="field-label">Documento (CPF/CNPJ)</label>
                    <input type="text" name="document" value="{{ old('document') }}">
                </div>
                <div>
                    <label class="field-label">E-mail</label>
                    <input type="email" name="email" value="{{ old('email') }}">
                    @error('email')<p style="color:var(--destructive);font-size:0.75rem;margin-top:0.25rem;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="field-label">Telefone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}">
                </div>
                <div>
                    <label class="field-label">Endereço</label>
                    <input type="text" name="address" value="{{ old('address') }}">
                </div>
                <div>
                    <label class="field-label">Cidade</label>
                    <input type="text" name="city" value="{{ old('city') }}">
                </div>
                <div>
                    <label class="field-label">País</label>
                    <input type="text" name="country" value="{{ old('country', 'Brasil') }}">
                </div>
                <div class="flex items-center gap-2 pt-6">
                    <input type="checkbox" name="active" value="1" id="active" checked style="width:auto;">
                    <label for="active" style="font-size:0.875rem;color:var(--foreground)">Cliente ativo</label>
                </div>
            </div>
            <div>
                <label class="field-label">Notas internas</label>
                <textarea name="notes" rows="3" style="resize:vertical;">{{ old('notes') }}</textarea>
            </div>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="btn-primary">Criar cliente</button>
                <a href="{{ route('admin.clients.index') }}" class="btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</x-admin-layout>
