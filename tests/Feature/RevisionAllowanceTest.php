<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Plan;
use App\Models\PlanVersion;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * As revisões incluídas no contrato — a maior fonte de discussão em trabalho
 * de arquitetura: «o contrato incluía três alterações, esta é a quinta».
 *
 * Três regras moldam estes testes. Só conta o que foi **enviado ao cliente**:
 * uma versão que o estúdio criou e descartou não gastou nada a ninguém. A
 * primeira entrega de cada planta não é revisão. E o limite **nunca** impede
 * um envio — o estúdio pode oferecer uma revisão, e um sistema que trava o
 * trabalho acaba contornado por fora.
 */
class RevisionAllowanceTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create([
            'client_id' => Client::factory(),
            'type' => 'arquitetura',
            'revisions_included' => 3,
        ]);
    }

    /** Cria uma planta com N versões, das quais as $sent primeiras foram enviadas. */
    private function planWithVersions(int $total, int $sent): Plan
    {
        $plan = Plan::factory()->create(['project_id' => $this->project->id]);

        for ($i = 1; $i <= $total; $i++) {
            PlanVersion::factory()->create([
                'plan_id' => $plan->id,
                'version' => $i,
                'sent_to_client_at' => $i <= $sent ? now() : null,
            ]);
        }

        return $plan;
    }

    public function test_the_first_delivery_of_a_plan_is_not_a_revision(): void
    {
        $this->planWithVersions(total: 1, sent: 1);

        $this->assertSame(0, $this->project->revisionsUsed());
        $this->assertSame(3, $this->project->revisionsRemaining());
    }

    public function test_each_version_sent_after_the_first_counts_as_one_revision(): void
    {
        $this->planWithVersions(total: 3, sent: 3);

        $this->assertSame(2, $this->project->revisionsUsed());
        $this->assertSame(1, $this->project->revisionsRemaining());
    }

    public function test_a_version_never_sent_to_the_client_costs_nothing(): void
    {
        // Cinco versões, mas só duas chegaram ao cliente: uma revisão, não quatro.
        $this->planWithVersions(total: 5, sent: 2);

        $this->assertSame(1, $this->project->revisionsUsed());
    }

    public function test_revisions_from_several_plans_add_up(): void
    {
        $this->planWithVersions(total: 3, sent: 3);
        $this->planWithVersions(total: 2, sent: 2);

        $this->assertSame(3, $this->project->revisionsUsed());
        $this->assertSame(0, $this->project->revisionsRemaining());
    }

    public function test_a_project_without_a_contract_limit_is_never_over(): void
    {
        $this->project->update(['revisions_included' => null]);
        $this->planWithVersions(total: 9, sent: 9);

        $this->assertFalse($this->project->hasRevisionLimit());
        $this->assertNull($this->project->revisionsRemaining());
        $this->assertFalse($this->project->revisionLimitExceeded());
    }

    public function test_an_empty_limit_is_not_the_same_as_zero(): void
    {
        $semLimite = Project::factory()->create([
            'client_id' => Client::factory(),
            'revisions_included' => null,
        ]);
        $semRevisoes = Project::factory()->create([
            'client_id' => Client::factory(),
            'revisions_included' => 0,
        ]);

        $this->assertFalse($semLimite->hasRevisionLimit());
        $this->assertTrue($semRevisoes->hasRevisionLimit());
    }

    public function test_going_over_the_limit_is_reported_and_not_blocked(): void
    {
        $this->planWithVersions(total: 6, sent: 6);

        $this->assertTrue($this->project->revisionLimitExceeded());
        $this->assertSame(-2, $this->project->revisionsRemaining());

        // A porta continua aberta: enviar mais uma versão não é recusado.
        $plan = Plan::factory()->create(['project_id' => $this->project->id]);
        $version = PlanVersion::factory()->create(['plan_id' => $plan->id, 'version' => 1]);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.plans.versions.send', [$this->project, $plan, $version]))
            ->assertRedirect();

        $this->assertNotNull($version->fresh()->sent_to_client_at);
    }

    public function test_the_counter_is_shown_to_the_team_and_to_the_client(): void
    {
        $this->planWithVersions(total: 3, sent: 3);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.show', $this->project).'?tab=plantas')
            ->assertOk()
            ->assertSee('Revisões do contrato:')
            ->assertSee('2 de 3');

        $clientUser = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => $this->project->client_id,
        ]);

        $this->actingAs($clientUser)
            ->get(route('portal.projects.show', $this->project))
            ->assertOk()
            ->assertSee('Revisões do contrato:')
            ->assertSee('2 de 3');
    }

    public function test_a_project_without_a_limit_shows_no_counter(): void
    {
        $this->project->update(['revisions_included' => null]);
        $this->planWithVersions(total: 2, sent: 2);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.show', $this->project).'?tab=plantas')
            ->assertOk()
            ->assertDontSee('Revisões do contrato:');
    }

    public function test_the_limit_is_saved_from_the_form_and_validated(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.projects.update', $this->project), [
                'client_id' => $this->project->client_id,
                'name' => $this->project->name,
                'status' => $this->project->status,
                'type' => $this->project->type,
                'revisions_included' => 5,
            ])->assertRedirect();

        $this->assertSame(5, $this->project->fresh()->revisions_included);

        $this->actingAs($admin)
            ->put(route('admin.projects.update', $this->project), [
                'client_id' => $this->project->client_id,
                'name' => $this->project->name,
                'status' => $this->project->status,
                'type' => $this->project->type,
                'revisions_included' => -1,
            ])->assertSessionHasErrors('revisions_included');
    }
}
