{{--
    Campo de palavra-passe com botão de alternância (olho).

    Encaminha todos os atributos recebidos para o <input> — name, id, required,
    autocomplete, placeholder, autofocus, class, style — para não quebrar o
    preenchimento automático dos gestores de palavras-passe nem o `for` das labels.

    Uso:
        <x-password-input id="password" name="password" required autocomplete="current-password" />
--}}
@props([
    'showLabel' => 'Mostrar senha',
    'hideLabel' => 'Ocultar senha',
])

<div x-data="{ mostrar: false }" style="position:relative;display:block;width:100%;">
    {{-- type="password" fica no markup como estado inicial e fallback sem JS; o Alpine passa a controlá-lo --}}
    <input
        type="password"
        x-bind:type="mostrar ? 'text' : 'password'"
        {{-- !important porque o merge do Blade coloca o style do chamador DEPOIS do nosso;
             sem isto um `padding:` abreviado da vista anularia a folga do botão --}}
        {{ $attributes->merge(['style' => 'padding-right:2.5rem !important;']) }}
    >

    <button
        type="button"
        x-on:click="mostrar = !mostrar"
        x-bind:aria-label="mostrar ? @js($hideLabel) : @js($showLabel)"
        x-bind:aria-pressed="mostrar ? 'true' : 'false'"
        aria-label="{{ $showLabel }}"
        aria-pressed="false"
        {{-- `.hover-eye` cobre o rato e o foco de teclado com a mesma regra CSS.
             Eram quatro handlers em linha (mouseover/mouseout/focus/blur) até a
             Content Security Policy passar a proibir script em atributo. --}}
        class="hover-eye"
        style="position:absolute;top:0;right:0;height:100%;width:2.5rem;display:flex;align-items:center;justify-content:center;padding:0;margin:0;background:none;border:none;border-radius:0.5rem;color:var(--muted-foreground);cursor:pointer;opacity:0.75;transition:opacity 0.15s,color 0.15s;"
    >
        {{-- Olho: senha oculta --}}
        <svg x-show="!mostrar" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg"
             fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
             style="width:1.125rem;height:1.125rem;display:block;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        </svg>

        {{-- Olho riscado: senha visível --}}
        <svg x-show="mostrar" x-cloak aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg"
             fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
             style="width:1.125rem;height:1.125rem;display:block;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243" />
        </svg>
    </button>
</div>
