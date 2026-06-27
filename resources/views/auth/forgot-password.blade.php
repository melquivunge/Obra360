<x-guest-layout>
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-2xl p-8">

        {{-- Logo block --}}
        <div class="flex flex-col items-center">
            <svg class="w-10 h-10 text-slate-800" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
            </svg>
            <span class="text-3xl font-bold text-slate-800 mt-1">Obra360</span>
        </div>

        <h2 class="text-lg font-semibold text-slate-800 text-center mt-4">Redefinir senha</h2>
        <p class="text-sm text-gray-500 text-center mt-1">
            Insira seu e-mail para receber o link de redefinição.
        </p>

        {{-- Success status --}}
        @if (session('status'))
            <div class="mt-4 text-sm text-green-600 bg-green-50 border border-green-200 px-4 py-3 rounded-lg">
                Link enviado! Verifique seu e-mail.
            </div>
        @endif

        {{-- Error messages --}}
        @if ($errors->any())
            <div class="mt-4">
                @foreach ($errors->all() as $error)
                    <p class="text-red-600 text-sm">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="mt-6">
            @csrf

            {{-- Email --}}
            <div>
                <x-input-label for="email" :value="__('E-mail')" />
                <x-text-input
                    id="email"
                    class="block mt-1 w-full"
                    type="email"
                    name="email"
                    :value="old('email')"
                    required
                    autofocus
                />
                <x-input-error :messages="$errors->get('email')" class="mt-1" />
            </div>

            {{-- Submit button --}}
            <div class="mt-4">
                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                    Enviar link de redefinição
                </button>
            </div>
        </form>

        <div class="mt-4 text-center">
            <a href="{{ route('login') }}" class="text-sm text-blue-600 hover:text-blue-800">
                Voltar para o login
            </a>
        </div>
    </div>
</x-guest-layout>
