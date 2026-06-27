@props(['href' => '#', 'active' => false])

@php
$baseClasses = 'flex items-center gap-3 px-4 py-3 rounded-lg mx-2 text-sm font-medium';
$stateClasses = $active
    ? 'bg-slate-900 text-white border-l-2 border-green-500'
    : 'text-slate-400 hover:bg-slate-700 hover:text-white';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $baseClasses . ' ' . $stateClasses]) }}>
    @if ($icon ?? false)
        <span class="w-5 h-5 flex-shrink-0" aria-hidden="true">
            {{ $icon }}
        </span>
    @endif
    {{ $slot }}
</a>
