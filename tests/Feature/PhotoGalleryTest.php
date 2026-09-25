<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Photo;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\User;
use App\Support\Thumbnail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoGalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploading_a_photo_generates_a_thumbnail(): void
    {
        Storage::fake('local');
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.photos.store', $project), [
                'photos' => [UploadedFile::fake()->image('obra.jpg', 1200, 900)],
            ])
            ->assertRedirect();

        $photo = Photo::first();

        $this->assertNotNull($photo->thumbnail_path);
        Storage::disk('local')->assertExists($photo->thumbnail_path);
        $this->assertStringContainsString('/thumbs/', $photo->thumbnail_path);
    }

    public function test_the_thumbnail_is_smaller_than_the_original(): void
    {
        Storage::fake('local');
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.photos.store', $project), [
                'photos' => [UploadedFile::fake()->image('grande.jpg', 2000, 1000)],
            ]);

        $photo = Photo::first();
        $size = getimagesizefromstring(Storage::disk('local')->get($photo->thumbnail_path));

        $this->assertSame(Thumbnail::MAX_EDGE, $size[0]);
        $this->assertLessThanOrEqual(Thumbnail::MAX_EDGE, $size[1]);
    }

    public function test_a_video_gets_no_thumbnail_and_the_upload_still_works(): void
    {
        Storage::fake('local');
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.photos.store', $project), [
                'photos' => [UploadedFile::fake()->create('clipe.mp4', 200, 'video/mp4')],
            ])
            ->assertRedirect();

        $photo = Photo::first();
        $this->assertSame('video', $photo->media_type);
        $this->assertNull($photo->thumbnail_path);
    }

    public function test_deleting_a_photo_removes_the_thumbnail_too(): void
    {
        Storage::fake('local');
        $project = Project::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.projects.photos.store', $project), [
            'photos' => [UploadedFile::fake()->image('obra.jpg')],
        ]);

        $photo = Photo::first();
        $thumb = $photo->thumbnail_path;

        $this->actingAs($admin)->delete(route('admin.projects.photos.destroy', [$project, $photo]));

        Storage::disk('local')->assertMissing($thumb);
    }

    public function test_thumb_query_parameter_serves_the_thumbnail(): void
    {
        Storage::fake('local');
        $project = Project::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.projects.photos.store', $project), [
            'photos' => [UploadedFile::fake()->image('obra.jpg', 1600, 1200)],
        ]);

        $photo = Photo::first();

        $full = $this->actingAs($admin)->get(route('admin.projects.photos.show', [$project, $photo]));
        $thumb = $this->actingAs($admin)->get(route('admin.projects.photos.show', [$project, $photo]).'?thumb=1');

        $full->assertOk();
        $thumb->assertOk();
        $this->assertLessThan(
            strlen($full->streamedContent()),
            strlen($thumb->streamedContent()),
            'A miniatura devia ser mais pequena que o original.'
        );
    }

    public function test_gallery_filters_by_phase(): void
    {
        $project = Project::factory()->create();
        $phase = ProjectPhase::factory()->create(['project_id' => $project->id]);

        Photo::factory()->create(['project_id' => $project->id, 'phase_id' => $phase->id, 'caption' => 'FotoDaFaseAlfa']);
        Photo::factory()->create(['project_id' => $project->id, 'phase_id' => null, 'caption' => 'FotoAvulsaBeta']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.show', ['project' => $project, 'photo_phase' => $phase->id]))
            ->assertOk()
            ->assertSee('FotoDaFaseAlfa')
            ->assertDontSee('FotoAvulsaBeta');
    }

    public function test_gallery_filters_by_date_range(): void
    {
        $project = Project::factory()->create();
        Photo::factory()->create(['project_id' => $project->id, 'taken_at' => '2026-01-10', 'caption' => 'Janeiro']);
        Photo::factory()->create(['project_id' => $project->id, 'taken_at' => '2026-06-10', 'caption' => 'Junho']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.show', [
                'project' => $project, 'photo_from' => '2026-05-01', 'photo_to' => '2026-07-01',
            ]))
            ->assertOk()
            ->assertSee('Junho')
            ->assertDontSee('Janeiro');
    }

    public function test_gallery_can_be_ordered_oldest_first(): void
    {
        $project = Project::factory()->create();
        Photo::factory()->create(['project_id' => $project->id, 'taken_at' => '2026-01-10', 'caption' => 'Antiga']);
        Photo::factory()->create(['project_id' => $project->id, 'taken_at' => '2026-06-10', 'caption' => 'Recente']);

        $html = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.show', ['project' => $project, 'photo_order' => 'antigas']))
            ->assertOk()
            ->getContent();

        $this->assertLessThan(
            strpos($html, 'Recente'),
            strpos($html, 'Antiga'),
            'Com ordem "antigas" a foto mais antiga devia aparecer primeiro.'
        );
    }

    // ── O tipo com que a foto é servida ──────────────────────────────────────

    /**
     * O `Content-Type` vem de uma lista fechada indexada pela extensão do
     * caminho que o servidor gerou no `store()`, e não do conteúdo do ficheiro:
     * media servida com um tipo adivinhado é como um ficheiro antigo se torna
     * HTML executado na origem do portal.
     */
    public function test_a_photo_is_served_with_the_type_from_the_allowlist(): void
    {
        Storage::fake('local');
        $project = Project::factory()->create();
        $photo = Photo::factory()->create([
            'project_id' => $project->id,
            'path' => "projects/{$project->id}/photos/obra.png",
            'thumbnail_path' => null,
        ]);
        Storage::disk('local')->put($photo->path, 'bytes');

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.photos.show', [$project, $photo]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    /** O portal do cliente mostra os vídeos que a equipa enviou. */
    public function test_the_client_portal_serves_a_video_with_its_own_type(): void
    {
        Storage::fake('local');
        $client = Client::factory()->create();
        $clientUser = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => $client->id,
        ]);
        $project = Project::factory()->create(['client_id' => $client->id]);
        $photo = Photo::factory()->create([
            'project_id' => $project->id,
            'path' => "projects/{$project->id}/photos/obra.mp4",
            'thumbnail_path' => null,
            'media_type' => 'video',
        ]);
        Storage::disk('local')->put($photo->path, 'bytes');

        $this->actingAs($clientUser)
            ->get(route('portal.projects.photos.show', [$project, $photo]))
            ->assertOk()
            ->assertHeader('Content-Type', 'video/mp4')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    /** Fora da lista não há tipo adivinhado — há 404. */
    public function test_a_photo_with_an_extension_outside_the_allowlist_is_not_served(): void
    {
        Storage::fake('local');
        $project = Project::factory()->create();
        $photo = Photo::factory()->create([
            'project_id' => $project->id,
            'path' => "projects/{$project->id}/photos/obra.svg",
            'thumbnail_path' => null,
        ]);
        Storage::disk('local')->put($photo->path, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.photos.show', [$project, $photo]))
            ->assertNotFound();
    }
}
