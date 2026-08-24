<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * O vocabulário da área interna, pela mesma regra que o portal do cliente já
 * segue (ver ProjectTypeTest): onde o texto fala de um registo concreto, segue
 * o tipo desse registo — «obra» para construção e remodelação, «projeto» para
 * arquitetura e design de interiores.
 *
 * Onde o texto nomeia o conjunto — o menu lateral, o título da listagem, o
 * atalho de voltar para ela — o nome é fixo, «Obras e Projetos», porque a
 * lista mistura os quatro tipos e um menu que mudasse de nome conforme o que lá
 * está seria pior do que um menu com um nome estável.
 */
class AdminVocabularyTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    // ── Um registo concreto segue o seu tipo ────────────────

    public function test_the_edit_page_of_an_architecture_project_says_projeto(): void
    {
        $project = Project::factory()->architecture()->create();

        $this->actingAs($this->admin())
            ->get(route('admin.projects.edit', $project))
            ->assertOk()
            ->assertSee('Editar projeto')
            ->assertSee('Ver projeto')
            ->assertSee('Nome do projeto')
            ->assertDontSee('Editar obra')
            ->assertDontSee('Nome da obra');
    }

    public function test_the_edit_page_of_a_site_work_still_says_obra(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($this->admin())
            ->get(route('admin.projects.edit', $project))
            ->assertOk()
            ->assertSee('Editar obra')
            ->assertSee('Ver obra')
            ->assertSee('Nome da obra')
            ->assertDontSee('Editar projeto')
            ->assertDontSee('Nome do projeto');
    }

    public function test_the_page_of_an_interior_design_project_says_projeto(): void
    {
        $project = Project::factory()->interiorDesign()->create();

        $this->actingAs($this->admin())
            ->get(route('admin.projects.show', $project))
            ->assertOk()
            ->assertSee('Excluir este projeto?')
            ->assertDontSee('Excluir esta obra');
    }

    public function test_the_page_of_a_site_work_still_says_obra(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($this->admin())
            ->get(route('admin.projects.show', $project))
            ->assertOk()
            ->assertSee('Excluir esta obra?')
            ->assertDontSee('Excluir este projeto');
    }

    /**
     * O aviso da origem removida fala do registo que se está a editar, e não do
     * projecto que lhe deu origem — daí seguir o tipo de quem o mostra.
     */
    public function test_the_missing_source_notice_follows_the_type_of_the_project_it_belongs_to(): void
    {
        $source = Project::factory()->architecture()->create();
        $derived = Project::factory()->interiorDesign()->create([
            'client_id' => $source->client_id,
            'source_project_id' => $source->id,
        ]);
        $source->delete();

        $this->actingAs($this->admin())
            ->get(route('admin.projects.edit', $derived))
            ->assertOk()
            ->assertSee('O projeto de origem deste projeto foi removido')
            ->assertDontSee('O projeto de origem desta obra foi removido');
    }

    // ── O conjunto tem um nome fixo ─────────────────────────

    public function test_the_list_heading_does_not_change_with_what_the_list_holds(): void
    {
        Project::factory()->architecture()->create();

        $this->actingAs($this->admin())
            ->get(route('admin.projects.index', ['type' => 'arquitetura']))
            ->assertOk()
            ->assertSee('Obras e Projetos')
            ->assertSee('Nova obra ou projeto');

        Project::factory()->create();

        $this->actingAs($this->admin())
            ->get(route('admin.projects.index', ['type' => 'construcao']))
            ->assertOk()
            ->assertSee('Obras e Projetos')
            ->assertSee('Nova obra ou projeto');
    }

    public function test_the_sidebar_names_the_collection_the_same_for_every_type(): void
    {
        Project::factory()->architecture()->create();

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Obras e Projetos');
    }

    public function test_the_back_link_of_a_design_project_points_to_the_collection(): void
    {
        $project = Project::factory()->architecture()->create();

        $this->actingAs($this->admin())
            ->get(route('admin.projects.show', $project))
            ->assertOk()
            ->assertSee('← Obras e Projetos');
    }
}
