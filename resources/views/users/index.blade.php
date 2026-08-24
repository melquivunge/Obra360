<x-admin-layout>
<div class="space-y-6 p-6 md:p-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 style="font-size:1.5rem;font-weight:700;">Usuários</h1>
            <p style="color:var(--muted-foreground);font-size:0.875rem;margin-top:0.25rem;">Gerencie os acessos ao sistema.</p>
        </div>
        <button type="button" onclick="openUserModal()" class="btn-primary">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Novo usuário
        </button>
    </div>

    @if(session('success'))
    <div style="background-color:oklch(0.6 0.18 145 / 0.12);border:1px solid oklch(0.6 0.18 145 / 0.4);color:oklch(0.7 0.15 145);border-radius:0.5rem;padding:0.75rem 1rem;font-size:0.875rem;">
        {{ session('success') }}
    </div>
    @endif

    <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead style="background-color:var(--surface)">
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Telefone</th>
                        <th>Perfil</th>
                        <th>Status</th>
                        <th style="text-align:right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    @php
                        $roleBadge = match($user->role) {
                            'admin'     => ['Administrador', 'background:oklch(0.6 0.18 280 / 0.15);color:oklch(0.7 0.18 280)'],
                            'manager'   => ['Gestor', 'background:oklch(0.6 0.18 220 / 0.15);color:oklch(0.65 0.18 220)'],
                            'financial' => ['Financeiro', 'background:oklch(0.78 0.15 75 / 0.15);color:oklch(0.78 0.15 75)'],
                            'client'    => ['Cliente', 'background:oklch(0.6 0.005 90 / 0.15);color:var(--muted-foreground)'],
                            default     => [$user->role, 'background:var(--surface);color:var(--muted-foreground)'],
                        };
                    @endphp
                    <tr>
                        <td style="font-weight:500;">{{ $user->name }}</td>
                        <td style="color:var(--muted-foreground)">{{ $user->email }}</td>
                        <td style="color:var(--muted-foreground)">{{ $user->phone ?? '—' }}</td>
                        <td>
                            <span style="display:inline-flex;padding:0.25rem 0.625rem;border-radius:9999px;font-size:0.7rem;font-weight:500;{{ $roleBadge[1] }}">{{ $roleBadge[0] }}</span>
                        </td>
                        <td>
                            @if($user->active)
                            <span style="display:inline-flex;padding:0.25rem 0.625rem;border-radius:9999px;font-size:0.7rem;font-weight:500;background:oklch(0.6 0.18 145 / 0.15);color:oklch(0.65 0.16 145)">Ativo</span>
                            @else
                            <span style="display:inline-flex;padding:0.25rem 0.625rem;border-radius:9999px;font-size:0.7rem;font-weight:500;background:oklch(0.62 0.21 25 / 0.15);color:var(--destructive)">Inativo</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            <div style="display:inline-flex;align-items:center;gap:0.75rem;">
                                <a href="{{ route('users.edit', $user) }}" style="font-size:0.875rem;color:var(--primary);" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">Editar</a>
                                <form method="POST" action="{{ route('users.toggle-active', $user) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" style="font-size:0.875rem;background:none;border:none;cursor:pointer;color:var(--muted-foreground);" onmouseover="this.style.color='var(--foreground)'" onmouseout="this.style.color='var(--muted-foreground)'">
                                        {{ $user->active ? 'Desativar' : 'Ativar' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('users.destroy', $user) }}" style="display:inline;" onsubmit="return confirm('Excluir este usuário?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" style="font-size:0.875rem;background:none;border:none;cursor:pointer;color:var(--destructive);" onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'">Excluir</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--muted-foreground);">Nenhum usuário cadastrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $users->links() }}
</div>

{{-- Create User Modal --}}
<div id="modal-create-user"
     style="display:none;position:fixed;inset:0;z-index:50;align-items:center;justify-content:center;background:rgb(0 0 0 / 0.7);padding:1rem;"
     onclick="if(event.target===this)closeUserModal()">
    <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.875rem;width:100%;max-width:36rem;max-height:90vh;overflow-y:auto;padding:1.5rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
            <h2 style="font-size:1.125rem;font-weight:700;margin:0;">Novo usuário</h2>
            <button type="button" onclick="closeUserModal()"
                    style="padding:0.375rem;border-radius:0.375rem;border:none;background:none;cursor:pointer;color:var(--muted-foreground);"
                    onmouseover="this.style.backgroundColor='var(--surface)';this.style.color='var(--foreground)'"
                    onmouseout="this.style.backgroundColor='';this.style.color='var(--muted-foreground)'"
                    aria-label="Fechar">
                <svg style="width:1.25rem;height:1.25rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('users.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="_form" value="create_user">

            <x-form-errors form="create_user" />

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="field-label">Nome completo <span style="color:var(--destructive)">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required>
                    <x-field-error field="name" form="create_user" />
                </div>
                <div>
                    <label class="field-label">E-mail <span style="color:var(--destructive)">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" required>
                    <x-field-error field="email" form="create_user" />
                </div>
                <div>
                    <label class="field-label">Telefone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}">
                </div>
                <div>
                    <label class="field-label">Perfil <span style="color:var(--destructive)">*</span></label>
                    <select name="role" required>
                        <option value="">Selecione…</option>
                        <option value="admin"     @selected(old('role')==='admin')>Administrador</option>
                        <option value="manager"   @selected(old('role')==='manager')>Gestor</option>
                        <option value="financial" @selected(old('role')==='financial')>Financeiro</option>
                        <option value="client"    @selected(old('role')==='client')>Cliente</option>
                    </select>
                    <x-field-error field="role" form="create_user" />
                </div>
                <div>
                    <label class="field-label">Senha <span style="color:var(--destructive)">*</span></label>
                    <x-password-input name="password" required autocomplete="new-password" />
                    <x-field-error field="password" form="create_user" />
                </div>
                <div>
                    <label class="field-label">Confirmar senha <span style="color:var(--destructive)">*</span></label>
                    <x-password-input name="password_confirmation" required autocomplete="new-password" />
                </div>
            </div>
            <div style="display:flex;gap:0.5rem;padding-top:0.5rem;">
                <button type="submit" class="btn-primary">Criar usuário</button>
                <button type="button" onclick="closeUserModal()" class="btn-secondary">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
function openUserModal() {
    var m = document.getElementById('modal-create-user');
    m.style.display = 'flex';
    setTimeout(function(){ m.querySelector('input[name="name"]').focus(); }, 50);
}
function closeUserModal() {
    document.getElementById('modal-create-user').style.display = 'none';
}
document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') closeUserModal();
});
@if(old('_form') === 'create_user')
document.addEventListener('DOMContentLoaded', function(){ openUserModal(); });
@endif
</script>
</x-admin-layout>
