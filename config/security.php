<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    |
    | Ver docs/production-readiness.md ("Content Security Policy") para a
    | política completa, a razão de cada directiva permissiva e o procedimento
    | de calibração sem deixar de a impor.
    |
    */

    'csp' => [

        // Desligar emite a resposta sem cabeçalho nenhum. Existe como travão
        // de emergência para produção: uma política mal calibrada partiria a
        // aplicação inteira e o operador tem de a poder desligar sem deploy.
        'enabled' => env('CSP_ENABLED', true),

        // Modo de relato: o browser comunica as violações e não bloqueia nada.
        //
        // A omissão é impor, em todos os ambientes. Produção começou em relato
        // para o primeiro deploy não poder partir nada, mas uma política que
        // não bloqueia não é uma protecção — é uma descrição —, e um valor por
        // omissão que só se aperta por intervenção manual fica por apertar.
        // Para voltar a relato temporariamente: CSP_REPORT_ONLY=true no .env.
        'report_only' => filter_var(env('CSP_REPORT_ONLY', false), FILTER_VALIDATE_BOOLEAN),

        // Destino dos relatórios de violação. Vazio remove a directiva
        // `report-uri` — o browser passa a escrever só na consola dele, o que
        // em produção equivale a não observar nada. A rota interna
        // (`csp.report`) escreve um aviso por violação em storage/logs.
        'report_uri' => env('CSP_REPORT_URI', '/_csp/report'),

        // Origens adicionais, separadas por vírgula, para ambientes que este
        // ficheiro não pode adivinhar (um CDN novo, um WebSocket noutro
        // domínio). Existem para não obrigar a um deploy de código quando a
        // política precisa de mais uma origem.
        'extra_script_src' => env('CSP_EXTRA_SCRIPT_SRC', ''),
        'extra_style_src' => env('CSP_EXTRA_STYLE_SRC', ''),
        'extra_img_src' => env('CSP_EXTRA_IMG_SRC', ''),
        'extra_font_src' => env('CSP_EXTRA_FONT_SRC', ''),
        'extra_connect_src' => env('CSP_EXTRA_CONNECT_SRC', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reverb (WebSocket) — origem para `connect-src`
    |--------------------------------------------------------------------------
    |
    | O `resources/js/app.js` liga-se ao Reverb com as variáveis `VITE_REVERB_*`,
    | que são as que o browser vê. São lidas aqui — e não `REVERB_*` — porque é
    | o endereço que o browser usa que a CSP tem de autorizar; num deploy com
    | Reverb atrás de um proxy os dois valores diferem.
    |
    | Sem host configurado nada é acrescentado: `connect-src` fica em 'self' e
    | o Echo, que já hoje falha em silêncio nesse caso, continua a falhar em
    | silêncio.
    |
    */

    'reverb' => [
        'host' => env('VITE_REVERB_HOST', env('REVERB_HOST')),
        'port' => env('VITE_REVERB_PORT', env('REVERB_PORT')),
        'scheme' => env('VITE_REVERB_SCHEME', env('REVERB_SCHEME', 'https')),
    ],

];
