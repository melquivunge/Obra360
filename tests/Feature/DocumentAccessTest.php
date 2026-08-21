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

class DocumentAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'file' => UploadedFile::fake()->create('contrato.pdf', 20, 'application/pdf'),
            'name' => 'Contrato assinado',
            'type' => 'contrato',
            'visibility' => 'cliente',
        ], $overrides);
    }

    /**
     * Um documento com ficheiro real no disco falso, pronto para download.
     */
    private function storedDocument(Project $project, string $visibility, string $name): Document
    {
        $path = "projects/{$project->id}/documents/".md5($name).'.pdf';
        Storage::disk('local')->put($path, 'conteudo-do-pdf');

        return Document::factory()->create([
            'project_id' => $project->id,
            'name' => $name,
            'visibility' => $visibility,
            'path' => $path,
        ]);
    }

    /**
     * Um cliente com utilizador e obra própria.
     *
     * @return array{0: User, 1: Project}
     */
    private function clientWithProject(): array
    {
        $client = Client::factory()->create();
        $user = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => $client->id,
        ]);

        return [$user, Project::factory()->create(['client_id' => $client->id])];
    }

    // ── Upload ───────────────────────────────────────────────

    public function test_admin_can_upload_a_document(): void
    {
        Storage::fake('local');
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.documents.store', $project), $this->payload())
            ->assertRedirect();

        $document = Document::firstOrFail();
        $this->assertSame('Contrato assinado', $document->name);
        $this->assertSame('cliente', $document->visibility);
        Storage::disk('local')->assertExists($document->path);
    }

    public function test_releasing_a_document_to_the_client_records_it_in_the_history(): void
    {
        Storage::fake('local');
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.documents.store', $project), $this->payload());

        $this->assertDatabaseHas('project_updates', [
            'project_id' => $project->id,
            'title' => 'Documento liberado: Contrato assinado',
            'type' => 'documento',
        ]);
    }

    public function test_an_internal_document_is_not_announced_in_the_history(): void
    {
        Storage::fake('local');
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.documents.store', $project), $this->payload([
                'name' => 'Orçamento interno',
                'visibility' => 'interno',
            ]));

        $this->assertDatabaseMissing('project_updates', ['type' => 'documento']);
    }

    public function test_invalid_document_payload_is_rejected(): void
    {
        Storage::fake('local');
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.documents.store', $project), [
                'file' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
                'name' => '',
                'type' => 'inexistente',
                'visibility' => 'publico',
            ])
            ->assertSessionHasErrors(['name', 'type', 'visibility']);

        $this->assertDatabaseCount('documents', 0);
    }

    // ── Download e remoção pelo staff ────────────────────────

    public function test_admin_can_download_a_document(): void
    {
        Storage::fake('local');
        $project = Project::factory()->create();
        $document = $this->storedDocument($project, 'interno', 'Planta.pdf');

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.documents.download', [$project, $document]));

        $response->assertOk();
        $this->assertSame('conteudo-do-pdf', $response->streamedContent());
    }

    public function test_admin_can_delete_a_document_and_its_file(): void
    {
        Storage::fake('local');
        $project = Project::factory()->create();
        $document = $this->storedDocument($project, 'interno', 'Planta.pdf');

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.projects.documents.destroy', [$project, $document]))
            ->assertRedirect();

        $this->assertSoftDeleted('documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing($document->path);
    }

    public function test_a_document_from_another_project_cannot_be_downloaded(): void
    {
        Storage::fake('local');
        $project = Project::factory()->create();
        $other = Project::factory()->create();
        $document = $this->storedDocument($other, 'interno', 'Planta.pdf');

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.documents.download', [$project, $document]))
            ->assertNotFound();
    }

    public function test_manager_cannot_download_a_document_of_another_managers_project(): void
    {
        Storage::fake('local');
        $project = Project::factory()->create();
        $document = $this->storedDocument($project, 'interno', 'Planta.pdf');

        $this->actingAs(User::factory()->manager()->create())
            ->get(route('admin.projects.documents.download', [$project, $document]))
            ->assertForbidden();
    }

    // ── DOC-03: visibilidade para o cliente ──────────────────

    public function test_client_downloads_a_document_released_to_them(): void
    {
        Storage::fake('local');
        [$user, $project] = $this->clientWithProject();
        $document = $this->storedDocument($project, 'cliente', 'Contrato.pdf');

        $response = $this->actingAs($user)
            ->get(route('portal.projects.documents.download', [$project, $document]));

        $response->assertOk();
        $this->assertSame('conteudo-do-pdf', $response->streamedContent());
    }

    public function test_an_internal_document_is_not_listed_in_the_client_portal(): void
    {
        Storage::fake('local');
        [$user, $project] = $this->clientWithProject();
        $this->storedDocument($project, 'interno', 'OrcamentoInternoZeta');
        $this->storedDocument($project, 'cliente', 'ContratoVisivelAlfa');

        $this->actingAs($user)
            ->get(route('portal.projects.show', $project))
            ->assertOk()
            ->assertSee('ContratoVisivelAlfa')
            ->assertDontSee('OrcamentoInternoZeta');
    }

    public function test_client_cannot_download_a_document_of_another_project_of_their_own(): void
    {
        Storage::fake('local');
        [$user, $project] = $this->clientWithProject();
        $otherProject = Project::factory()->create(['client_id' => $user->client_id]);
        $document = $this->storedDocument($otherProject, 'cliente', 'Contrato.pdf');

        $this->actingAs($user)
            ->get(route('portal.projects.documents.download', [$project, $document]))
            ->assertNotFound();
    }

    public function test_client_cannot_reach_the_admin_download_route(): void
    {
        Storage::fake('local');
        [$user, $project] = $this->clientWithProject();
        $document = $this->storedDocument($project, 'cliente', 'Contrato.pdf');

        $this->actingAs($user)
            ->get(route('admin.projects.documents.download', [$project, $document]))
            ->assertForbidden();
    }
}
