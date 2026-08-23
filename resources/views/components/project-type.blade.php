@props(['project'])

{{--
    TIPO-04: a etiqueta do tipo de projecto. Fica mais discreta do que a etiqueta
    de estado, para não competir com ela; os serviços de projecto (arquitectura e
    design de interiores) levam um tom próprio, de modo que numa lista mista se
    distinguem das obras num relance.
--}}
@php
    $tone = $project->isSiteWork()
        ? 'background-color:var(--color-surface);color:var(--color-muted-foreground);border:1px solid var(--color-border);'
        : 'background-color:oklch(0.6 0.18 220 / 0.14);color:oklch(0.72 0.13 220);border:1px solid oklch(0.6 0.18 220 / 0.3);';
@endphp

<span {{ $attributes->merge(['class' => 'shrink-0']) }}
      style="display:inline-flex;align-items:center;font-size:0.6875rem;font-weight:500;padding:0.125rem 0.5rem;border-radius:9999px;white-space:nowrap;line-height:1.4;{{ $tone }}">
    {{ $project->typeLabel() }}
</span>
