<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Recolhe os relatórios de violação da Content Security Policy.
 *
 * Existe para que o modo de relato (`CSP_REPORT_ONLY=true`) sirva para alguma
 * coisa em produção: sem um destino, o browser do cliente escreve a violação na
 * consola dele e o operador nunca a vê. Ver docs/production-readiness.md.
 *
 * O que é registado é deliberadamente pouco. Um relatório de CSP traz o URL
 * completo da página onde a violação ocorreu, e esse URL pode carregar dados na
 * query string — por isso só o caminho é escrito, nunca a query. O mesmo para o
 * recurso bloqueado.
 */
class CspReportController extends Controller
{
    /**
     * O corpo é lido a partir do conteúdo bruto: o browser envia
     * `application/csp-report`, que o Laravel não trata como JSON.
     */
    public function store(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true);

        $report = is_array($payload) ? ($payload['csp-report'] ?? $payload) : null;

        if (! is_array($report)) {
            return response()->noContent();
        }

        Log::warning('CSP violation', [
            'directive' => $this->field($report, 'effective-directive') ?: $this->field($report, 'violated-directive'),
            'blocked' => $this->withoutQuery($this->field($report, 'blocked-uri')),
            'document' => $this->withoutQuery($this->field($report, 'document-uri')),
            'line' => is_numeric($report['line-number'] ?? null) ? (int) $report['line-number'] : null,
            'disposition' => $this->field($report, 'disposition'),
        ]);

        return response()->noContent();
    }

    /**
     * Um campo do relatório, truncado. O relatório vem de fora e não há limite
     * ao tamanho de um valor lá dentro.
     */
    private function field(array $report, string $key): ?string
    {
        $value = $report[$key] ?? null;

        return is_string($value) ? mb_substr($value, 0, 300) : null;
    }

    /**
     * Corta a query string. `blocked-uri` e `document-uri` são URLs da própria
     * aplicação e a query pode trazer filtros, identificadores e endereços de
     * correio — nada disso pertence ao registo.
     */
    private function withoutQuery(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        return explode('?', $url, 2)[0];
    }
}
