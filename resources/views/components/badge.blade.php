@props(['color' => 'gray'])

@php
$colorClasses = match($color) {
    'purple' => 'bg-purple-100 text-purple-700',
    'blue'   => 'bg-blue-100 text-blue-700',
    'yellow' => 'bg-yellow-100 text-yellow-700',
    'green'  => 'bg-green-100 text-green-700',
    'red'    => 'bg-red-100 text-red-700',
    default  => 'bg-gray-100 text-gray-600',
};
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center text-xs font-medium px-2 py-0.5 rounded-full ' . $colorClasses]) }}>
    {{ $slot }}
</span>
