<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Obra360') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans">

    {{-- Top nav --}}
    <header class="h-16 bg-white border-b border-gray-200 shadow-sm flex items-center justify-between px-6">

        {{-- Left: logo + nav items --}}
        <div class="flex items-center gap-6">
            {{-- Logo --}}
            <div class="flex items-center gap-2 flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                     stroke="currentColor" class="w-6 h-6 text-slate-800" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                </svg>
                <span class="text-lg font-bold text-slate-800">Obra360</span>
            </div>

            {{-- Desktop nav items --}}
            <nav class="hidden lg:flex items-center gap-1" aria-label="Navegação principal">
                <a href="#"
                   class="{{ request()->is('minhas-obras*') ? 'text-slate-800 font-semibold' : 'text-gray-600 hover:text-gray-900' }} text-sm font-medium px-3 py-2 rounded-md hover:bg-gray-100">
                    Minhas Obras
                </a>
                <a href="#"
                   class="{{ request()->is('documentos-cliente*') ? 'text-slate-800 font-semibold' : 'text-gray-600 hover:text-gray-900' }} text-sm font-medium px-3 py-2 rounded-md hover:bg-gray-100">
                    Documentos
                </a>
                <a href="#"
                   class="{{ request()->is('mensagens*') ? 'text-slate-800 font-semibold' : 'text-gray-600 hover:text-gray-900' }} text-sm font-medium px-3 py-2 rounded-md hover:bg-gray-100">
                    Mensagens
                </a>
                <a href="/profile"
                   class="{{ request()->is('profile*') ? 'text-slate-800 font-semibold' : 'text-gray-600 hover:text-gray-900' }} text-sm font-medium px-3 py-2 rounded-md hover:bg-gray-100">
                    Meu Perfil
                </a>
            </nav>
        </div>

        {{-- Right: avatar + hamburger (mobile) + Sair --}}
        <div class="flex items-center gap-3">
            @auth
                <div class="hidden lg:flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-slate-800 text-white text-xs flex items-center justify-center font-medium">
                        {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 2)) }}
                    </div>
                    <form method="POST" action="/logout">
                        @csrf
                        <button type="submit"
                                class="text-sm font-medium text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md hover:bg-gray-100">
                            Sair
                        </button>
                    </form>
                </div>
            @endauth

            {{-- Mobile hamburger --}}
            <button type="button"
                    data-client-nav-toggle
                    class="lg:hidden text-gray-500 hover:text-gray-700"
                    aria-label="Abrir menu">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                     stroke="currentColor" class="w-6 h-6" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>
        </div>
    </header>

    {{-- Mobile dropdown nav --}}
    <div data-client-nav
         class="lg:hidden hidden bg-white border-b border-gray-200 shadow-sm px-4 py-2">
        <nav class="flex flex-col gap-1">
            <a href="#"
               class="{{ request()->is('minhas-obras*') ? 'text-slate-800 font-semibold' : 'text-gray-600 hover:text-gray-900' }} text-sm font-medium px-3 py-2 rounded-md hover:bg-gray-100 block">
                Minhas Obras
            </a>
            <a href="#"
               class="{{ request()->is('documentos-cliente*') ? 'text-slate-800 font-semibold' : 'text-gray-600 hover:text-gray-900' }} text-sm font-medium px-3 py-2 rounded-md hover:bg-gray-100 block">
                Documentos
            </a>
            <a href="#"
               class="{{ request()->is('mensagens*') ? 'text-slate-800 font-semibold' : 'text-gray-600 hover:text-gray-900' }} text-sm font-medium px-3 py-2 rounded-md hover:bg-gray-100 block">
                Mensagens
            </a>
            <a href="/profile"
               class="{{ request()->is('profile*') ? 'text-slate-800 font-semibold' : 'text-gray-600 hover:text-gray-900' }} text-sm font-medium px-3 py-2 rounded-md hover:bg-gray-100 block">
                Meu Perfil
            </a>
            @auth
                <form method="POST" action="/logout" class="mt-1 border-t border-gray-100 pt-1">
                    @csrf
                    <button type="submit"
                            class="text-sm font-medium text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md hover:bg-gray-100 w-full text-left">
                        Sair
                    </button>
                </form>
            @endauth
        </nav>
    </div>

    {{-- Main content --}}
    <main class="bg-gray-50 min-h-screen p-6">
        <x-flash-message />
        {{ $slot }}
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var toggleBtn = document.querySelector('[data-client-nav-toggle]');
            var nav = document.querySelector('[data-client-nav]');
            if (toggleBtn && nav) {
                toggleBtn.addEventListener('click', function () {
                    nav.classList.toggle('hidden');
                });
            }
        });
    </script>

</body>
</html>
