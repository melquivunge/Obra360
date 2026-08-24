@php
    $layout = auth()->user()?->isClient() ? 'client-layout' : 'admin-layout';
    $roleLabels = ['admin'=>'Administrador','manager'=>'Gestor','financial'=>'Financeiro','client'=>'Cliente'];
    $roleLabel = $roleLabels[$user->role] ?? $user->role;
@endphp
<x-dynamic-component :component="$layout">
    <div class="p-4 md:p-8 space-y-6">
        {{-- Header --}}
        <div>
            <h1 style="font-size:1.375rem;font-weight:700;letter-spacing:-0.01em;">Meu Perfil</h1>
            <p style="color:var(--color-muted-foreground);font-size:0.875rem;margin-top:0.25rem;">Gerenciar informações da conta</p>
        </div>

        {{-- Hero card --}}
        <div style="background-color:var(--color-card);border:1px solid var(--color-border);border-radius:0.875rem;padding:1.5rem;display:flex;align-items:center;gap:1.25rem;flex-wrap:wrap;">
            {{-- Avatar grande --}}
            <div style="width:4rem;height:4rem;border-radius:9999px;background-color:var(--color-primary);color:var(--color-primary-foreground);display:flex;align-items:center;justify-content:center;font-size:1.25rem;font-weight:700;flex-shrink:0;">
                {{ strtoupper(substr($user->name ?? 'U', 0, 2)) }}
            </div>
            <div class="flex-1 min-w-0">
                <h2 style="font-size:1.125rem;font-weight:700;margin:0 0 0.25rem;">{{ $user->name }}</h2>
                <p style="font-size:0.8125rem;color:var(--color-muted-foreground);margin:0 0 0.5rem;">{{ $user->email }}</p>
                <div style="display:flex;flex-wrap:wrap;gap:0.5rem;align-items:center;">
                    <span style="display:inline-flex;padding:0.2rem 0.625rem;border-radius:9999px;font-size:0.75rem;font-weight:600;background-color:oklch(0.74 0.135 78 / 0.15);color:var(--color-primary);">{{ $roleLabel }}</span>
                    @if($user->active)
                    <span style="display:inline-flex;align-items:center;gap:0.25rem;padding:0.2rem 0.625rem;border-radius:9999px;font-size:0.75rem;font-weight:500;background-color:oklch(0.65 0.16 145 / 0.15);color:oklch(0.65 0.16 145);">
                        <span style="width:0.4rem;height:0.4rem;border-radius:9999px;background-color:oklch(0.65 0.16 145);display:inline-block;"></span>Ativo
                    </span>
                    @else
                    <span style="display:inline-flex;align-items:center;gap:0.25rem;padding:0.2rem 0.625rem;border-radius:9999px;font-size:0.75rem;font-weight:500;background-color:oklch(0.62 0.21 25 / 0.15);color:oklch(0.62 0.21 25);">
                        <span style="width:0.4rem;height:0.4rem;border-radius:9999px;background-color:oklch(0.62 0.21 25);display:inline-block;"></span>Inativo
                    </span>
                    @endif
                    <span style="font-size:0.75rem;color:var(--color-muted-foreground);">Membro desde {{ $user->created_at->format('M Y') }}</span>
                </div>
            </div>
        </div>

        {{-- Informações de conta --}}
        <div style="background-color:var(--color-card);border:1px solid var(--color-border);border-radius:0.875rem;padding:1.5rem;">
            <h2 style="font-size:0.9375rem;font-weight:700;margin-bottom:1rem;">Informações de conta</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <p style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.06em;color:var(--color-muted-foreground);margin-bottom:0.25rem;">Função</p>
                    <p style="font-size:0.9375rem;font-weight:500;">{{ $roleLabel }}</p>
                </div>
                <div>
                    <p style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.06em;color:var(--color-muted-foreground);margin-bottom:0.25rem;">Status</p>
                    <p style="font-size:0.9375rem;font-weight:500;">{{ $user->active ? 'Ativo' : 'Inativo' }}</p>
                </div>
                <div>
                    <p style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.06em;color:var(--color-muted-foreground);margin-bottom:0.25rem;">Membro desde</p>
                    <p style="font-size:0.9375rem;font-weight:500;">{{ $user->created_at->format('d/m/Y') }}</p>
                </div>
                <div>
                    <p style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.06em;color:var(--color-muted-foreground);margin-bottom:0.25rem;">E-mail verificado</p>
                    <p style="font-size:0.9375rem;font-weight:500;">{{ $user->email_verified_at ? $user->email_verified_at->format('d/m/Y') : 'Não verificado' }}</p>
                </div>
                @if($user->phone)
                <div>
                    <p style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.06em;color:var(--color-muted-foreground);margin-bottom:0.25rem;">Celular</p>
                    <p style="font-size:0.9375rem;font-weight:500;">{{ $user->phone }}</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Informações pessoais --}}
        <div style="background-color:var(--color-card);border:1px solid var(--color-border);border-radius:0.875rem;padding:1.5rem;">
            <h2 style="font-size:0.9375rem;font-weight:700;margin-bottom:0.375rem;">Informações pessoais</h2>
            <p style="font-size:0.8125rem;color:var(--color-muted-foreground);margin-bottom:1.5rem;">Atualize o nome e o endereço de e-mail da conta.</p>
            @include('profile.partials.update-profile-information-form')
        </div>

        {{-- Alterar password --}}
        <div style="background-color:var(--color-card);border:1px solid var(--color-border);border-radius:0.875rem;padding:1.5rem;">
            <h2 style="font-size:0.9375rem;font-weight:700;margin-bottom:0.375rem;">Alterar senha</h2>
            <p style="font-size:0.8125rem;color:var(--color-muted-foreground);margin-bottom:1.5rem;">Use uma senha longa e aleatória para manter sua conta segura.</p>
            @include('profile.partials.update-password-form')
        </div>

        {{-- Eliminar conta --}}
        <div style="background-color:var(--color-card);border:1px solid var(--color-border);border-radius:0.875rem;padding:1.5rem;">
            <h2 style="font-size:0.9375rem;font-weight:700;margin-bottom:0.375rem;color:oklch(0.62 0.21 25);">Excluir conta</h2>
            <p style="font-size:0.8125rem;color:var(--color-muted-foreground);margin-bottom:1.5rem;">Após excluir a conta, todos os dados serão removidos permanentemente.</p>
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-dynamic-component>
