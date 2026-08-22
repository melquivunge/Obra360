<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Document;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Documentos são o material mais sensível do portal: contratos, orçamentos e
 * recibos. Estes testes fixam as duas garantias que ainda não tinham rede —
 * quem pode descarregar o quê, e o que a aplicação aceita como upload.
 */
class DocumentAccessTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;

    private User $clientUser;

    private User $manager;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $this->client = Client::factory()->create();
        $this->clientUser = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => $this->client->id,
        ]);
        $this->manager = User::factory()->manager()->create();
        $this->project = Project::factory()->create([
            'client_id' => $this->client->id,
            'manager_id' => $this->manager->id,
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function uploadPayload(array $overrides = []): array
    {
        return array_merge([
            'file' => UploadedFile::fake()->create('contrato.pdf', 100, 'application/pdf'),
            'name' => 'Contrato de empreitada',
            'type' => 'contrato',
            'visibility' => 'interno',
        ], $overrides);
    }

    private function documentFor(Project $project, string $visibility): Document
    {
        $path = "projects/{$project->id}/documents/doc.pdf";
        Storage::disk('local')->put($path, 'conteudo');

        return Document::factory()->create([
            'project_id' => $project->id,
            'visibility' => $visibility,
            'path' => $path,
        ]);
    }

    // ── Upload: validação ─────────────────────────────────────

    public function test_upload_stores_the_file_on_the_private_disk(): void
    {
        $this->actingAs($this->manager)
            ->post(route('admin.projects.documents.store', $this->project), $this->uploadPayload())
            ->assertRedirect();

        $document = Document::firstOrFail();
        Storage::disk('local')->assertExists($document->path);
        Storage::disk('public')->assertMissing($document->path);
    }

    public function test_upload_rejects_an_executable_disguised_by_extension(): void
    {
        $this->actingAs($this->manager)
            ->post(route('admin.projects.documents.store', $this->project), $this->uploadPayload([
                'file' => UploadedFile::fake()->create('payload.exe', 10, 'application/octet-stream'),
            ]))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_upload_rejects_a_file_over_the_size_limit(): void
    {
        $this->actingAs($this->manager)
            ->post(route('admin.projects.documents.store', $this->project), $this->uploadPayload([
                'file' => UploadedFile::fake()->create('enorme.pdf', 51201, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_upload_rejects_an_unknown_visibility(): void
    {
        $this->actingAs($this->manager)
            ->post(route('admin.projects.documents.store', $this->project), $this->uploadPayload([
                'visibility' => 'publico',
            ]))
            ->assertSessionHasErrors('visibility');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_photo_upload_rejects_a_disallowed_extension(): void
    {
        $this->actingAs($this->manager)
            ->post(route('admin.projects.photos.store', $this->project), [
                'photos' => [UploadedFile::fake()->create('script.php', 10, 'text/x-php')],
            ])
            ->assertSessionHasErrors('photos.0');

        $this->assertDatabaseCount('photos', 0);
    }

    public function test_releasing_a_document_to_the_client_is_recorded_in_the_history(): void
    {
        $this->actingAs($this->manager)
            ->post(route('admin.projects.documents.store', $this->project), $this->uploadPayload([
                'visibility' => 'cliente',
                'name' => 'Orçamento final',
            ]));

        $this->assertDatabaseHas('project_updates', [
            'project_id' => $this->project->id,
            'title' => 'Documento liberado: Orçamento final',
            'type' => 'documento',
        ]);
    }

    public function test_an_internal_document_is_not_announced_to_the_client(): void
    {
        $this->actingAs($this->manager)
            ->post(route('admin.projects.documents.store', $this->project), $this->uploadPayload());

        $this->assertDatabaseMissing('project_updates', ['type' => 'documento']);
    }

    // ── Download: portal do cliente ───────────────────────────

    public function test_client_can_download_a_document_released_to_them(): void
    {
        $document = $this->documentFor($this->project, 'cliente');

        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.documents.download', [$this->project, $document]))
            ->assertOk()
            ->assertDownload($document->name);
    }

    public function test_client_cannot_download_an_internal_document(): void
    {
        $document = $this->documentFor($this->project, 'interno');

        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.documents.download', [$this->project, $document]))
            ->assertForbidden();
    }

    public function test_client_cannot_download_another_clients_document(): void
    {
        $otherProject = Project::factory()->create(['client_id' => Client::factory()]);
        $document = $this->documentFor($otherProject, 'cliente');

        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.documents.download', [$otherProject, $document]))
            ->assertForbidden();
    }

    public function test_a_document_from_another_project_returns_404(): void
    {
        $otherProject = Project::factory()->create(['client_id' => $this->client->id]);
        $document = $this->documentFor($otherProject, 'cliente');

        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.documents.download', [$this->project, $document]))
            ->assertNotFound();
    }

    public function test_a_guest_cannot_download_a_document(): void
    {
        $document = $this->documentFor($this->project, 'cliente');

        $this->get(route('portal.projects.documents.download', [$this->project, $document]))
            ->assertRedirect(route('login'));
    }

    // ── Download e remoção: administração ─────────────────────

    public function test_a_manager_cannot_reach_documents_of_another_managers_project(): void
    {
        $otherProject = Project::factory()->create();
        $document = $this->documentFor($otherProject, 'interno');

        $this->actingAs($this->manager)
            ->get(route('admin.projects.documents.download', [$otherProject, $document]))
            ->assertForbidden();

        $this->actingAs($this->manager)
            ->delete(route('admin.projects.documents.destroy', [$otherProject, $document]))
            ->assertForbidden();

        $this->assertDatabaseCount('documents', 1);
    }

    public function test_admin_download_of_a_document_from_another_project_returns_404(): void
    {
        $otherProject = Project::factory()->create();
        $document = $this->documentFor($otherProject, 'interno');

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.documents.download', [$this->project, $document]))
            ->assertNotFound();
    }

    public function test_deleting_a_document_removes_the_file_and_the_row(): void
    {
        $document = $this->documentFor($this->project, 'interno');

        $this->actingAs($this->manager)
            ->delete(route('admin.projects.documents.destroy', [$this->project, $document]))
            ->assertRedirect();

        Storage::disk('local')->assertMissing($document->path);
        $this->assertSoftDeleted('documents', ['id' => $document->id]);
    }
}
