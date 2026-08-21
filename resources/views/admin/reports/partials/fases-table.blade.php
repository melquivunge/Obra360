@if($phases->isEmpty())
    <p class="rep-empty">Sem fases registadas.</p>
@else
    <table class="rep-table">
        <thead>
            <tr><th>#</th><th>Fase</th><th>Status</th><th class="num">%</th><th>Início</th><th>Previsão</th><th>Conclusão</th></tr>
        </thead>
        <tbody>
        @foreach($phases as $phase)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $phase->name }}</td>
                <td>{{ \App\Models\ProjectPhase::STATUSES[$phase->status] ?? $phase->status }}</td>
                <td class="num">{{ (int) $phase->percentage }}%</td>
                <td>{{ $phase->start_date?->format('d/m/Y') ?? '—' }}</td>
                <td>{{ $phase->expected_end_date?->format('d/m/Y') ?? '—' }}</td>
                <td>{{ $phase->real_end_date?->format('d/m/Y') ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif
