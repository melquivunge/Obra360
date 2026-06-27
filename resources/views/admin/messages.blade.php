<x-admin-layout>
<div class="p-6 md:p-8">
    <h1 style="font-size:1.5rem;font-weight:700;margin-bottom:1.25rem;">Mensagens</h1>

    <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;display:flex;height:calc(100vh - 10rem);overflow:hidden;">

        {{-- Project list --}}
        <div id="proj-list" style="width:280px;min-width:200px;border-right:1px solid var(--border);overflow-y:auto;flex-shrink:0;">
            @forelse($projects as $project)
            @php $last = $project->messages->first(); @endphp
            <button type="button"
                onclick="openConversation({{ $project->id }}, @js($project->name))"
                id="proj-btn-{{ $project->id }}"
                style="width:100%;text-align:left;padding:0.875rem 1rem;border:none;border-bottom:1px solid var(--border);background:transparent;cursor:pointer;display:block;color:var(--foreground);"
                onmouseover="if(currentProjectId!={{ $project->id }})this.style.backgroundColor='var(--surface)'"
                onmouseout="if(currentProjectId!={{ $project->id }})this.style.backgroundColor=''">
                <div style="display:flex;justify-content:space-between;align-items:baseline;gap:0.5rem;">
                    <p style="font-size:0.875rem;font-weight:600;truncate:true;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;max-width:150px;">{{ $project->name }}</p>
                    @if($last)<p style="font-size:0.7rem;color:var(--muted-foreground);white-space:nowrap;">{{ $last->created_at->diffForHumans(null, true, 1) }}</p>@endif
                </div>
                <p style="font-size:0.75rem;color:var(--muted-foreground);overflow:hidden;white-space:nowrap;text-overflow:ellipsis;margin-top:0.125rem;">
                    {{ $last ? ($last->user?->name.': '.Str::limit($last->body,45)) : 'Nenhuma mensagem' }}
                </p>
            </button>
            @empty
            <p style="padding:1.5rem;font-size:0.875rem;color:var(--muted-foreground);">Nenhuma obra cadastrada.</p>
            @endforelse
        </div>

        {{-- Chat area --}}
        <div style="flex:1;display:flex;flex-direction:column;min-width:0;" id="chat-area">
            {{-- Placeholder --}}
            <div id="chat-placeholder" style="flex:1;display:flex;align-items:center;justify-content:center;color:var(--muted-foreground);">
                <div style="text-align:center;">
                    <svg style="width:3rem;height:3rem;margin:0 auto 0.75rem;opacity:0.4;" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/></svg>
                    <p style="font-size:0.875rem;">Selecione uma obra para ver as mensagens</p>
                </div>
            </div>
            {{-- Chat UI (hidden until a project is selected) --}}
            <div id="chat-ui" style="display:none;flex:1;flex-direction:column;">
                <div id="chat-header" style="padding:0.875rem 1rem;border-bottom:1px solid var(--border);">
                    <p id="chat-project-name" style="font-weight:700;"></p>
                    <p style="font-size:0.75rem;color:var(--muted-foreground);">Conversa em tempo real</p>
                </div>
                <div id="chat-messages" style="flex:1;overflow-y:auto;padding:1rem;display:flex;flex-direction:column;gap:0.75rem;"></div>
                <div style="padding:0.75rem;border-top:1px solid var(--border);">
                    <div style="display:flex;gap:0.5rem;align-items:flex-end;">
                        <textarea id="chat-input" rows="2" placeholder="Escreva uma mensagem… (Enter envia, Shift+Enter nova linha)" style="flex:1;resize:none;"></textarea>
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

function appendMsg(body, userName, isMine, time){
    const list = document.getElementById('chat-messages');
    const d = document.createElement('div');
    d.style.cssText = `display:flex;justify-content:${isMine?'flex-end':'flex-start'}`;
    const bubble = document.createElement('div');
    bubble.style.cssText = `max-width:80%;border-radius:1rem;padding:0.625rem 0.875rem;font-size:0.875rem;background:${isMine?'var(--primary)':'var(--surface)'};color:${isMine?'var(--primary-foreground)':'var(--foreground)'}`;
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

async function openConversation(projectId, projectName){
    if(currentProjectId === projectId) return;

    // Deactivate old button
    if(currentProjectId){
        const old = document.getElementById(`proj-btn-${currentProjectId}`);
        if(old) old.style.backgroundColor = '';
    }

    currentProjectId = projectId;
    const btn = document.getElementById(`proj-btn-${projectId}`);
    if(btn) btn.style.backgroundColor = 'oklch(0.74 0.135 78 / 0.12)';

    document.getElementById('chat-placeholder').style.display = 'none';
    const ui = document.getElementById('chat-ui');
    ui.style.display = 'flex';
    ui.style.flexDirection = 'column';

    document.getElementById('chat-project-name').textContent = projectName;
    const list = document.getElementById('chat-messages');
    list.innerHTML = '<p style="color:var(--muted-foreground);font-size:0.875rem;text-align:center;">Carregando…</p>';

    // Unsubscribe old channel
    if(echoChannel && window.Echo){
        window.Echo.leave(`project.${echoChannel}`);
    }

    // Load messages
    const res = await fetch(`/admin/obras/${projectId}/mensagens`, {headers:{'Accept':'application/json'}});
    const msgs = await res.json();
    list.innerHTML = '';
    if(msgs.length === 0){
        list.innerHTML = '<p style="color:var(--muted-foreground);font-size:0.875rem;text-align:center;margin:auto;">Nenhuma mensagem ainda.</p>';
    } else {
        msgs.forEach(m => appendMsg(m.body, m.user_name, m.is_mine, m.created_at));
    }

    // Subscribe to Echo channel
    if(window.Echo){
        echoChannel = projectId;
        window.Echo.private(`project.${projectId}`).listen('.message.sent', data => {
            if(data.user_id !== myId) appendMsg(data.body, data.user_name, false, data.created_at);
        });
    }
}

async function sendMessage(){
    const input = document.getElementById('chat-input');
    const btn = document.getElementById('chat-send');
    const body = input.value.trim();
    if(!body || !currentProjectId) return;
    btn.disabled = true;
    try {
        const r = await fetch(`/admin/obras/${currentProjectId}/mensagens`, {
            method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
            body: JSON.stringify({body})
        });
        const data = await r.json();
        if(data.ok){
            input.value = '';
            appendMsg(body,'Eu',true,new Date().toLocaleString('pt-BR',{day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'}));
        }
    } finally { btn.disabled=false; }
}

document.getElementById('chat-send').addEventListener('click', sendMessage);
document.getElementById('chat-input').addEventListener('keydown', e => {
    if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();sendMessage();}
});
</script>
</x-admin-layout>
