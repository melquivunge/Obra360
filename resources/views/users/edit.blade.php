<x-admin-layout>
<div class="p-6 md:p-8 max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('users.index') }}" style="font-size:0.875rem;color:var(--muted-foreground)">← Usuários</a>
        <h1 style="font-size:1.5rem;font-weight:700;margin-top:0.5rem;">Editar usuário</h1>
    </div>
    <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:1.5rem;">
        <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-4">
            @csrf @method('PUT')
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="field-label">Nome completo <span style="color:var(--destructive)">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required autofocus>
                    @error('name')<p style="color:var(--destructive);font-size:0.75rem;margin-top:0.25rem;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="field-label">E-mail <span style="color:var(--destructive)">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                    @error('email')<p style="color:var(--destructive);font-size:0.75rem;margin-top:0.25rem;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="field-label">Telefone</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}">
                </div>
                <div>
                    <label class="field-label">Perfil <span style="color:var(--destructive)">*</span></label>
                    <select name="role" required>
                        <option value="admin"     @selected(old('role',$user->role)==='admin')>Administrador</option>
                        <option value="manager"   @selected(old('role',$user->role)==='manager')>Gestor da Obra</option>
                        <option value="financial" @selected(old('role',$user->role)==='financial')>Financeiro</option>
                        <option value="client"    @selected(old('role',$user->role)==='client')>Cliente</option>
                    </select>
                    @error('role')<p style="color:var(--destructive);font-size:0.75rem;margin-top:0.25rem;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="field-label">Nova senha</label>
                    <input type="password" name="password">
                    <p style="font-size:0.75rem;color:var(--muted-foreground);margin-top:0.25rem;">Deixe em branco para manter a senha atual.</p>
                    @error('password')<p style="color:var(--destructive);font-size:0.75rem;margin-top:0.25rem;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="field-label">Confirmar nova senha</label>
                    <input type="password" name="password_confirmation">
                </div>
            </div>
            <div style="padding-top:0.5rem;border-top:1px solid var(--border);margin-top:0.5rem;display:flex;align-items:center;gap:0.75rem;">
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" value="1" id="user_active"
                       @checked(old('active', $user->active))
                       style="width:1rem;height:1rem;border-radius:0.25rem;cursor:pointer;">
                <label for="user_active" style="font-size:0.875rem;color:var(--foreground);cursor:pointer;">Utilizador ativo</label>
            </div>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="btn-primary">Salvar alterações</button>
                <a href="{{ route('users.index') }}" class="btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</x-admin-layout>
