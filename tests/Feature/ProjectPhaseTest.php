<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\ProjectUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectPhaseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Fundações',
            'status' => 'em_andamento',
            'percentage' => 40,
        ], $overrides);
    }

    public function test_admin_can_add_a_phase(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.phases.store', $project), $this->payload())
            ->assertRedirect();

        $phase = ProjectPhase::firstOrFail();
        $this->assertSame('Fundações', $phase->name);
        $this->assertSame($project->id, $phase->project_id);
        $this->assertSame(40, $phase->percentage);
    }

    public function test_adding_a_phase_records_it_in_the_history(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.phases.store', $project), $this->payload());

        $this->assertDatabaseHas('project_updates', [
            'project_id' => $project->id,
            'title' => 'Nova fase: Fundações',
            'type' => 'fase',
        ]);
    }

    public function test_sort_order_defaults_to_the_end_of_the_list(): void
    {
        $project = Project::factory()->create();
        ProjectPhase::factory()->create(['project_id' => $project->id, 'sort_order' => 0]);
        ProjectPhase::factory()->create(['project_id' => $project->id, 'sort_order' => 1]);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.phases.store', $project), $this->payload());

        $this->assertSame(2, ProjectPhase::where('name', 'Fundações')->firstOrFail()->sort_order);
    }

    public function test_an_explicit_sort_order_is_respected(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.phases.store', $project), $this->payload(['sort_order' => 7]));

        $this->assertSame(7, ProjectPhase::firstOrFail()->sort_order);
    }

    public function test_phases_are_listed_by_sort_order_not_by_creation(): void
    {
        $project = Project::factory()->create();
        ProjectPhase::factory()->create(['project_id' => $project->id, 'name' => 'Acabamento', 'sort_order' => 2]);
        ProjectPhase::factory()->create(['project_id' => $project->id, 'name' => 'Alvenaria', 'sort_order' => 1]);
        ProjectPhase::factory()->create(['project_id' => $project->id, 'name' => 'Fundação', 'sort_order' => 0]);

        $this->assertSame(
            ['Fundação', 'Alvenaria', 'Acabamento'],
            $project->phases()->pluck('name')->all()
        );
    }

    // ── FASE-05: histórico e progresso ───────────────────────

    public function test_changing_a_phase_status_records_it_in_the_history(): void
    {
        $project = Project::factory()->create();
        $phase = ProjectPhase::factory()->create([
            'project_id' => $project->id,
            'name' => 'Cobertura',
            'status' => 'pendente',
            'percentage' => 0,
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.projects.phases.update', [$project, $phase]), $this->payload([
                'name' => 'Cobertura',
                'status' => 'concluida',
                'percentage' => 100,
            ]))
            ->assertRedirect();

        $this->assertSame('concluida', $phase->fresh()->status);
        $this->assertDatabaseHas('project_updates', [
            'project_id' => $project->id,
            'title' => 'Fase atualizada: Cobertura — 100%',
            'type' => 'fase',
        ]);
    }

    public function test_project_progress_is_the_average_of_the_phase_percentages(): void
    {
        $project = Project::factory()->create(['progress' => 0]);
        ProjectPhase::factory()->create(['project_id' => $project->id, 'percentage' => 100]);
        $phase = ProjectPhase::factory()->create(['project_id' => $project->id, 'percentage' => 0]);

        $this->assertSame(50, $project->fresh()->progress);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.projects.phases.update', [$project, $phase]), $this->payload([
                'name' => $phase->name,
                'percentage' => 50,
            ]));

        $this->assertSame(75, $project->fresh()->progress);
    }

    public function test_deleting_a_phase_recalculates_the_project_progress(): void
    {
        $project = Project::factory()->create();
        ProjectPhase::factory()->create(['project_id' => $project->id, 'percentage' => 100]);
        $zero = ProjectPhase::factory()->create(['project_id' => $project->id, 'percentage' => 0]);
        $this->assertSame(50, $project->fresh()->progress);

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.projects.phases.destroy', [$project, $zero]))
            ->assertRedirect();

        $this->assertDatabaseMissing('project_phases', ['id' => $zero->id]);
        $this->assertSame(100, $project->fresh()->progress);
    }

    // ── Validação e autorização ──────────────────────────────

    public function test_invalid_phase_payload_is_rejected(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.phases.store', $project), [
                'name' => '',
                'status' => 'inexistente',
                'percentage' => 140,
            ])
            ->assertSessionHasErrors(['name', 'status', 'percentage']);

        $this->assertDatabaseCount('project_phases', 0);
    }

    public function test_manager_cannot_add_a_phase_to_another_managers_project(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->manager()->create())
            ->post(route('admin.projects.phases.store', $project), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('project_phases', 0);
    }

    public function test_manager_can_manage_the_phases_of_their_own_project(): void
    {
        $manager = User::factory()->manager()->create();
        $project = Project::factory()->create(['manager_id' => $manager->id]);

        $this->actingAs($manager)
            ->post(route('admin.projects.phases.store', $project), $this->payload())
            ->assertRedirect();

        $this->assertDatabaseCount('project_phases', 1);
    }

    public function test_a_phase_from_another_project_cannot_be_deleted(): void
    {
        $project = Project::factory()->create();
        $other = Project::factory()->create();
        $phase = ProjectPhase::factory()->create(['project_id' => $other->id]);

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.projects.phases.destroy', [$project, $phase]))
            ->assertNotFound();

        $this->assertDatabaseHas('project_phases', ['id' => $phase->id]);
    }

    public function test_financial_user_cannot_manage_phases(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->financial()->create())
            ->post(route('admin.projects.phases.store', $project), $this->payload())
            ->assertForbidden();
    }

    public function test_phase_history_entries_belong_to_the_right_project(): void
    {
        $project = Project::factory()->create();
        $other = Project::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.phases.store', $project), $this->payload());

        $this->assertSame(1, ProjectUpdate::where('project_id', $project->id)->count());
        $this->assertSame(0, ProjectUpdate::where('project_id', $other->id)->count());
    }
}
