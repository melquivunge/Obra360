<x-admin-layout>
<div class="space-y-6 p-6 md:p-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 style="font-size:1.5rem;font-weight:700;">Clientes</h1>
            <p style="color:var(--muted-foreground);font-size:0.875rem;margin-top:0.25rem;">Gerencie os clientes da empresa.</p>
        </div>
        <button type="button" data-modal-open="modal-create-client" class="btn-primary">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Novo cliente
        </button>
    </div>

    @if(session('success'))
    <div style="background-color:oklch(0.6 0.18 145 / 0.12);border:1px solid oklch(0.6 0.18 145 / 0.4);color:oklch(0.7 0.15 145);border-radius:0.5rem;padding:0.75rem 1rem;font-size:0.875rem;">
        {{ session('success') }}
    </div>
    @endif

    {{-- Search --}}
    <form method="GET" action="{{ route('admin.clients.index') }}" class="flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nome, e-mail ou documento…" style="max-width:24rem;">
        <button type="submit" class="btn-secondary">Buscar</button>
        @if(request('search'))
        <a href="{{ route('admin.clients.index') }}" class="btn-secondary">Limpar</a>
        @endif
    </form>

    {{-- Table --}}
    <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead style="background-color:var(--surface);">
                    <tr>
                        <th>Nome</th>
                        <th>Documento</th>
                        <th>Contato</th>
                        <th>Cidade</th>
                        <th>Status</th>
                        <th style="text-align:right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients as $client)
                    <tr>
                        <td>
                            <p class="font-medium">{{ $client->name }}</p>
                        </td>
                        <td style="color:var(--muted-foreground)">{{ $client->document ?: '—' }}</td>
                        <td style="color:var(--muted-foreground)">
                            <div>{{ $client->email ?: '—' }}</div>
                            @if($client->phone)<div style="font-size:0.75rem;">{{ $client->phone }}</div>@endif
                        </td>
                        <td style="color:var(--muted-foreground)">{{ $client->city ?: '—' }}</td>
                        <td>
                            @if($client->active)
                                <span class="badge badge-concluida">Ativo</span>
                            @else
                                <span class="badge badge-planejada">Inativo</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            <div class="flex gap-1 justify-end">
                                <a href="{{ route('admin.clients.edit', $client) }}"
                                   style="padding:0.375rem;border-radius:0.375rem;color:var(--muted-foreground);display:inline-flex;"
                                   class="hover-surface" title="Editar">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
                                </a>
                                <form method="POST" action="{{ route('admin.clients.destroy', $client) }}" data-confirm="Excluir este cliente?">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            style="padding:0.375rem;border-radius:0.375rem;color:var(--muted-foreground);background:none;border:none;cursor:pointer;display:inline-flex;"
                                            class="hover-danger" title="Excluir">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align:center;color:var(--muted-foreground);padding:3rem;">Nenhum cliente cadastrado.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $clients->links() }}
</div>

{{-- Create Client Modal --}}
<div id="modal-create-client" data-modal-overlay
     style="display:none;position:fixed;inset:0;z-index:50;align-items:center;justify-content:center;background:rgb(0 0 0 / 0.7);padding:1rem;">
    <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.875rem;width:100%;max-width:38rem;max-height:90vh;overflow-y:auto;padding:1.5rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
            <h2 style="font-size:1.125rem;font-weight:700;margin:0;">Novo cliente</h2>
            <button type="button" data-modal-close="modal-create-client" class="hover-surface"
                    style="padding:0.375rem;border-radius:0.375rem;border:none;background:none;cursor:pointer;color:var(--muted-foreground);"
                    aria-label="Fechar">
                <svg style="width:1.25rem;height:1.25rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.clients.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="_form" value="create_client">

            <x-form-errors form="create_client" />

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="field-label">Nome <span style="color:var(--destructive)">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required autofocus>
                    <x-field-error field="name" form="create_client" />
                </div>
                <div>
                    <label class="field-label">Documento (CPF/CNPJ)</label>
                    <input type="text" name="document" value="{{ old('document') }}">
                </div>
                <div>
                    <label class="field-label">E-mail</label>
                    <input type="email" name="email" value="{{ old('email') }}">
                    <x-field-error field="email" form="create_client" />
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
                <div style="display:flex;align-items:center;gap:0.5rem;padding-top:1.5rem;">
                    <input type="checkbox" name="active" value="1" id="active_modal" {{ old_checked('active', true) ? 'checked' : '' }} style="width:1rem;height:1rem;border-radius:0.25rem;">
                    <label for="active_modal" style="font-size:0.875rem;color:var(--foreground);cursor:pointer;">Cliente ativo</label>
                </div>
            </div>
            <div>
                <label class="field-label">Notas internas</label>
                <textarea name="notes" rows="2" style="resize:vertical;">{{ old('notes') }}</textarea>
            </div>

            {{-- Acesso ao portal --}}
            <div style="border-top:1px solid var(--border);padding-top:1rem;margin-top:0.5rem;">
                <p style="font-size:0.8125rem;font-weight:600;color:var(--foreground);margin:0 0 0.75rem;">
                    Acesso ao portal
                    <span style="font-weight:400;color:var(--muted-foreground);margin-left:0.25rem;">— opcional</span>
                </p>
                <p style="font-size:0.75rem;color:var(--muted-foreground);margin:0 0 0.875rem;">Preencha a senha para criar automaticamente um usuário de acesso para este cliente.</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="field-label">Senha</label>
                        <x-password-input name="password" autocomplete="new-password" placeholder="Mín. 8 caracteres" />
                        <x-field-error field="password" form="create_client" />
                    </div>
                    <div>
                        <label class="field-label">Confirmar senha</label>
                        <x-password-input name="password_confirmation" autocomplete="new-password" />
                    </div>
                </div>
            </div>

            <div style="display:flex;gap:0.5rem;padding-top:0.25rem;">
                <button type="submit" class="btn-primary">Criar cliente</button>
                <button type="button" data-modal-close="modal-create-client" class="btn-secondary">Cancelar</button>
            </div>
        </form>
    </div>
</div>

{{-- Abrir, fechar, clicar fora e a tecla Escape são tratados por
     resources/js/app.js para qualquer `[data-modal-overlay]`. Aqui fica só o
     caso próprio desta página: um erro de validação recarregou a página e o
     formulário que falhou vivia dentro do modal — sem isto a mensagem existia
     por detrás de um botão fechado. --}}
@if(old('_form') === 'create_client')
<script @cspNonce>
document.addEventListener('DOMContentLoaded', function () {
    window.obra360.openModal('modal-create-client');
});
</script>
@endif
</x-admin-layout>
