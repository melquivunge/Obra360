<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Plan;
use App\Models\PlanVersion;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * OBRA-01 a OBRA-04: a obra fica ligada ao projecto de arquitectura ou de
 * design que lhe deu origem.
 *
 * É o requisito que motivou toda esta fase — «quando o cliente quiser seguir
 * com a obra, ao criar a obra e seleccionar o cliente já vem a planta na obra».
 * Não há cópia de ficheiros: a planta tem um único dono e a obra lê-a onde ela
 * vive, de modo que uma correcção no projecto aparece na obra sem ninguém a
 * repetir.
 */
class ProjectSourceLinkTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->client = Client::factory()->create();
        $this->admin = User::factory()->admin()->create();
    }

    /**
     * Payload aceite por store/update, no formato de ProjectCrudTest.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'client_id' => $this->client->id,
            'name' => 'ObraResidenciaAlfa',
            'type' => 'construcao',
            'status' => 'planejada',
            'budget' => 50000,
            'show_financials' => '1',
        ], $overrides);
    }

    private function architectureProject(?Client $client = null, string $name = 'ProjectoArquiteturaAlfa'): Project
    {
        return Project::factory()->architecture()->create([
            'client_id' => ($client ?? $this->client)->id,
            'name' => $name,
        ]);
    }

    private function siteWork(?Project $source = null, string $name = 'ObraResidenciaAlfa'): Project
    {
        return Project::factory()->create([
            'client_id' => ($source?->client_id) ?? $this->client->id,
            'name' => $name,
            'source_project_id' => $source?->id,
        ]);
    }

    /** Uma planta com a versão corrente aprovada pelo cliente. */
    private function approvedPlan(Project $project, string $name): Plan
    {
        $plan = Plan::factory()->create([
            'project_id' => $project->id,
            'name' => $name,
            'status' => 'aprovada',
        ]);

        $version = PlanVersion::factory()->create([
            'plan_id' => $plan->id,
            'version' => 1,
            'path' => "projects/{$project->id}/plans/{$plan->id}/planta.pdf",
            'sent_to_client_at' => now(),
            'approved_at' => now(),
        ]);

        Storage::disk('local')->put($version->path, 'planta');

        return $plan;
    }

    /** Uma planta que o cliente ainda não aprovou. */
    private function unapprovedPlan(Project $project, string $name): Plan
    {
        $plan = Plan::factory()->create([
            'project_id' => $project->id,
            'name' => $name,
            'status' => 'enviada',
        ]);

        PlanVersion::factory()->create([
            'plan_id' => $plan->id,
            'version' => 1,
            'sent_to_client_at' => now(),
        ]);

        return $plan;
    }

    private function clientUserFor(Project $project): User
    {
        return User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => $project->client_id,
        ]);
    }

    // ── OBRA-01: a ligação grava-se ─────────────────────────

    public function test_the_source_project_is_saved_when_the_site_work_is_created(): void
    {
        $source = $this->architectureProject();

        $this->actingAs($this->admin)
            ->post(route('admin.projects.store'), $this->payload([
                'source_project_id' => $source->id,
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(
            $source->id,
            Project::where('name', 'ObraResidenciaAlfa')->firstOrFail()->source_project_id,
        );
    }

    public function test_the_source_project_is_saved_when_the_site_work_is_edited(): void
    {
        $source = $this->architectureProject();
        $work = $this->siteWork();

        $this->actingAs($this->admin)
            ->put(route('admin.projects.update', $work), $this->payload([
                'source_project_id' => $source->id,
            ]))
            ->assertRedirect(route('admin.projects.show', $work))
            ->assertSessionHasNoErrors();

        $this->assertSame($source->id, $work->fresh()->source_project_id);
    }

    public function test_the_link_can_be_removed_on_edit(): void
    {
        $work = $this->siteWork($this->architectureProject());

        $this->actingAs($this->admin)
            ->put(route('admin.projects.update', $work), $this->payload([
                'source_project_id' => '',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertNull($work->fresh()->source_project_id);
    }

    public function test_a_site_work_without_a_source_is_still_created(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.projects.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertNull(Project::firstOrFail()->source_project_id);
    }

    public function test_the_link_enters_the_project_history(): void
    {
        $source = $this->architectureProject();

        $this->actingAs($this->admin)
            ->post(route('admin.projects.store'), $this->payload([
                'source_project_id' => $source->id,
            ]));

        $this->assertDatabaseHas('project_updates', [
            'project_id' => Project::where('name', 'ObraResidenciaAlfa')->firstOrFail()->id,
            'title' => 'Ligada ao projecto de origem: ProjectoArquiteturaAlfa',
            'type' => 'origem',
        ]);
    }

    // ── OBRA-01: o que o servidor recusa ────────────────────

    public function test_a_source_project_of_another_client_is_rejected(): void
    {
        $other = $this->architectureProject(Client::factory()->create());

        $this->actingAs($this->admin)
            ->post(route('admin.projects.store'), $this->payload([
                'source_project_id' => $other->id,
            ]))
            ->assertSessionHasErrors('source_project_id');

        $this->assertDatabaseCount('projects', 1);
    }

    public function test_a_source_project_of_another_client_is_rejected_on_edit(): void
    {
        $other = $this->architectureProject(Client::factory()->create());
        $work = $this->siteWork();

        $this->actingAs($this->admin)
            ->put(route('admin.projects.update', $work), $this->payload([
                'source_project_id' => $other->id,
            ]))
            ->assertSessionHasErrors('source_project_id');

        $this->assertNull($work->fresh()->source_project_id);
    }

    public function test_a_source_that_is_not_architecture_or_design_is_rejected(): void
    {
        $anotherWork = $this->siteWork(null, 'OutraObra');

        $this->actingAs($this->admin)
            ->post(route('admin.projects.store'), $this->payload([
                'source_project_id' => $anotherWork->id,
            ]))
            ->assertSessionHasErrors('source_project_id');

        $this->assertDatabaseMissing('projects', ['name' => 'ObraResidenciaAlfa', 'deleted_at' => null]);
    }

    public function test_a_design_project_is_an_acceptable_source(): void
    {
        $source = Project::factory()->interiorDesign()->create([
            'client_id' => $this->client->id,
            'name' => 'DesignInterioresAlfa',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.projects.store'), $this->payload([
                'source_project_id' => $source->id,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame($source->id, Project::where('name', 'ObraResidenciaAlfa')->firstOrFail()->source_project_id);
    }

    public function test_a_project_cannot_be_its_own_source(): void
    {
        $work = $this->siteWork();

        $this->actingAs($this->admin)
            ->put(route('admin.projects.update', $work), $this->payload([
                'source_project_id' => $work->id,
            ]))
            ->assertSessionHasErrors('source_project_id');

        $this->assertNull($work->fresh()->source_project_id);
    }

    public function test_a_circular_chain_is_rejected(): void
    {
        // Dois projectos de design do mesmo cliente: o segundo já aponta para o
        // primeiro, logo o primeiro não pode passar a apontar para o segundo.
        $first = Project::factory()->interiorDesign()->create(['client_id' => $this->client->id, 'name' => 'DesignAlfa']);
        $second = Project::factory()->interiorDesign()->create([
            'client_id' => $this->client->id,
            'name' => 'DesignBeta',
            'source_project_id' => $first->id,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.projects.update', $first), $this->payload([
                'name' => 'DesignAlfa',
                'type' => 'design_interiores',
                'source_project_id' => $second->id,
            ]))
            ->assertSessionHasErrors('source_project_id');

        $this->assertNull($first->fresh()->source_project_id);
    }

    public function test_a_source_in_the_recycle_bin_is_rejected(): void
    {
        $source = $this->architectureProject();
        $source->delete();

        $this->actingAs($this->admin)
            ->post(route('admin.projects.store'), $this->payload([
                'source_project_id' => $source->id,
            ]))
            ->assertSessionHasErrors('source_project_id');
    }

    public function test_a_source_that_does_not_exist_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.projects.store'), $this->payload([
                'source_project_id' => 99999,
            ]))
            ->assertSessionHasErrors('source_project_id');
    }

    // ── OBRA-02: as plantas aprovadas aparecem na obra ──────

    public function test_the_site_work_shows_the_approved_plans_of_its_source(): void
    {
        $source = $this->architectureProject();
        $this->approvedPlan($source, 'PlantaAprovadaAlfa');
        $work = $this->siteWork($source);

        $this->actingAs($this->admin)
            ->get(route('admin.projects.show', $work))
            ->assertOk()
            ->assertSee('Plantas do projecto de origem')
            ->assertSee('PlantaAprovadaAlfa');
    }

    public function test_the_site_work_does_not_show_plans_that_are_not_approved(): void
    {
        $source = $this->architectureProject();
        $this->approvedPlan($source, 'PlantaAprovadaAlfa');
        $this->unapprovedPlan($source, 'PlantaPorAprovarBeta');
        $work = $this->siteWork($source);

        $this->actingAs($this->admin)
            ->get(route('admin.projects.show', $work))
            ->assertOk()
            ->assertSee('PlantaAprovadaAlfa')
            ->assertDontSee('PlantaPorAprovarBeta');
    }

    public function test_a_site_work_without_a_source_shows_no_inherited_plans(): void
    {
        $source = $this->architectureProject();
        $this->approvedPlan($source, 'PlantaAprovadaAlfa');
        $work = $this->siteWork();

        $this->actingAs($this->admin)
            ->get(route('admin.projects.show', $work))
            ->assertOk()
            ->assertDontSee('PlantaAprovadaAlfa')
            ->assertDontSee('Plantas do projecto de origem');
    }

    public function test_the_inherited_plans_are_read_from_the_source_and_never_copied(): void
    {
        $source = $this->architectureProject();
        $plan = $this->approvedPlan($source, 'PlantaAprovadaAlfa');
        $work = $this->siteWork($source);

        // Não há linha nenhuma criada do lado da obra: a planta continua a ser
        // do projecto que a desenhou.
        $this->assertSame(0, Plan::where('project_id', $work->id)->count());
        $this->assertSame($source->id, $plan->project_id);

        // E corrigir o nome no projecto aparece na obra sem mais nada.
        $plan->update(['name' => 'PlantaCorrigidaGama']);

        $this->actingAs($this->admin)
            ->get(route('admin.projects.show', $work))
            ->assertOk()
            ->assertSee('PlantaCorrigidaGama')
            ->assertDontSee('PlantaAprovadaAlfa');
    }

    public function test_a_manager_downloads_an_inherited_plan_of_a_project_they_do_not_manage(): void
    {
        $architect = User::factory()->manager()->create();
        $builder = User::factory()->manager()->create();

        $source = $this->architectureProject();
        $source->update(['manager_id' => $architect->id]);
        $plan = $this->approvedPlan($source, 'PlantaAprovadaAlfa');
        $version = $plan->versions()->firstOrFail();

        $work = $this->siteWork($source);
        $work->update(['manager_id' => $builder->id]);

        $this->actingAs($builder)
            ->get(route('admin.projects.plans.versions.download', [$source, $plan, $version]))
            ->assertOk();
    }

    public function test_a_manager_with_no_work_derived_from_the_project_still_gets_403(): void
    {
        $architect = User::factory()->manager()->create();
        $stranger = User::factory()->manager()->create();

        $source = $this->architectureProject();
        $source->update(['manager_id' => $architect->id]);
        $plan = $this->approvedPlan($source, 'PlantaAprovadaAlfa');

        $this->actingAs($stranger)
            ->get(route('admin.projects.plans.versions.download', [$source, $plan, $plan->versions()->firstOrFail()]))
            ->assertForbidden();
    }

    // ── OBRA-03: do projecto vê-se a obra ───────────────────

    public function test_the_source_project_page_shows_the_resulting_site_work(): void
    {
        $source = $this->architectureProject();
        $this->siteWork($source, 'ObraResultanteAlfa');

        $this->actingAs($this->admin)
            ->get(route('admin.projects.show', $source))
            ->assertOk()
            ->assertSee('Deu origem a')
            ->assertSee('ObraResultanteAlfa');
    }

    public function test_the_site_work_page_shows_the_project_it_came_from(): void
    {
        $source = $this->architectureProject();
        $work = $this->siteWork($source);

        $this->actingAs($this->admin)
            ->get(route('admin.projects.show', $work))
            ->assertOk()
            ->assertSee('Nasceu do projecto')
            ->assertSee('ProjectoArquiteturaAlfa');
    }

    // ── OBRA-04: a continuidade no portal do cliente ────────

    public function test_the_client_sees_on_the_site_work_which_project_it_came_from(): void
    {
        $source = $this->architectureProject();
        $work = $this->siteWork($source);

        $this->actingAs($this->clientUserFor($work))
            ->get(route('portal.projects.show', $work))
            ->assertOk()
            ->assertSee('Esta obra nasceu do projecto')
            ->assertSee('ProjectoArquiteturaAlfa');
    }

    public function test_the_client_sees_on_the_project_which_work_it_led_to(): void
    {
        $source = $this->architectureProject();
        $this->siteWork($source, 'ObraResultanteAlfa');

        $this->actingAs($this->clientUserFor($source))
            ->get(route('portal.projects.show', $source))
            ->assertOk()
            ->assertSee('Deu origem à obra')
            ->assertSee('ObraResultanteAlfa');
    }

    public function test_the_client_never_sees_a_link_to_a_project_that_is_not_theirs(): void
    {
        $foreign = $this->architectureProject(Client::factory()->create(), 'ProjectoDeOutroCliente');
        $work = $this->siteWork(null, 'ObraDoCliente');
        // Uma ligação impossível de criar pela interface — a validação recusa-a.
        // Se alguma vez existir na base de dados, o portal não é onde se
        // descobre que ela existe.
        $work->forceFill(['source_project_id' => $foreign->id])->save();

        $this->actingAs($this->clientUserFor($work))
            ->get(route('portal.projects.show', $work))
            ->assertOk()
            ->assertDontSee('ProjectoDeOutroCliente')
            ->assertDontSee('Esta obra nasceu do projecto');
    }

    // ── A origem removida ───────────────────────────────────

    public function test_a_soft_deleted_source_keeps_the_link_and_leaves_the_page_standing(): void
    {
        $source = $this->architectureProject();
        $this->approvedPlan($source, 'PlantaAprovadaAlfa');
        $work = $this->siteWork($source);

        $source->delete();

        $this->actingAs($this->admin)
            ->get(route('admin.projects.show', $work))
            ->assertOk()
            ->assertSee('O projecto de origem foi removido')
            ->assertDontSee('PlantaAprovadaAlfa');

        // A ligação sobrevive: é o que permite restaurar a continuidade inteira.
        $this->assertSame($source->id, $work->fresh()->source_project_id);
    }

    public function test_restoring_the_source_brings_the_continuity_back(): void
    {
        $source = $this->architectureProject();
        $this->approvedPlan($source, 'PlantaAprovadaAlfa');
        $work = $this->siteWork($source);

        $source->delete();
        $source->restore();

        $this->actingAs($this->admin)
            ->get(route('admin.projects.show', $work))
            ->assertOk()
            ->assertSee('PlantaAprovadaAlfa')
            ->assertDontSee('O projecto de origem foi removido');
    }

    public function test_the_client_portal_survives_a_removed_source(): void
    {
        $source = $this->architectureProject();
        $work = $this->siteWork($source);

        $source->delete();

        $this->actingAs($this->clientUserFor($work))
            ->get(route('portal.projects.show', $work))
            ->assertOk()
            ->assertDontSee('Esta obra nasceu do projecto')
            ->assertDontSee('ProjectoArquiteturaAlfa');
    }

    public function test_editing_a_work_whose_source_was_removed_does_not_drop_the_link(): void
    {
        $source = $this->architectureProject();
        $work = $this->siteWork($source);

        $source->delete();

        $this->actingAs($this->admin)
            ->get(route('admin.projects.edit', $work))
            ->assertOk()
            ->assertSee('O projecto de origem desta obra foi removido');

        // O formulário não desenha o campo nesse estado, por isso o pedido
        // chega sem ele — e a ligação fica como estava.
        $payload = $this->payload(['name' => $work->name]);
        unset($payload['source_project_id']);

        $this->actingAs($this->admin)
            ->put(route('admin.projects.update', $work), $payload)
            ->assertSessionHasNoErrors();

        $this->assertSame($source->id, $work->fresh()->source_project_id);
    }

    public function test_deleting_the_source_for_good_leaves_the_work_without_an_origin(): void
    {
        $source = $this->architectureProject();
        $work = $this->siteWork($source);

        $source->forceDelete();

        // `nullOnDelete` na migração: a obra fica sem origem em vez de ficar a
        // apontar para uma linha que já não existe.
        $this->assertNull($work->fresh()->source_project_id);

        $this->actingAs($this->admin)
            ->get(route('admin.projects.show', $work))
            ->assertOk();
    }

    // ── Autorização ─────────────────────────────────────────

    public function test_a_financial_user_cannot_change_the_source_link(): void
    {
        $source = $this->architectureProject();
        $work = $this->siteWork();

        $this->actingAs(User::factory()->financial()->create())
            ->put(route('admin.projects.update', $work), $this->payload([
                'source_project_id' => $source->id,
            ]))
            ->assertForbidden();

        $this->assertNull($work->fresh()->source_project_id);
    }

    public function test_a_manager_cannot_reach_a_work_they_do_not_manage(): void
    {
        $work = $this->siteWork($this->architectureProject());

        $this->actingAs(User::factory()->manager()->create())
            ->get(route('admin.projects.show', $work))
            ->assertForbidden();
    }
}
