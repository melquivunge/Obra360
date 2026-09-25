@if($phases->isEmpty())
    <p class="rep-empty">{{ __('Sem fases registradas.') }}</p>
@else
    <table class="rep-table">
        <thead>
            <tr><th>#</th><th>{{ __('Fase') }}</th><th>{{ __('Status') }}</th><th class="num">%</th><th>{{ __('Início') }}</th><th>{{ __('Previsão') }}</th><th>{{ __('Conclusão') }}</th></tr>
        </thead>
        <tbody>
        @foreach($phases as $phase)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $phase->name }}</td>
                <td>{{ __(\App\Models\ProjectPhase::STATUSES[$phase->status] ?? $phase->status) }}</td>
                <td class="num">{{ (int) $phase->percentage }}%</td>
                <td>{{ $phase->start_date?->format(app()->getLocale() === 'en' ? 'm/d/Y' : 'd/m/Y') ?? '—' }}</td>
                <td>{{ $phase->expected_end_date?->format(app()->getLocale() === 'en' ? 'm/d/Y' : 'd/m/Y') ?? '—' }}</td>
                <td>{{ $phase->real_end_date?->format(app()->getLocale() === 'en' ? 'm/d/Y' : 'd/m/Y') ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif
