<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Plan;
use App\Models\PlanComment;
use App\Models\PlanVersion;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Comentários presos a uma versão de planta.
 *
 * A regra que os governa é a mesma que governa o download, a visualização e a
 * aprovação: uma versão que ainda não foi enviada não existe para o cliente —
 * e a resposta é 404 e não 403, porque um 403 confirmaria que o esboço existe.
 */
class PlanCommentTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;

    private User $clientUser;

    private User $manager;

    private Project $project;

    private Plan $plan;

    private PlanVersion $sent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = Client::factory()->create();
        $this->clientUser = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => $this->client->id,
        ]);
        $this->manager = User::factory()->manager()->create();
        $this->project = Project::factory()->create([
            'client_id' => $this->client->id,
            'manager_id' => $this->manager->id,
            'type' => 'arquitetura',
        ]);
        $this->plan = Plan::factory()->create(['project_id' => $this->project->id]);
        $this->sent = PlanVersion::factory()->create([
            'plan_id' => $this->plan->id,
            'version' => 1,
            'sent_to_client_at' => now(),
        ]);
    }

    private function clientRoute(PlanVersion $version): string
    {
        return route('portal.projects.plans.versions.comments.store', [$this->project, $this->plan, $version]);
    }

    private function teamRoute(PlanVersion $version): string
    {
        return route('admin.projects.plans.versions.comments.store', [$this->project, $this->plan, $version]);
    }

    public function test_the_client_comments_a_version_that_was_sent_to_them(): void
    {
        $this->actingAs($this->clientUser)
            ->post($this->clientRoute($this->sent), ['body' => 'A cozinha ficou pequena nesta parede.'])
            ->assertRedirect();

        $this->assertDatabaseHas('plan_comments', [
            'plan_version_id' => $this->sent->id,
            'user_id' => $this->clientUser->id,
            'body' => 'A cozinha ficou pequena nesta parede.',
        ]);
    }

    public function test_the_client_cannot_comment_a_version_that_was_never_sent(): void
    {
        $draft = PlanVersion::factory()->create([
            'plan_id' => $this->plan->id,
            'version' => 2,
            'sent_to_client_at' => null,
        ]);

        $this->actingAs($this->clientUser)
            ->post($this->clientRoute($draft), ['body' => 'Não devia conseguir'])
            ->assertNotFound();

        $this->assertDatabaseCount('plan_comments', 0);
    }

    public function test_a_client_cannot_comment_another_clients_plan(): void
    {
        $outro = Project::factory()->create(['client_id' => Client::factory()]);
        $planta = Plan::factory()->create(['project_id' => $outro->id]);
        $versao = PlanVersion::factory()->create([
            'plan_id' => $planta->id,
            'version' => 1,
            'sent_to_client_at' => now(),
        ]);

        $this->actingAs($this->clientUser)
            ->post(route('portal.projects.plans.versions.comments.store', [$outro, $planta, $versao]), ['body' => 'Olá'])
            ->assertForbidden();

        $this->assertDatabaseCount('plan_comments', 0);
    }

    public function test_the_team_comments_on_the_same_thread(): void
    {
        $this->actingAs($this->clientUser)
            ->post($this->clientRoute($this->sent), ['body' => 'Pedido do cliente']);

        $this->actingAs($this->manager)
            ->post($this->teamRoute($this->sent), ['body' => 'Resposta da equipe'])
            ->assertRedirect();

        $this->assertSame(
            ['Pedido do cliente', 'Resposta da equipe'],
            $this->sent->comments()->pluck('body')->all()
        );
    }

    public function test_comments_on_a_superseded_version_stay_readable(): void
    {
        PlanComment::factory()->create([
            'plan_version_id' => $this->sent->id,
            'user_id' => $this->clientUser->id,
            'body' => 'Pedido feito sobre a v1',
        ]);

        // Chega a v2 e a v1 passa a substituída — o pedido continua na v1.
        $this->sent->update(['superseded_at' => now()]);
        PlanVersion::factory()->create([
            'plan_id' => $this->plan->id,
            'version' => 2,
            'sent_to_client_at' => now(),
        ]);

        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.show', $this->project))
            ->assertOk()
            ->assertSee('Pedido feito sobre a v1');

        $this->actingAs($this->manager)
            ->get(route('admin.projects.show', $this->project).'?tab=plantas')
            ->assertOk()
            ->assertSee('Pedido feito sobre a v1');
    }

    public function test_both_sides_see_the_same_thread(): void
    {
        PlanComment::factory()->create([
            'plan_version_id' => $this->sent->id,
            'user_id' => $this->clientUser->id,
            'body' => 'Escrito pelo cliente',
        ]);
        PlanComment::factory()->create([
            'plan_version_id' => $this->sent->id,
            'user_id' => $this->manager->id,
            'body' => 'Escrito pela equipe',
        ]);

        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.show', $this->project))
            ->assertSee('Escrito pelo cliente')
            ->assertSee('Escrito pela equipe');

        $this->actingAs($this->manager)
            ->get(route('admin.projects.show', $this->project).'?tab=plantas')
            ->assertSee('Escrito pelo cliente')
            ->assertSee('Escrito pela equipe');
    }

    public function test_an_empty_comment_is_refused(): void
    {
        $this->actingAs($this->clientUser)
            ->post($this->clientRoute($this->sent), ['body' => ''])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('plan_comments', 0);
    }

    public function test_a_manager_cannot_comment_on_another_managers_project(): void
    {
        $outro = Project::factory()->create();
        $planta = Plan::factory()->create(['project_id' => $outro->id]);
        $versao = PlanVersion::factory()->create(['plan_id' => $planta->id, 'version' => 1]);

        $this->actingAs($this->manager)
            ->post(route('admin.projects.plans.versions.comments.store', [$outro, $planta, $versao]), ['body' => 'Olá'])
            ->assertForbidden();
    }

    public function test_a_version_from_another_plan_returns_404(): void
    {
        $outraPlanta = Plan::factory()->create(['project_id' => $this->project->id]);
        $versao = PlanVersion::factory()->create([
            'plan_id' => $outraPlanta->id,
            'version' => 1,
            'sent_to_client_at' => now(),
        ]);

        $this->actingAs($this->manager)
            ->post($this->teamRoute($versao), ['body' => 'Olá'])
            ->assertNotFound();
    }
}
