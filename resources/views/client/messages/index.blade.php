<x-client-layout>
<div class="p-4 md:p-6" style="display:flex;flex-direction:column;height:calc(100vh - 3.5rem);">

    {{-- Header --}}
    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;flex-shrink:0;">
        <a href="{{ route('portal.projects.show', $project) }}"
           style="display:inline-flex;align-items:center;justify-content:center;width:2.25rem;height:2.25rem;border-radius:0.5rem;border:1px solid var(--color-border);background-color:var(--color-card);color:var(--color-muted-foreground);text-decoration:none;flex-shrink:0;transition:border-color 0.15s,color 0.15s;"
           aria-label="Voltar para a obra"
           onmouseover="this.style.borderColor='var(--color-primary)';this.style.color='var(--color-primary)'"
           onmouseout="this.style.borderColor='var(--color-border)';this.style.color='var(--color-muted-foreground)'">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1rem;height:1rem;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
        </a>

        <div class="min-w-0 flex-1">
            <div style="display:flex;align-items:center;gap:0.625rem;flex-wrap:wrap;">
                <h1 style="font-size:1.125rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    Conversa — {{ $project->name }}
                </h1>
                <span style="display:inline-flex;align-items:center;padding:0.2rem 0.625rem;border-radius:9999px;background-color:oklch(0.74 0.135 78 / 0.15);color:var(--color-primary);font-size:0.75rem;font-weight:600;flex-shrink:0;">
                    {{ count($messages) }} {{ count($messages) === 1 ? 'mensagem' : 'mensagens' }}
                </span>
            </div>
            <p style="font-size:0.75rem;color:var(--color-muted-foreground);margin-top:0.125rem;">
                {{ $project->client->name ?? '—' }}
            </p>
        </div>
    </div>

    {{-- Chat container --}}
    <div style="flex:1;min-height:0;background-color:var(--color-card);border:1px solid var(--color-border);border-radius:0.875rem;display:flex;flex-direction:column;overflow:hidden;"
         x-data="chatClient({{ $project->id }}, {{ json_encode($messages) }})"
         x-init="init()">

        {{-- Messages area --}}
        <div x-ref="msgArea"
             style="flex:1;overflow-y:auto;padding:1rem;display:flex;flex-direction:column;gap:0.75rem;">

            {{-- Empty state --}}
            <div x-show="messages.length === 0 && !loading"
                 style="display:flex;flex-direction:column;align-items:center;justify-content:center;flex:1;gap:0.5rem;padding:2rem 0;color:var(--color-muted-foreground);">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:2.5rem;height:2.5rem;opacity:0.35;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/>
                </svg>
                <p style="font-size:0.9375rem;font-weight:500;">Nenhuma mensagem ainda.</p>
                <p style="font-size:0.8125rem;">Seja o primeiro a escrever!</p>
            </div>

            {{-- Loading state --}}
            <div x-show="loading"
                 style="display:flex;align-items:center;justify-content:center;flex:1;gap:0.5rem;color:var(--color-muted-foreground);font-size:0.875rem;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.25rem;height:1.25rem;animation:spin 1s linear infinite;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                </svg>
                Carregando…
            </div>

            {{-- Messages --}}
            <template x-for="m in messages" :key="m.id">
                <div :style="m.is_mine
                    ? 'display:flex;justify-content:flex-end;'
                    : 'display:flex;justify-content:flex-start;'">
                    <div style="max-width:80%;">
                        {{-- Sender name (only for others) --}}
                        <p x-show="!m.is_mine"
                           x-text="m.user_name"
                           style="font-size:0.75rem;font-weight:600;color:var(--color-muted-foreground);margin-bottom:0.25rem;padding-left:0.25rem;"></p>

                        {{-- Bubble --}}
                        <div :style="m.is_mine
                            ? 'background-color:var(--color-primary);color:var(--color-primary-foreground);padding:0.5rem 0.875rem;border-radius:1rem 1rem 0.25rem 1rem;font-size:0.875rem;'
                            : 'background-color:var(--color-surface);border:1px solid var(--color-border);color:var(--color-foreground);padding:0.5rem 0.875rem;border-radius:1rem 1rem 1rem 0.25rem;font-size:0.875rem;'">
                            <p x-text="m.body" style="white-space:pre-wrap;word-break:break-word;margin:0;"></p>
                            <p x-text="m.created_at"
                               :style="m.is_mine
                                   ? 'font-size:0.65rem;opacity:0.6;margin-top:0.25rem;text-align:right;margin-bottom:0;'
                                   : 'font-size:0.65rem;opacity:0.5;margin-top:0.25rem;text-align:right;margin-bottom:0;'"></p>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Input area --}}
        <div style="border-top:1px solid var(--color-border);padding:0.875rem;display:flex;flex-direction:column;gap:0.375rem;flex-shrink:0;">
            <div style="display:flex;gap:0.625rem;align-items:flex-end;">
                <textarea x-model="newMsg"
                          @keydown.enter.prevent="!$event.shiftKey && send()"
                          rows="2"
                          maxlength="1000"
                          placeholder="Escreva uma mensagem… (Enter envia, Shift+Enter quebra linha)"
                          style="flex:1;resize:none;background-color:var(--color-surface);border:1px solid var(--color-border);border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.875rem;color:var(--color-foreground);outline:none;transition:border-color 0.15s;max-height:100px;overflow-y:auto;"
                          @focus="$el.style.borderColor='var(--color-primary)'"
                          @blur="$el.style.borderColor='var(--color-border)'"></textarea>

                <button @click="send()"
                        :disabled="sending || !newMsg.trim()"
                        class="btn-primary"
                        style="white-space:nowrap;flex-shrink:0;gap:0.375rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1rem;height:1rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/>
                    </svg>
                    <span x-text="sending ? 'Enviando…' : 'Enviar'"></span>
                </button>
            </div>

            <p style="text-align:right;font-size:0.7rem;color:var(--color-muted-foreground);margin:0;">
                <span x-text="newMsg.length"></span>/1000
            </p>
        </div>
    </div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<script>
function chatClient(projectId, initialMessages) {
    return {
        messages: initialMessages || [],
        newMsg: '',
        sending: false,
        loading: false,

        init() {
            // Scroll to bottom on load using initial server-side messages
            this.$nextTick(() => {
                this.scrollToBottom();
            });

            // Real-time: listen via WebSocket if Echo is available
            if (window.Echo) {
                window.Echo.private('project.' + projectId)
                    .listen('.message.sent', (e) => {
                        if (!this.messages.find(m => m.id === e.id)) {
                            this.messages.push({ ...e, is_mine: false });
                            this.$nextTick(() => this.scrollToBottom());
                        }
                    });
            }
        },

        scrollToBottom() {
            if (this.$refs.msgArea) {
                this.$refs.msgArea.scrollTop = this.$refs.msgArea.scrollHeight;
            }
        },

        async load() {
            this.loading = true;
            try {
                const r = await fetch('/portal/obras/' + projectId + '/mensagens', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                this.messages = await r.json();
            } catch (e) {
                console.error(e);
            }
            this.loading = false;
            this.$nextTick(() => this.scrollToBottom());
        },

        async send() {
            const text = this.newMsg.trim();
            if (!text || this.sending) return;
            this.sending = true;
            try {
                const r = await fetch('/portal/obras/' + projectId + '/mensagens', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({ body: text })
                });
                const data = await r.json();
                if (data.ok) {
                    this.messages.push({
                        id: data.id,
                        body: text,
                        is_mine: true,
                        user_name: 'Eu',
                        created_at: new Date().toLocaleString('pt-BR', {
                            day: '2-digit', month: '2-digit', year: 'numeric',
                            hour: '2-digit', minute: '2-digit'
                        })
                    });
                    this.newMsg = '';
                    this.$nextTick(() => this.scrollToBottom());
                }
            } catch (e) {
                console.error(e);
            }
            this.sending = false;
        }
    };
}
</script>
</x-client-layout>
