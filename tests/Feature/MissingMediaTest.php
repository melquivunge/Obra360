<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Document;
use App\Models\Photo;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Produção tinha oito registos de media cujo ficheiro nunca chegou ao
 * servidor — vieram de uma base de dados de outro ambiente. Cada um deles
 * devolvia 500 ao cliente, porque `Storage::response()` sobre um caminho
 * inexistente rebenta. Um registo órfão é um 404, não um erro de servidor.
 */
class MissingMediaTest extends TestCase
{
    use RefreshDatabase;

    private User $clientUser;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $client = Client::factory()->create();
        $this->clientUser = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => $client->id,
        ]);
        $this->project = Project::factory()->create([
            'client_id' => $client->id,
            'cover_image_path' => 'covers/desaparecida.png',
        ]);
    }

    public function test_a_cover_without_a_file_returns_404(): void
    {
        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.cover', $this->project))
            ->assertNotFound();
    }

    public function test_a_photo_without_a_file_returns_404(): void
    {
        $photo = Photo::factory()->create([
            'project_id' => $this->project->id,
            'path' => 'projects/1/photos/desaparecida.png',
            'thumbnail_path' => null,
        ]);

        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.photos.show', [$this->project, $photo]))
            ->assertNotFound();
    }

    public function test_a_document_without_a_file_returns_404(): void
    {
        $document = Document::factory()->create([
            'project_id' => $this->project->id,
            'visibility' => 'cliente',
            'path' => 'projects/1/documents/desaparecido.pdf',
        ]);

        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.documents.download', [$this->project, $document]))
            ->assertNotFound();
    }

    public function test_the_admin_side_answers_the_same_way(): void
    {
        $admin = User::factory()->admin()->create();
        $photo = Photo::factory()->create([
            'project_id' => $this->project->id,
            'path' => 'projects/1/photos/desaparecida.png',
            'thumbnail_path' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.projects.cover', $this->project))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('admin.projects.photos.show', [$this->project, $photo]))
            ->assertNotFound();
    }

    public function test_media_on_the_public_disk_is_no_longer_served(): void
    {
        Storage::disk('public')->put('covers/desaparecida.png', 'conteudo');

        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.cover', $this->project))
            ->assertNotFound();
    }
}
