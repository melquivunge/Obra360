<x-admin-layout>
<div class="space-y-6 p-6 md:p-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('admin.reports.index') }}" style="font-size:0.8125rem;color:var(--muted-foreground);">{{ __('← Relatórios') }}</a>
            <h1 style="font-size:1.5rem;font-weight:700;margin-top:0.25rem;">{{ __($title) }}</h1>
            <p style="color:var(--muted-foreground);font-size:0.875rem;margin-top:0.25rem;">
                {{ $project->name }}@if($project->code) · {{ $project->code }}@endif
            </p>
        </div>
        <a href="{{ route('admin.reports.pdf', [$project, $type]) }}" class="btn-primary">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            {{ __('Exportar PDF') }}
        </a>
    </div>

    <div class="flex flex-wrap gap-2">
        @foreach(\App\Http\Controllers\Admin\ReportController::TYPES as $key => $label)
            <a href="{{ route('admin.reports.show', [$project, $key]) }}"
               class="{{ $key === $type ? 'btn-primary' : '' }}"
               @style(['padding:0.4rem 0.75rem;border-radius:0.5rem;font-size:0.8125rem;border:1px solid var(--border);color:var(--muted-foreground);' => $key !== $type])>
                {{ __($label) }}
            </a>
        @endforeach
    </div>

    <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:1.25rem;">
        @include('admin.reports.partials.'.$type)
    </div>
</div>

<style>
    .rep-table { width:100%; border-collapse:collapse; margin-bottom:0.5rem; font-size:0.875rem; }
    .rep-table th, .rep-table td { border:1px solid var(--border); padding:0.5rem 0.625rem; text-align:left; vertical-align:top; }
    .rep-table thead th, .rep-table tfoot th { background:oklch(0.74 0.135 78 / 0.08); font-size:0.75rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--muted-foreground); }
    .rep-table tbody th { width:30%; color:var(--muted-foreground); font-weight:500; }
    .rep-table .num { text-align:right; }
    .rep-h3 { font-size:0.9375rem; font-weight:600; margin:1.25rem 0 0.5rem; padding-bottom:0.25rem; border-bottom:1px solid var(--border); }
    .rep-empty { color:var(--muted-foreground); font-style:italic; margin:0.25rem 0 0.75rem; font-size:0.875rem; }
</style>
</x-admin-layout>
