<?php

namespace Tests\Feature;

use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * Varredura das vistas: nada em `resources/views` pode voltar a depender de
 * script em linha.
 *
 * Existe porque a Content Security Policy é silenciosa do lado do servidor —
 * um `onclick=` novo passa em todos os outros testes e só falha no browser de
 * quem estiver a usar a aplicação. Esta é a única rede que apanha a regressão
 * no sítio onde ela nasce.
 */
class InlineScriptFreeViewsTest extends TestCase
{
    /**
     * Todos os nomes de atributo que começam por `on` são handlers de evento —
     * o HTML não tem nenhum atributo com esse prefixo que não o seja. A
     * expressão é propositadamente genérica para apanhar também os eventos que
     * a aplicação ainda não usa.
     */
    public function test_no_view_uses_an_inline_event_handler(): void
    {
        $offenders = [];

        foreach ($this->views() as $file) {
            $source = $this->withoutBladeComments($file->getContents());

            if (preg_match_all('/\bon[a-z]+\s*=\s*["\']/', $source, $matches)) {
                $offenders[$this->relative($file)] = array_unique($matches[0]);
            }
        }

        $this->assertSame([], $offenders, $this->explain(
            $offenders,
            'Handler de evento em atributo. A CSP bloqueia-o: declare a intenção num atributo '.
            '`data-…` e trate-a em resources/js/app.js, ou num bloco <script @cspNonce> da própria vista.'
        ));
    }

    /**
     * Um `<script>` sem nonce é um bloco que a política deixa de correr — e o
     * sintoma é uma página meia funcional, não um erro.
     */
    public function test_every_script_block_in_the_views_carries_the_nonce(): void
    {
        $offenders = [];

        foreach ($this->views() as $file) {
            $source = $this->withoutBladeComments($file->getContents());

            preg_match_all('/<script\b[^>]*>/', $source, $matches);

            $withoutNonce = array_filter(
                $matches[0],
                fn (string $tag) => ! str_contains($tag, '@cspNonce') && ! str_contains($tag, 'nonce='),
            );

            if ($withoutNonce !== []) {
                $offenders[$this->relative($file)] = array_values($withoutNonce);
            }
        }

        $this->assertSame([], $offenders, $this->explain(
            $offenders,
            'Bloco <script> sem nonce. Escreva `<script @cspNonce>`.'
        ));
    }

    /**
     * `href="javascript:…"` é a outra porta de script em linha, e a política
     * fecha-a do mesmo modo.
     */
    public function test_no_view_uses_a_javascript_url(): void
    {
        $offenders = [];

        foreach ($this->views() as $file) {
            if (str_contains($this->withoutBladeComments($file->getContents()), 'javascript:')) {
                $offenders[] = $this->relative($file);
            }
        }

        $this->assertSame([], $offenders);
    }

    /**
     * Não se comenta a explicação de um handler citando o próprio atributo: a
     * varredura acima não distingue código de comentário, e não vale a pena
     * que distinga — a regra é simples de respeitar.
     *
     * @return iterable<SplFileInfo>
     */
    private function views(): iterable
    {
        return Finder::create()->files()->in(resource_path('views'))->name('*.blade.php');
    }

    private function withoutBladeComments(string $source): string
    {
        return (string) preg_replace('/\{\{--.*?--\}\}/s', '', $source);
    }

    private function relative(SplFileInfo $file): string
    {
        return str_replace(base_path().'/', '', $file->getPathname());
    }

    /**
     * @param  array<string, list<string>>  $offenders
     */
    private function explain(array $offenders, string $advice): string
    {
        if ($offenders === []) {
            return '';
        }

        $lines = [];

        foreach ($offenders as $file => $found) {
            $lines[] = $file.': '.implode(', ', $found);
        }

        return $advice."\n".implode("\n", $lines);
    }
}
