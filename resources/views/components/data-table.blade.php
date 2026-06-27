@props(['head' => null])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200']) }}>
    <table class="min-w-full divide-y divide-gray-200">
        @if ($head)
            <thead class="bg-gray-50">
                <tr>
                    {{ $head }}
                </tr>
            </thead>
        @endif
        <tbody class="bg-white divide-y divide-gray-200">
            {{ $slot }}
        </tbody>
    </table>
</div>
