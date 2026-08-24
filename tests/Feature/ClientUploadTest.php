<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Document;
use App\Models\Photo;
use App\Models\Project;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * ADV-03: o cliente envia fotos e documentos pelo portal.
 *
 * Até aqui o portal era só de leitura, e isso obrigava o cliente a mandar por
 * WhatsApp o que quisesse mostrar — o hábito que este produto existe para
 * substituir. Uma foto de uma infiltração pertence à obra, não a uma conversa.
 */
class ClientUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $clientUser;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $client = Client::factory()->create();
        $this->clientUser = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => $client->id,
        ]);
        $this->project = Project::factory()->create(['client_id' => $client->id]);
    }

    public function test_the_client_sends_photos_and_they_are_marked_as_theirs(): void
    {
        $this->actingAs($this->clientUser)
            ->post(route('portal.projects.uploads.photos', $this->project), [
                'photos' => [UploadedFile::fake()->image('infiltracao.jpg')],
                'caption' => 'Mancha no teto do quarto',
            ])->assertRedirect();

        $photo = Photo::firstOrFail();

        $this->assertSame($this->project->id, $photo->project_id);
        $this->assertSame($this->clientUser->id, $photo->uploaded_by);
        $this->assertSame('Mancha no teto do quarto', $photo->caption);
        Storage::disk('local')->assertExists($photo->path);
    }

    public function test_the_client_sends_a_document_that_stays_visible_to_them(): void
    {
        $this->actingAs($this->clientUser)
            ->post(route('portal.projects.uploads.document', $this->project), [
                'name' => 'Contrato assinado',
                'file' => UploadedFile::fake()->create('contrato.pdf', 120, 'application/pdf'),
            ])->assertRedirect();

        $document = Document::firstOrFail();

        $this->assertSame('Contrato assinado', $document->name);
        $this->assertSame($this->clientUser->id, $document->uploaded_by);
        // Escondê-lo do cliente que acabou de o enviar seria absurdo.
        $this->assertTrue($document->isClientVisible());
    }

    public function test_the_history_says_it_came_from_the_client(): void
    {
        $this->actingAs($this->clientUser)
            ->post(route('portal.projects.uploads.photos', $this->project), [
                'photos' => [
                    UploadedFile::fake()->image('a.jpg'),
                    UploadedFile::fake()->image('b.jpg'),
                ],
            ]);

        $this->assertDatabaseHas('project_updates', [
            'project_id' => $this->project->id,
            'title' => 'O cliente enviou 2 fotos',
            'type' => 'envio_cliente',
        ]);
    }

    public function test_a_client_cannot_send_to_another_clients_project(): void
    {
        $outro = Project::factory()->create(['client_id' => Client::factory()]);

        $this->actingAs($this->clientUser)
            ->post(route('portal.projects.uploads.photos', $outro), [
                'photos' => [UploadedFile::fake()->image('a.jpg')],
            ])->assertForbidden();

        $this->assertDatabaseCount('photos', 0);
    }

    public function test_the_company_can_turn_client_uploads_off(): void
    {
        Setting::set('allow_client_uploads', '0');

        $this->actingAs($this->clientUser)
            ->post(route('portal.projects.uploads.photos', $this->project), [
                'photos' => [UploadedFile::fake()->image('a.jpg')],
            ])->assertForbidden();

        $this->actingAs($this->clientUser)
            ->post(route('portal.projects.uploads.document', $this->project), [
                'name' => 'X',
                'file' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
            ])->assertForbidden();

        $this->assertDatabaseCount('photos', 0);
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_the_form_disappears_when_uploads_are_off(): void
    {
        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.show', $this->project))
            ->assertSee('Enviar fotos');

        Setting::set('allow_client_uploads', '0');

        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.show', $this->project))
            ->assertDontSee('Enviar fotos');
    }

    public function test_an_executable_disguised_as_a_photo_is_refused(): void
    {
        $this->actingAs($this->clientUser)
            ->post(route('portal.projects.uploads.photos', $this->project), [
                'photos' => [UploadedFile::fake()->create('payload.exe', 10, 'application/octet-stream')],
            ])->assertSessionHasErrors('photos.0');

        $this->assertDatabaseCount('photos', 0);
    }

    public function test_a_document_with_a_disallowed_extension_is_refused(): void
    {
        $this->actingAs($this->clientUser)
            ->post(route('portal.projects.uploads.document', $this->project), [
                'name' => 'Script',
                'file' => UploadedFile::fake()->create('script.php', 10, 'text/x-php'),
            ])->assertSessionHasErrors('file');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_more_than_ten_photos_at_once_is_refused(): void
    {
        $fotos = [];
        for ($i = 0; $i < 11; $i++) {
            $fotos[] = UploadedFile::fake()->image("foto{$i}.jpg");
        }

        $this->actingAs($this->clientUser)
            ->post(route('portal.projects.uploads.photos', $this->project), ['photos' => $fotos])
            ->assertSessionHasErrors('photos');

        $this->assertDatabaseCount('photos', 0);
    }

    public function test_the_team_sees_who_sent_the_photo(): void
    {
        $this->actingAs($this->clientUser)
            ->post(route('portal.projects.uploads.photos', $this->project), [
                'photos' => [UploadedFile::fake()->image('a.jpg')],
            ]);

        $photo = Photo::firstOrFail();

        $this->assertSame($this->clientUser->name, $photo->uploader->name);
    }
}
