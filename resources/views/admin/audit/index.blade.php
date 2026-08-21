<x-admin-layout>
<div class="space-y-6 p-6 md:p-8">
    <div>
        <h1 style="font-size:1.5rem;font-weight:700;">Auditoria</h1>
        <p style="color:var(--muted-foreground);font-size:0.875rem;margin-top:0.25rem;">
            Registo de criações, alterações e remoções no sistema.
        </p>
    </div>

    <form method="GET" action="{{ route('admin.audit.index') }}" class="flex flex-wrap gap-3">
        <select name="action" style="max-width:12rem;" onchange="this.form.submit()">
            <option value="">Todas as ações</option>
            @foreach($actions as $key => $label)
                <option value="{{ $key }}" {{ request('action') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="type" style="max-width:14rem;" onchange="this.form.submit()">
            <option value="">Todos os tipos</option>
            @foreach($types as $class => $label)
                <option value="{{ $class }}" {{ request('type') === $class ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="user_id" style="max-width:14rem;" onchange="this.form.submit()">
            <option value="">Todos os utilizadores</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}" {{ (int) request('user_id') === $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
            @endforeach
        </select>
        @if(request()->hasAny(['action','type','user_id']))
            <a href="{{ route('admin.audit.index') }}" style="align-self:center;font-size:0.8125rem;color:var(--muted-foreground);">Limpar</a>
        @endif
    </form>

    @if($logs->isEmpty())
        <p style="color:var(--muted-foreground);font-style:italic;font-size:0.875rem;">Nenhum registo encontrado.</p>
    @else
    <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;overflow:hidden;">
        <table style="width:100%;border-collapse:collapse;font-size:0.875rem;">
            <thead>
                <tr style="background-color:oklch(0.74 0.135 78 / 0.08);">
                    <th style="padding:0.625rem 0.75rem;text-align:left;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--muted-foreground);">Quando</th>
                    <th style="padding:0.625rem 0.75rem;text-align:left;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--muted-foreground);">Quem</th>
                    <th style="padding:0.625rem 0.75rem;text-align:left;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--muted-foreground);">Ação</th>
                    <th style="padding:0.625rem 0.75rem;text-align:left;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--muted-foreground);">Tipo</th>
                    <th style="padding:0.625rem 0.75rem;text-align:left;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--muted-foreground);">Registo</th>
                    <th style="padding:0.625rem 0.75rem;text-align:left;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--muted-foreground);">IP</th>
                </tr>
            </thead>
            <tbody>
            @foreach($logs as $log)
                <tr style="border-top:1px solid var(--border);">
                    <td style="padding:0.625rem 0.75rem;white-space:nowrap;color:var(--muted-foreground);">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                    <td style="padding:0.625rem 0.75rem;">{{ $log->user?->name ?? $log->user_name ?? 'Sistema' }}</td>
                    <td style="padding:0.625rem 0.75rem;">{{ $log->actionLabel() }}</td>
                    <td style="padding:0.625rem 0.75rem;">{{ $log->subjectLabel() }}</td>
                    <td style="padding:0.625rem 0.75rem;">{{ $log->description }}</td>
                    <td style="padding:0.625rem 0.75rem;color:var(--muted-foreground);">{{ $log->ip_address ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{ $logs->links() }}
    @endif
</div>
</x-admin-layout>
