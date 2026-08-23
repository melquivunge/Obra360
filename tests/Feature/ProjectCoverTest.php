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
 * aparecer — e garantem que uma obra sem capa não arrasta nenhum URL de capa
 * para o HTML, para não sobrar uma imagem partida.
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

    public function test_the_admin_project_page_shows_the_cover(): void
    {
        $project = $this->projectWithCover();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.show', $project))
            ->assertOk()
            ->assertSee(route('admin.projects.cover', $project), false);
    }

    public function test_the_admin_project_page_without_a_cover_shows_nothing(): void
    {
        $project = $this->projectWithoutCover();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.show', $project))
            ->assertOk()
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

    public function test_the_admin_list_without_a_cover_shows_nothing(): void
    {
        $project = $this->projectWithoutCover();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.index'))
            ->assertOk()
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

    public function test_the_client_project_page_without_a_cover_shows_nothing(): void
    {
        $project = $this->projectWithoutCover();

        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.show', $project))
            ->assertOk()
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
}
