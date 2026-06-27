<x-admin-layout>
    <x-slot name="title">Usuários</x-slot>

    <div class="p-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Usuários</h1>
            <a href="{{ route('users.create') }}"
               class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition">
                Novo Usuário
            </a>
        </div>

        <x-flash-message />

        <x-data-table>
            <x-slot name="head">
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Nome</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">E-mail</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Telefone</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Perfil</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Ações</th>
            </x-slot>

            @forelse ($users as $user)
                @php
                    $roleLabel = match($user->role) {
                        'admin'     => 'Administrador',
                        'manager'   => 'Gestor da Obra',
                        'financial' => 'Financeiro',
                        'client'    => 'Cliente',
                        default     => $user->role,
                    };
                    $roleColor = match($user->role) {
                        'admin'     => 'purple',
                        'manager'   => 'blue',
                        'financial' => 'yellow',
                        'client'    => 'gray',
                        default     => 'gray',
                    };
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $user->name }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $user->email }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $user->phone ?? '—' }}</td>
                    <td class="px-6 py-4">
                        <x-badge :color="$roleColor">{{ $roleLabel }}</x-badge>
                    </td>
                    <td class="px-6 py-4">
                        @if ($user->active)
                            <x-badge color="green">Ativo</x-badge>
                        @else
                            <x-badge color="red">Inativo</x-badge>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('users.edit', $user) }}"
                               class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                Editar
                            </a>

                            <form method="POST" action="{{ route('users.toggle-active', $user) }}">
                                @csrf
                                <button type="submit"
                                        class="text-sm font-medium {{ $user->active ? 'text-gray-500 hover:text-gray-700' : 'text-green-600 hover:text-green-800' }}">
                                    {{ $user->active ? 'Desativar' : 'Ativar' }}
                                </button>
                            </form>

                            <form method="POST" action="{{ route('users.destroy', $user) }}"
                                  onsubmit="return confirm('Tem certeza que deseja excluir este usuário? Esta ação não pode ser desfeita.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="text-sm font-medium text-red-600 hover:text-red-800">
                                    Excluir
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                        Nenhum usuário cadastrado ainda. Crie o primeiro usuário.
                    </td>
                </tr>
            @endforelse
        </x-data-table>

        <div class="mt-4">
            {{ $users->links() }}
        </div>
    </div>
</x-admin-layout>
