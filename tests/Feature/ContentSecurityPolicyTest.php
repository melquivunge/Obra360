<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

/**
 * A Content Security Policy: o cabeçalho, o nonce por pedido e o modo de relato.
 *
 * A varredura das vistas (nenhum handler em linha, nenhum <script> sem nonce)
 * está em InlineScriptFreeViewsTest.
 */
class ContentSecurityPolicyTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = 'Content-Security-Policy';

    private const HEADER_REPORT_ONLY = 'Content-Security-Policy-Report-Only';

    private function policyOn(string $uri = '/login'): string
    {
        return (string) $this->get($uri)->headers->get(self::HEADER);
    }

    // ── O cabeçalho ──────────────────────────────────────────────────────────

    public function test_the_policy_is_enforced_outside_production(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $this->assertNotNull($response->headers->get(self::HEADER));
        $this->assertNull($response->headers->get(self::HEADER_REPORT_ONLY));
    }

    public function test_the_policy_blocks_script_injection(): void
    {
        $policy = $this->policyOn();

        $this->assertMatchesRegularExpression("/script-src [^;]*'nonce-[A-Za-z0-9]+'/", $policy);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $policy);

        // A directiva de script não pode ganhar `'unsafe-inline'` por descuido:
        // é o único ponto de toda esta política que a torna útil.
        preg_match('/script-src ([^;]+)/', $policy, $m);
        $this->assertStringNotContainsString("'unsafe-inline'", $m[1]);
    }

    public function test_the_cheap_lockdown_directives_are_present(): void
    {
        $policy = $this->policyOn();

        $this->assertStringContainsString("object-src 'none'", $policy);
        $this->assertStringContainsString("base-uri 'self'", $policy);
        $this->assertStringContainsString("form-action 'self'", $policy);
        $this->assertStringContainsString("frame-ancestors 'none'", $policy);
        $this->assertStringContainsString("default-src 'self'", $policy);
        $this->assertStringContainsString("worker-src 'self'", $policy);
    }

    /**
     * `frame-ancestors 'none'` e `X-Frame-Options: SAMEORIGIN` diriam coisas
     * diferentes ao mesmo browser. Alinhados, dizem a mesma.
     */
    public function test_frame_ancestors_agrees_with_x_frame_options(): void
    {
        $response = $this->get(route('login'));

        $this->assertStringContainsString("frame-ancestors 'none'", (string) $response->headers->get(self::HEADER));
        $response->assertHeader('X-Frame-Options', 'DENY');
    }

    /**
     * A cedência assumida: as vistas trazem perto de mil atributos `style=`.
     * Se esta asserção falhar por `style-src` ter deixado de a ter, é porque
     * alguém apertou a política — o que é bom, mas tem de ser deliberado.
     */
    public function test_style_src_keeps_unsafe_inline_deliberately(): void
    {
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline'", $this->policyOn());
    }

    /**
     * O Alpine avalia as expressões dos atributos com `new Function`. Sem isto
     * nenhum `x-data` funcionava.
     */
    public function test_script_src_keeps_unsafe_eval_for_alpine(): void
    {
        $this->assertStringContainsString("'unsafe-eval'", $this->policyOn());
    }

    /**
     * As imagens do browser vêm de rotas da própria origem, mais os blobs que
     * o visualizador 3D cria em memória ao decodificar as texturas de um GLB.
     *
     * `data:` continua **fora**: as base64 dos relatórios são desenhadas pelo
     * DomPDF no servidor e nunca passam por uma política de browser, portanto
     * permiti-las só abriria caminho a conteúdo injectado.
     */
    public function test_img_src_allows_only_the_origin_and_blobs(): void
    {
        $policy = $this->policyOn();

        preg_match('/img-src ([^;]+)/', $policy, $m);
        $this->assertSame("'self' blob:", trim($m[1]));
        $this->assertStringNotContainsString('data:', $m[1]);
    }

    // ── O nonce ──────────────────────────────────────────────────────────────

    public function test_the_nonce_changes_on_every_request(): void
    {
        $first = $this->nonceFrom($this->policyOn());
        $second = $this->nonceFrom($this->policyOn());

        $this->assertNotSame('', $first);
        $this->assertNotSame($first, $second);
    }

    public function test_every_script_tag_on_a_page_carries_the_request_nonce(): void
    {
        $project = Project::factory()->create();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.show', $project))
            ->assertOk();

        $nonce = $this->nonceFrom((string) $response->headers->get(self::HEADER));
        preg_match_all('/<script\b[^>]*>/', $response->getContent(), $tags);

        $this->assertNotEmpty($tags[0], 'A página não tem <script> nenhum — a asserção deixou de valer.');

        foreach ($tags[0] as $tag) {
            $this->assertStringContainsString('nonce="'.$nonce.'"', $tag);
        }
    }

    /**
     * O `@vite` recebe o mesmo nonce que o cabeçalho anuncia: sem isto o pacote
     * da aplicação seria bloqueado e não sobrava nada a funcionar.
     */
    public function test_the_vite_tags_carry_the_request_nonce(): void
    {
        $response = $this->get(route('login'))->assertOk();

        $nonce = $this->nonceFrom((string) $response->headers->get(self::HEADER));

        $this->assertMatchesRegularExpression(
            '/<script[^>]*type="module"[^>]*nonce="'.preg_quote($nonce, '/').'"/',
            $response->getContent()
        );
    }

    // ── Modo de relato ───────────────────────────────────────────────────────

    public function test_report_only_mode_swaps_the_header_and_blocks_nothing(): void
    {
        config(['security.csp.report_only' => true]);

        $response = $this->get(route('login'))->assertOk();

        $this->assertNull($response->headers->get(self::HEADER));
        $reported = (string) $response->headers->get(self::HEADER_REPORT_ONLY);
        $this->assertStringContainsString("object-src 'none'", $reported);
        $this->assertStringContainsString('report-uri', $reported);
    }

    public function test_the_policy_can_be_switched_off_entirely(): void
    {
        config(['security.csp.enabled' => false]);

        $response = $this->get(route('login'))->assertOk();

        $this->assertNull($response->headers->get(self::HEADER));
        $this->assertNull($response->headers->get(self::HEADER_REPORT_ONLY));
        // O resto dos cabeçalhos não depende da CSP.
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_an_empty_report_uri_removes_the_directive(): void
    {
        config(['security.csp.report_uri' => '']);

        $this->assertStringNotContainsString('report-uri', $this->policyOn());
    }

    public function test_the_report_endpoint_logs_the_violation_without_the_query_string(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message, array $context) {
                return $message === 'CSP violation'
                    && $context['directive'] === 'script-src'
                    && $context['blocked'] === 'inline'
                    && $context['document'] === 'https://obra360.test/admin/obras/7';
            });

        $this->call(
            'POST',
            route('csp.report'),
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/csp-report'],
            json_encode(['csp-report' => [
                'effective-directive' => 'script-src',
                'blocked-uri' => 'inline',
                'document-uri' => 'https://obra360.test/admin/obras/7?email=cliente@exemplo.pt',
                'line-number' => 42,
            ]])
        )->assertNoContent();
    }

    public function test_the_report_endpoint_survives_a_body_that_is_not_a_report(): void
    {
        Log::shouldReceive('warning')->never();

        $this->call('POST', route('csp.report'), [], [], [], [], 'isto não é json')
            ->assertNoContent();
    }

    // ── Origens externas ─────────────────────────────────────────────────────

    /**
     * O WebSocket do Reverb vive fora da origem da aplicação; sem isto em
     * `connect-src` o portal do cliente perdia o tempo real.
     *
     * Os dois esquemas têm de lá estar mesmo com `REVERB_SCHEME=https`: o
     * pusher-js está configurado com `enabledTransports: ['ws', 'wss']` e troca
     * de esquema quando uma ligação falha. Foi assim que isto se descobriu — a
     * primeira versão desta política autorizava só `wss` e o browser bloqueou a
     * reconexão em `ws`, e vice-versa.
     */
    public function test_both_websocket_schemes_of_the_reverb_host_are_allowed(): void
    {
        config([
            'security.reverb.host' => 'ws.obra360app.com',
            'security.reverb.port' => '443',
            'security.reverb.scheme' => 'https',
        ]);

        $policy = $this->policyOn();

        foreach (['ws://ws.obra360app.com', 'ws://ws.obra360app.com:443',
            'wss://ws.obra360app.com', 'wss://ws.obra360app.com:443'] as $origin) {
            $this->assertStringContainsString($origin, $policy);
        }
    }

    public function test_without_reverb_configured_connect_src_stays_on_the_origin(): void
    {
        config(['security.reverb.host' => null]);

        preg_match('/connect-src ([^;]+)/', $this->policyOn(), $m);

        $this->assertSame("'self'", trim($m[1]));
    }

    /**
     * O servidor de HMR do Vite tem origem própria: os módulos, o CSS e o
     * WebSocket de recarga vêm todos de fora da aplicação. Sem isto,
     * `composer run dev` deixava de desenhar a página.
     */
    public function test_the_vite_dev_server_origin_is_allowed_while_it_is_running(): void
    {
        $hotFile = tempnam(sys_get_temp_dir(), 'obra360-hot-');
        file_put_contents($hotFile, 'http://localhost:5173');
        Vite::useHotFile($hotFile);

        try {
            $policy = $this->policyOn();
        } finally {
            @unlink($hotFile);
        }

        $this->assertStringContainsString('script-src', $policy);
        $this->assertStringContainsString('http://localhost:5173', $policy);
        // O WebSocket da recarga a quente vive no mesmo porto, noutro esquema.
        $this->assertStringContainsString('ws://localhost:5173', $policy);

        preg_match('/style-src ([^;]+)/', $policy, $style);
        $this->assertStringContainsString('http://localhost:5173', $style[1]);

        preg_match('/font-src ([^;]+)/', $policy, $font);
        $this->assertStringContainsString('http://localhost:5173', $font[1]);
    }

    /**
     * Em produção o ficheiro `hot` não existe e a política não deve trazer
     * origem nenhuma de desenvolvimento.
     */
    public function test_without_the_hot_file_no_dev_origin_leaks_into_the_policy(): void
    {
        Vite::useHotFile(sys_get_temp_dir().'/obra360-hot-que-nao-existe');

        $policy = $this->policyOn();
        preg_match('/script-src ([^;]+)/', $policy, $m);

        $this->assertSame("'self' 'nonce-".$this->nonceFrom($policy)."' 'unsafe-eval'", trim($m[1]));
    }

    /**
     * A válvula de escape para um ambiente que precise de mais uma origem sem
     * esperar por um deploy de código.
     */
    public function test_extra_origins_can_be_added_by_configuration(): void
    {
        config(['security.csp.extra_connect_src' => 'https://um.exemplo.pt, https://outro.exemplo.pt']);

        $policy = $this->policyOn();

        $this->assertStringContainsString('https://um.exemplo.pt', $policy);
        $this->assertStringContainsString('https://outro.exemplo.pt', $policy);
    }

    private function nonceFrom(string $policy): string
    {
        preg_match("/'nonce-([A-Za-z0-9]+)'/", $policy, $m);

        return $m[1] ?? '';
    }
}
