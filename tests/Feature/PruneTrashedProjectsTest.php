<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Expense;
use App\Models\Photo;
use App\Models\Plan;
use App\Models\PlanVersion;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A pergunta que estes testes existem para responder não é «apaga?», é «apaga
 * a coisa certa?». Um comando de limpeza demasiado zeloso destrói as fotos de
 * uma obra que ainda podia ser restaurada — que é precisamente o que a
 * exclusão reversível existe para evitar — e um comando demasiado tímido nunca
 * liberta o disco de um alojamento partilhado.
 */
class PruneTrashedProjectsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_it_keeps_the_files_of_a_project_that_can_still_be_restored(): void
    {
        $project = $this->trashedProject(10);
        $arquivos = $this->fillWithMedia($project);

        $this->artisan('obra360:trash:prune --commit')->assertSuccessful();

        foreach ($arquivos as $arquivo) {
            Storage::disk('local')->assertExists($arquivo);
        }

        $this->assertNotNull(Project::onlyTrashed()->find($project->id));
    }

    public function test_it_keeps_the_files_of_a_project_that_was_never_deleted(): void
    {
        $project = Project::factory()->create();
        $arquivos = $this->fillWithMedia($project);

        $this->artisan('obra360:trash:prune --days=1 --commit')->assertSuccessful();

        foreach ($arquivos as $arquivo) {
            Storage::disk('local')->assertExists($arquivo);
        }

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'deleted_at' => null]);
    }

    public function test_it_deletes_the_files_of_a_project_past_the_retention_window(): void
    {
        $project = $this->trashedProject(120);
        $arquivos = $this->fillWithMedia($project);

        $this->artisan('obra360:trash:prune --commit')->assertSuccessful();

        foreach ($arquivos as $arquivo) {
            Storage::disk('local')->assertMissing($arquivo);
        }

        // A linha e tudo o que dela dependia saem juntos — não fica registo
        // órfão a apontar para arquivos que já não existem.
        $this->assertNull(Project::withTrashed()->find($project->id));
        $this->assertDatabaseCount('photos', 0);
        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('expenses', 0);
        $this->assertDatabaseCount('plans', 0);
        $this->assertDatabaseCount('plan_versions', 0);
    }

    public function test_a_dry_run_deletes_nothing(): void
    {
        $project = $this->trashedProject(120);
        $arquivos = $this->fillWithMedia($project);

        $this->artisan('obra360:trash:prune')->assertSuccessful();

        foreach ($arquivos as $arquivo) {
            Storage::disk('local')->assertExists($arquivo);
        }

        $this->assertNotNull(Project::onlyTrashed()->find($project->id));
    }

    public function test_it_deletes_the_files_of_a_photo_that_was_removed_one_by_one(): void
    {
        // O `destroy` da foto apaga o arquivo logo, mas a linha fica na
        // reciclagem. Quando a obra expira, a linha tem de sair também — e o
        // arquivo que já não existe não pode fazer o comando ir abaixo.
        $project = $this->trashedProject(120);
        $foto = Photo::factory()->create([
            'project_id' => $project->id,
            'path' => "projects/{$project->id}/photos/removida.jpg",
        ]);
        $foto->delete();

        $this->artisan('obra360:trash:prune --commit')->assertSuccessful();

        $this->assertDatabaseCount('photos', 0);
    }

    public function test_an_external_plan_version_has_no_file_of_ours_to_delete(): void
    {
        $project = $this->trashedProject(120);
        $plan = Plan::factory()->create(['project_id' => $project->id]);

        $arquivo = "projects/{$project->id}/plans/{$plan->id}/v1.pdf";
        Storage::disk('local')->put($arquivo, 'planta');
        PlanVersion::factory()->for($plan)->create(['version' => 1, 'path' => $arquivo]);

        // PLAN-08: mora no Google Drive. Não há arquivo nosso para apagar, e o
        // endereço do outro lado não é nosso para mexer.
        PlanVersion::factory()->for($plan)->external()->create(['version' => 2]);

        $this->artisan('obra360:trash:prune --commit')->assertSuccessful();

        Storage::disk('local')->assertMissing($arquivo);
        $this->assertDatabaseCount('plan_versions', 0);
    }

    public function test_it_deletes_the_files_of_an_expired_plan_of_a_live_project(): void
    {
        // PLAN-09: apagar uma planta é reversível e mantém as versões — e os
        // arquivos delas — intactos. Passado o prazo, saem.
        $project = Project::factory()->create();
        $plan = Plan::factory()->create(['project_id' => $project->id]);

        $arquivo = "projects/{$project->id}/plans/{$plan->id}/v1.pdf";
        Storage::disk('local')->put($arquivo, 'planta');
        PlanVersion::factory()->for($plan)->create(['version' => 1, 'path' => $arquivo]);

        $plan->delete();
        $plan->forceFill(['deleted_at' => now()->subDays(120)])->saveQuietly();

        $this->artisan('obra360:trash:prune --commit')->assertSuccessful();

        Storage::disk('local')->assertMissing($arquivo);
        $this->assertNull(Plan::withTrashed()->find($plan->id));
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'deleted_at' => null]);
    }

    public function test_it_keeps_the_files_of_a_plan_that_can_still_be_restored(): void
    {
        $project = Project::factory()->create();
        $plan = Plan::factory()->create(['project_id' => $project->id]);

        $arquivo = "projects/{$project->id}/plans/{$plan->id}/v1.pdf";
        Storage::disk('local')->put($arquivo, 'planta');
        PlanVersion::factory()->for($plan)->create(['version' => 1, 'path' => $arquivo]);

        $plan->delete();

        $this->artisan('obra360:trash:prune --commit')->assertSuccessful();

        Storage::disk('local')->assertExists($arquivo);
        $this->assertNotNull(Plan::onlyTrashed()->find($plan->id));
    }

    public function test_it_postpones_a_project_that_is_still_the_source_of_a_live_project(): void
    {
        // OBRA-01: a obra derivada mostra as plantas aprovadas da origem sem
        // cópia nenhuma. Apagar a origem a sério levaria essas plantas — e o
        // `nullOnDelete` deixaria a obra sem ligação, sem volta atrás.
        $origem = $this->trashedProject(200, ['type' => 'arquitetura']);
        $arquivos = $this->fillWithMedia($origem);

        Project::factory()->create(['source_project_id' => $origem->id]);

        $this->artisan('obra360:trash:prune --commit')->assertSuccessful();

        foreach ($arquivos as $arquivo) {
            Storage::disk('local')->assertExists($arquivo);
        }

        $this->assertNotNull(Project::onlyTrashed()->find($origem->id));
    }

    public function test_it_postpones_a_source_whose_derived_project_can_still_be_restored(): void
    {
        $origem = $this->trashedProject(200, ['type' => 'arquitetura']);
        $derivada = $this->trashedProject(10, ['source_project_id' => $origem->id]);

        $this->artisan('obra360:trash:prune --commit')->assertSuccessful();

        $this->assertNotNull(Project::onlyTrashed()->find($origem->id));
        $this->assertNotNull(Project::onlyTrashed()->find($derivada->id));
    }

    public function test_it_removes_the_source_once_the_derived_project_expired_too(): void
    {
        $origem = $this->trashedProject(200, ['type' => 'arquitetura']);
        $arquivos = $this->fillWithMedia($origem);
        $this->trashedProject(120, ['source_project_id' => $origem->id]);

        $this->artisan('obra360:trash:prune --commit')->assertSuccessful();

        foreach ($arquivos as $arquivo) {
            Storage::disk('local')->assertMissing($arquivo);
        }

        $this->assertNull(Project::withTrashed()->find($origem->id));
    }

    public function test_the_retention_window_is_configurable(): void
    {
        $project = $this->trashedProject(45);
        $arquivos = $this->fillWithMedia($project);

        // Com o prazo por omissão (90 dias) nada acontece — é a opção que
        // decide, não o acaso das datas escolhidas.
        $this->artisan('obra360:trash:prune --commit')->assertSuccessful();
        $this->assertNotNull(Project::onlyTrashed()->find($project->id));

        $this->artisan('obra360:trash:prune --days=30 --commit')->assertSuccessful();

        $this->assertNull(Project::withTrashed()->find($project->id));
        foreach ($arquivos as $arquivo) {
            Storage::disk('local')->assertMissing($arquivo);
        }
    }

    public function test_a_non_positive_retention_window_is_refused(): void
    {
        $project = $this->trashedProject(1);
        $arquivos = $this->fillWithMedia($project);

        $this->artisan('obra360:trash:prune --days=0 --commit')->assertFailed();
        $this->artisan('obra360:trash:prune --days=-1 --commit')->assertFailed();
        $this->artisan('obra360:trash:prune --days=abc --commit')->assertFailed();

        $this->assertNotNull(Project::onlyTrashed()->find($project->id));
        foreach ($arquivos as $arquivo) {
            Storage::disk('local')->assertExists($arquivo);
        }
    }

    /**
     * Uma obra na reciclagem desde há `$daysAgo` dias.
     */
    private function trashedProject(int $daysAgo, array $attributes = []): Project
    {
        $project = Project::factory()->create($attributes);
        $project->delete();
        $project->forceFill(['deleted_at' => now()->subDays($daysAgo)])->saveQuietly();

        return $project;
    }

    /**
     * Enche a obra com um exemplar de cada arquivo que ela pode ter e devolve
     * os caminhos, todos já criados no disco falso.
     *
     * @return array<int, string>
     */
    private function fillWithMedia(Project $project): array
    {
        $capa = "covers/{$project->id}.jpg";
        $foto = "projects/{$project->id}/photos/foto.jpg";
        $miniatura = "projects/{$project->id}/photos/thumb_foto.jpg";
        $documento = "projects/{$project->id}/documents/contrato.pdf";
        $recibo = "projects/{$project->id}/receipts/recibo.pdf";

        $project->forceFill(['cover_image_path' => $capa])->saveQuietly();

        Photo::factory()->create([
            'project_id' => $project->id,
            'path' => $foto,
            'thumbnail_path' => $miniatura,
        ]);
        Document::factory()->create(['project_id' => $project->id, 'path' => $documento]);
        Expense::factory()->create(['project_id' => $project->id, 'receipt_path' => $recibo]);

        $plan = Plan::factory()->create(['project_id' => $project->id]);
        $planta = "projects/{$project->id}/plans/{$plan->id}/v1.pdf";
        PlanVersion::factory()->for($plan)->create(['version' => 1, 'path' => $planta]);

        $arquivos = [$capa, $foto, $miniatura, $documento, $recibo, $planta];

        foreach ($arquivos as $arquivo) {
            Storage::disk('local')->put($arquivo, 'conteudo');
        }

        return $arquivos;
    }
}
