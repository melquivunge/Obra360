<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Photo;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A capa era guardada mas nunca mostrada: fora da pré-visualização do formulário
 * de edição não aparecia em página nenhuma. Estes testes fixam onde ela passa a
 * aparecer — e garantem que uma obra sem capa própria mostra a imagem padrão em
 * vez de deixar o espaço vazio.
 */
class ProjectCoverTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;

    private User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->client = Client::factory()->create();
        $this->clientUser = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => $this->client->id,
        ]);
    }

    private function projectWithCover(): Project
    {
        return Project::factory()->create([
            'client_id' => $this->client->id,
            'cover_image_path' => 'covers/fachada.jpg',
        ]);
    }

    private function projectWithoutCover(): Project
    {
        return Project::factory()->create([
            'client_id' => $this->client->id,
            'cover_image_path' => null,
        ]);
    }

    /**
     * O URL da imagem padrão sai do mesmo sítio que o componente usa, para o
     * teste não fixar um caminho à mão que depois diverge da aplicação.
     */
    private function defaultCover(): string
    {
        return asset('images/capa-padrao.jpg');
    }

    public function test_the_admin_project_page_shows_the_cover(): void
    {
        $project = $this->projectWithCover();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.show', $project))
            ->assertOk()
            ->assertSee(route('admin.projects.cover', $project), false);
    }

    public function test_the_admin_project_page_without_a_cover_shows_the_default_image(): void
    {
        $project = $this->projectWithoutCover();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.show', $project))
            ->assertOk()
            ->assertSee($this->defaultCover(), false)
            ->assertDontSee(route('admin.projects.cover', $project), false);
    }

    public function test_the_admin_list_shows_the_cover_thumbnail(): void
    {
        $project = $this->projectWithCover();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.index'))
            ->assertOk()
            ->assertSee(route('admin.projects.cover', $project), false);
    }

    public function test_the_admin_list_without_a_cover_shows_the_default_thumbnail(): void
    {
        $project = $this->projectWithoutCover();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.index'))
            ->assertOk()
            ->assertSee($this->defaultCover(), false)
            ->assertDontSee(route('admin.projects.cover', $project), false);
    }

    public function test_the_client_project_page_shows_the_cover(): void
    {
        $project = $this->projectWithCover();

        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.show', $project))
            ->assertOk()
            ->assertSee(route('portal.projects.cover', $project), false);
    }

    public function test_the_client_project_page_without_a_cover_shows_the_default_image(): void
    {
        $project = $this->projectWithoutCover();

        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.show', $project))
            ->assertOk()
            ->assertSee($this->defaultCover(), false)
            ->assertDontSee(route('portal.projects.cover', $project), false);
    }

    /**
     * A decisão de produto: uma capa escolhida à mão não desaparece só porque
     * alguém carregou uma foto na obra.
     */
    public function test_the_chosen_cover_wins_over_the_latest_photo(): void
    {
        $project = $this->projectWithCover();
        $photo = Photo::factory()->create([
            'project_id' => $project->id,
            'media_type' => 'image',
        ]);

        $this->actingAs($this->clientUser)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee(route('portal.projects.cover', $project), false)
            ->assertDontSee(route('portal.projects.photos.show', [$project, $photo]), false);
    }

    public function test_without_a_cover_the_dashboard_falls_back_to_the_latest_photo(): void
    {
        $project = $this->projectWithoutCover();
        $photo = Photo::factory()->create([
            'project_id' => $project->id,
            'media_type' => 'image',
        ]);

        $this->actingAs($this->clientUser)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee(route('portal.projects.photos.show', [$project, $photo]), false)
            ->assertDontSee(route('portal.projects.cover', $project), false);
    }

    /**
     * O cartão do painel tinha um estado vazio com um ícone de edifício quando
     * não havia capa nem fotos. Passa a mostrar a imagem padrão, como o resto.
     */
    public function test_the_dashboard_card_without_a_cover_or_photos_shows_the_default_image(): void
    {
        $project = $this->projectWithoutCover();

        $this->actingAs($this->clientUser)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee($this->defaultCover(), false)
            ->assertDontSee(route('portal.projects.cover', $project), false);
    }

    /**
     * Quando o registo tem capa mas o ficheiro não está no disco a rota devolve
     * 404 — a imagem padrão entra no lugar em vez de se retirar o elemento.
     *
     * O comportamento vive em resources/js/app.js desde a introdução da CSP; a
     * vista declara apenas o endereço da reserva. O handler `onerror=` em linha
     * que existia aqui não pode voltar: seria script em atributo e a política
     * bloqueia-o.
     */
    public function test_the_cover_declares_the_default_image_as_fallback_without_an_inline_handler(): void
    {
        $project = $this->projectWithCover();

        $html = $this->actingAs($this->clientUser)
            ->get(route('portal.projects.show', $project))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            'data-fallback-src="'.$this->defaultCover().'"',
            $html
        );
        $this->assertStringNotContainsString('onerror=', $html);
        $this->assertStringNotContainsString('this.closest(\'[data-capa]\').remove()', $html);
    }
}
