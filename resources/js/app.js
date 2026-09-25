import Alpine from 'alpinejs';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Alpine = Alpine;
window.Pusher = Pusher;

// Alpine inicia antes de qualquer ligação WebSocket para não bloquear a UI
Alpine.start();

// Echo/Reverb — opcional; não deve bloquear o resto da app se falhar
try {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
} catch (e) {
    console.warn('Echo/Reverb não disponível:', e);
}

/* ─────────────────────────────────────────────────────────────────────────────
   Comportamentos partilhados das vistas

   Tudo o que está abaixo existia antes em atributos `onclick`, `onchange`,
   `onsubmit` e `onerror` espalhados pelas vistas. A Content Security Policy não
   permite script em linha (ver app/Http/Middleware/SecurityHeaders.php), por
   isso o markup passa a declarar a *intenção* num atributo `data-…` e o
   comportamento vive aqui, num único ficheiro que o browser carrega com nonce.

   Os ouvintes são delegados no `document`: apanham também os elementos que
   apareçam depois (uma linha nova numa lista, um modal reaberto por erro de
   validação) sem ninguém ter de os voltar a ligar.
   ───────────────────────────────────────────────────────────────────────────── */

// ── Modais ───────────────────────────────────────────────────────────────────
//
// Um modal é qualquer elemento com `data-modal-overlay`; abre-se pondo-o em
// `display:flex`. O foco vai para o primeiro campo preenchível, que é o que a
// pessoa vem fazer — e o `setTimeout` existe porque focar um elemento que
// acabou de sair de `display:none` não pega em todos os browsers.

function openModal(id) {
    const modal = typeof id === 'string' ? document.getElementById(id) : id;
    if (!modal) return;
    modal.style.display = 'flex';
    const first = modal.querySelector(
        'select,input:not([type=hidden]):not([type=file]):not([type=checkbox]),textarea'
    );
    if (first) setTimeout(() => first.focus(), 50);
}

function closeModal(id) {
    const modal = typeof id === 'string' ? document.getElementById(id) : id;
    if (modal) modal.style.display = 'none';
}

function closeAllModals() {
    document.querySelectorAll('[data-modal-overlay]').forEach((m) => {
        m.style.display = 'none';
    });
}

// ── Lightbox ─────────────────────────────────────────────────────────────────
//
// O mesmo markup serve a área interna e o portal do cliente; o `src` e a
// legenda vêm dos atributos da miniatura carregada.

function openLightbox(src, caption) {
    const overlay = document.getElementById('lightbox-overlay');
    const img = document.getElementById('lightbox-img');
    const cap = document.getElementById('lightbox-caption');
    if (!overlay || !img) return;
    img.src = src;
    img.alt = caption || '';
    if (cap) cap.textContent = caption || '';
    overlay.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    const overlay = document.getElementById('lightbox-overlay');
    if (!overlay) return;
    overlay.style.display = 'none';
    document.body.style.overflow = '';
}

function lightboxIsOpen() {
    const overlay = document.getElementById('lightbox-overlay');
    return !!overlay && overlay.style.display === 'flex';
}

// As vistas que precisam de abrir um modal por código — reabrir o formulário
// que falhou a validação, preencher o modal de despesa para edição — chamam
// por aqui. É a única superfície pública deste ficheiro.
window.obra360 = { openModal, closeModal, closeAllModals, openLightbox, closeLightbox };

// ── Cliques ──────────────────────────────────────────────────────────────────

document.addEventListener('click', function (event) {
    const target = event.target;

    // Fechar o modal ao carregar fora da caixa. Só quando o clique é
    // exactamente no fundo: um clique dentro do conteúdo não deve fechar nada.
    if (target instanceof Element && target.hasAttribute('data-modal-overlay')) {
        closeModal(target);
        return;
    }

    const opener = target.closest?.('[data-modal-open]');
    if (opener) {
        openModal(opener.getAttribute('data-modal-open'));
        return;
    }

    const closer = target.closest?.('[data-modal-close]');
    if (closer) {
        const id = closer.getAttribute('data-modal-close');
        closeModal(id || closer.closest('[data-modal-overlay]'));
        return;
    }

    // Lightbox: fechar tem de vir antes de abrir, senão o botão de fechar
    // (que vive dentro do overlay) voltava a abrir a imagem.
    if (target.closest?.('[data-lightbox-close]') || (target instanceof Element && target.id === 'lightbox-overlay')) {
        closeLightbox();
        return;
    }

    const photo = target.closest?.('[data-lightbox-src]');
    if (photo) {
        openLightbox(photo.getAttribute('data-lightbox-src'), photo.getAttribute('data-lightbox-caption') || '');
        return;
    }

    // Barra lateral da área interna.
    if (target.closest?.('[data-sidebar-toggle]')) {
        toggleSidebar(true);
        return;
    }
    if (target.closest?.('[data-sidebar-close]')) {
        toggleSidebar(false);
        return;
    }

    // Um link que na verdade submete o formulário onde vive (sair da sessão:
    // tem de ser POST, mas é desenhado como item de menu).
    const submitter = target.closest?.('[data-submit-closest]');
    if (submitter) {
        event.preventDefault();
        submitter.closest('form')?.submit();
        return;
    }

    // Seleccionar todo o texto de um campo só de leitura ao carregar nele.
    const selectAll = target.closest?.('[data-select-all]');
    if (selectAll) {
        selectAll.select?.();
        return;
    }

    // Copiar um valor para a área de transferência (código de barras, chave PIX).
    const copier = target.closest?.('[data-copy]');
    if (copier) {
        const value = copier.getAttribute('data-copy');
        navigator.clipboard
            ?.writeText(value)
            .then(() => window.alert(document.querySelector('meta[name=copy-success]')?.content || 'Copied!'))
            .catch(() => {});
    }
});

// ── Alterações de campo ──────────────────────────────────────────────────────
//
// Filtros que submetem o formulário assim que o valor muda.

document.addEventListener('change', function (event) {
    const field = event.target;
    if (field instanceof Element && field.hasAttribute('data-auto-submit')) {
        field.closest('form')?.submit();
    }
});

// ── Submissões ───────────────────────────────────────────────────────────────
//
// Confirmação antes de uma acção destrutiva. A mensagem vem do markup porque é
// ela que diz o que vai desaparecer.

document.addEventListener('submit', function (event) {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    const question = form.getAttribute('data-confirm');
    if (question && !window.confirm(question)) {
        event.preventDefault();
    }
});

// ── Imagens em falta ─────────────────────────────────────────────────────────
//
// O `error` de uma imagem não sobe na árvore, por isso o ouvinte é registado na
// fase de captura. O atributo é removido antes de trocar o `src`: se a imagem
// de reserva também faltar, o handler não volta a disparar sobre si mesmo.

document.addEventListener(
    'error',
    function (event) {
        const img = event.target;
        if (!(img instanceof HTMLImageElement)) return;
        const fallback = img.getAttribute('data-fallback-src');
        if (!fallback) return;
        img.removeAttribute('data-fallback-src');
        img.src = fallback;
    },
    true
);

// ── Teclado ──────────────────────────────────────────────────────────────────

document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    if (lightboxIsOpen()) {
        closeLightbox();
        return;
    }
    closeAllModals();
});

// ── Barra lateral da área interna ────────────────────────────────────────────

function toggleSidebar(open) {
    const sidebar = document.querySelector('[data-sidebar]');
    const backdrop = document.querySelector('[data-sidebar-backdrop]');
    if (!sidebar || !backdrop) return;
    sidebar.classList.toggle('-translate-x-full', !open);
    backdrop.classList.toggle('hidden', !open);
}

// ── Mensagem efémera ─────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    const flash = document.querySelector('[data-flash]');
    if (!flash) return;
    setTimeout(() => {
        flash.style.transition = 'opacity 0.5s ease';
        flash.style.opacity = '0';
        setTimeout(() => flash.remove(), 500);
    }, 4000);
});

// ── Visualizador 3D ──────────────────────────────────────────────────────────
//
// O `<model-viewer>` traz consigo um motor WebGL e pesa vários megabytes. Só
// entra quando há mesmo um modelo na página, e só na primeira vez: carregá-lo
// em todas as páginas para servir a minoria que tem um GLB seria fazer toda a
// gente pagar por uma funcionalidade de poucos.
let visualizador3dCarregado = false;

function carregarVisualizador3d() {
    if (visualizador3dCarregado || !document.querySelector('model-viewer')) {
        return;
    }

    visualizador3dCarregado = true;
    import('@google/model-viewer').catch((e) => console.warn('Visualizador 3D indisponível:', e));
}

document.addEventListener('DOMContentLoaded', carregarVisualizador3d);
// Os modelos vivem dentro de separadores e blocos que abrem por clique: sem
// isto, um GLB escondido no arranque nunca chegaria a ser desenhado.
document.addEventListener('click', () => setTimeout(carregarVisualizador3d, 0));
