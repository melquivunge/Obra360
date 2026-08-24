<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security headers + Content Security Policy.
 *
 * Set here rather than in the web server config because production runs on
 * cPanel/Apache while local development runs on the Docker nginx — putting them
 * in the application is the only way both get them.
 *
 * ── Sobre a CSP ──────────────────────────────────────────────────────────────
 *
 * O objectivo desta política é travar a injecção de *script*. As telas usam
 * estilos em linha em todo o lado (perto de mil atributos `style=`), por isso
 * `style-src` fica com `'unsafe-inline'`: é uma cedência consciente, não um
 * esquecimento. O raciocínio, e o que seria preciso para a apertar, estão em
 * docs/production-readiness.md.
 *
 * O `script-src` não tem `'unsafe-inline'`. Cada bloco `<script>` legítimo leva
 * um nonce novo por pedido — inclusive os que o `@vite` gera, porque o nonce é
 * registado no Vite antes de a vista ser desenhada. Um `<script>` injectado por
 * um atacante não conhece o nonce e não corre.
 *
 * O nonce é gerado antes de `$next()` de propósito: a vista é desenhada dentro
 * dessa chamada e precisa do valor para o escrever nas etiquetas.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Gera o nonce e regista-o no Vite, que o aplica às etiquetas que
        // emite. `@cspNonce` (ver AppServiceProvider) lê o mesmo valor, por
        // isso há uma única fonte de verdade por pedido.
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            // DENY, e não SAMEORIGIN, para não contradizer o
            // `frame-ancestors 'none'` da CSP. Nada na aplicação é embebido
            // num iframe — não existe um único `<iframe>` nas vistas.
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'X-Permitted-Cross-Domain-Policies' => 'none',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        ];

        // HSTS only over HTTPS — sending it over plain HTTP does nothing and
        // would be wrong for local development.
        if ($request->secure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        if (config('security.csp.enabled')) {
            $headers[$this->cspHeaderName()] = $this->policy($nonce);
        }

        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value, false);
        }

        return $response;
    }

    /**
     * O nome do cabeçalho decide se o browser bloqueia ou apenas relata.
     */
    private function cspHeaderName(): string
    {
        return config('security.csp.report_only')
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';
    }

    /**
     * @return string A política já serializada.
     */
    private function policy(string $nonce): string
    {
        $vite = $this->viteDevOrigins();
        $reverb = $this->reverbOrigins();

        $directives = [
            // Tudo o que não tem directiva própria abaixo fica na origem.
            'default-src' => ["'self'"],

            // Baratas e de valor real: fecham a reescrita do `<base>`, os
            // plugins e o embebimento em iframes de terceiros.
            'base-uri' => ["'self'"],
            'object-src' => ["'none'"],
            'frame-src' => ["'none'"],
            'frame-ancestors' => ["'none'"],

            // Um formulário só pode ser submetido para a própria aplicação.
            'form-action' => ["'self'"],

            // 'unsafe-eval' é necessário: o Alpine 3 avalia as expressões dos
            // `x-data`/`x-on` com `new Function`. Sai daqui no dia em que se
            // passar à build CSP do Alpine (`@alpinejs/csp`), que obriga a
            // registar cada componente como objecto em JavaScript em vez de o
            // escrever no atributo — um trabalho de vista a vista.
            'script-src' => array_merge(
                ["'self'", "'nonce-{$nonce}'", "'unsafe-eval'"],
                $vite,
                $this->extra('extra_script_src'),
            ),

            // 'unsafe-inline' consciente: ver o cabeçalho da classe.
            'style-src' => array_merge(
                ["'self'", "'unsafe-inline'"],
                $vite,
                $this->extra('extra_style_src'),
            ),

            // Sem `data:`. As imagens do browser vêm todas de rotas
            // autenticadas da própria origem; as base64 do relatório em PDF
            // são desenhadas pelo DomPDF no servidor e nunca passam por aqui.
            // `blob:` por causa do visualizador 3D: o <model-viewer> decodifica
            // as texturas do GLB em memória e aponta-lhes URLs blob. Sem isto o
            // modelo desenha-se sem textura nenhuma, em cinzento.
            'img-src' => array_merge(["'self'", 'blob:'], $this->extra('extra_img_src')),

            // As fontes (Instrument Sans, via Bunny) são descarregadas na
            // build e servidas de public/build — nada é pedido ao CDN em
            // tempo de execução.
            'font-src' => array_merge(["'self'"], $vite, $this->extra('extra_font_src')),

            'connect-src' => array_merge(
                ["'self'"],
                $vite,
                $reverb,
                $this->extra('extra_connect_src'),
            ),

            'media-src' => ["'self'"],

            // O service worker do PWA (public/sw.js).
            // `blob:` também aqui: o visualizador 3D descomprime a geometria
            // num worker criado a partir de um blob.
            'worker-src' => ["'self'", 'blob:'],
            'manifest-src' => ["'self'"],
        ];

        $policy = [];

        foreach ($directives as $name => $sources) {
            $policy[] = $name.' '.implode(' ', array_values(array_unique($sources)));
        }

        if ($reportUri = trim((string) config('security.csp.report_uri'))) {
            $policy[] = 'report-uri '.$reportUri;
        }

        return implode('; ', $policy);
    }

    /**
     * Origens configuradas à mão, em lista separada por vírgulas.
     *
     * @return list<string>
     */
    private function extra(string $key): array
    {
        $value = (string) config("security.csp.{$key}", '');

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }

    /**
     * O servidor de HMR do Vite vive numa origem própria (localhost:5173 por
     * omissão), logo os módulos, o CSS e o WebSocket de recarga vêm de fora da
     * aplicação. Sem isto, `composer run dev` deixaria de funcionar.
     *
     * Devolve lista vazia sempre que o ficheiro `hot` não existe — ou seja, em
     * produção e sempre que o Vite não está a correr.
     *
     * @return list<string>
     */
    private function viteDevOrigins(): array
    {
        if (! Vite::isRunningHot()) {
            return [];
        }

        $url = rtrim(trim((string) @file_get_contents(Vite::hotFile())), '/');

        if ($url === '') {
            return [];
        }

        $parts = parse_url($url);

        if (! isset($parts['host'])) {
            return [];
        }

        $authority = $parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
        $secure = ($parts['scheme'] ?? 'http') === 'https';

        return [
            ($secure ? 'https' : 'http').'://'.$authority,
            ($secure ? 'wss' : 'ws').'://'.$authority,
        ];
    }

    /**
     * Origem do WebSocket do Reverb, tal como o browser a vê.
     *
     * Os dois esquemas, e não só o configurado. O `resources/js/app.js` declara
     * `enabledTransports: ['ws', 'wss']`, e o pusher-js usa mesmo os dois: se a
     * primeira ligação falha, a tentativa seguinte muda de esquema. Autorizar
     * só o esquema do `REVERB_SCHEME` deixava a reconexão a bater na política —
     * um sintoma que só aparece quando o WebSocket já está com problemas, que é
     * o pior momento para inventar outro.
     *
     * Emite também a forma sem porto: quando o porto é o predefinido do esquema
     * o pusher-js liga-se sem o escrever no URL, e uma fonte que só trouxesse
     * `:443` não casaria com essa ligação em todos os browsers.
     *
     * @return list<string>
     */
    private function reverbOrigins(): array
    {
        $host = trim((string) config('security.reverb.host'));

        if ($host === '') {
            return [];
        }

        $port = trim((string) config('security.reverb.port'));
        $origins = [];

        foreach (['ws', 'wss'] as $scheme) {
            $origins[] = $scheme.'://'.$host;

            if ($port !== '') {
                $origins[] = $scheme.'://'.$host.':'.$port;
            }
        }

        return $origins;
    }
}
