<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\ProjectUpdate;
use App\Models\Setting;
use App\Models\User;
use App\Support\PhaseTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Etapas de entrega pré-definidas por tipo de projeto.
 *
 * Escrever as fases à mão é aceitável numa obra — cada obra é diferente. Num
 * projeto de arquitetura não é: as etapas são praticamente sempre as mesmas e
 * são conhecimento do ofício. As de arquitetura seguem a NBR 13532.
 *
 * O que estes testes protegem, além de criar as etapas: que elas continuam
 * editáveis (são ponto de partida, não imposição), que o progresso não fica
 * estranho, e que o histórico do cliente não leva cinco linhas de uma vez.
 */
class PhaseTemplateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    private function project(string $type): Project
    {
        return Project::factory()->create([
            'client_id' => Client::factory(),
            'type' => $type,
            'progress' => 0,
        ]);
    }

    public function test_it_creates_the_architecture_stages_in_order_at_zero_percent(): void
    {
        $project = $this->project('arquitetura');

        $this->actingAs($this->admin)
            ->post(route('admin.projects.phases.template', $project))
            ->assertRedirect();

        $phases = $project->phases()->orderBy('sort_order')->get();

        $this->assertSame(
            ['Levantamento', 'Estudo preliminar', 'Anteprojeto', 'Projeto legal', 'Projeto executivo'],
            $phases->pluck('name')->all()
        );
        $this->assertSame([0, 1, 2, 3, 4], $phases->pluck('sort_order')->all());
        $this->assertSame([0, 0, 0, 0, 0], $phases->pluck('percentage')->all());
        $this->assertTrue($phases->every(fn ($p) => $p->status === 'pendente'));
    }

    public function test_interior_design_has_its_own_stages(): void
    {
        $project = $this->project('design_interiores');

        $this->actingAs($this->admin)->post(route('admin.projects.phases.template', $project));

        $this->assertSame(
            ['Levantamento', 'Conceito', 'Layout', 'Detalhamento', 'Especificação'],
            $project->phases()->orderBy('sort_order')->pluck('name')->all()
        );
    }

    public function test_construction_has_no_template_because_every_site_is_different(): void
    {
        foreach (['construcao', 'remodelacao'] as $type) {
            $project = $this->project($type);

            $this->assertFalse(PhaseTemplates::has($type));

            $this->actingAs($this->admin)
                ->post(route('admin.projects.phases.template', $project))
                ->assertNotFound();

            $this->assertSame(0, $project->phases()->count());
        }
    }

    public function test_the_template_is_not_offered_once_the_work_has_phases(): void
    {
        $project = $this->project('arquitetura');
        ProjectPhase::factory()->create(['project_id' => $project->id]);

        $this->assertFalse(PhaseTemplates::availableFor($project->fresh()));

        $this->actingAs($this->admin)
            ->post(route('admin.projects.phases.template', $project))
            ->assertNotFound();

        $this->assertSame(1, $project->phases()->count());
    }

    public function test_the_stages_stay_editable_and_removable(): void
    {
        $project = $this->project('arquitetura');
        $this->actingAs($this->admin)->post(route('admin.projects.phases.template', $project));

        $phase = $project->phases()->orderBy('sort_order')->first();

        $this->actingAs($this->admin)
            ->put(route('admin.projects.phases.update', [$project, $phase]), [
                'name' => 'Levantamento e sondagem',
                'status' => 'em_andamento',
                'percentage' => 40,
            ])->assertRedirect();

        $this->assertSame('Levantamento e sondagem', $phase->fresh()->name);

        $this->actingAs($this->admin)
            ->delete(route('admin.projects.phases.destroy', [$project, $phase]))
            ->assertRedirect();

        $this->assertSame(4, $project->phases()->count());
    }

    public function test_the_progress_stays_at_zero_right_after_applying(): void
    {
        $project = $this->project('arquitetura');

        $this->actingAs($this->admin)->post(route('admin.projects.phases.template', $project));

        $this->assertSame(0, $project->fresh()->progress);
    }

    public function test_manual_progress_mode_is_still_respected(): void
    {
        Setting::set('progress_mode', 'manual');
        $project = $this->project('arquitetura');
        $project->update(['progress' => 35]);

        $this->actingAs($this->admin)->post(route('admin.projects.phases.template', $project));

        $this->assertSame(35, $project->fresh()->progress);
    }

    public function test_the_client_history_gets_one_entry_and_not_five(): void
    {
        $project = $this->project('arquitetura');

        $this->actingAs($this->admin)->post(route('admin.projects.phases.template', $project));

        $entries = ProjectUpdate::where('project_id', $project->id)->where('type', 'fase')->get();

        $this->assertCount(1, $entries);
        $this->assertSame('Etapas de entrega definidas', $entries->first()->title);
    }

    public function test_a_manager_cannot_apply_a_template_to_another_managers_project(): void
    {
        $project = Project::factory()->create(['type' => 'arquitetura']);

        $this->actingAs(User::factory()->manager()->create())
            ->post(route('admin.projects.phases.template', $project))
            ->assertForbidden();
    }

    public function test_the_offer_is_shown_only_when_it_applies(): void
    {
        $comModelo = $this->project('arquitetura');
        $semModelo = $this->project('construcao');

        $this->actingAs($this->admin)
            ->get(route('admin.projects.show', $comModelo).'?tab=fases')
            ->assertSee('Começar pelas etapas habituais');

        $this->actingAs($this->admin)
            ->get(route('admin.projects.show', $semModelo).'?tab=fases')
            ->assertDontSee('Começar pelas etapas habituais');
    }
}
