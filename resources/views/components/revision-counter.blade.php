{{--
    O contador de revisões do contrato.

    Aparece só quando o contrato fixou um limite: sem limite acordado, um
    contador seria ruído. Nunca impede nada — o estúdio pode oferecer uma
    revisão, e um sistema que trava o trabalho acaba contornado por fora. O
    valor está em o número estar à vista dos dois lados **antes** de a
    discussão acontecer.
--}}
@props(['project'])

@if($project->hasRevisionLimit())
    @php
        $usadas = $project->revisionsUsed();
        $restantes = $project->revisionsRemaining();
        $excedeu = $restantes < 0;
        $noLimite = $restantes === 0;
        $cor = $excedeu ? 'var(--destructive)' : ($noLimite ? 'var(--warning, var(--primary))' : 'var(--muted-foreground)');
    @endphp
    <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;padding:0.625rem 0.875rem;border:1px solid var(--border);border-radius:0.5rem;background-color:var(--surface);">
        <span style="font-size:0.8125rem;color:var(--muted-foreground);">{{ __('Revisões do contrato:') }}</span>
        <strong style="font-size:0.8125rem;color:{{ $cor }};">{{ $usadas }} {{ __('de') }} {{ $project->revisions_included }}</strong>
        @if($excedeu)
            <span style="font-size:0.75rem;color:var(--destructive);">{{ abs($restantes) }} {{ __('além do contratado') }}</span>
        @elseif($noLimite)
            <span style="font-size:0.75rem;color:var(--muted-foreground);">{{ __('a próxima revisão passa o contratado') }}</span>
        @else
            <span style="font-size:0.75rem;color:var(--muted-foreground);">{{ $restantes }} {{ $restantes === 1 ? __('restante') : __('restantes') }}</span>
        @endif
    </div>
@endif
