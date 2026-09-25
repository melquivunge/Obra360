<x-admin-layout>
<div class="p-6 md:p-8 max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('users.index') }}" style="font-size:0.875rem;color:var(--muted-foreground)">{{ __('← Usuários') }}</a>
        <h1 style="font-size:1.5rem;font-weight:700;margin-top:0.5rem;">{{ __('Novo usuário') }}</h1>
    </div>
    <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:1.5rem;">
        <form method="POST" action="{{ route('users.store') }}" class="space-y-4">
            @csrf

            <x-form-errors />
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="field-label">{{ __('Nome completo') }} <span style="color:var(--destructive)">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required autofocus>
                    <x-field-error field="name" />
                </div>
                <div>
                    <label class="field-label">{{ __('E-mail') }} <span style="color:var(--destructive)">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" required>
                    <x-field-error field="email" />
                </div>
                <div>
                    <label class="field-label">{{ __('Telefone') }}</label>
                    <input type="text" name="phone" value="{{ old('phone') }}">
                </div>
                <div>
                    <label class="field-label">{{ __('Perfil') }} <span style="color:var(--destructive)">*</span></label>
                    <select name="role" required>
                        <option value="">{{ __('Selecione…') }}</option>
                        <option value="admin"     @selected(old('role')==='admin')>{{ __('Administrador') }}</option>
                        <option value="manager"   @selected(old('role')==='manager')>{{ __('Gestor') }}</option>
                        <option value="financial" @selected(old('role')==='financial')>{{ __('Financeiro') }}</option>
                        <option value="client"    @selected(old('role')==='client')>{{ __('Cliente') }}</option>
                    </select>
                    <x-field-error field="role" />
                </div>
                <div>
                    <label class="field-label">{{ __('Senha') }} <span style="color:var(--destructive)">*</span></label>
                    <x-password-input name="password" required autocomplete="new-password" />
                    <x-field-error field="password" />
                </div>
                <div>
                    <label class="field-label">{{ __('Confirmar senha') }} <span style="color:var(--destructive)">*</span></label>
                    <x-password-input name="password_confirmation" required autocomplete="new-password" />
                </div>
            </div>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="btn-primary">{{ __('Criar usuário') }}</button>
                <a href="{{ route('users.index') }}" class="btn-secondary">{{ __('Cancelar') }}</a>
            </div>
        </form>
    </div>
</div>
</x-admin-layout>
