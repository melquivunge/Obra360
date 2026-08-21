<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Expense;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * SEC-04 — validação de uploads: tipo MIME e tamanho, nas três entradas de
 * ficheiro do painel (fotos, documentos e comprovativos de despesa) mais a
 * imagem de capa da obra.
 */
class UploadValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    /**
     * Ficheiros que nenhuma das entradas deve aceitar.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function disallowedFiles(): array
    {
        return [
            'php' => ['shell.php', 'application/x-php'],
            'executável' => ['malware.exe', 'application/x-msdownload'],
            'zip' => ['pacote.zip', 'application/zip'],
            'svg' => ['vetor.svg', 'image/svg+xml'],
        ];
    }

    // ── Fotos ────────────────────────────────────────────────

    #[DataProvider('disallowedFiles')]
    public function test_photo_upload_rejects_a_disallowed_mime_type(string $name, string $mime): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.photos.store', $project), [
                'photos' => [UploadedFile::fake()->create($name, 10, $mime)],
            ])
            ->assertSessionHasErrors('photos.0');

        $this->assertDatabaseCount('photos', 0);
    }

    public function test_photo_upload_rejects_a_file_over_the_size_limit(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.photos.store', $project), [
                'photos' => [UploadedFile::fake()->create('enorme.jpg', 51201, 'image/jpeg')],
            ])
            ->assertSessionHasErrors('photos.0');

        $this->assertDatabaseCount('photos', 0);
    }

    public function test_photo_upload_requires_at_least_one_file(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.photos.store', $project), ['caption' => 'Sem ficheiro'])
            ->assertSessionHasErrors('photos');
    }

    public function test_photo_upload_rejects_a_phase_from_another_project(): void
    {
        $project = Project::factory()->create();
        $other = Project::factory()->create();
        $phase = ProjectPhase::factory()->create(['project_id' => $other->id]);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.photos.store', $project), [
                'photos' => [UploadedFile::fake()->image('obra.jpg')],
                'phase_id' => $phase->id,
            ])
            ->assertSessionHasErrors('phase_id');
    }

    // ── Documentos ───────────────────────────────────────────

    #[DataProvider('disallowedFiles')]
    public function test_document_upload_rejects_a_disallowed_mime_type(string $name, string $mime): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.documents.store', $project), [
                'file' => UploadedFile::fake()->create($name, 10, $mime),
                'name' => 'Ficheiro suspeito',
                'type' => 'outro',
                'visibility' => 'interno',
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_document_upload_rejects_a_file_over_the_size_limit(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.documents.store', $project), [
                'file' => UploadedFile::fake()->create('enorme.pdf', 51201, 'application/pdf'),
                'name' => 'Documento gigante',
                'type' => 'outro',
                'visibility' => 'interno',
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('documents', 0);
    }

    // ── Comprovativos de despesa ─────────────────────────────

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function expensePayload(array $overrides = []): array
    {
        return array_merge([
            'category' => 'material',
            'description' => 'Cimento',
            'amount' => 100,
            'spent_at' => '2026-05-01',
        ], $overrides);
    }

    public function test_expense_receipt_rejects_a_file_over_the_size_limit(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.expenses.store', $project), $this->expensePayload([
                'receipt' => UploadedFile::fake()->create('comprovativo.pdf', 10241, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('receipt');

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_expense_receipt_rejects_a_document_format_it_does_not_support(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.expenses.store', $project), $this->expensePayload([
                'receipt' => UploadedFile::fake()->create('recibo.docx', 10, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            ]))
            ->assertSessionHasErrors('receipt');
    }

    public function test_an_allowed_receipt_is_accepted_and_stored(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.expenses.store', $project), $this->expensePayload([
                'receipt' => UploadedFile::fake()->create('recibo.pdf', 50, 'application/pdf'),
            ]))
            ->assertRedirect();

        $receiptPath = Expense::firstOrFail()->receipt_path;
        $this->assertNotNull($receiptPath);
        Storage::disk('local')->assertExists($receiptPath);
    }

    // ── Imagem de capa da obra ───────────────────────────────

    public function test_project_cover_rejects_a_non_image(): void
    {
        $client = Client::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.store'), [
                'client_id' => $client->id,
                'name' => 'Obra com capa inválida',
                'status' => 'planejada',
                'cover_image' => UploadedFile::fake()->create('capa.pdf', 10, 'application/pdf'),
            ])
            ->assertSessionHasErrors('cover_image');

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_project_cover_rejects_an_image_over_the_size_limit(): void
    {
        $client = Client::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.store'), [
                'client_id' => $client->id,
                'name' => 'Obra com capa enorme',
                'status' => 'planejada',
                'cover_image' => UploadedFile::fake()->create('capa.jpg', 4097, 'image/jpeg'),
            ])
            ->assertSessionHasErrors('cover_image');
    }

    public function test_an_allowed_cover_is_accepted_and_stored(): void
    {
        $client = Client::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.store'), [
                'client_id' => $client->id,
                'name' => 'Obra com capa válida',
                'status' => 'planejada',
                'cover_image' => UploadedFile::fake()->image('capa.jpg', 800, 600),
            ])
            ->assertRedirect();

        $path = Project::firstOrFail()->cover_image_path;
        $this->assertNotNull($path);
        Storage::disk('local')->assertExists($path);
    }
}
