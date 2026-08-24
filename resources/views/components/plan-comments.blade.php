{{--
    O fio de conversa de uma versão de planta.

    Fica preso à versão e não à planta: um pedido feito sobre a v2 continua a
    fazer sentido lido na v2 depois de a v3 sair. Os dois lados veem o mesmo
    fio — é essa a diferença face ao chat do projeto, onde «nesta parede aqui»
    se perde entre mensagens de meses.
--}}
@props(['version', 'action', 'form'])

<div class="w-full" style="margin-top:0.5rem;padding-top:0.5rem;border-top:1px dashed var(--border);">
    @forelse($version->comments as $comment)
        <div style="display:flex;gap:0.5rem;padding:0.375rem 0;">
            <span style="font-size:0.75rem;font-weight:600;white-space:nowrap;color:{{ $comment->isFromClient() ? 'var(--primary)' : 'var(--muted-foreground)' }};">
                {{ $comment->authorName() }}{{ $comment->isFromClient() ? ' (cliente)' : '' }}
            </span>
            <span style="font-size:0.8125rem;flex:1;min-width:0;">{{ $comment->body }}</span>
            <span style="font-size:0.6875rem;color:var(--muted-foreground);white-space:nowrap;">{{ $comment->created_at->format('d/m H:i') }}</span>
        </div>
    @empty
        <p style="font-size:0.75rem;color:var(--muted-foreground);padding:0.25rem 0;">Sem comentários nesta versão.</p>
    @endforelse

    <form method="POST" action="{{ $action }}" style="display:flex;gap:0.5rem;margin-top:0.5rem;">
        @csrf
        <input type="hidden" name="_form" value="{{ $form }}">
        <input type="text" name="body" maxlength="2000" required
               placeholder="Comentar esta versão…"
               style="flex:1;min-width:0;font-size:0.8125rem;">
        <button type="submit" class="btn-secondary" style="font-size:0.8125rem;white-space:nowrap;">Comentar</button>
    </form>
    <x-field-error field="body" :form="$form" />
</div>
