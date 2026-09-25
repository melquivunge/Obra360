<section>
    <form method="post" action="{{ route('password.update') }}" style="display:flex;flex-direction:column;gap:1.25rem;">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" style="display:block;font-size:0.8125rem;font-weight:500;margin-bottom:0.375rem;color:var(--color-foreground);">{{ __('Senha atual') }}</label>
            <x-password-input id="update_password_current_password" name="current_password" autocomplete="current-password"
                              style="width:100%;background-color:var(--color-surface);border:1px solid var(--color-border);border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.875rem;color:var(--color-foreground);outline:none;transition:border-color 0.15s;box-sizing:border-box;"
                              class="focus-primary" />
            @error('current_password', 'updatePassword')
                <p style="font-size:0.75rem;color:oklch(0.62 0.21 25);margin-top:0.375rem;">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="update_password_password" style="display:block;font-size:0.8125rem;font-weight:500;margin-bottom:0.375rem;color:var(--color-foreground);">{{ __('Nova senha') }}</label>
            <x-password-input id="update_password_password" name="password" autocomplete="new-password"
                              style="width:100%;background-color:var(--color-surface);border:1px solid var(--color-border);border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.875rem;color:var(--color-foreground);outline:none;transition:border-color 0.15s;box-sizing:border-box;"
                              class="focus-primary" />
            @error('password', 'updatePassword')
                <p style="font-size:0.75rem;color:oklch(0.62 0.21 25);margin-top:0.375rem;">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="update_password_password_confirmation" style="display:block;font-size:0.8125rem;font-weight:500;margin-bottom:0.375rem;color:var(--color-foreground);">{{ __('Confirmar senha') }}</label>
            <x-password-input id="update_password_password_confirmation" name="password_confirmation" autocomplete="new-password"
                              style="width:100%;background-color:var(--color-surface);border:1px solid var(--color-border);border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.875rem;color:var(--color-foreground);outline:none;transition:border-color 0.15s;box-sizing:border-box;"
                              class="focus-primary" />
            @error('password_confirmation', 'updatePassword')
                <p style="font-size:0.75rem;color:oklch(0.62 0.21 25);margin-top:0.375rem;">{{ $message }}</p>
            @enderror
        </div>

        <div style="display:flex;align-items:center;gap:1rem;">
            <button type="submit" class="btn-primary">{{ __('Salvar') }}</button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    style="font-size:0.8125rem;color:var(--color-primary);"
                >{{ __('Salvo.') }}</p>
            @endif
        </div>
    </form>
</section>
