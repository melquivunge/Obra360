<x-admin-layout>
<div class="p-4 md:p-8">
    <h1 style="font-size:1.5rem;font-weight:700;margin-bottom:1.25rem;" class="hidden md:block">Mensagens</h1>

    {{-- Wrapper: mobile = painel único, desktop = dois painéis --}}
    <div id="msg-wrapper" style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;overflow:hidden;display:flex;height:calc(100vh - 8rem);">

        {{-- PAINEL: Lista de obras --}}
        <div id="proj-list" style="width:100%;max-width:280px;border-right:1px solid var(--border);overflow-y:auto;flex-shrink:0;display:flex;flex-direction:column;">
            <div style="padding:0.875rem 1rem;border-bottom:1px solid var(--border);flex-shrink:0;">
                <p style="font-weight:700;font-size:0.9375rem;">Obras e Projetos</p>
            </div>
            @forelse($projects as $project)
            @php $last = $project->messages->first(); @endphp
            <button type="button"
                onclick="openConversation({{ $project->id }}, @js($project->name))"
                id="proj-btn-{{ $project->id }}"
                style="width:100%;text-align:left;padding:0.875rem 1rem;border:none;border-bottom:1px solid var(--border);background:transparent;cursor:pointer;color:var(--foreground);"
                onmouseover="if(currentProjectId!=={{ $project->id }})this.style.backgroundColor='var(--surface)'"
                onmouseout="if(currentProjectId!=={{ $project->id }})this.style.backgroundColor=''">
                <div style="display:flex;justify-content:space-between;align-items:baseline;gap:0.5rem;">
                    <p style="font-size:0.875rem;font-weight:600;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;max-width:160px;">{{ $project->name }}</p>
                    @if($last)<p style="font-size:0.7rem;color:var(--muted-foreground);white-space:nowrap;flex-shrink:0;">{{ $last->created_at->diffForHumans(null, true, 1) }}</p>@endif
                </div>
                <p style="font-size:0.75rem;color:var(--muted-foreground);overflow:hidden;white-space:nowrap;text-overflow:ellipsis;margin-top:0.125rem;">
                    {{ $last ? ($last->user?->name.': '.Str::limit($last->body, 45)) : 'Nenhuma mensagem' }}
                </p>
            </button>
            @empty
            <p style="padding:1.5rem;font-size:0.875rem;color:var(--muted-foreground);">Nenhuma obra ou projeto cadastrado.</p>
            @endforelse
        </div>

        {{-- PAINEL: Chat --}}
        <div id="chat-area" style="flex:1;display:flex;flex-direction:column;min-width:0;">

            {{-- Placeholder (desktop) --}}
            <div id="chat-placeholder" style="flex:1;display:flex;align-items:center;justify-content:center;color:var(--muted-foreground);">
                <div style="text-align:center;">
                    <svg style="width:3rem;height:3rem;margin:0 auto 0.75rem;opacity:0.4;" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/></svg>
                    <p style="font-size:0.875rem;">Selecione uma obra ou projeto para ver as mensagens</p>
                </div>
            </div>

            {{-- Chat UI --}}
            <div id="chat-ui" style="display:none;flex:1;flex-direction:column;min-height:0;">
                <div id="chat-header" style="padding:0.75rem 1rem;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:0.75rem;flex-shrink:0;">
                    {{-- Botão voltar (só mobile) --}}
                    <button id="btn-back" onclick="backToList()" style="display:none;padding:0.375rem;border:none;background:none;cursor:pointer;color:var(--muted-foreground);flex-shrink:0;">
                        <svg style="width:1.25rem;height:1.25rem;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                    </button>
                    <div>
                        <p id="chat-project-name" style="font-weight:700;font-size:0.9375rem;"></p>
                        <p style="font-size:0.75rem;color:var(--muted-foreground);">Conversa em tempo real</p>
                    </div>
                </div>
                <div id="chat-messages" style="flex:1;overflow-y:auto;padding:1rem;display:flex;flex-direction:column;gap:0.75rem;min-height:0;"></div>
                <div style="padding:0.75rem;border-top:1px solid var(--border);flex-shrink:0;">
                    <div style="display:flex;gap:0.5rem;align-items:flex-end;">
                        <textarea id="chat-input" rows="2" placeholder="Escreva uma mensagem…" style="flex:1;resize:none;"></textarea>
                        <button id="chat-send" class="btn-primary">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
                            Enviar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentProjectId = null;
let echoChannel = null;
const myId = {{ auth()->id() }};
const csrf = document.querySelector('meta[name=csrf-token]').content;

function isMobile() { return window.innerWidth < 768; }

function applyLayout() {
    const list = document.getElementById('proj-list');
    const chat = document.getElementById('chat-area');
    const btnBack = document.getElementById('btn-back');

    if (isMobile()) {
        // Mobile: mostrar apenas a lista se nenhum projeto seleccionado
        list.style.maxWidth = '100%';
        list.style.borderRight = 'none';
        if (currentProjectId) {
            list.style.display = 'none';
            chat.style.display = 'flex';
            btnBack.style.display = 'block';
        } else {
            list.style.display = 'flex';
            chat.style.display = 'none';
            btnBack.style.display = 'none';
        }
    } else {
        // Desktop: ambos os painéis visíveis
        list.style.maxWidth = '280px';
        list.style.display = 'flex';
        list.style.borderRight = '1px solid var(--border)';
        chat.style.display = 'flex';
        btnBack.style.display = 'none';
    }
}

function backToList() {
    currentProjectId = null;
    document.getElementById('chat-ui').style.display = 'none';
    document.getElementById('chat-placeholder').style.display = 'flex';
    document.querySelectorAll('[id^="proj-btn-"]').forEach(b => b.style.backgroundColor = '');
    applyLayout();
}

function appendMsg(body, userName, isMine, time) {
    const list = document.getElementById('chat-messages');
    const d = document.createElement('div');
    d.style.cssText = `display:flex;justify-content:${isMine ? 'flex-end' : 'flex-start'}`;
    const bubble = document.createElement('div');
    bubble.style.cssText = `max-width:80%;border-radius:1rem;padding:0.625rem 0.875rem;font-size:0.875rem;background:${isMine ? 'var(--primary)' : 'var(--surface)'};color:${isMine ? 'var(--primary-foreground)' : 'var(--foreground)'}`;
    if (!isMine) {
        const name = document.createElement('p');
        name.style.cssText = 'font-size:0.75rem;font-weight:600;opacity:0.8;margin-bottom:0.125rem;';
        name.textContent = userName || 'Usuário';
        bubble.appendChild(name);
    }
    const text = document.createElement('p');
    text.style.whiteSpace = 'pre-wrap';
    text.textContent = body;
    bubble.appendChild(text);
    const sentAt = document.createElement('p');
    sentAt.style.cssText = 'font-size:0.7rem;margin-top:0.25rem;opacity:0.7;';
    sentAt.textContent = time;
    bubble.appendChild(sentAt);
    d.appendChild(bubble);
    list.appendChild(d);
    list.scrollTop = list.scrollHeight;
}

async function openConversation(projectId, projectName) {
    if (currentProjectId === projectId) return;

    if (currentProjectId) {
        const old = document.getElementById(`proj-btn-${currentProjectId}`);
        if (old) old.style.backgroundColor = '';
    }

    currentProjectId = projectId;
    const btn = document.getElementById(`proj-btn-${projectId}`);
    if (btn) btn.style.backgroundColor = 'oklch(0.74 0.135 78 / 0.12)';

    // Mobile: mudar para painel de chat
    applyLayout();

    document.getElementById('chat-placeholder').style.display = 'none';
    const ui = document.getElementById('chat-ui');
    ui.style.display = 'flex';
    ui.style.flexDirection = 'column';

    document.getElementById('chat-project-name').textContent = projectName;
    const list = document.getElementById('chat-messages');
    list.innerHTML = '<p style="color:var(--muted-foreground);font-size:0.875rem;text-align:center;margin:auto;">Carregando…</p>';

    if (echoChannel && window.Echo) window.Echo.leave(`project.${echoChannel}`);

    const res = await fetch(`/admin/obras/${projectId}/mensagens`, { headers: { 'Accept': 'application/json' } });
    const msgs = await res.json();
    list.innerHTML = '';
    if (msgs.length === 0) {
        list.innerHTML = '<p style="color:var(--muted-foreground);font-size:0.875rem;text-align:center;margin:auto;">Nenhuma mensagem ainda.</p>';
    } else {
        msgs.forEach(m => appendMsg(m.body, m.user_name, m.is_mine, m.created_at));
    }

    if (window.Echo) {
        echoChannel = projectId;
        window.Echo.private(`project.${projectId}`).listen('.message.sent', data => {
            if (data.user_id !== myId) appendMsg(data.body, data.user_name, false, data.created_at);
        });
    }
}

async function sendMessage() {
    const input = document.getElementById('chat-input');
    const btn = document.getElementById('chat-send');
    const body = input.value.trim();
    if (!body || !currentProjectId) return;
    btn.disabled = true;
    try {
        const r = await fetch(`/admin/obras/${currentProjectId}/mensagens`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ body })
        });
        const data = await r.json();
        if (data.ok) {
            input.value = '';
            appendMsg(body, 'Eu', true, new Date().toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }));
        }
    } finally { btn.disabled = false; }
}

document.getElementById('chat-send').addEventListener('click', sendMessage);
document.getElementById('chat-input').addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
});

// Inicializar layout e reagir a redimensionamento
applyLayout();
window.addEventListener('resize', applyLayout);
</script>
</x-admin-layout>
