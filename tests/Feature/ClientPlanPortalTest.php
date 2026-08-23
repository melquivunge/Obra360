<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Plan;
use App\Models\PlanVersion;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * PORT-01 a PORT-06: as plantas no portal do cliente.
 *
 * A regra central desta fatia não é uma funcionalidade, é uma ausência: uma
 * versão que ainda não foi enviada não existe para o cliente. Não aparece na
 * lista, não se descarrega, não se vê e não se aprova — e cada uma dessas
 * portas é testada em separado, porque foi sempre a que ninguém verificou que
 * deixou passar o esboço de trabalho.
 */
class ClientPlanPortalTest extends TestCase
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

        $this->client = Client::factory()->create();
        $this->clientUser = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => $this->client->id,
        ]);
        $this->manager = User::factory()->manager()->create();
        $this->project = Project::factory()->create([
            'client_id' => $this->client->id,
            'manager_id' => $this->manager->id,
            'type' => 'arquitetura',
        ]);
    }

    private function plan(?Project $project = null): Plan
    {
        return Plan::factory()->create([
            'project_id' => ($project ?? $this->project)->id,
            'name' => 'Planta baixa — piso 0',
        ]);
    }

    /**
     * Uma versão com ficheiro real no disco falso, para os downloads terem o
     * que servir.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function version(Plan $plan, array $attributes = []): PlanVersion
    {
        $number = $attributes['version'] ?? 1;
        $path = "projects/{$plan->project_id}/plans/{$plan->id}/v{$number}.pdf";
        Storage::disk('local')->put($path, '%PDF-1.4 desenho');

        return PlanVersion::factory()->create(array_merge([
            'plan_id' => $plan->id,
            'version' => $number,
            'path' => $path,
            'original_name' => "piso-0-v{$number}.pdf",
            'format' => 'pdf',
        ], $attributes));
    }

    private function sentVersion(Plan $plan, array $attributes = []): PlanVersion
    {
        return $this->version($plan, array_merge(['sent_to_client_at' => now()], $attributes));
    }

    private function approve(Plan $plan, PlanVersion $version, ?User $as = null)
    {
        return $this->actingAs($as ?? $this->clientUser)
            ->from(route('portal.projects.show', $this->project))
            ->post(route('portal.projects.plans.versions.approve', [$this->project, $plan, $version]));
    }

    // ── PORT-01: o que não foi enviado não existe ─────────────

    public function test_the_plans_tab_lists_only_versions_sent_to_the_client(): void
    {
        $plan = $this->plan();
        $this->version($plan, ['version' => 1, 'sent_to_client_at' => now(), 'superseded_at' => now()]);
        $this->version($plan, ['version' => 2]); // esboço de trabalho

        $response = $this->actingAs($this->clientUser)
            ->get(route('portal.projects.show', $this->project))
            ->assertOk()
            ->assertSee('Plantas')
            ->assertSee('Planta baixa — piso 0', false);

        $response->assertSee('Versão v1', false);
        $response->assertDontSee('Versão v2', false);
        $response->assertDontSee('piso-0-v2.pdf');
    }

    public function test_a_plan_without_any_sent_version_does_not_appear_at_all(): void
    {
        $plan = $this->plan();
        $this->version($plan);

        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.show', $this->project))
            ->assertOk()
            ->assertDontSee('Planta baixa — piso 0', false)
            ->assertDontSee('Plantas e desenhos');
    }

    public function test_the_plans_tab_is_absent_when_the_project_has_no_sent_plans(): void
    {
        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.show', $this->project))
            ->assertOk()
            ->assertDontSee('Plantas e desenhos');
    }

    /**
     * 404 e não 403: um 403 confirmaria que a versão existe, e saber que há uma
     * revisão por mostrar já é informação a mais sobre o trabalho do estúdio.
     */
    public function test_an_unsent_version_cannot_be_downloaded(): void
    {
        $plan = $this->plan();
        $version = $this->version($plan);

        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.plans.versions.download', [$this->project, $plan, $version]))
            ->assertNotFound();
    }

    public function test_an_unsent_version_cannot_be_previewed(): void
    {
        $plan = $this->plan();
        $version = $this->version($plan);

        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.plans.versions.preview', [$this->project, $plan, $version]))
            ->assertNotFound();
    }

    public function test_an_unsent_version_cannot_be_approved(): void
    {
        $plan = $this->plan();
        $version = $this->version($plan);

        $this->approve($plan, $version)->assertNotFound();

        $this->assertDatabaseHas('plan_versions', [
            'id' => $version->id,
            'approved_at' => null,
            'approved_by' => null,
        ]);
    }

    // ── PORT-02: descarregar corrente e anteriores ────────────

    public function test_client_downloads_the_current_version(): void
    {
        $plan = $this->plan();
        $version = $this->sentVersion($plan);

        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.plans.versions.download', [$this->project, $plan, $version]))
            ->assertOk()
            ->assertDownload('piso-0-v1.pdf');
    }

    public function test_a_superseded_version_stays_downloadable(): void
    {
        $plan = $this->plan();
        $old = $this->sentVersion($plan, ['version' => 1, 'superseded_at' => now()]);
        $this->sentVersion($plan, ['version' => 2]);

        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.plans.versions.download', [$this->project, $plan, $old]))
            ->assertOk()
            ->assertDownload('piso-0-v1.pdf');
    }

    /**
     * PORT-02: uma versão que só tem endereço externo liga directamente ao
     * endereço. A rota não a serve nem reencaminha para ela — a aplicação não é
     * um redireccionador aberto.
     */
    public function test_an_external_version_is_linked_and_never_served_by_the_download_route(): void
    {
        $plan = $this->plan();
        $version = PlanVersion::factory()->external('https://drive.example.com/planta-piso-0')->create([
            'plan_id' => $plan->id,
            'version' => 1,
            'sent_to_client_at' => now(),
        ]);

        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.show', $this->project))
            ->assertOk()
            ->assertSee('https://drive.example.com/planta-piso-0', false);

        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.plans.versions.download', [$this->project, $plan, $version]))
            ->assertNotFound();
    }

    // ── PORT-03: ver no browser ───────────────────────────────

    public function test_a_pdf_is_served_inline_for_viewing(): void
    {
        $plan = $this->plan();
        $version = $this->sentVersion($plan);

        $response = $this->actingAs($this->clientUser)
            ->get(route('portal.projects.plans.versions.preview', [$this->project, $plan, $version]))
            ->assertOk();

        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('inline', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_an_image_is_served_inline_with_its_declared_type(): void
    {
        $plan = $this->plan();
        $version = $this->sentVersion($plan, ['format' => 'png', 'original_name' => 'alcado.png']);

        $response = $this->actingAs($this->clientUser)
            ->get(route('portal.projects.plans.versions.preview', [$this->project, $plan, $version]))
            ->assertOk();

        $this->assertSame('image/png', $response->headers->get('Content-Type'));
    }

    /** Os formatos pesados descarregam-se; visualizador 3D fica para depois. */
    public function test_heavy_formats_are_not_previewable_but_stay_downloadable(): void
    {
        $plan = $this->plan();

        foreach (['dwg', 'skp', 'rvt', 'ifc', 'glb'] as $format) {
            $version = $this->sentVersion($plan, [
                'version' => 1,
                'format' => $format,
                'original_name' => "modelo.{$format}",
            ]);

            $this->actingAs($this->clientUser)
                ->get(route('portal.projects.plans.versions.preview', [$this->project, $plan, $version]))
                ->assertNotFound();

            $this->actingAs($this->clientUser)
                ->get(route('portal.projects.plans.versions.download', [$this->project, $plan, $version]))
                ->assertOk();

            $version->delete();
        }
    }

    // ── PORT-04 e PORT-06: a aprovação ────────────────────────

    public function test_client_approves_the_current_version_and_the_author_and_date_are_recorded(): void
    {
        $plan = $this->plan();
        $version = $this->sentVersion($plan);

        $this->approve($plan, $version)->assertRedirect(route('portal.projects.show', $this->project));

        $version->refresh();
        $this->assertNotNull($version->approved_at);
        $this->assertSame($this->clientUser->id, $version->approved_by);
        $this->assertSame('aprovada', $plan->fresh()->status);
    }

    public function test_approving_twice_neither_duplicates_nor_overwrites_the_original_approval(): void
    {
        $plan = $this->plan();
        $version = $this->sentVersion($plan);

        $this->approve($plan, $version);
        $version->refresh();
        $firstApprover = $version->approved_by;
        $firstMoment = $version->approved_at;

        // Um segundo utilizador do mesmo cliente — o separador que ficou aberto.
        $second = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => $this->client->id,
        ]);

        $this->travel(1)->hours();
        $this->approve($plan, $version, $second)->assertRedirect();

        $version->refresh();
        $this->assertSame($firstApprover, $version->approved_by);
        $this->assertTrue($firstMoment->equalTo($version->approved_at));

        $this->assertSame(1, ProjectUpdate::where('type', 'planta_aprovada')->count());
    }

    public function test_a_superseded_version_cannot_be_approved(): void
    {
        $plan = $this->plan();
        $old = $this->sentVersion($plan, ['version' => 1, 'superseded_at' => now()]);
        $this->sentVersion($plan, ['version' => 2]);

        $this->approve($plan, $old)->assertSessionHasErrors('plan_version');

        $this->assertDatabaseHas('plan_versions', [
            'id' => $old->id,
            'approved_at' => null,
        ]);
    }

    // ── PORT-05: histórico e auditoria ────────────────────────

    public function test_the_approval_enters_the_project_history(): void
    {
        $plan = $this->plan();
        $version = $this->sentVersion($plan);

        $this->approve($plan, $version);

        $this->assertDatabaseHas('project_updates', [
            'project_id' => $this->project->id,
            'title' => 'Planta aprovada pelo cliente: Planta baixa — piso 0 (v1)',
            'type' => 'planta_aprovada',
            'user_id' => $this->clientUser->id,
        ]);
    }

    public function test_the_approval_is_recorded_in_the_audit_trail_with_its_author(): void
    {
        $plan = $this->plan();
        $version = $this->sentVersion($plan);

        $this->approve($plan, $version);

        $log = AuditLog::where('auditable_type', PlanVersion::class)
            ->where('auditable_id', $version->id)
            ->where('action', 'updated')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($this->clientUser->id, $log->user_id);
        $this->assertSame($this->clientUser->name, $log->user_name);
        $this->assertArrayHasKey('approved_by', $log->changes['para']);
        $this->assertArrayHasKey('approved_at', $log->changes['para']);
    }

    // ── Autorização ───────────────────────────────────────────

    public function test_a_user_of_another_client_gets_403_on_the_plans_of_this_project(): void
    {
        $plan = $this->plan();
        $version = $this->sentVersion($plan);

        $stranger = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => Client::factory()->create()->id,
        ]);

        $this->actingAs($stranger)
            ->get(route('portal.projects.plans.versions.download', [$this->project, $plan, $version]))
            ->assertForbidden();

        $this->actingAs($stranger)
            ->get(route('portal.projects.plans.versions.preview', [$this->project, $plan, $version]))
            ->assertForbidden();

        $this->approve($plan, $version, $stranger)->assertForbidden();
    }

    public function test_a_plan_from_another_project_returns_404(): void
    {
        $otherProject = Project::factory()->create(['client_id' => $this->client->id]);
        $plan = $this->plan($otherProject);
        $version = $this->sentVersion($plan);

        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.plans.versions.download', [$this->project, $plan, $version]))
            ->assertNotFound();
    }

    public function test_a_version_from_another_plan_returns_404(): void
    {
        $plan = $this->plan();
        $other = $this->plan();
        $version = $this->sentVersion($other);

        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.plans.versions.download', [$this->project, $plan, $version]))
            ->assertNotFound();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $plan = $this->plan();
        $version = $this->sentVersion($plan);

        $this->get(route('portal.projects.plans.versions.download', [$this->project, $plan, $version]))
            ->assertRedirect(route('login'));
    }

    public function test_an_internal_user_cannot_use_the_portal_plan_routes(): void
    {
        $plan = $this->plan();
        $version = $this->sentVersion($plan);

        $this->actingAs($this->manager)
            ->get(route('portal.projects.plans.versions.download', [$this->project, $plan, $version]))
            ->assertForbidden();
    }
}
