<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Payload aceite por store/update — só os campos obrigatórios mais os
     * habituais do formulário.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Client $client, array $overrides = []): array
    {
        return array_merge([
            'client_id' => $client->id,
            'name' => 'Residência Alfa',
            'code' => 'OBR-001',
            'type' => 'construcao',
            'status' => 'planejada',
            'budget' => 50000,
            'location' => 'Luanda',
            'show_financials' => '1',
        ], $overrides);
    }

    public function test_admin_can_create_a_project(): void
    {
        $client = Client::factory()->create();
        $manager = User::factory()->manager()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.store'), $this->payload($client, [
                'manager_id' => $manager->id,
            ]))
            ->assertRedirect();

        $project = Project::firstOrFail();
        $this->assertSame('Residência Alfa', $project->name);
        $this->assertSame($client->id, $project->client_id);
        $this->assertSame($manager->id, $project->manager_id);
        $this->assertSame(0, $project->progress);
        $this->assertTrue($project->show_financials);
    }

    public function test_creating_a_project_records_it_in_the_history(): void
    {
        $client = Client::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.store'), $this->payload($client));

        $this->assertDatabaseHas('project_updates', [
            'project_id' => Project::firstOrFail()->id,
            'title' => 'Obra criada',
            'type' => 'status',
        ]);
    }

    public function test_a_manager_creating_a_project_becomes_its_manager(): void
    {
        $client = Client::factory()->create();
        $manager = User::factory()->manager()->create();
        $otherManager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->post(route('admin.projects.store'), $this->payload($client, [
                'manager_id' => $otherManager->id,
            ]))
            ->assertRedirect();

        $this->assertSame($manager->id, Project::firstOrFail()->manager_id);
    }

    public function test_invalid_payload_is_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.store'), [
                'client_id' => 999,
                'name' => '',
                'status' => 'inexistente',
                'budget' => -10,
            ])
            ->assertSessionHasErrors(['client_id', 'name', 'status', 'budget']);

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_admin_can_update_a_project(): void
    {
        $project = Project::factory()->create(['name' => 'Nome antigo']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.projects.update', $project), $this->payload($project->client, [
                'name' => 'Nome novo',
                'status' => 'em_andamento',
            ]))
            ->assertRedirect(route('admin.projects.show', $project));

        $project->refresh();
        $this->assertSame('Nome novo', $project->name);
        $this->assertSame('em_andamento', $project->status);
    }

    public function test_changing_the_status_records_it_in_the_history(): void
    {
        $project = Project::factory()->create(['status' => 'planejada']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.projects.update', $project), $this->payload($project->client, [
                'status' => 'concluida',
            ]));

        $this->assertDatabaseHas('project_updates', [
            'project_id' => $project->id,
            'title' => 'Status alterado para: Concluída',
            'type' => 'status',
        ]);
    }

    public function test_keeping_the_same_status_records_nothing(): void
    {
        $project = Project::factory()->create(['status' => 'planejada']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.projects.update', $project), $this->payload($project->client, [
                'status' => 'planejada',
            ]));

        $this->assertSame(0, ProjectUpdate::where('project_id', $project->id)->count());
    }

    /**
     * O formulário de edição usa uma checkbox sem campo escondido: um PUT que
     * não envie `show_financials` desliga o resumo financeiro do cliente.
     * Documenta o comportamento atual — ver relatório.
     */
    public function test_updating_without_show_financials_turns_the_flag_off(): void
    {
        $project = Project::factory()->create(['show_financials' => true]);
        $payload = $this->payload($project->client);
        unset($payload['show_financials']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.projects.update', $project), $payload);

        $this->assertFalse($project->fresh()->show_financials);
    }

    public function test_admin_can_soft_delete_a_project(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.projects.destroy', $project))
            ->assertRedirect(route('admin.projects.index'));

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    public function test_a_soft_deleted_project_disappears_from_the_list(): void
    {
        $project = Project::factory()->create(['name' => 'ObraRemovidaZeta']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->delete(route('admin.projects.destroy', $project));

        $this->actingAs($admin)
            ->get(route('admin.projects.index'))
            ->assertOk()
            ->assertDontSee('ObraRemovidaZeta');
    }

    // ── PROJ-05: âmbito de escrita do gestor ─────────────────

    public function test_manager_can_update_their_own_project(): void
    {
        $manager = User::factory()->manager()->create();
        $project = Project::factory()->create(['manager_id' => $manager->id]);

        $this->actingAs($manager)
            ->put(route('admin.projects.update', $project), $this->payload($project->client, [
                'name' => 'Editada pelo gestor',
            ]))
            ->assertRedirect();

        $this->assertSame('Editada pelo gestor', $project->fresh()->name);
    }

    public function test_manager_cannot_open_the_edit_form_of_another_managers_project(): void
    {
        $manager = User::factory()->manager()->create();
        $project = Project::factory()->create();

        $this->actingAs($manager)
            ->get(route('admin.projects.edit', $project))
            ->assertForbidden();
    }

    public function test_manager_cannot_update_another_managers_project(): void
    {
        $manager = User::factory()->manager()->create();
        $project = Project::factory()->create(['name' => 'Obra de outro gestor']);

        $this->actingAs($manager)
            ->put(route('admin.projects.update', $project), $this->payload($project->client, [
                'name' => 'Invadida',
            ]))
            ->assertForbidden();

        $this->assertSame('Obra de outro gestor', $project->fresh()->name);
    }

    public function test_manager_cannot_delete_another_managers_project(): void
    {
        $manager = User::factory()->manager()->create();
        $project = Project::factory()->create();

        $this->actingAs($manager)
            ->delete(route('admin.projects.destroy', $project))
            ->assertForbidden();

        $this->assertNotSoftDeleted('projects', ['id' => $project->id]);
    }

    public function test_financial_user_cannot_create_or_delete_projects(): void
    {
        $financial = User::factory()->financial()->create();
        $project = Project::factory()->create();

        $this->actingAs($financial)
            ->post(route('admin.projects.store'), $this->payload($project->client))
            ->assertForbidden();

        $this->actingAs($financial)
            ->delete(route('admin.projects.destroy', $project))
            ->assertForbidden();
    }

    public function test_client_user_cannot_reach_the_project_admin_routes(): void
    {
        $clientUser = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => Client::factory()->create()->id,
        ]);
        $project = Project::factory()->create();

        $this->actingAs($clientUser)
            ->post(route('admin.projects.store'), $this->payload($project->client))
            ->assertForbidden();

        $this->actingAs($clientUser)
            ->delete(route('admin.projects.destroy', $project))
            ->assertForbidden();
    }

    /**
     * `GET /admin/obras/nova` chegava a ser apanhada por `/admin/obras/{project}`,
     * declarada antes, e devolvia 404. A rota literal passou a ser declarada
     * primeiro; a cobertura da ordem está em RouteOrderTest.
     */
    public function test_admin_can_open_the_new_project_form(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.create'))
            ->assertOk();
    }
}
