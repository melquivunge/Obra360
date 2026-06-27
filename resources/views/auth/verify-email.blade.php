<x-guest-layout>
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-2xl p-8">
        <div class="flex flex-col items-center">
            <svg class="w-10 h-10 text-slate-800" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
            </svg>
            <span class="text-3xl font-bold text-slate-800 mt-1">Obra360</span>
        </div>

        <h2 class="text-lg font-semibold text-slate-800 text-center mt-4">Verifique seu e-mail</h2>
        <p class="mt-2 text-sm text-gray-500 text-center">
            Obrigado por se cadastrar! Confirme seu e-mail clicando no link que enviamos. Se não recebeu, podemos enviar outro.
        </p>

        @if (session('status') == 'verification-link-sent')
            <div class="mt-4 text-sm text-green-700 bg-green-50 border border-green-200 px-4 py-3 rounded-lg">
                Um novo link de verificação foi enviado para o seu e-mail.
            </div>
        @endif

        <div class="mt-6 space-y-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf

                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                    Reenviar e-mail de verificação
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="text-center">
                @csrf

                <button type="submit" class="text-sm text-blue-600 hover:text-blue-800">
                    Sair
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>
