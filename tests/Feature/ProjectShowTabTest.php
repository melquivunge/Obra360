<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectShowTabTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Os filtros da galeria submetem por GET, pelo que o separador activo tem de
     * viajar no pedido — caso contrário o Alpine reinicia sempre em "geral".
     */
    public function test_the_active_tab_comes_from_the_query_string(): void
    {
        $project = Project::factory()->create();
        $phase = ProjectPhase::factory()->create(['project_id' => $project->id]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.show', [
                'project' => $project, 'photo_phase' => $phase->id, 'tab' => 'fotos',
            ]))
            ->assertOk()
            ->assertSee("projectShow({$project->id}, 'fotos')", false);
    }

    public function test_the_active_tab_defaults_to_geral_without_a_tab_parameter(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.show', $project))
            ->assertOk()
            ->assertSee("projectShow({$project->id}, 'geral')", false);
    }

    /**
     * O valor vem do URL, logo não é de confiança: qualquer separador
     * desconhecido tem de cair no predefinido em vez de deixar a página sem conteúdo.
     */
    public function test_an_unknown_tab_falls_back_to_geral(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.show', ['project' => $project, 'tab' => 'inexistente']))
            ->assertOk()
            ->assertSee("projectShow({$project->id}, 'geral')", false);
    }

    public function test_the_photo_filter_form_carries_the_tab_forward(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.show', ['project' => $project, 'tab' => 'fotos']))
            ->assertOk()
            ->assertSee('<input type="hidden" name="tab" value="fotos">', false);
    }

    public function test_the_clear_filters_link_keeps_the_photos_tab(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.show', ['project' => $project, 'photo_order' => 'antigas', 'tab' => 'fotos']))
            ->assertOk()
            ->assertSee(route('admin.projects.show', [$project, 'tab' => 'fotos']), false);
    }
}
