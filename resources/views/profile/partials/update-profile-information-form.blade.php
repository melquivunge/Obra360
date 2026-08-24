<section>
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" style="display:flex;flex-direction:column;gap:1.25rem;">
        @csrf
        @method('patch')

        <div>
            <label for="name" style="display:block;font-size:0.8125rem;font-weight:500;margin-bottom:0.375rem;color:var(--color-foreground);">Nome</label>
            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name"
                   style="width:100%;background-color:var(--color-surface);border:1px solid var(--color-border);border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.875rem;color:var(--color-foreground);outline:none;transition:border-color 0.15s;box-sizing:border-box;"
                   onfocus="this.style.borderColor='var(--color-primary)'"
                   onblur="this.style.borderColor='var(--color-border)'">
            @error('name')
                <p style="font-size:0.75rem;color:oklch(0.62 0.21 25);margin-top:0.375rem;">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" style="display:block;font-size:0.8125rem;font-weight:500;margin-bottom:0.375rem;color:var(--color-foreground);">E-mail</label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username"
                   style="width:100%;background-color:var(--color-surface);border:1px solid var(--color-border);border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.875rem;color:var(--color-foreground);outline:none;transition:border-color 0.15s;box-sizing:border-box;"
                   onfocus="this.style.borderColor='var(--color-primary)'"
                   onblur="this.style.borderColor='var(--color-border)'">
            @error('email')
                <p style="font-size:0.75rem;color:oklch(0.62 0.21 25);margin-top:0.375rem;">{{ $message }}</p>
            @enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div style="margin-top:0.5rem;">
                    <p style="font-size:0.8125rem;color:var(--color-muted-foreground);">
                        Seu endereço de e-mail não está verificado.
                        <button form="send-verification"
                                style="background:none;border:none;padding:0;font-size:0.8125rem;color:var(--color-primary);cursor:pointer;text-decoration:underline;">
                            Clique aqui para reenviar o e-mail de verificação.
                        </button>
                    </p>
                    @if (session('status') === 'verification-link-sent')
                        <p style="margin-top:0.375rem;font-size:0.8125rem;color:var(--color-primary);">
                            Um novo link de verificação foi enviado para o seu e-mail.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <label for="phone" style="display:block;font-size:0.8125rem;font-weight:500;margin-bottom:0.375rem;color:var(--color-foreground);">Celular</label>
            <input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" autocomplete="tel"
                   style="width:100%;background-color:var(--color-surface);border:1px solid var(--color-border);border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.875rem;color:var(--color-foreground);outline:none;transition:border-color 0.15s;box-sizing:border-box;"
                   onfocus="this.style.borderColor='var(--color-primary)'"
                   onblur="this.style.borderColor='var(--color-border)'"
                   placeholder="+351 9XX XXX XXX">
            @error('phone')
                <p style="font-size:0.75rem;color:oklch(0.62 0.21 25);margin-top:0.375rem;">{{ $message }}</p>
            @enderror
        </div>

        <div style="display:flex;align-items:center;gap:1rem;">
            <button type="submit" class="btn-primary">Salvar</button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    style="font-size:0.8125rem;color:var(--color-primary);"
                >Salvo.</p>
            @endif
        </div>
    </form>
</section>
