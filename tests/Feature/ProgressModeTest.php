<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FASE-04: o progresso da obra é derivado da média das fases ou definido à
 * mão, conforme a definição `progress_mode`. Em modo derivado o campo não
 * existe no formulário e mexer numa fase manda; em manual é o contrário.
 */
class ProgressModeTest extends TestCase
{
    use RefreshDatabase;

    private function project(): Project
    {
        return Project::factory()->create([
            'client_id' => Client::factory(),
            'progress' => 0,
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function payload(Project $project, array $overrides = []): array
    {
        return array_merge([
            'client_id' => $project->client_id,
            'name' => $project->name,
            'type' => $project->type,
            'status' => $project->status,
            'show_financials' => '1',
        ], $overrides);
    }

    public function test_derived_mode_recalculates_progress_from_the_phases(): void
    {
        $project = $this->project();

        ProjectPhase::factory()->create(['project_id' => $project->id, 'percentage' => 100]);
        ProjectPhase::factory()->create(['project_id' => $project->id, 'percentage' => 50]);

        $this->assertSame(75, $project->fresh()->progress);
    }

    public function test_manual_mode_leaves_the_progress_untouched_when_phases_change(): void
    {
        Setting::set('progress_mode', 'manual');
        $project = $this->project();
        $project->update(['progress' => 40]);

        ProjectPhase::factory()->create(['project_id' => $project->id, 'percentage' => 100]);

        $this->assertSame(40, $project->fresh()->progress);
    }

    public function test_manual_mode_accepts_the_progress_sent_by_the_form(): void
    {
        Setting::set('progress_mode', 'manual');
        $project = $this->project();

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.projects.update', $project), $this->payload($project, ['progress' => 65]))
            ->assertRedirect();

        $this->assertSame(65, $project->fresh()->progress);
    }

    public function test_manual_mode_rejects_a_progress_outside_zero_to_one_hundred(): void
    {
        Setting::set('progress_mode', 'manual');
        $project = $this->project();

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.projects.update', $project), $this->payload($project, ['progress' => 140]))
            ->assertSessionHasErrors('progress');

        $this->assertSame(0, $project->fresh()->progress);
    }

    public function test_derived_mode_ignores_a_progress_sent_by_hand(): void
    {
        $project = $this->project();
        ProjectPhase::factory()->create(['project_id' => $project->id, 'percentage' => 20]);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.projects.update', $project), $this->payload($project, ['progress' => 99]))
            ->assertRedirect();

        $this->assertSame(20, $project->fresh()->progress);
    }

    public function test_the_setting_is_saved_and_validated(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), ['company_name' => 'Obra360', 'progress_mode' => 'manual'])
            ->assertRedirect();
        $this->assertTrue(Project::progressIsManual());

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), ['company_name' => 'Obra360', 'progress_mode' => 'automatico'])
            ->assertSessionHasErrors('progress_mode');
    }
}
