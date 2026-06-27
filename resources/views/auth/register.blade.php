<x-guest-layout>
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-2xl p-8">
        <div class="flex flex-col items-center">
            <svg class="w-10 h-10 text-slate-800" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
            </svg>
            <span class="text-3xl font-bold text-slate-800 mt-1">Obra360</span>
        </div>

        <h2 class="text-lg font-semibold text-slate-800 text-center mt-4">Criar conta</h2>

        <form method="POST" action="{{ route('register') }}" class="mt-6">
            @csrf

            <div>
                <x-input-label for="name" :value="'Nome completo'" />
                <x-text-input
                    id="name"
                    class="block mt-1 w-full"
                    type="text"
                    name="name"
                    :value="old('name')"
                    required
                    autofocus
                    autocomplete="name"
                />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>

            <div class="mt-4">
                <x-input-label for="email" :value="'E-mail'" />
                <x-text-input
                    id="email"
                    class="block mt-1 w-full"
                    type="email"
                    name="email"
                    :value="old('email')"
                    required
                    autocomplete="username"
                />
                <x-input-error :messages="$errors->get('email')" class="mt-1" />
            </div>

            <div class="mt-4">
                <x-input-label for="password" :value="'Senha'" />
                <x-text-input
                    id="password"
                    class="block mt-1 w-full"
                    type="password"
                    name="password"
                    required
                    autocomplete="new-password"
                />
                <x-input-error :messages="$errors->get('password')" class="mt-1" />
            </div>

            <div class="mt-4">
                <x-input-label for="password_confirmation" :value="'Confirmar senha'" />
                <x-text-input
                    id="password_confirmation"
                    class="block mt-1 w-full"
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
            </div>

            <div class="mt-6">
                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                    Cadastrar
                </button>
            </div>

            <div class="mt-4 text-center">
                <a href="{{ route('login') }}" class="text-sm text-blue-600 hover:text-blue-800">
                    Já possui conta? Entrar
                </a>
            </div>
        </form>
    </div>
</x-guest-layout>
