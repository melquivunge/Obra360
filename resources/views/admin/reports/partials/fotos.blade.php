@php $limit = \App\Http\Controllers\Admin\ReportController::photoLimit(); @endphp

@if($photos->isEmpty())
    <p class="rep-empty">Sem fotos registadas.</p>
@else
    <table class="rep-table">
        <thead><tr><th>Data</th><th>Título</th><th>Fase</th></tr></thead>
        <tbody>
        @foreach($photos as $photo)
            <tr>
                <td>{{ ($photo->taken_at ?? $photo->created_at)?->format('d/m/Y') }}</td>
                <td>{{ $photo->title ?: $photo->caption ?: '—' }}</td>
                <td>{{ $photo->phase?->name ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    @if($pdf)
        <h3 class="rep-h3">Galeria</h3>
        @if($photos->count() > $limit)
            <p class="rep-empty">A mostrar as {{ $limit }} fotos mais recentes de {{ $photos->count() }}.</p>
        @endif
        <table class="rep-gallery"><tr>
        @foreach($photos->take($limit) as $photo)
            @php $src = \App\Http\Controllers\Admin\ReportController::embed($photo->path); @endphp
            @if($src)
                <td>
                    <img src="{{ $src }}" alt="">
                    <div class="cap">{{ ($photo->taken_at ?? $photo->created_at)?->format('d/m/Y') }}</div>
                </td>
                @if($loop->iteration % 3 === 0)</tr><tr>@endif
            @endif
        @endforeach
        </tr></table>
    @else
        <h3 class="rep-h3">Galeria</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach($photos as $photo)
            <a href="{{ route('admin.projects.photos.show', [$project, $photo]) }}" target="_blank"
               style="display:block;border:1px solid var(--border);border-radius:0.5rem;overflow:hidden;">
                <img src="{{ route('admin.projects.photos.show', [$project, $photo]) }}" alt=""
                     style="width:100%;height:7rem;object-fit:cover;display:block;">
                <div style="padding:0.375rem 0.5rem;font-size:0.75rem;color:var(--muted-foreground);">
                    {{ ($photo->taken_at ?? $photo->created_at)?->format('d/m/Y') }}
                </div>
            </a>
        @endforeach
        </div>
    @endif
@endif
