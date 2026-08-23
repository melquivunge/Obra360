<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TIPO-01 a TIPO-05: um projecto tem um tipo — construção, remodelação,
 * projecto de arquitectura ou design de interiores. O tipo escolhe-se na
 * criação, altera-se na edição, filtra a lista e aparece nas páginas, tanto na
 * administração como no portal do cliente.
 */
class ProjectTypeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Payload aceite por store/update, no formato de ProjectCrudTest.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Client $client, array $overrides = []): array
    {
        return array_merge([
            'client_id' => $client->id,
            'name' => 'Residência Alfa',
            'type' => 'construcao',
            'status' => 'planejada',
            'budget' => 50000,
            'show_financials' => '1',
        ], $overrides);
    }

    private function clientUserFor(Project $project): User
    {
        return User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => $project->client_id,
        ]);
    }

    // ── TIPO-02: o que já existia é construção ───────────────

    public function test_a_project_without_a_declared_type_is_construction(): void
    {
        $this->assertSame('construcao', (new Project)->type);
        $this->assertSame('construcao', Project::factory()->create()->type);
    }

    public function test_the_factory_keeps_producing_construction_by_default(): void
    {
        $project = Project::factory()->create();

        $this->assertTrue($project->isSiteWork());
        $this->assertSame('Construção', $project->typeLabel());
        $this->assertSame('obra', $project->noun());
        $this->assertSame('a obra', $project->nounWithArticle());
    }

    public function test_an_architecture_project_is_not_site_work(): void
    {
        $project = Project::factory()->architecture()->create();

        $this->assertFalse($project->isSiteWork());
        $this->assertSame('Projecto de arquitectura', $project->typeLabel());
        $this->assertSame('projecto', $project->noun());
        $this->assertSame('o projecto', $project->nounWithArticle());
    }

    // ── TIPO-03: escolhido na criação, alterado na edição ────

    public function test_the_type_is_saved_when_a_project_is_created(): void
    {
        $client = Client::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.store'), $this->payload($client, [
                'type' => 'design_interiores',
            ]))
            ->assertRedirect();

        $this->assertSame('design_interiores', Project::firstOrFail()->type);
    }

    public function test_the_type_is_saved_when_a_project_is_edited(): void
    {
        $project = Project::factory()->create(['type' => 'construcao']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.projects.update', $project), $this->payload($project->client, [
                'type' => 'arquitetura',
            ]))
            ->assertRedirect(route('admin.projects.show', $project));

        $this->assertSame('arquitetura', $project->fresh()->type);
    }

    public function test_an_unknown_type_is_rejected_on_create(): void
    {
        $client = Client::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.store'), $this->payload($client, [
                'type' => 'paisagismo',
            ]))
            ->assertSessionHasErrors('type');

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_a_missing_type_is_rejected_on_create(): void
    {
        $client = Client::factory()->create();
        $payload = $this->payload($client);
        unset($payload['type']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.store'), $payload)
            ->assertSessionHasErrors('type');

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_an_unknown_type_is_rejected_on_update(): void
    {
        $project = Project::factory()->create(['type' => 'construcao']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.projects.update', $project), $this->payload($project->client, [
                'type' => 'paisagismo',
            ]))
            ->assertSessionHasErrors('type');

        $this->assertSame('construcao', $project->fresh()->type);
    }

    // ── TIPO-05: filtro da lista ─────────────────────────────

    public function test_the_list_filtered_by_type_returns_only_that_type(): void
    {
        Project::factory()->create(['name' => 'ObraDeConstrucaoAlfa']);
        Project::factory()->architecture()->create(['name' => 'ProjectoArquiteturaBeta']);
        Project::factory()->interiorDesign()->create(['name' => 'DesignInterioresGama']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.index', ['type' => 'arquitetura']))
            ->assertOk()
            ->assertSee('ProjectoArquiteturaBeta')
            ->assertDontSee('ObraDeConstrucaoAlfa')
            ->assertDontSee('DesignInterioresGama');
    }

    public function test_the_list_without_a_type_filter_returns_every_type(): void
    {
        Project::factory()->create(['name' => 'ObraDeConstrucaoAlfa']);
        Project::factory()->architecture()->create(['name' => 'ProjectoArquiteturaBeta']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.index'))
            ->assertOk()
            ->assertSee('ObraDeConstrucaoAlfa')
            ->assertSee('ProjectoArquiteturaBeta');
    }

    // ── TIPO-04: o tipo é visível ────────────────────────────

    public function test_the_type_shows_on_the_admin_list(): void
    {
        Project::factory()->architecture()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.index'))
            ->assertOk()
            ->assertSee('Projecto de arquitectura');
    }

    public function test_the_type_shows_on_the_admin_project_page(): void
    {
        $project = Project::factory()->interiorDesign()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.show', $project))
            ->assertOk()
            ->assertSee('Design de interiores');
    }

    public function test_the_type_shows_on_the_client_portal_project_page(): void
    {
        $project = Project::factory()->architecture()->create();

        $this->actingAs($this->clientUserFor($project))
            ->get(route('portal.projects.show', $project))
            ->assertOk()
            ->assertSee('Projecto de arquitectura');
    }

    public function test_the_type_shows_on_the_client_portal_dashboard_cards(): void
    {
        $project = Project::factory()->interiorDesign()->create();

        $this->actingAs($this->clientUserFor($project))
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee('Design de interiores');
    }

    // ── Vocabulário: «obra» não serve para um projecto ───────

    public function test_the_portal_does_not_call_a_design_project_an_obra(): void
    {
        $project = Project::factory()->architecture()->create();

        $this->actingAs($this->clientUserFor($project))
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee('Meus Projectos')
            ->assertDontSee('Minhas Obras</h1>', false);
    }

    public function test_the_history_of_a_design_project_does_not_say_obra_criada(): void
    {
        $client = Client::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.store'), $this->payload($client, [
                'type' => 'arquitetura',
            ]));

        $this->assertDatabaseHas('project_updates', [
            'project_id' => Project::firstOrFail()->id,
            'title' => 'Projecto criado',
            'type' => 'status',
        ]);
    }
}
