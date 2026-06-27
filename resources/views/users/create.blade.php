<x-admin-layout>
    <x-slot name="title">Novo Usuário</x-slot>

    <div class="p-8">
        <div class="max-w-2xl mx-auto bg-white rounded-xl shadow-sm p-8">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Novo Usuário</h1>

            <form method="POST" action="{{ route('users.store') }}">
                @csrf

                <div class="space-y-5">
                    <div>
                        <x-input-label for="name" :value="'Nome completo'" />
                        <x-text-input id="name" name="name" type="text"
                                      class="mt-1 block w-full"
                                      :value="old('name')" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="email" :value="'E-mail'" />
                        <x-text-input id="email" name="email" type="email"
                                      class="mt-1 block w-full"
                                      :value="old('email')" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="phone" :value="'Telefone'" />
                        <x-text-input id="phone" name="phone" type="text"
                                      class="mt-1 block w-full"
                                      :value="old('phone')" />
                        <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="role" :value="'Perfil'" />
                        <select id="role" name="role"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">Selecione um perfil</option>
                            <option value="admin"     @selected(old('role') === 'admin')>Administrador</option>
                            <option value="manager"   @selected(old('role') === 'manager')>Gestor da Obra</option>
                            <option value="financial" @selected(old('role') === 'financial')>Financeiro</option>
                            <option value="client"    @selected(old('role') === 'client')>Cliente</option>
                        </select>
                        <x-input-error :messages="$errors->get('role')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="password" :value="'Senha'" />
                        <x-text-input id="password" name="password" type="password"
                                      class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('password')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" :value="'Confirmar senha'" />
                        <x-text-input id="password_confirmation" name="password_confirmation"
                                      type="password" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
                    </div>
                </div>

                <div class="flex items-center gap-3 mt-8">
                    <x-primary-button>Salvar</x-primary-button>
                    <a href="{{ route('users.index') }}"
                       class="text-sm font-medium text-gray-500 hover:text-gray-700">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
