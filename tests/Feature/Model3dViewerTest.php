<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Plan;
use App\Models\PlanVersion;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * O visualizador 3D das plantas em GLB.
 *
 * A regra que importa é onde ele **não** aparece: prometer «ver em 3D» num
 * Revit ou num SketchUp e depois não desenhar nada seria pior do que oferecer
 * só o descarregamento. Converter esses formatos no servidor não é viável num
 * alojamento partilhado.
 */
class Model3dViewerTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    private Plan $plan;

    private User $manager;

    private User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        $client = Client::factory()->create();
        $this->manager = User::factory()->manager()->create();
        $this->clientUser = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => $client->id,
        ]);
        $this->project = Project::factory()->create([
            'client_id' => $client->id,
            'manager_id' => $this->manager->id,
            'type' => 'arquitetura',
        ]);
        $this->plan = Plan::factory()->create([
            'project_id' => $this->project->id,
            'discipline' => '3d',
        ]);
    }

    private function version(string $format, bool $sent = true, ?string $externalUrl = null): PlanVersion
    {
        return PlanVersion::factory()->create([
            'plan_id' => $this->plan->id,
            'version' => 1,
            'format' => $format,
            'path' => $externalUrl ? null : "projects/{$this->project->id}/plans/modelo.{$format}",
            'external_url' => $externalUrl,
            'sent_to_client_at' => $sent ? now() : null,
        ]);
    }

    public function test_a_glb_is_viewable(): void
    {
        $this->assertTrue($this->version('glb')->isViewable3d());
    }

    public function test_a_gltf_is_viewable(): void
    {
        $this->assertTrue($this->version('gltf')->isViewable3d());
    }

    #[DataProvider('heavyFormats')]
    public function test_formats_that_need_conversion_are_not_offered(string $format): void
    {
        $this->assertFalse($this->version($format)->isViewable3d());
    }

    public static function heavyFormats(): array
    {
        return [['rvt'], ['skp'], ['ifc'], ['dwg'], ['dxf'], ['pdf'], ['zip']];
    }

    public function test_a_version_that_lives_on_an_external_address_is_not_viewable(): void
    {
        $version = $this->version('glb', externalUrl: 'https://drive.google.com/modelo.glb');

        $this->assertFalse($version->isViewable3d());
    }

    public function test_the_team_sees_the_viewer_for_a_glb(): void
    {
        $this->version('glb');

        $this->actingAs($this->manager)
            ->get(route('admin.projects.show', $this->project).'?tab=plantas')
            ->assertOk()
            ->assertSee('<model-viewer', false);
    }

    public function test_the_team_does_not_see_a_viewer_for_a_revit_file(): void
    {
        $this->version('rvt');

        $this->actingAs($this->manager)
            ->get(route('admin.projects.show', $this->project).'?tab=plantas')
            ->assertOk()
            ->assertDontSee('<model-viewer', false);
    }

    public function test_the_client_sees_the_viewer_for_a_glb_that_was_sent(): void
    {
        $this->version('glb');

        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.show', $this->project))
            ->assertOk()
            ->assertSee('<model-viewer', false);
    }

    public function test_the_client_sees_no_viewer_for_a_version_never_sent(): void
    {
        $this->version('glb', sent: false);

        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.show', $this->project))
            ->assertOk()
            ->assertDontSee('<model-viewer', false);
    }

    public function test_the_policy_allows_the_blobs_the_viewer_needs(): void
    {
        $response = $this->actingAs($this->manager)->get(route('admin.dashboard'));

        $policy = $response->headers->get('Content-Security-Policy')
            ?? $response->headers->get('Content-Security-Policy-Report-Only');

        preg_match('/img-src ([^;]+)/', $policy, $img);
        preg_match('/worker-src ([^;]+)/', $policy, $worker);

        // Sem estes dois, o modelo desenha-se cinzento e sem geometria.
        $this->assertStringContainsString('blob:', $img[1]);
        $this->assertStringContainsString('blob:', $worker[1]);
    }
}
