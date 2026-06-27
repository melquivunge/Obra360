<x-guest-layout>
    <div style="width:100%;max-width:26rem;background-color:var(--card);border:1px solid var(--border);border-radius:1rem;padding:2rem;box-shadow:0 20px 60px rgb(0 0 0 / 0.5);">

        {{-- Logo --}}
        <div style="text-align:center;margin-bottom:2rem;">
            <div style="display:inline-flex;align-items:center;justify-content:center;width:3.25rem;height:3.25rem;background-color:oklch(0.74 0.135 78 / 0.15);border-radius:0.875rem;margin-bottom:0.875rem;">
                <svg style="width:1.625rem;height:1.625rem;color:var(--primary);" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                </svg>
            </div>
            <h1 style="font-size:1.5rem;font-weight:700;color:var(--foreground);margin:0 0 0.25rem;">Obra360</h1>
            <p style="font-size:0.875rem;color:var(--muted-foreground);margin:0;">Acesse sua conta para continuar</p>
        </div>

        {{-- Error messages --}}
        @if ($errors->any())
            <div style="background-color:oklch(0.62 0.21 25 / 0.12);border:1px solid oklch(0.62 0.21 25 / 0.4);border-radius:0.5rem;padding:0.75rem 1rem;margin-bottom:1.25rem;">
                @foreach ($errors->all() as $error)
                    <p style="color:var(--destructive);font-size:0.875rem;margin:0;">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{-- Session status --}}
        @if (session('status'))
            <div style="background-color:oklch(0.6 0.18 145 / 0.12);border:1px solid oklch(0.6 0.18 145 / 0.4);border-radius:0.5rem;padding:0.75rem 1rem;margin-bottom:1.25rem;">
                <p style="color:var(--success);font-size:0.875rem;margin:0;">{{ session('status') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" style="display:flex;flex-direction:column;gap:1.125rem;">
            @csrf

            <div>
                <label for="email" class="field-label">E-mail</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                       placeholder="admin@obra360.test">
            </div>

            <div>
                <label for="password" class="field-label">Senha</label>
                <input id="password" type="password" name="password" required autocomplete="current-password"
                       placeholder="••••••••">
            </div>

            <div style="display:flex;align-items:center;justify-content:space-between;">
                <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
                    <input type="checkbox" name="remember" style="width:1rem;height:1rem;border-radius:0.25rem;">
                    <span style="font-size:0.8125rem;color:var(--muted-foreground);">Lembrar-me</span>
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" style="font-size:0.8125rem;color:var(--primary);">Esqueci a senha</a>
                @endif
            </div>

            <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:0.625rem 1rem;font-size:0.9375rem;margin-top:0.25rem;">
                Entrar
            </button>
        </form>
    </div>
</x-guest-layout>
