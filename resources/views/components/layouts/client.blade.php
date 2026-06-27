<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ \App\Models\Setting::get('company_name', config('app.name', 'Obra360')) }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="background-color: var(--color-background); color: var(--color-foreground); min-height: 100vh;">

    {{-- ── Mobile nav backdrop ─────────────────────────────── --}}
    <div id="client-nav-backdrop"
         class="fixed inset-0 z-40 hidden lg:hidden"
         style="background-color: rgba(0,0,0,0.6);"
         onclick="toggleClientNav(false)"></div>

    {{-- ── Slide-in nav (mobile) ───────────────────────────── --}}
    <div id="client-nav-mobile"
         class="fixed top-0 left-0 h-full z-50 w-64 flex flex-col lg:hidden"
         style="background-color: var(--color-card); border-right: 1px solid var(--color-border); transform: translateX(-100%); transition: transform 0.25s ease;">
        <div class="flex items-center justify-between px-4 py-5" style="border-bottom: 1px solid var(--color-border);">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 flex-shrink-0" style="color: var(--color-primary);" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21" />
                </svg>
                <span class="text-lg font-bold" style="color: var(--color-foreground);">{{ \App\Models\Setting::get('company_name', 'Obra360') }}</span>
            </div>
            <button onclick="toggleClientNav(false)" style="color: var(--color-muted-foreground);" aria-label="Fechar menu">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <nav class="flex-1 py-3 px-3 space-y-1">
            @php $onDash = request()->routeIs('portal.dashboard'); @endphp
            <a href="{{ route('portal.dashboard') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
               style="color:{{ $onDash ? 'var(--color-primary)' : 'var(--color-muted-foreground)' }}; background-color:{{ $onDash ? 'oklch(0.74 0.135 78 / 0.1)' : 'transparent' }};"
               onmouseover="if(!{{ $onDash ? 'true' : 'false' }}) this.style.backgroundColor='var(--color-surface)'"
               onmouseout="if(!{{ $onDash ? 'true' : 'false' }}) this.style.backgroundColor=''">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 flex-shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21" /></svg>
                Minhas Obras
            </a>
            @php $onProfile = request()->routeIs('profile.edit'); @endphp
            <a href="{{ route('profile.edit') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
               style="color:{{ $onProfile ? 'var(--color-primary)' : 'var(--color-muted-foreground)' }}; background-color:{{ $onProfile ? 'oklch(0.74 0.135 78 / 0.1)' : 'transparent' }};"
               onmouseover="if(!{{ $onProfile ? 'true' : 'false' }}) this.style.backgroundColor='var(--color-surface)'"
               onmouseout="if(!{{ $onProfile ? 'true' : 'false' }}) this.style.backgroundColor=''">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 flex-shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                Meu Perfil
            </a>
        </nav>
        <div class="px-4 pb-4" style="border-top: 1px solid var(--color-border); padding-top: 1rem;">
            <form method="POST" action="/logout">
                @csrf
                <button type="submit" class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg text-sm font-medium transition-colors" style="color: var(--color-muted-foreground); background:none; border:none; cursor:pointer; width:100%;" onmouseover="this.style.backgroundColor='var(--color-surface)';this.style.color='var(--color-foreground)'" onmouseout="this.style.backgroundColor='';this.style.color='var(--color-muted-foreground)'">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" /></svg>
                    Sair
                </button>
            </form>
        </div>
    </div>

    {{-- ── Desktop sidebar (lg+) ───────────────────────────── --}}
    <aside class="hidden lg:flex flex-col fixed top-0 left-0 h-full z-30"
           style="width:256px;background-color: var(--color-card); border-right: 1px solid var(--color-border);">
        {{-- Brand --}}
        <div class="flex items-center gap-2 px-4 py-5" style="border-bottom: 1px solid var(--color-border);">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 flex-shrink-0" style="color: var(--color-primary);">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21" />
            </svg>
            <span class="text-lg font-bold" style="color: var(--color-foreground);">{{ \App\Models\Setting::get('company_name', 'Obra360') }}</span>
        </div>

        {{-- Nav links --}}
        <nav class="flex-1 px-3 py-4 space-y-0.5">
            @php $onDash = request()->routeIs('portal.dashboard'); @endphp
            <a href="{{ route('portal.dashboard') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium w-full"
               style="color:{{ $onDash ? 'var(--color-primary)' : 'var(--color-muted-foreground)' }}; background-color:{{ $onDash ? 'oklch(0.74 0.135 78 / 0.1)' : 'transparent' }}; border-left:{{ $onDash ? '2px solid var(--color-primary)' : '2px solid transparent' }}; padding-left:{{ $onDash ? '10px' : '12px' }}; transition: background-color 0.15s, color 0.15s;"
               onmouseover="this.style.backgroundColor='{{ $onDash ? 'oklch(0.74 0.135 78 / 0.15)' : 'var(--color-surface)' }}'"
               onmouseout="this.style.backgroundColor='{{ $onDash ? 'oklch(0.74 0.135 78 / 0.1)' : 'transparent' }}'">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 flex-shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21" />
                </svg>
                Minhas Obras
            </a>

            @php $onProfile = request()->routeIs('profile.edit'); @endphp
            <a href="{{ route('profile.edit') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium w-full"
               style="color:{{ $onProfile ? 'var(--color-primary)' : 'var(--color-muted-foreground)' }}; background-color:{{ $onProfile ? 'oklch(0.74 0.135 78 / 0.1)' : 'transparent' }}; border-left:{{ $onProfile ? '2px solid var(--color-primary)' : '2px solid transparent' }}; padding-left:{{ $onProfile ? '10px' : '12px' }}; transition: background-color 0.15s, color 0.15s;"
               onmouseover="this.style.backgroundColor='{{ $onProfile ? 'oklch(0.74 0.135 78 / 0.15)' : 'var(--color-surface)' }}'"
               onmouseout="this.style.backgroundColor='{{ $onProfile ? 'oklch(0.74 0.135 78 / 0.1)' : 'transparent' }}'">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 flex-shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
                Meu Perfil
            </a>
        </nav>

        {{-- User info + logout --}}
        @auth
        <div class="px-2 py-3" style="border-top: 1px solid var(--color-border);">
            <div class="flex items-center gap-2 px-3 py-2 mb-1">
                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                     style="background: var(--color-primary); color: var(--color-primary-foreground);">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-medium truncate" style="color: var(--color-foreground);">{{ auth()->user()->name }}</p>
                    <p class="text-xs truncate" style="color: var(--color-muted-foreground);">Cliente</p>
                </div>
            </div>
            <form method="POST" action="/logout">
                @csrf
                <button type="submit"
                        class="flex items-center gap-3 w-full px-3 py-2 rounded-lg text-left text-sm font-medium"
                        style="color: var(--color-muted-foreground); background:none; border:none; cursor:pointer; transition: background-color 0.15s, color 0.15s;"
                        onmouseover="this.style.backgroundColor='var(--color-surface)';this.style.color='var(--color-foreground)'"
                        onmouseout="this.style.backgroundColor='';this.style.color='var(--color-muted-foreground)'">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 flex-shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                    </svg>
                    Sair
                </button>
            </form>
        </div>
        @endauth
    </aside>

    {{-- ── Topbar (mobile hamburger + desktop user info) ───── --}}
    <header class="sticky top-0 z-30 flex items-center justify-between px-4 md:px-6 h-14 lg:ml-64"
            style="background-color: var(--color-card); border-bottom: 1px solid oklch(0.74 0.135 78 / 0.2);">
        {{-- Mobile: hamburger + brand --}}
        <div class="flex items-center gap-3 lg:hidden">
            <button onclick="toggleClientNav(true)" style="color: var(--color-muted-foreground);" aria-label="Abrir menu">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
            </button>
            <a href="{{ route('portal.dashboard') }}" class="flex items-center gap-2 no-underline">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 flex-shrink-0" style="color: var(--color-primary);">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21" />
                </svg>
                <span class="font-bold text-sm" style="color: var(--color-primary);">{{ \App\Models\Setting::get('company_name', 'Obra360') }}</span>
            </a>
        </div>

        {{-- Desktop: page context / empty left side --}}
        <div class="hidden lg:block"></div>

        {{-- Right: icons + user chip --}}
        @auth
        @php
            $unreadMessages = 0;
            $unreadNotifications = 0;
            $recentUnreadMsgs = collect();
            $msgProject = null;
            if (auth()->check() && auth()->user()->client_id) {
                $clientId = auth()->user()->client_id;
                $lastViewed = session('messages_last_viewed_at')
                    ? \Carbon\Carbon::createFromTimestamp(session('messages_last_viewed_at'))
                    : now()->subDays(30);

                $recentUnreadMsgs = \App\Models\Message::whereHas('project', fn($q) =>
                        $q->where('client_id', $clientId)
                    )
                    ->where('user_id', '!=', auth()->id())
                    ->where('created_at', '>', $lastViewed)
                    ->with(['project', 'user'])
                    ->latest()
                    ->take(6)
                    ->get();

                $unreadMessages = $recentUnreadMsgs->count();
                $msgProject = \App\Models\Project::where('client_id', $clientId)->first();
            }
            $msgHref = $msgProject
                ? route('portal.projects.messages', $msgProject)
                : route('portal.dashboard');
        @endphp
        <div class="flex items-center gap-1">
            {{-- Mensagens --}}
            <div id="msg-wrapper" style="position:relative;">
                <button type="button"
                    id="msg-btn"
                    style="position:relative;display:inline-flex;align-items:center;justify-content:center;width:2.25rem;height:2.25rem;border-radius:0.5rem;color:var(--color-muted-foreground);transition:background-color 0.15s,color 0.15s;background:none;border:none;cursor:pointer;"
                    title="Mensagens"
                    aria-label="Mensagens"
                    onclick="toggleMsgDropdown(event)"
                    onmouseover="this.style.backgroundColor='var(--color-surface)';this.style.color='var(--color-foreground)'"
                    onmouseout="this.style.backgroundColor='';this.style.color='var(--color-muted-foreground)'">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.125rem;height:1.125rem;" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                    <span id="msg-badge" style="position:absolute;top:0.125rem;right:0.125rem;min-width:1rem;height:1rem;border-radius:9999px;background-color:var(--color-primary);color:var(--color-primary-foreground);font-size:0.625rem;font-weight:700;display:{{ $unreadMessages > 0 ? 'flex' : 'none' }};align-items:center;justify-content:center;padding:0 0.2rem;line-height:1;">
                        {{ $unreadMessages > 9 ? '9+' : $unreadMessages }}
                    </span>
                </button>

                {{-- Dropdown --}}
                <div id="msg-dropdown"
                     style="display:none;position:absolute;top:calc(100% + 0.5rem);right:0;width:320px;background-color:var(--color-card);border:1px solid var(--color-border);border-radius:0.75rem;box-shadow:0 8px 30px rgb(0 0 0 / 0.4);z-index:100;overflow:hidden;">

                    {{-- Header --}}
                    <div style="padding:0.875rem 1rem;border-bottom:1px solid var(--color-border);display:flex;align-items:center;justify-content:space-between;">
                        <p style="font-size:0.8125rem;font-weight:600;color:var(--color-foreground);margin:0;">Mensagens não lidas</p>
                        @if($msgProject)
                        <a href="{{ $msgHref }}" style="font-size:0.75rem;color:var(--color-primary);text-decoration:none;font-weight:500;"
                           onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                            Ver todas
                        </a>
                        @endif
                    </div>

                    @if($recentUnreadMsgs->isEmpty())
                        <div style="padding:1.5rem 1rem;text-align:center;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.5rem;height:1.5rem;color:var(--color-muted-foreground);opacity:0.4;margin:0 auto 0.5rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                            <p style="font-size:0.8125rem;color:var(--color-muted-foreground);margin:0;">Sem mensagens não lidas</p>
                        </div>
                    @else
                        <div style="position:relative;">
                            <div id="msg-scroll-list" style="max-height:300px;overflow-y:auto;">
                                @foreach($recentUnreadMsgs as $msg)
                                @php
                                    $msgProjectItem = $msg->project;
                                    $msgItemHref = $msgProjectItem
                                        ? route('portal.projects.messages', $msgProjectItem)
                                        : route('portal.dashboard');
                                @endphp
                                <a href="{{ $msgItemHref }}"
                                   style="display:block;padding:0.75rem 1rem;border-bottom:1px solid var(--color-border);text-decoration:none;transition:background-color 0.1s;"
                                   onmouseover="this.style.backgroundColor='var(--color-surface)'"
                                   onmouseout="this.style.backgroundColor=''">
                                    <div style="display:flex;align-items:flex-start;gap:0.625rem;">
                                        <div style="width:1.75rem;height:1.75rem;border-radius:9999px;background-color:oklch(0.74 0.135 78 / 0.2);color:var(--color-primary);display:flex;align-items:center;justify-content:center;font-size:0.625rem;font-weight:700;flex-shrink:0;margin-top:0.125rem;">
                                            {{ strtoupper(substr($msg->user?->name ?? 'E', 0, 2)) }}
                                        </div>
                                        <div style="min-width:0;flex:1;">
                                            <div style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;margin-bottom:0.2rem;">
                                                <p style="font-size:0.8125rem;font-weight:600;color:var(--color-foreground);margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                                    {{ $msg->user?->name ?? 'Equipa' }}
                                                </p>
                                                <span style="font-size:0.6875rem;color:var(--color-muted-foreground);white-space:nowrap;flex-shrink:0;">
                                                    {{ $msg->created_at->diffForHumans() }}
                                                </span>
                                            </div>
                                            <p style="font-size:0.6875rem;color:var(--color-primary);margin:0 0 0.25rem;font-weight:500;">
                                                {{ $msgProjectItem?->name ?? '—' }}
                                            </p>
                                            <p style="font-size:0.8125rem;color:var(--color-muted-foreground);margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                                {{ Str::limit($msg->body, 60) }}
                                            </p>
                                        </div>
                                    </div>
                                </a>
                                @endforeach
                            </div>
                            <div id="msg-scroll-indicator"
                                 aria-hidden="true"
                                 style="position:absolute;bottom:0;left:0;right:0;height:3rem;background:linear-gradient(to bottom,transparent,var(--color-card));pointer-events:none;transition:opacity 0.2s;opacity:0;"></div>
                        </div>

                        {{-- Footer --}}
                        <div style="padding:0.75rem 1rem;border-top:1px solid var(--color-border);">
                            <a href="{{ $msgHref }}"
                               style="display:block;text-align:center;font-size:0.8125rem;font-weight:600;color:var(--color-primary);text-decoration:none;padding:0.5rem;border-radius:0.5rem;transition:background-color 0.15s;"
                               onmouseover="this.style.backgroundColor='oklch(0.74 0.135 78 / 0.1)'"
                               onmouseout="this.style.backgroundColor=''">
                                Ir para mensagens da obra
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Notificações --}}
            @php
                $recentUpdates = collect();
                if(auth()->check() && auth()->user()->client_id) {
                    $recentUpdates = \App\Models\ProjectUpdate::whereHas('project', fn($q) => $q->where('client_id', auth()->user()->client_id))->latest()->take(5)->get();
                }
            @endphp
            <div id="notif-wrapper" style="position:relative;">
                <button type="button"
                   id="notif-btn"
                   style="position:relative;display:inline-flex;align-items:center;justify-content:center;width:2.25rem;height:2.25rem;border-radius:0.5rem;color:var(--color-muted-foreground);transition:background-color 0.15s,color 0.15s;background:none;border:none;cursor:pointer;"
                   title="Notificações"
                   aria-label="Notificações"
                   onclick="toggleNotifDropdown(event)"
                   onmouseover="this.style.backgroundColor='var(--color-surface)';this.style.color='var(--color-foreground)'"
                   onmouseout="this.style.backgroundColor='';this.style.color='var(--color-muted-foreground)'">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.125rem;height:1.125rem;" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>
                    @if($unreadNotifications > 0)
                    <span style="position:absolute;top:0.125rem;right:0.125rem;min-width:1rem;height:1rem;border-radius:9999px;background-color:oklch(0.62 0.21 25);color:white;font-size:0.625rem;font-weight:700;display:flex;align-items:center;justify-content:center;padding:0 0.2rem;line-height:1;">
                        {{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}
                    </span>
                    @endif
                </button>

                {{-- Dropdown panel --}}
                <div id="notif-dropdown"
                     style="display:none;position:absolute;top:calc(100% + 0.5rem);right:0;width:320px;background-color:var(--color-card);border:1px solid var(--color-border);border-radius:0.75rem;box-shadow:0 8px 30px rgb(0 0 0 / 0.4);z-index:100;overflow:hidden;">
                    <div style="padding:0.875rem 1rem;border-bottom:1px solid var(--color-border);">
                        <p style="font-size:0.8125rem;font-weight:600;color:var(--color-foreground);margin:0;">Actualizações recentes</p>
                    </div>
                    @if($recentUpdates->isEmpty())
                        <div style="padding:1.25rem 1rem;text-align:center;">
                            <p style="font-size:0.8125rem;color:var(--color-muted-foreground);margin:0;">Sem actualizações recentes</p>
                        </div>
                    @else
                        <div style="position:relative;">
                            <div id="notif-scroll-list" style="max-height:320px;overflow-y:auto;">
                                @foreach($recentUpdates as $update)
                                <div style="padding:0.75rem 1rem;border-bottom:1px solid var(--color-border);transition:background-color 0.1s;"
                                     onmouseover="this.style.backgroundColor='var(--color-surface)'"
                                     onmouseout="this.style.backgroundColor=''">
                                    <p style="font-size:0.8125rem;font-weight:500;color:var(--color-foreground);margin:0 0 0.3rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $update->title }}</p>
                                    <div style="display:flex;align-items:center;gap:0.5rem;">
                                        <span style="font-size:0.6875rem;padding:0.125rem 0.375rem;border-radius:9999px;background-color:oklch(0.74 0.135 78 / 0.15);color:var(--color-primary);font-weight:500;">
                                            {{ \App\Models\ProjectUpdate::TYPES[$update->type] ?? $update->type }}
                                        </span>
                                        <span style="font-size:0.6875rem;color:var(--color-muted-foreground);">{{ $update->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <div id="notif-scroll-indicator"
                                 aria-hidden="true"
                                 style="position:absolute;bottom:0;left:0;right:0;height:3rem;background:linear-gradient(to bottom,transparent,var(--color-card));pointer-events:none;transition:opacity 0.2s;opacity:0;"></div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Divisor --}}
            <div style="width:1px;height:1.25rem;background-color:var(--color-border);margin:0 0.25rem;"></div>

            {{-- Avatar + nome --}}
            <div class="flex items-center gap-2 pl-1">
                <span class="hidden md:block text-sm" style="color:var(--color-muted-foreground);">{{ auth()->user()->name }}</span>
                <div style="width:2rem;height:2rem;border-radius:9999px;background-color:var(--color-primary);color:var(--color-primary-foreground);display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;flex-shrink:0;">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                </div>
            </div>
        </div>
        @endauth
    </header>

    {{-- ── Page content ────────────────────────────────────── --}}
    <main class="lg:ml-64" style="min-height: calc(100vh - 3.5rem);">
        <div style="max-width:1200px;margin:0 auto;">
            <x-flash-message />
            {{ $slot }}
        </div>
    </main>

    <script>
        function toggleClientNav(open) {
            const nav = document.getElementById('client-nav-mobile');
            const backdrop = document.getElementById('client-nav-backdrop');
            if (open) {
                nav.style.transform = 'translateX(0)';
                backdrop.classList.remove('hidden');
            } else {
                nav.style.transform = 'translateX(-100%)';
                backdrop.classList.add('hidden');
            }
        }

        function toggleMsgDropdown(e) {
            e.stopPropagation();
            const dropdown = document.getElementById('msg-dropdown');
            if (!dropdown) return;
            const isOpen = dropdown.style.display === 'block';
            // Fechar notificações se abertas
            const notifDropdown = document.getElementById('notif-dropdown');
            if (notifDropdown) notifDropdown.style.display = 'none';

            dropdown.style.display = isOpen ? 'none' : 'block';
            if (!isOpen) {
                requestAnimationFrame(setupMsgScroll);
                // Marcar como lidas via session
                fetch('/portal/mensagens/marcar-lidas', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                        'Accept': 'application/json'
                    }
                }).then(() => {
                    const badge = document.getElementById('msg-badge');
                    if (badge) badge.style.display = 'none';
                }).catch(() => {
                    const badge = document.getElementById('msg-badge');
                    if (badge) badge.style.display = 'none';
                });
            }
        }

        function setupMsgScroll() {
            const list = document.getElementById('msg-scroll-list');
            const indicator = document.getElementById('msg-scroll-indicator');
            if (!list || !indicator) return;
            function updateIndicator() {
                const canScroll = list.scrollHeight > list.clientHeight;
                const atBottom = list.scrollTop + list.clientHeight >= list.scrollHeight - 4;
                indicator.style.opacity = (canScroll && !atBottom) ? '1' : '0';
            }
            list.onscroll = updateIndicator;
            updateIndicator();
        }

        function toggleNotifDropdown(e) {
            e.stopPropagation();
            const dropdown = document.getElementById('notif-dropdown');
            if (!dropdown) return;
            // Fechar mensagens se abertas
            const msgDropdown = document.getElementById('msg-dropdown');
            if (msgDropdown) msgDropdown.style.display = 'none';
            const isOpen = dropdown.style.display === 'block';
            dropdown.style.display = isOpen ? 'none' : 'block';
            if (!isOpen) {
                requestAnimationFrame(setupNotifScroll);
            }
        }

        function setupNotifScroll() {
            const list = document.getElementById('notif-scroll-list');
            const indicator = document.getElementById('notif-scroll-indicator');
            if (!list || !indicator) return;
            function updateIndicator() {
                const canScroll = list.scrollHeight > list.clientHeight;
                const atBottom = list.scrollTop + list.clientHeight >= list.scrollHeight - 4;
                indicator.style.opacity = (canScroll && !atBottom) ? '1' : '0';
            }
            // Evitar listeners duplicados em re-aberturas do dropdown
            list.onscroll = updateIndicator;
            updateIndicator();
        }

        document.addEventListener('click', function (e) {
            const notifWrapper = document.getElementById('notif-wrapper');
            const notifDropdown = document.getElementById('notif-dropdown');
            if (notifDropdown && notifWrapper && !notifWrapper.contains(e.target)) {
                notifDropdown.style.display = 'none';
            }
            const msgWrapper = document.getElementById('msg-wrapper');
            const msgDropdown = document.getElementById('msg-dropdown');
            if (msgDropdown && msgWrapper && !msgWrapper.contains(e.target)) {
                msgDropdown.style.display = 'none';
            }
        });
    </script>
</body>
</html>
