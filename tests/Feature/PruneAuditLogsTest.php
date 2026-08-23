<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * A tabela `audit_logs` é a que cresce mais depressa (uma linha por escrita em
 * nove modelos) e o alojamento de produção é partilhado, daí a política de
 * retenção. O teste que mais interessa é o inverso do óbvio: um comando de
 * limpeza demasiado agressivo apaga o histórico de que se precisa para
 * responder a "quem alterou isto e quando" numa disputa com um cliente.
 */
class PruneAuditLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_logs_older_than_the_retention_window(): void
    {
        $antigo = $this->auditLog('Fora do prazo', now()->subDays(400));

        $this->artisan('obra360:audit:prune --commit')->assertSuccessful();

        $this->assertDatabaseMissing('audit_logs', ['id' => $antigo->id]);
    }

    public function test_it_keeps_logs_inside_the_retention_window(): void
    {
        $ontem = $this->auditLog('De ontem', now()->subDay());
        $meioAno = $this->auditLog('De há meio ano', now()->subDays(180));
        $quaseUmAno = $this->auditLog('De há quase um ano', now()->subDays(364));
        $foraDoPrazo = $this->auditLog('Fora do prazo', now()->subDays(400));

        $this->artisan('obra360:audit:prune --commit')->assertSuccessful();

        $this->assertDatabaseHas('audit_logs', ['id' => $ontem->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $meioAno->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $quaseUmAno->id]);
        $this->assertDatabaseMissing('audit_logs', ['id' => $foraDoPrazo->id]);
    }

    public function test_the_retention_window_is_configurable(): void
    {
        $dentro = $this->auditLog('Dentro dos 30 dias', now()->subDays(10));
        $fora = $this->auditLog('Fora dos 30 dias', now()->subDays(45));

        // Com o prazo por omissão (365 dias) nenhum dos dois é tocado — é a
        // opção que decide, não o acaso das datas escolhidas.
        $this->artisan('obra360:audit:prune --commit')->assertSuccessful();
        $this->assertDatabaseHas('audit_logs', ['id' => $fora->id]);

        $this->artisan('obra360:audit:prune --days=30 --commit')->assertSuccessful();

        $this->assertDatabaseHas('audit_logs', ['id' => $dentro->id]);
        $this->assertDatabaseMissing('audit_logs', ['id' => $fora->id]);
    }

    public function test_a_dry_run_deletes_nothing(): void
    {
        $antigo = $this->auditLog('Fora do prazo', now()->subDays(400));

        $this->artisan('obra360:audit:prune')->assertSuccessful();

        $this->assertDatabaseHas('audit_logs', ['id' => $antigo->id]);
    }

    public function test_a_non_positive_retention_window_is_refused(): void
    {
        $antigo = $this->auditLog('Fora de qualquer prazo', now()->subYears(5));

        $this->artisan('obra360:audit:prune --days=0 --commit')->assertFailed();
        $this->artisan('obra360:audit:prune --days=-1 --commit')->assertFailed();
        $this->artisan('obra360:audit:prune --days=abc --commit')->assertFailed();

        $this->assertDatabaseHas('audit_logs', ['id' => $antigo->id]);
    }

    public function test_it_deletes_every_expired_row_beyond_a_single_chunk(): void
    {
        for ($i = 0; $i < 1005; $i++) {
            $this->auditLog("Antigo {$i}", now()->subDays(400));
        }
        $recente = $this->auditLog('Recente', now()->subDay());

        $this->artisan('obra360:audit:prune --commit')->assertSuccessful();

        $this->assertSame(1, AuditLog::count());
        $this->assertDatabaseHas('audit_logs', ['id' => $recente->id]);
    }

    private function auditLog(string $description, Carbon $createdAt): AuditLog
    {
        $log = AuditLog::create([
            'user_name' => 'Encarregado',
            'action' => 'updated',
            'auditable_type' => Project::class,
            'auditable_id' => 1,
            'description' => $description,
        ]);

        // `created_at` não é fillable e é preenchido pelo Eloquent na criação.
        $log->forceFill(['created_at' => $createdAt])->save();

        return $log;
    }
}
