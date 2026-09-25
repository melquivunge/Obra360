<x-admin-layout>
<div class="p-6 md:p-8 space-y-6 max-w-2xl">
    <div>
        <a href="{{ route('admin.clients.index') }}" style="font-size:0.875rem;color:var(--muted-foreground);">{{ __('← Clientes') }}</a>
        <h1 style="font-size:1.5rem;font-weight:700;margin-top:0.5rem;">{{ __('Editar cliente') }}</h1>
    </div>

    <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:1.5rem;">
        <form method="POST" action="{{ route('admin.clients.update', $client) }}" class="space-y-4">
            @csrf @method('PUT')
            <input type="hidden" name="_form" value="editar_cliente">

            <x-form-errors form="editar_cliente" />
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="field-label">{{ __('Nome') }} <span style="color:var(--destructive)">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $client->name) }}" required>
                    <x-field-error field="name" form="editar_cliente" />
                </div>
                <div>
                    <label class="field-label">{{ __('Documento') }}</label>
                    <input type="text" name="document" value="{{ old('document', $client->document) }}">
                </div>
                <div>
                    <label class="field-label">{{ __('E-mail') }}</label>
                    <input type="email" name="email" value="{{ old('email', $client->email) }}">
                    <x-field-error field="email" form="editar_cliente" />
                </div>
                <div>
                    <label class="field-label">{{ __('Telefone') }}</label>
                    <input type="text" name="phone" value="{{ old('phone', $client->phone) }}">
                </div>
                <div>
                    <label class="field-label">{{ __('Endereço') }}</label>
                    <input type="text" name="address" value="{{ old('address', $client->address) }}">
                </div>
                <div>
                    <label class="field-label">{{ __('Cidade') }}</label>
                    <input type="text" name="city" value="{{ old('city', $client->city) }}">
                </div>
                <div>
                    <label class="field-label">{{ __('País') }}</label>
                    <input type="text" name="country" value="{{ old('country', $client->country) }}">
                </div>
                <div class="flex items-center gap-2 pt-6">
                    <input type="checkbox" name="active" value="1" id="active" {{ old_checked('active', (bool) $client->active) ? 'checked' : '' }} style="width:auto;">
                    <label for="active" style="font-size:0.875rem;color:var(--foreground)">{{ __('Cliente ativo') }}</label>
                </div>
            </div>
            <div>
                <label class="field-label">{{ __('Notas internas') }}</label>
                <textarea name="notes" rows="3" style="resize:vertical;">{{ old('notes', $client->notes) }}</textarea>
            </div>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="btn-primary">{{ __('Salvar alterações') }}</button>
                <a href="{{ route('admin.clients.index') }}" class="btn-secondary">{{ __('Cancelar') }}</a>
            </div>
        </form>
    </div>

    {{-- Linked users --}}
    <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:1.5rem;">
        <h2 style="font-size:1rem;font-weight:700;margin-bottom:1rem;">{{ __('Usuários vinculados') }}</h2>
        @if($linkedUsers->count())
        <table class="data-table mb-4">
            <thead style="background-color:var(--surface)"><tr><th>{{ __('Nome') }}</th><th>{{ __('E-mail') }}</th><th style="text-align:right">{{ __('Ação') }}</th></tr></thead>
            <tbody>
                @foreach($linkedUsers as $u)
                <tr>
                    <td>{{ $u->name }}</td>
                    <td style="color:var(--muted-foreground)">{{ $u->email }}</td>
                    <td style="text-align:right">
                        <form method="POST" action="{{ route('admin.clients.unlink-user', [$client, $u]) }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-danger" style="font-size:0.75rem;padding:0.25rem 0.625rem;">{{ __('Desvincular') }}</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="color:var(--muted-foreground);font-size:0.875rem;margin-bottom:1rem;">{{ __('Nenhum usuário vinculado.') }}</p>
        @endif

        @if($availableUsers->count())
        <x-form-errors form="vincular_utilizador" />
        <form method="POST" action="{{ route('admin.clients.link-user', $client) }}" class="flex gap-2">
            @csrf
            <input type="hidden" name="_form" value="vincular_utilizador">
            <select name="user_id" style="max-width:20rem;">
                <option value="">{{ __('Selecione um usuário…') }}</option>
                @foreach($availableUsers as $u)
                <option value="{{ $u->id }}" {{ old('user_id')==$u->id?'selected':'' }}>{{ $u->name }} ({{ $u->email }})</option>
                @endforeach
            </select>
            <button type="submit" class="btn-primary">{{ __('Vincular') }}</button>
        </form>
        @endif
    </div>
</div>
</x-admin-layout>
