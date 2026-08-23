<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Document;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Photo;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Fixa a superfície do papel `financial`.
 *
 * Decisão de produto: o financeiro vê todas as obras e trabalha a parte
 * financeira de todas elas (despesas e cobranças), mas não toca na estrutura da
 * obra — nem obras, nem fases, nem fotos, nem documentos, nem utilizadores, nem
 * definições. O âmbito é definido pelo middleware `role:` em routes/web.php e
 * não por obra, porque um financeiro nunca é gestor de obra nenhuma.
 *
 * A última secção é de não-regressão: a remoção do bloco `isFinancial()` de
 * AuthorizesProjectAccess não pode ter alargado o âmbito do gestor.
 */
class FinancialRoleTest extends TestCase
{
    use RefreshDatabase;

    private function financial(): User
    {
        return User::factory()->financial()->create();
    }

    /**
     * Uma obra gerida por outro utilizador — o caso que antes dava 403 ao
     * financeiro em todo o lado.
     */
    private function projectOfAnotherManager(): Project
    {
        return Project::factory()->create([
            'manager_id' => User::factory()->manager()->create()->id,
        ]);
    }

    /** @return array<string, mixed> */
    private function expensePayload(array $overrides = []): array
    {
        return array_merge([
            'category' => array_key_first(Expense::CATEGORIES),
            'description' => 'Cimento e areia',
            'amount' => 1500,
            'spent_at' => '2026-08-01',
        ], $overrides);
    }

    /** @return array<string, mixed> */
    private function paymentPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Primeira tranche',
            'amount' => 2500,
            'type' => 'pix',
            'status' => 'pending',
        ], $overrides);
    }

    /** @return array<string, mixed> */
    private function projectPayload(Client $client): array
    {
        return [
            'client_id' => $client->id,
            'name' => 'Obra criada pelo financeiro',
            'type' => 'construcao',
            'status' => 'planejada',
            'budget' => 50000,
        ];
    }

    // ── O financeiro CONSEGUE: leitura de todas as obras ─────────────────────

    public function test_financial_user_lists_every_project_including_ones_they_do_not_manage(): void
    {
        Project::factory()->create([
            'name' => 'ObraDoGestorAlfa',
            'manager_id' => User::factory()->manager()->create()->id,
        ]);
        Project::factory()->create([
            'name' => 'ObraDoGestorBeta',
            'manager_id' => User::factory()->manager()->create()->id,
        ]);

        $this->actingAs($this->financial())
            ->get(route('admin.projects.index'))
            ->assertOk()
            ->assertSee('ObraDoGestorAlfa')
            ->assertSee('ObraDoGestorBeta');
    }

    public function test_financial_user_can_open_a_project_they_do_not_manage(): void
    {
        $project = $this->projectOfAnotherManager();

        $this->actingAs($this->financial())
            ->get(route('admin.projects.show', $project))
            ->assertOk()
            ->assertSee($project->name);
    }

    public function test_financial_user_can_open_the_admin_dashboard(): void
    {
        $this->actingAs($this->financial())
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    // ── O financeiro CONSEGUE: relatórios ────────────────────────────────────

    public function test_financial_user_lists_every_project_in_the_reports_picker(): void
    {
        Project::factory()->create([
            'name' => 'ObraParaRelatorio',
            'manager_id' => User::factory()->manager()->create()->id,
        ]);

        $this->actingAs($this->financial())
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('ObraParaRelatorio');
    }

    public function test_financial_user_can_open_a_report_for_a_project_they_do_not_manage(): void
    {
        $project = $this->projectOfAnotherManager();

        $this->actingAs($this->financial())
            ->get(route('admin.reports.show', [$project, 'financeiro']))
            ->assertOk();
    }

    public function test_financial_user_can_download_a_report_pdf(): void
    {
        $project = $this->projectOfAnotherManager();

        $response = $this->actingAs($this->financial())
            ->get(route('admin.reports.pdf', [$project, 'financeiro']));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    // ── O financeiro CONSEGUE: despesas em qualquer obra ─────────────────────

    public function test_financial_user_can_create_an_expense_on_a_project_they_do_not_manage(): void
    {
        $project = $this->projectOfAnotherManager();

        $this->actingAs($this->financial())
            ->post(route('admin.projects.expenses.store', $project), $this->expensePayload())
            ->assertRedirect();

        $this->assertDatabaseHas('expenses', [
            'project_id' => $project->id,
            'description' => 'Cimento e areia',
        ]);
    }

    public function test_financial_user_can_update_an_expense_on_a_project_they_do_not_manage(): void
    {
        $project = $this->projectOfAnotherManager();
        $expense = Expense::factory()->create(['project_id' => $project->id]);

        $this->actingAs($this->financial())
            ->put(route('admin.projects.expenses.update', [$project, $expense]), $this->expensePayload([
                'description' => 'Descrição corrigida',
            ]))
            ->assertRedirect();

        $this->assertSame('Descrição corrigida', $expense->fresh()->description);
    }

    public function test_financial_user_can_delete_an_expense_on_a_project_they_do_not_manage(): void
    {
        $project = $this->projectOfAnotherManager();
        $expense = Expense::factory()->create(['project_id' => $project->id]);

        $this->actingAs($this->financial())
            ->delete(route('admin.projects.expenses.destroy', [$project, $expense]))
            ->assertRedirect();

        $this->assertSoftDeleted('expenses', ['id' => $expense->id]);
    }

    // ── O financeiro CONSEGUE: cobranças em qualquer obra ────────────────────

    public function test_financial_user_can_create_a_payment_on_a_project_they_do_not_manage(): void
    {
        $project = $this->projectOfAnotherManager();

        $this->actingAs($this->financial())
            ->post(route('admin.projects.payments.store', $project), $this->paymentPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'project_id' => $project->id,
            'title' => 'Primeira tranche',
        ]);
    }

    public function test_financial_user_can_update_a_payment_on_a_project_they_do_not_manage(): void
    {
        $project = $this->projectOfAnotherManager();
        $payment = Payment::factory()->create([
            'project_id' => $project->id,
            'client_id' => $project->client_id,
        ]);

        $this->actingAs($this->financial())
            ->put(route('admin.projects.payments.update', [$project, $payment]), $this->paymentPayload([
                'title' => 'Tranche revista',
            ]))
            ->assertRedirect();

        $this->assertSame('Tranche revista', $payment->fresh()->title);
    }

    public function test_financial_user_can_mark_a_payment_as_paid_on_a_project_they_do_not_manage(): void
    {
        $project = $this->projectOfAnotherManager();
        $payment = Payment::factory()->create([
            'project_id' => $project->id,
            'client_id' => $project->client_id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->financial())
            ->post(route('admin.projects.payments.mark-paid', [$project, $payment]))
            ->assertRedirect();

        $payment->refresh();
        $this->assertSame('paid', $payment->status);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_financial_user_can_delete_a_payment_on_a_project_they_do_not_manage(): void
    {
        $project = $this->projectOfAnotherManager();
        $payment = Payment::factory()->create([
            'project_id' => $project->id,
            'client_id' => $project->client_id,
        ]);

        $this->actingAs($this->financial())
            ->delete(route('admin.projects.payments.destroy', [$project, $payment]))
            ->assertRedirect();

        $this->assertSoftDeleted('payments', ['id' => $payment->id]);
    }

    // ── O financeiro NÃO CONSEGUE: estrutura da obra ─────────────────────────

    public function test_financial_user_cannot_create_a_project(): void
    {
        $client = Client::factory()->create();

        $this->actingAs($this->financial())
            ->post(route('admin.projects.store'), $this->projectPayload($client))
            ->assertForbidden();

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_financial_user_cannot_open_the_project_edit_form(): void
    {
        $project = $this->projectOfAnotherManager();

        $this->actingAs($this->financial())
            ->get(route('admin.projects.edit', $project))
            ->assertForbidden();
    }

    public function test_financial_user_cannot_update_a_project(): void
    {
        $project = $this->projectOfAnotherManager();
        $original = $project->name;

        $this->actingAs($this->financial())
            ->put(route('admin.projects.update', $project), $this->projectPayload($project->client))
            ->assertForbidden();

        $this->assertSame($original, $project->fresh()->name);
    }

    public function test_financial_user_cannot_delete_a_project(): void
    {
        $project = $this->projectOfAnotherManager();

        $this->actingAs($this->financial())
            ->delete(route('admin.projects.destroy', $project))
            ->assertForbidden();

        $this->assertNotSoftDeleted('projects', ['id' => $project->id]);
    }

    // ── O financeiro NÃO CONSEGUE: fases ─────────────────────────────────────

    public function test_financial_user_cannot_create_a_phase(): void
    {
        $project = $this->projectOfAnotherManager();

        $this->actingAs($this->financial())
            ->post(route('admin.projects.phases.store', $project), [
                'name' => 'Fundações',
                'status' => 'pendente',
                'percentage' => 0,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('project_phases', 0);
    }

    public function test_financial_user_cannot_update_a_phase(): void
    {
        $project = $this->projectOfAnotherManager();
        $phase = ProjectPhase::factory()->create([
            'project_id' => $project->id,
            'name' => 'Fase original',
        ]);

        $this->actingAs($this->financial())
            ->put(route('admin.projects.phases.update', [$project, $phase]), [
                'name' => 'Fase alterada',
                'status' => 'concluida',
                'percentage' => 100,
            ])
            ->assertForbidden();

        $this->assertSame('Fase original', $phase->fresh()->name);
    }

    public function test_financial_user_cannot_delete_a_phase(): void
    {
        $project = $this->projectOfAnotherManager();
        $phase = ProjectPhase::factory()->create(['project_id' => $project->id]);

        $this->actingAs($this->financial())
            ->delete(route('admin.projects.phases.destroy', [$project, $phase]))
            ->assertForbidden();

        $this->assertDatabaseHas('project_phases', ['id' => $phase->id]);
    }

    // ── O financeiro NÃO CONSEGUE: fotos e documentos ────────────────────────

    public function test_financial_user_cannot_upload_photos(): void
    {
        Storage::fake('local');
        $project = $this->projectOfAnotherManager();

        $this->actingAs($this->financial())
            ->post(route('admin.projects.photos.store', $project), [
                'photos' => [UploadedFile::fake()->image('obra.jpg')],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('photos', 0);
    }

    public function test_financial_user_cannot_delete_a_photo(): void
    {
        $project = $this->projectOfAnotherManager();
        $photo = Photo::factory()->create(['project_id' => $project->id]);

        $this->actingAs($this->financial())
            ->delete(route('admin.projects.photos.destroy', [$project, $photo]))
            ->assertForbidden();

        $this->assertDatabaseHas('photos', ['id' => $photo->id]);
    }

    public function test_financial_user_cannot_upload_documents(): void
    {
        Storage::fake('local');
        $project = $this->projectOfAnotherManager();

        $this->actingAs($this->financial())
            ->post(route('admin.projects.documents.store', $project), [
                'name' => 'Contrato',
                'type' => 'contrato',
                'visibility' => 'interno',
                'document' => UploadedFile::fake()->create('contrato.pdf', 100, 'application/pdf'),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_financial_user_cannot_delete_a_document(): void
    {
        $project = $this->projectOfAnotherManager();
        $document = Document::factory()->create(['project_id' => $project->id]);

        $this->actingAs($this->financial())
            ->delete(route('admin.projects.documents.destroy', [$project, $document]))
            ->assertForbidden();

        $this->assertDatabaseHas('documents', ['id' => $document->id]);
    }

    public function test_financial_user_cannot_post_a_project_update(): void
    {
        $project = $this->projectOfAnotherManager();

        $this->actingAs($this->financial())
            ->post(route('admin.projects.updates.store', $project), [
                'title' => 'Atualização do financeiro',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('project_updates', [
            'title' => 'Atualização do financeiro',
        ]);
    }

    // ── O financeiro NÃO CONSEGUE: administração ─────────────────────────────

    public function test_financial_user_cannot_list_users(): void
    {
        $this->actingAs($this->financial())
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_financial_user_cannot_create_users(): void
    {
        $this->actingAs($this->financial())
            ->post(route('users.store'), [
                'name' => 'Novo Admin',
                'email' => 'novo@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => User::ROLE_ADMIN,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'novo@example.com']);
    }

    public function test_financial_user_cannot_open_the_settings_page(): void
    {
        $this->actingAs($this->financial())
            ->get(route('admin.settings.index'))
            ->assertForbidden();
    }

    public function test_financial_user_cannot_update_the_settings(): void
    {
        $this->actingAs($this->financial())
            ->put(route('admin.settings.update'), ['show_client_financials' => '0'])
            ->assertForbidden();
    }

    // ── Não-regressão: o gestor continua limitado às suas obras ──────────────

    public function test_manager_still_cannot_open_another_managers_project(): void
    {
        $manager = User::factory()->manager()->create();
        $project = $this->projectOfAnotherManager();

        $this->actingAs($manager)
            ->get(route('admin.projects.show', $project))
            ->assertForbidden();
    }

    public function test_manager_still_cannot_write_to_another_managers_project(): void
    {
        $manager = User::factory()->manager()->create();
        $project = $this->projectOfAnotherManager();

        $this->actingAs($manager)
            ->post(route('admin.projects.phases.store', $project), [
                'name' => 'Fase intrusa',
                'status' => 'pendente',
                'percentage' => 0,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('project_phases', 0);
    }

    public function test_manager_still_only_sees_their_own_projects_in_the_list(): void
    {
        $manager = User::factory()->manager()->create();
        Project::factory()->create(['name' => 'ObraPropriaDoGestor', 'manager_id' => $manager->id]);
        Project::factory()->create([
            'name' => 'ObraAlheiaAoGestor',
            'manager_id' => User::factory()->manager()->create()->id,
        ]);

        $this->actingAs($manager)
            ->get(route('admin.projects.index'))
            ->assertOk()
            ->assertSee('ObraPropriaDoGestor')
            ->assertDontSee('ObraAlheiaAoGestor');
    }

    public function test_manager_still_only_sees_their_own_projects_in_the_reports_picker(): void
    {
        $manager = User::factory()->manager()->create();
        Project::factory()->create(['name' => 'RelatorioProprio', 'manager_id' => $manager->id]);
        Project::factory()->create([
            'name' => 'RelatorioAlheio',
            'manager_id' => User::factory()->manager()->create()->id,
        ]);

        $this->actingAs($manager)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('RelatorioProprio')
            ->assertDontSee('RelatorioAlheio');
    }

    public function test_manager_still_can_open_their_own_project(): void
    {
        $manager = User::factory()->manager()->create();
        $project = Project::factory()->create(['manager_id' => $manager->id]);

        $this->actingAs($manager)
            ->get(route('admin.projects.show', $project))
            ->assertOk();
    }
}
