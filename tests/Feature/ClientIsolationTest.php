<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Document;
use App\Models\Message;
use App\Models\Photo;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * SEC-02 — um utilizador do cliente A não alcança nada do cliente B no portal.
 */
class ClientIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;

    private Project $projectA;

    private Project $projectB;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $clientA = Client::factory()->create(['name' => 'Cliente Alfa']);
        $clientB = Client::factory()->create(['name' => 'Cliente Beta']);

        $this->userA = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => $clientA->id,
        ]);

        $this->projectA = Project::factory()->create([
            'client_id' => $clientA->id,
            'name' => 'ObraDoAlfa',
            'cover_image_path' => 'covers/alfa.jpg',
        ]);
        $this->projectB = Project::factory()->create([
            'client_id' => $clientB->id,
            'name' => 'ObraDoBeta',
            'cover_image_path' => 'covers/beta.jpg',
        ]);

        Storage::disk('local')->put('covers/alfa.jpg', 'capa-alfa');
        Storage::disk('local')->put('covers/beta.jpg', 'capa-beta');
    }

    private function photoFor(Project $project, string $caption): Photo
    {
        $path = "projects/{$project->id}/photos/".md5($caption).'.jpg';
        Storage::disk('local')->put($path, 'imagem');

        return Photo::factory()->create([
            'project_id' => $project->id,
            'caption' => $caption,
            'path' => $path,
        ]);
    }

    private function documentFor(Project $project, string $name): Document
    {
        $path = "projects/{$project->id}/documents/".md5($name).'.pdf';
        Storage::disk('local')->put($path, 'documento');

        return Document::factory()->create([
            'project_id' => $project->id,
            'name' => $name,
            'visibility' => 'cliente',
            'path' => $path,
        ]);
    }

    // ── Obra ─────────────────────────────────────────────────

    public function test_client_sees_their_own_project(): void
    {
        $this->actingAs($this->userA)
            ->get(route('portal.projects.show', $this->projectA))
            ->assertOk()
            ->assertSee('ObraDoAlfa');
    }

    public function test_client_cannot_open_a_project_of_another_client(): void
    {
        $this->actingAs($this->userA)
            ->get(route('portal.projects.show', $this->projectB))
            ->assertForbidden();
    }

    public function test_the_client_dashboard_only_lists_their_own_projects(): void
    {
        $this->actingAs($this->userA)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee('ObraDoAlfa')
            ->assertDontSee('ObraDoBeta');
    }

    // ── Capa ─────────────────────────────────────────────────

    public function test_client_cannot_fetch_the_cover_of_another_clients_project(): void
    {
        $this->actingAs($this->userA)
            ->get(route('portal.projects.cover', $this->projectB))
            ->assertForbidden();
    }

    public function test_client_fetches_the_cover_of_their_own_project(): void
    {
        $response = $this->actingAs($this->userA)
            ->get(route('portal.projects.cover', $this->projectA));

        $response->assertOk();
        $this->assertSame('capa-alfa', $response->streamedContent());
    }

    // ── Fotos ────────────────────────────────────────────────

    public function test_client_cannot_fetch_a_photo_of_another_clients_project(): void
    {
        $photo = $this->photoFor($this->projectB, 'FotoDoBeta');

        $this->actingAs($this->userA)
            ->get(route('portal.projects.photos.show', [$this->projectB, $photo]))
            ->assertForbidden();
    }

    public function test_a_photo_of_another_client_cannot_be_smuggled_through_an_own_project(): void
    {
        $photo = $this->photoFor($this->projectB, 'FotoDoBeta');

        $this->actingAs($this->userA)
            ->get(route('portal.projects.photos.show', [$this->projectA, $photo]))
            ->assertNotFound();
    }

    // ── Documentos ───────────────────────────────────────────

    public function test_client_cannot_download_a_document_of_another_client(): void
    {
        $document = $this->documentFor($this->projectB, 'ContratoDoBeta');

        $this->actingAs($this->userA)
            ->get(route('portal.projects.documents.download', [$this->projectB, $document]))
            ->assertForbidden();
    }

    // ── Mensagens ────────────────────────────────────────────

    public function test_client_cannot_read_the_messages_of_another_client(): void
    {
        Message::create([
            'project_id' => $this->projectB->id,
            'user_id' => User::factory()->admin()->create()->id,
            'body' => 'MensagemPrivadaDoBeta',
        ]);

        $this->actingAs($this->userA)
            ->getJson(route('portal.projects.messages', $this->projectB))
            ->assertForbidden();
    }

    public function test_client_cannot_post_a_message_on_another_clients_project(): void
    {
        $this->actingAs($this->userA)
            ->postJson(route('portal.projects.messages.store', $this->projectB), [
                'body' => 'Intruso',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('messages', 0);
    }

    // ── Utilizador cliente sem cliente associado ─────────────

    public function test_a_client_user_without_a_client_reaches_nothing(): void
    {
        $orphan = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => null,
        ]);

        $this->actingAs($orphan)
            ->get(route('portal.projects.show', $this->projectA))
            ->assertForbidden();
    }

    public function test_admin_routes_stay_closed_to_every_client_user(): void
    {
        $this->actingAs($this->userA)
            ->get(route('admin.projects.show', $this->projectA))
            ->assertForbidden();

        $this->actingAs($this->userA)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }
}
