<?php

namespace App\Http\Middleware;

use App\Models\PlanVersion;
use Closure;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;

/**
 * LIM-02: um upload acima do `post_max_size` do PHP nunca chega à validação.
 *
 * O PHP descarta o corpo do pedido antes de o entregar à aplicação: `$_POST` e
 * `$_FILES` chegam vazios e com eles desaparece o `_token`. O que o utilizador
 * veria, sem esta guarda, é «419 — a página expirou», sobre um formulário que
 * acabou de abrir; e se o token sobrevivesse, «o campo ficheiro é obrigatório»
 * a quem escolheu um ficheiro. As duas respostas mandam a pessoa procurar o
 * problema no sítio errado.
 *
 * O caso mais comum — `Content-Length` acima do `post_max_size` — é travado
 * antes disto pelo `ValidatePostSize` do Laravel, que devolve 413; a resposta
 * honesta para esse está em resources/views/errors/413.blade.php. Esta classe
 * apanha o irmão silencioso: um pedido em `Transfer-Encoding: chunked`, que não
 * anuncia comprimento nenhum, escapa àquela verificação e chega aqui vazio.
 *
 * Ocupa o lugar do middleware de CSRF no grupo `web` (ver bootstrap/app.php)
 * porque é aí que a resposta errada nasce, e porque é o primeiro ponto do
 * pipeline com sessão disponível para devolver o erro ao formulário.
 *
 * A condição é apertada de propósito: um POST `multipart/form-data` sem um
 * único campo e sem um único ficheiro. Nenhum formulário legítimo tem essa
 * forma — todos levam pelo menos o `_token`.
 */
class ExplainTruncatedUpload extends PreventRequestForgery
{
    public function handle($request, Closure $next)
    {
        if ($this->isTruncated($request)) {
            return back()->withErrors(['file' => PlanVersion::truncatedUploadMessage()]);
        }

        return parent::handle($request, $next);
    }

    private function isTruncated(Request $request): bool
    {
        if (! $request->isMethod('POST')) {
            return false;
        }

        if (! str_contains((string) $request->header('Content-Type'), 'multipart/form-data')) {
            return false;
        }

        return $request->request->count() === 0 && $request->files->count() === 0;
    }
}
