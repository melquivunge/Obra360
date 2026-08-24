<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

/**
 * Política de retenção da tabela `audit_logs` (SEC-05).
 *
 * O AuditObserver escreve uma linha por cada create/update/delete/restore em
 * nove modelos, portanto esta é a tabela que cresce mais depressa da aplicação
 * — e o alojamento de produção é partilhado, com espaço e base de dados
 * finitos.
 *
 * À semelhança do `obra360:media:migrate-public`, o modo por omissão é de
 * ensaio: sem `--commit` o comando conta e mostra, mas não apaga nada. O
 * agendador chama-o explicitamente com `--commit`.
 */
class PruneAuditLogs extends Command
{
    /**
     * Prazo de retenção por omissão, em dias.
     *
     * Um ano cobre o ciclo completo de uma obra mais o fecho de contas do
     * exercício seguinte, que é a janela em que a pergunta "quem alterou isto
     * e quando" costuma aparecer — reclamação de cliente, disputa de valores,
     * auditoria contabilística. Prazos mais curtos apagam o histórico enquanto
     * a obra ainda está a decorrer.
     */
    public const DEFAULT_DAYS = 365;

    /**
     * Quantas linhas apagar de cada vez. Deletes pequenos e repetidos evitam
     * bloqueios longos e transações grandes num MySQL partilhado.
     */
    private const CHUNK = 1000;

    protected $signature = 'obra360:audit:prune
        {--days= : Dias de histórico a manter (por omissão 365)}
        {--commit : Excluir de verdade; sem esta opção é apenas uma simulação}';

    protected $description = 'Exclui registros de auditoria mais antigos do que o prazo de retenção (padrão de 365 dias).';

    public function handle(): int
    {
        $days = $this->retentionDays();

        if ($days === null) {
            $this->error('--days tem de ser um número inteiro maior do que zero.');

            return self::FAILURE;
        }

        // Corta no início do dia para o limite ser estável ao longo do dia e
        // ligeiramente conservador: o dia mais antigo é mantido por inteiro.
        $cutoff = now()->subDays($days)->startOfDay();

        $total = AuditLog::query()->where('created_at', '<', $cutoff)->count();

        $this->line("Prazo de retenção: {$days} dia(s) — alvo: registros anteriores a {$cutoff->toDateTimeString()}.");

        if ($total === 0) {
            $this->info('Nada a fazer — não há registros fora do prazo.');

            return self::SUCCESS;
        }

        if (! $this->option('commit')) {
            $this->warn("Simulação: {$total} registro(s) seriam excluídos.");
            $this->comment('Execute novamente com --commit para excluir.');

            return self::SUCCESS;
        }

        $deleted = 0;

        do {
            $ids = AuditLog::query()
                ->where('created_at', '<', $cutoff)
                ->orderBy('id')
                ->limit(self::CHUNK)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $deleted += AuditLog::query()->whereKey($ids)->delete();
        } while ($ids->count() === self::CHUNK);

        $this->info("Registros de auditoria excluídos: {$deleted}.");

        return self::SUCCESS;
    }

    /**
     * Devolve o prazo de retenção pedido, ou null se a opção for inválida.
     *
     * Um prazo de zero ou negativo apagaria o registo de auditoria todo — é
     * recusado em vez de interpretado.
     */
    private function retentionDays(): ?int
    {
        $option = $this->option('days');

        if ($option === null) {
            return self::DEFAULT_DAYS;
        }

        if (! is_numeric($option) || (float) $option !== (float) (int) $option || (int) $option < 1) {
            return null;
        }

        return (int) $option;
    }
}
