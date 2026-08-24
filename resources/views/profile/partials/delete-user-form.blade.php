<section x-data="{ open: {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }} }">
    <button type="button"
            @click="open = true"
            style="background-color:oklch(0.62 0.21 25);color:white;border:none;border-radius:0.5rem;padding:0.5rem 1rem;font-size:0.875rem;font-weight:600;cursor:pointer;">
        Excluir conta
    </button>

    {{-- Modal overlay --}}
    <div x-show="open"
         x-transition
         style="position:fixed;inset:0;z-index:50;display:flex;align-items:center;justify-content:center;background-color:rgba(0,0,0,0.6);"
         @click.self="open = false"
         x-cloak>

        <div style="background-color:var(--color-card);border:1px solid var(--color-border);border-radius:0.875rem;padding:1.5rem;max-width:28rem;width:100%;margin:1rem;box-shadow:0 20px 60px rgba(0,0,0,0.4);">
            <h2 style="font-size:0.9375rem;font-weight:700;color:var(--color-foreground);margin-bottom:0.75rem;">
                Tem certeza de que deseja excluir sua conta?
            </h2>
            <p style="font-size:0.8125rem;color:var(--color-muted-foreground);margin-bottom:1.5rem;line-height:1.5;">
                Após excluir a conta, todos os recursos e dados serão removidos permanentemente. Informe sua senha para confirmar.
            </p>

            <form method="post" action="{{ route('profile.destroy') }}">
                @csrf
                @method('delete')

                <div style="margin-bottom:1.25rem;">
                    <label for="password_delete" style="display:block;font-size:0.8125rem;font-weight:500;margin-bottom:0.375rem;color:var(--color-foreground);">Senha</label>
                    <x-password-input id="password_delete" name="password"
                                      placeholder="Senha"
                                      style="width:100%;background-color:var(--color-surface);border:1px solid var(--color-border);border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.875rem;color:var(--color-foreground);outline:none;transition:border-color 0.15s;box-sizing:border-box;"
                                      class="focus-danger" />
                    @error('password', 'userDeletion')
                        <p style="font-size:0.75rem;color:oklch(0.62 0.21 25);margin-top:0.375rem;">{{ $message }}</p>
                    @enderror
                </div>

                <div style="display:flex;justify-content:flex-end;gap:0.75rem;">
                    <button type="button"
                            @click="open = false"
                            style="background-color:var(--color-surface);color:var(--color-foreground);border:1px solid var(--color-border);border-radius:0.5rem;padding:0.5rem 1rem;font-size:0.875rem;font-weight:500;cursor:pointer;">
                        Cancelar
                    </button>
                    <button type="submit"
                            style="background-color:oklch(0.62 0.21 25);color:white;border:none;border-radius:0.5rem;padding:0.5rem 1rem;font-size:0.875rem;font-weight:600;cursor:pointer;">
                        Excluir conta
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
