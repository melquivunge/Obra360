<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Plan;
use App\Models\PlanVersion;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * As plantas são o produto de um projecto de arquitectura, e o que distingue
 * uma planta de um documento é a numeração das versões: o valor está em saber,
 * meses depois, qual era o desenho bom e qual foi substituído por qual.
 *
 * Estes testes fixam isso, e as três armadilhas que o rodeiam: a validação de
 * formatos que o `mimes:` do Laravel não sabe resolver, o tecto de upload do
 * servidor (LIM-01/LIM-02/LIM-03) e quem chega a quê.
 */
class PlanTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;

    private User $manager;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->client = Client::factory()->create();
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

    /** @param array<string, mixed> $overrides */
    private function versionPayload(array $overrides = []): array
    {
        return array_merge([
            'file' => UploadedFile::fake()->create('piso-0.pdf', 100, 'application/pdf'),
        ], $overrides);
    }

    private function uploadVersion(Plan $plan, array $overrides = [], ?User $as = null)
    {
        return $this->actingAs($as ?? $this->manager)->post(
            route('admin.projects.plans.versions.store', [$plan->project, $plan]),
            $this->versionPayload($overrides),
        );
    }

    // ── A planta ──────────────────────────────────────────────

    public function test_a_plan_is_created_with_a_name_and_a_discipline(): void
    {
        $this->actingAs($this->manager)
            ->post(route('admin.projects.plans.store', $this->project), [
                'name' => 'Planta baixa — piso 0',
                'discipline' => '3d',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('plans', [
            'project_id' => $this->project->id,
            'name' => 'Planta baixa — piso 0',
            'discipline' => '3d',
            'status' => 'em_elaboracao',
            'created_by' => $this->manager->id,
        ]);
    }

    public function test_an_unknown_discipline_is_rejected(): void
    {
        $this->actingAs($this->manager)
            ->post(route('admin.projects.plans.store', $this->project), [
                'name' => 'Corte AA',
                'discipline' => '4d',
            ])
            ->assertSessionHasErrors('discipline');

        $this->assertDatabaseCount('plans', 0);
    }

    // ── PLAN-02/PLAN-04: numeração e substituição ─────────────

    public function test_the_first_version_is_v1_and_the_second_is_v2(): void
    {
        $plan = $this->plan();

        $this->uploadVersion($plan)->assertRedirect();
        $this->uploadVersion($plan)->assertRedirect();

        $this->assertSame([1, 2], $plan->versions()->reorder('version')->pluck('version')->all());
    }

    public function test_a_new_version_supersedes_the_previous_one_which_stays_downloadable(): void
    {
        $plan = $this->plan();
        $this->uploadVersion($plan);
        $first = $plan->versions()->firstOrFail();

        $this->uploadVersion($plan);

        $this->assertNotNull($first->fresh()->superseded_at, 'A v1 devia ficar marcada como substituída.');
        $this->assertNull($plan->versions()->where('version', 2)->firstOrFail()->superseded_at);

        $this->actingAs($this->manager)
            ->get(route('admin.projects.plans.versions.download', [$this->project, $plan, $first]))
            ->assertOk()
            ->assertDownload('piso-0.pdf');
    }

    public function test_the_current_version_is_the_highest_numbered_one(): void
    {
        $plan = $this->plan();
        $this->uploadVersion($plan);
        $this->uploadVersion($plan);

        $this->assertSame(2, $plan->fresh()->currentVersion->version);
    }

    // ── PLAN-06: onde o ficheiro vive ─────────────────────────

    public function test_the_file_is_stored_on_the_private_disk(): void
    {
        $plan = $this->plan();
        $this->uploadVersion($plan);

        $version = $plan->versions()->firstOrFail();

        Storage::disk('local')->assertExists($version->path);
        $this->assertStringContainsString("projects/{$this->project->id}/plans/{$plan->id}", $version->path);
        $this->assertSame('piso-0.pdf', $version->original_name);
        $this->assertSame('pdf', $version->format);
        $this->assertNotNull($version->size);
    }

    // ── PLAN-07: formatos ─────────────────────────────────────

    /**
     * A rede de segurança da decisão de usar `extensions:` em vez de `mimes:`.
     * Metade destes formatos — dwg, skp, rvt, ifc, glb — não existe no mapa de
     * tipos MIME que o `mimes:` consulta, e seria recusada em silêncio.
     */
    public function test_every_accepted_format_passes_validation(): void
    {
        $plan = $this->plan();

        foreach (PlanVersion::FORMATS as $format) {
            $this->uploadVersion($plan, [
                'file' => UploadedFile::fake()->create("desenho.{$format}", 50),
            ])->assertSessionHasNoErrors("Formato {$format} devia ser aceite.");
        }

        $this->assertSame(count(PlanVersion::FORMATS), $plan->versions()->count());
    }

    public function test_a_format_that_is_not_accepted_is_rejected(): void
    {
        $plan = $this->plan();

        $this->uploadVersion($plan, [
            'file' => UploadedFile::fake()->create('malicioso.exe', 10),
        ])->assertSessionHasErrors('file');

        $this->assertDatabaseCount('plan_versions', 0);
    }

    public function test_a_php_file_disguised_with_an_accepted_name_is_rejected(): void
    {
        $plan = $this->plan();

        $this->uploadVersion($plan, [
            'file' => UploadedFile::fake()->create('planta.php', 10, 'text/x-php'),
        ])->assertSessionHasErrors('file');

        $this->assertDatabaseCount('plan_versions', 0);
    }

    // ── LIM-01/LIM-02: o tecto do servidor ────────────────────

    public function test_a_file_over_the_server_limit_explains_the_limit(): void
    {
        $plan = $this->plan();

        $this->uploadVersion($plan, [
            'file' => UploadedFile::fake()->create('modelo.rvt', PlanVersion::maxUploadKilobytes() + 1),
        ])->assertSessionHasErrors(['file' => PlanVersion::overLimitMessage()]);

        $this->assertDatabaseCount('plan_versions', 0);
    }

    public function test_the_upload_form_states_the_limit_before_anyone_tries(): void
    {
        $this->actingAs($this->manager)
            ->get(route('admin.projects.show', $this->project))
            ->assertOk()
            ->assertSee('Limite deste servidor: '.PlanVersion::maxUploadLabel().' por arquivo.', false);
    }

    /**
     * Um envio acima do `post_max_size` do PHP com `Content-Length` declarado é
     * travado pelo `ValidatePostSize` do Laravel — antes de a sessão arrancar,
     * portanto sem forma de devolver o erro ao formulário. O que resta é a
     * página de 413, e o que ela não pode ser é o texto genérico do servidor.
     */
    public function test_an_oversized_request_gets_a_413_page_that_explains_the_limit(): void
    {
        $plan = $this->plan();

        $this->actingAs($this->manager)->call(
            'POST',
            route('admin.projects.plans.versions.store', [$this->project, $plan]),
            [], [], [],
            [
                'CONTENT_TYPE' => 'multipart/form-data; boundary=----ObraBoundary',
                'CONTENT_LENGTH' => '1342177280',
            ],
        )
            ->assertStatus(413)
            ->assertSee(PlanVersion::maxUploadLabel())
            ->assertSee('endereço externo', false);

        $this->assertDatabaseCount('plan_versions', 0);
    }

    /**
     * O irmão silencioso: um pedido sem `Content-Length` — em
     * `Transfer-Encoding: chunked` — escapa ao `ValidatePostSize`, mas o PHP
     * descarta-lhe o corpo na mesma. Chega vazio, sem `$_FILES` e sem `_token`:
     * a resposta por omissão seria «419 — a página expirou», e se o token
     * sobrevivesse, «o campo ficheiro é obrigatório» a quem escolheu um
     * ficheiro. As duas mandam a pessoa procurar o problema no sítio errado.
     */
    public function test_a_request_truncated_without_a_length_says_what_really_happened(): void
    {
        $plan = $this->plan();

        $this->actingAs($this->manager)->call(
            'POST',
            route('admin.projects.plans.versions.store', [$this->project, $plan]),
            [], [], [],
            [
                'CONTENT_TYPE' => 'multipart/form-data; boundary=----ObraBoundary',
                'HTTP_TRANSFER_ENCODING' => 'chunked',
                'HTTP_REFERER' => route('admin.projects.show', [$this->project, 'tab' => 'plantas']),
            ],
        )->assertSessionHasErrors(['file' => PlanVersion::truncatedUploadMessage()]);

        $this->assertDatabaseCount('plan_versions', 0);
    }

    // ── PLAN-08: o endereço externo ───────────────────────────

    public function test_an_external_address_replaces_the_file(): void
    {
        $plan = $this->plan();

        $this->actingAs($this->manager)
            ->post(route('admin.projects.plans.versions.store', [$this->project, $plan]), [
                'external_url' => 'https://drive.google.com/file/d/abc123',
                'change_note' => 'Modelo Revit de 180 MB, fora do limite do servidor.',
            ])
            ->assertRedirect();

        $version = $plan->versions()->firstOrFail();

        $this->assertNull($version->path);
        $this->assertTrue($version->isExternal());
        $this->assertSame('https://drive.google.com/file/d/abc123', $version->external_url);
        $this->assertSame(1, $version->version);
    }

    public function test_an_external_version_is_not_served_by_the_download_route(): void
    {
        $plan = $this->plan();
        $version = PlanVersion::factory()->external()->create(['plan_id' => $plan->id]);

        $this->actingAs($this->manager)
            ->get(route('admin.projects.plans.versions.download', [$this->project, $plan, $version]))
            ->assertNotFound();
    }

    public function test_a_version_needs_either_a_file_or_an_address(): void
    {
        $plan = $this->plan();

        $this->actingAs($this->manager)
            ->post(route('admin.projects.plans.versions.store', [$this->project, $plan]), [
                'change_note' => 'Sem nada.',
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('plan_versions', 0);
    }

    public function test_a_version_cannot_carry_a_file_and_an_address_at_once(): void
    {
        $plan = $this->plan();

        $this->uploadVersion($plan, [
            'external_url' => 'https://drive.google.com/file/d/abc123',
        ])->assertSessionHasErrors('external_url');

        $this->assertDatabaseCount('plan_versions', 0);
    }

    // ── PLAN-05: estados e histórico do projecto ──────────────

    public function test_sending_a_version_to_the_client_is_recorded_in_the_project_history(): void
    {
        $plan = $this->plan();
        $this->uploadVersion($plan);
        $version = $plan->versions()->firstOrFail();

        $this->actingAs($this->manager)
            ->post(route('admin.projects.plans.versions.send', [$this->project, $plan, $version]))
            ->assertRedirect();

        $this->assertNotNull($version->fresh()->sent_to_client_at);
        $this->assertSame('enviada', $plan->fresh()->status);
        $this->assertDatabaseHas('project_updates', [
            'project_id' => $this->project->id,
            'title' => 'Planta enviada: Planta baixa — piso 0 (v1)',
            'type' => 'planta',
        ]);
    }

    public function test_a_new_version_marks_a_plan_the_client_already_saw_as_superseded(): void
    {
        $plan = $this->plan();
        $this->uploadVersion($plan);
        $this->actingAs($this->manager)->post(route('admin.projects.plans.versions.send', [
            $this->project, $plan, $plan->versions()->firstOrFail(),
        ]));

        $this->uploadVersion($plan);

        $this->assertSame('substituida', $plan->fresh()->status);
    }

    public function test_a_superseded_version_cannot_be_sent_to_the_client(): void
    {
        $plan = $this->plan();
        $this->uploadVersion($plan);
        $first = $plan->versions()->firstOrFail();
        $this->uploadVersion($plan);

        $this->actingAs($this->manager)
            ->post(route('admin.projects.plans.versions.send', [$this->project, $plan, $first]))
            ->assertSessionHasErrors('version');

        $this->assertNull($first->fresh()->sent_to_client_at);
    }

    // ── PLAN-09: apagar é reversível ──────────────────────────

    public function test_deleting_a_plan_is_reversible_and_keeps_the_version_history(): void
    {
        $plan = $this->plan();
        $this->uploadVersion($plan);
        $this->uploadVersion($plan);

        $this->actingAs($this->manager)
            ->delete(route('admin.projects.plans.destroy', [$this->project, $plan]))
            ->assertRedirect();

        $this->assertSoftDeleted('plans', ['id' => $plan->id]);
        $this->assertDatabaseCount('plan_versions', 2);

        Plan::withTrashed()->findOrFail($plan->id)->restore();

        $this->assertDatabaseHas('plans', ['id' => $plan->id, 'deleted_at' => null]);
        $this->assertSame(2, Plan::findOrFail($plan->id)->versions()->count());
    }

    // ── Acesso ────────────────────────────────────────────────

    public function test_a_manager_cannot_reach_the_plans_of_another_managers_project(): void
    {
        $other = Project::factory()->create(['type' => 'arquitetura']);
        $plan = $this->plan($other);
        $version = PlanVersion::factory()->create(['plan_id' => $plan->id]);

        $this->actingAs($this->manager)
            ->post(route('admin.projects.plans.versions.store', [$other, $plan]), $this->versionPayload())
            ->assertForbidden();

        $this->actingAs($this->manager)
            ->get(route('admin.projects.plans.versions.download', [$other, $plan, $version]))
            ->assertForbidden();

        $this->actingAs($this->manager)
            ->delete(route('admin.projects.plans.destroy', [$other, $plan]))
            ->assertForbidden();
    }

    public function test_a_plan_from_another_project_returns_404(): void
    {
        $other = Project::factory()->create(['manager_id' => $this->manager->id, 'type' => 'arquitetura']);
        $plan = $this->plan($other);

        $this->actingAs($this->manager)
            ->delete(route('admin.projects.plans.destroy', [$this->project, $plan]))
            ->assertNotFound();
    }

    public function test_a_version_from_another_plan_returns_404(): void
    {
        $plan = $this->plan();
        $otherPlan = $this->plan();
        $version = PlanVersion::factory()->create(['plan_id' => $otherPlan->id]);

        $this->actingAs($this->manager)
            ->get(route('admin.projects.plans.versions.download', [$this->project, $plan, $version]))
            ->assertNotFound();
    }

    public function test_a_client_cannot_reach_the_internal_plan_routes(): void
    {
        $clientUser = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => $this->client->id,
        ]);
        $plan = $this->plan();

        $this->actingAs($clientUser)
            ->post(route('admin.projects.plans.store', $this->project), ['name' => 'X', 'discipline' => '2d'])
            ->assertForbidden();

        $this->actingAs($clientUser)
            ->post(route('admin.projects.plans.versions.store', [$this->project, $plan]), $this->versionPayload())
            ->assertForbidden();
    }

    public function test_a_guest_is_redirected_from_the_plan_routes(): void
    {
        $plan = $this->plan();

        $this->post(route('admin.projects.plans.store', $this->project), ['name' => 'X', 'discipline' => '2d'])
            ->assertRedirect(route('login'));

        $this->delete(route('admin.projects.plans.destroy', [$this->project, $plan]))
            ->assertRedirect(route('login'));
    }

    // ── O papel financeiro ────────────────────────────────────

    public function test_a_financial_user_cannot_write_plans(): void
    {
        $financial = User::factory()->financial()->create();
        $plan = $this->plan();
        $version = PlanVersion::factory()->create(['plan_id' => $plan->id]);

        $this->actingAs($financial)
            ->post(route('admin.projects.plans.store', $this->project), ['name' => 'X', 'discipline' => '2d'])
            ->assertForbidden();

        $this->actingAs($financial)
            ->post(route('admin.projects.plans.versions.store', [$this->project, $plan]), $this->versionPayload())
            ->assertForbidden();

        $this->actingAs($financial)
            ->post(route('admin.projects.plans.versions.send', [$this->project, $plan, $version]))
            ->assertForbidden();

        $this->actingAs($financial)
            ->delete(route('admin.projects.plans.destroy', [$this->project, $plan]))
            ->assertForbidden();

        $this->assertDatabaseCount('plans', 1);
        $this->assertDatabaseCount('plan_versions', 1);
    }

    /** Ler é outra coisa: o financeiro lê a obra toda para contexto. */
    public function test_a_financial_user_can_download_a_plan_version(): void
    {
        $financial = User::factory()->financial()->create();
        $plan = $this->plan();
        $this->uploadVersion($plan);

        $this->actingAs($financial)
            ->get(route('admin.projects.plans.versions.download', [
                $this->project, $plan, $plan->versions()->firstOrFail(),
            ]))
            ->assertOk();
    }

    // ── O separador segue o tipo de projecto ──────────────────

    public function test_the_plans_tab_is_always_there_for_a_design_project(): void
    {
        $financial = User::factory()->financial()->create();

        $this->actingAs($financial)
            ->get(route('admin.projects.show', $this->project))
            ->assertOk()
            ->assertSee('Plantas');
    }

    public function test_a_reader_of_a_construction_project_without_plans_gets_no_empty_tab(): void
    {
        $financial = User::factory()->financial()->create();
        $site = Project::factory()->create(['type' => 'construcao']);

        $this->actingAs($financial)
            ->get(route('admin.projects.show', $site))
            ->assertOk()
            ->assertDontSee('Plantas');
    }

    public function test_a_construction_project_can_still_have_plans(): void
    {
        $site = Project::factory()->create([
            'type' => 'construcao',
            'manager_id' => $this->manager->id,
        ]);

        $this->actingAs($this->manager)
            ->get(route('admin.projects.show', $site))
            ->assertOk()
            ->assertSee('Plantas');

        $this->actingAs($this->manager)
            ->post(route('admin.projects.plans.store', $site), [
                'name' => 'Planta de implantação',
                'discipline' => '2d',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('plans', ['project_id' => $site->id, 'name' => 'Planta de implantação']);
    }

    // ── Auditoria ─────────────────────────────────────────────

    public function test_plans_and_versions_are_written_to_the_audit_trail(): void
    {
        $plan = $this->plan();
        $this->uploadVersion($plan);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Plan::class,
            'auditable_id' => $plan->id,
            'action' => 'created',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => PlanVersion::class,
            'auditable_id' => $plan->versions()->firstOrFail()->id,
            'action' => 'created',
        ]);
    }
}
