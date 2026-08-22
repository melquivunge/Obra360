<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Photo;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * O comando move media legada do disco público (servido sem autenticação) para
 * o disco privado. Se deixar algum tipo de ficheiro para trás, o fallback nos
 * controllers não pode ser removido — daí o teste cobrir os quatro tipos.
 */
class MigratePublicMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_migrates_covers_photos_thumbnails_and_documents(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $paths = [
            'cover' => 'projects/1/cover.jpg',
            'photo' => 'projects/1/photos/foto.jpg',
            'thumb' => 'projects/1/photos/thumbs/foto.jpg',
            'doc' => 'projects/1/documents/contrato.pdf',
        ];
        foreach ($paths as $path) {
            Storage::disk('public')->put($path, 'conteudo');
        }

        $project = Project::factory()->create(['cover_image_path' => $paths['cover']]);
        Photo::factory()->create([
            'project_id' => $project->id,
            'path' => $paths['photo'],
            'thumbnail_path' => $paths['thumb'],
        ]);
        Document::factory()->create(['project_id' => $project->id, 'path' => $paths['doc']]);

        $this->artisan('obra360:media:migrate-public --commit --delete-source')
            ->assertSuccessful();

        foreach ($paths as $path) {
            Storage::disk('local')->assertExists($path);
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_a_dry_run_copies_nothing(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        Storage::disk('public')->put('projects/1/cover.jpg', 'conteudo');
        Project::factory()->create(['cover_image_path' => 'projects/1/cover.jpg']);

        $this->artisan('obra360:media:migrate-public')->assertSuccessful();

        Storage::disk('local')->assertMissing('projects/1/cover.jpg');
        Storage::disk('public')->assertExists('projects/1/cover.jpg');
    }

    public function test_delete_source_without_commit_is_refused(): void
    {
        $this->artisan('obra360:media:migrate-public --delete-source')->assertFailed();
    }
}
