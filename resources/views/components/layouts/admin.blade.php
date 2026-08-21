<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ \App\Models\Setting::get('company_name', config('app.name', 'Obra360')) }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="background-color:var(--background);color:var(--foreground);">

    {{-- Mobile backdrop --}}
    <div data-sidebar-backdrop
         class="fixed inset-0 z-40 hidden lg:hidden"
         style="background:rgba(0,0,0,0.7)"></div>

    {{-- Sidebar --}}
    <aside data-sidebar
           class="fixed left-0 top-0 h-full flex flex-col z-50 transition-transform duration-200 -translate-x-full lg:translate-x-0"
           style="width:256px;background-color:var(--card);border-right:1px solid var(--border);">

        {{-- Logo --}}
        <div class="px-4 py-5 flex items-center justify-between" style="border-bottom:1px solid var(--border);">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                     stroke="currentColor" class="w-6 h-6" style="color:var(--primary)" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                </svg>
                <span class="text-lg font-bold" style="color:var(--foreground)">{{ \App\Models\Setting::get('company_name', 'Obra360') }}</span>
            </a>
            <button type="button" class="lg:hidden" style="color:var(--muted-foreground)"
                    onclick="document.querySelector('[data-sidebar]').classList.add('-translate-x-full');document.querySelector('[data-sidebar-backdrop]').classList.add('hidden');"
                    aria-label="Fechar menu">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                     stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 py-3 overflow-y-auto px-2">
            @php
                $navItems = [
                    ['href' => route('admin.dashboard'),       'label' => 'Dashboard',      'match' => 'admin', 'exact' => true,
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>'],
                    ['href' => route('admin.clients.index'),   'label' => 'Clientes',        'match' => 'admin/clientes', 'roles' => ['admin'],
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>'],
                    ['href' => route('admin.projects.index'),  'label' => 'Obras',           'match' => 'admin/obras',
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z"/>'],
                    ['href' => route('admin.messages.index'),  'label' => 'Mensagens',       'match' => 'admin/mensagens',
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/>'],
                    ['href' => route('admin.reports.index'),   'label' => 'Relatórios',      'match' => 'admin/relatorios',
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>'],
                    ['href' => route('users.index'),           'label' => 'Usuários',        'match' => 'usuarios', 'roles' => ['admin'],
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/>'],
                    ['href' => route('admin.audit.index'),     'label' => 'Auditoria',       'match' => 'admin/auditoria', 'roles' => ['admin'],
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z"/>'],
                    ['href' => route('admin.settings.index'),  'label' => 'Configurações',   'match' => 'admin/configuracoes', 'roles' => ['admin'],
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>'],
                ];
            @endphp

            @foreach($navItems as $item)
                @continue(isset($item['roles']) && ! in_array(auth()->user()->role, $item['roles'], true))
                @php $isActive = request()->is($item['match']) || (empty($item['exact']) && request()->is($item['match'].'/*')); @endphp
                <a href="{{ $item['href'] }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg mb-0.5 text-sm font-medium transition-colors"
                   style="{{ $isActive ? 'color:var(--primary);background-color:oklch(0.74 0.135 78 / 0.1);border-left:2px solid var(--primary);padding-left:10px;' : 'color:var(--muted-foreground);border-left:2px solid transparent;' }}"
                   onmouseover="{{ $isActive ? '' : "this.style.backgroundColor='var(--surface)';this.style.color='var(--foreground)';" }}"
                   onmouseout="{{ $isActive ? '' : "this.style.backgroundColor='';this.style.color='var(--muted-foreground)';" }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="currentColor" class="w-5 h-5 shrink-0" aria-hidden="true">
                        {!! $item['icon'] !!}
                    </svg>
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        {{-- Logout --}}
        <div class="px-2 py-3" style="border-top:1px solid var(--border);">
            @auth
            <div class="flex items-center gap-2 px-3 py-2 mb-1">
                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                     style="background:var(--primary);color:var(--primary-foreground)">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-medium truncate" style="color:var(--foreground)">{{ auth()->user()->name }}</p>
                    <p class="text-xs truncate" style="color:var(--muted-foreground)">{{ ucfirst(auth()->user()->role) }}</p>
                </div>
            </div>
            @endauth
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg w-full text-left text-sm font-medium transition-colors"
                        style="color:var(--muted-foreground)"
                        onmouseover="this.style.backgroundColor='var(--surface)';this.style.color='var(--foreground)'"
                        onmouseout="this.style.backgroundColor='';this.style.color='var(--muted-foreground)'">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="currentColor" class="w-5 h-5 shrink-0" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/>
                    </svg>
                    Sair
                </button>
            </form>
        </div>
    </aside>

    {{-- Main wrapper --}}
    <div class="lg:min-h-screen lg:ml-64" style="background-color:var(--background);">

        {{-- Topbar --}}
        <header class="h-14 flex items-center justify-between px-4 lg:px-6 sticky top-0 z-30"
                style="background-color:var(--card);border-bottom:1px solid oklch(0.74 0.135 78 / 0.2);">
            <div class="flex items-center gap-3">
                <button type="button" data-sidebar-toggle class="lg:hidden p-1 rounded-md"
                        style="color:var(--muted-foreground)" aria-label="Abrir menu">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                    </svg>
                </button>
                <nav class="text-sm" style="color:var(--muted-foreground)" aria-label="Breadcrumb">
                    {{ $breadcrumb ?? '' }}
                </nav>
            </div>
        </header>

        {{-- Content --}}
        <main style="padding:1rem;">
            <x-flash-message />
            {{ $slot }}
        </main>
    </div>

    <script>
    // Mobile sidebar toggle
    document.addEventListener('DOMContentLoaded', function() {
        var toggle = document.querySelector('[data-sidebar-toggle]');
        var sidebar = document.querySelector('[data-sidebar]');
        var backdrop = document.querySelector('[data-sidebar-backdrop]');
        if (!toggle || !sidebar || !backdrop) return;
        toggle.addEventListener('click', function() {
            sidebar.classList.remove('-translate-x-full');
            backdrop.classList.remove('hidden');
        });
        backdrop.addEventListener('click', function() {
            sidebar.classList.add('-translate-x-full');
            backdrop.classList.add('hidden');
        });
    });
    </script>
</body>
</html>
