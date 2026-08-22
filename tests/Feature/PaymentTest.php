<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Primeira parcela',
            'amount' => 1500,
            'type' => 'pix',
            'status' => 'pending',
            'due_date' => '2026-09-30',
        ], $overrides);
    }

    private function payment(Project $project, array $overrides = []): Payment
    {
        return Payment::factory()->create(array_merge([
            'project_id' => $project->id,
            'client_id' => $project->client_id,
            'title' => 'Parcela existente',
            'amount' => 1000,
            'status' => 'pending',
        ], $overrides));
    }

    public function test_admin_can_register_a_payment(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.payments.store', $project), $this->payload())
            ->assertRedirect();

        $payment = Payment::firstOrFail();
        $this->assertSame('Primeira parcela', $payment->title);
        $this->assertSame('1500.00', $payment->amount);
        $this->assertSame('pending', $payment->status);
        $this->assertNull($payment->paid_at);
    }

    public function test_a_new_payment_inherits_the_client_of_the_project(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.payments.store', $project), $this->payload());

        $this->assertSame($client->id, Payment::firstOrFail()->client_id);
    }

    public function test_a_payment_created_as_paid_gets_a_paid_at_timestamp(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.payments.store', $project), $this->payload(['status' => 'paid']));

        $this->assertNotNull(Payment::firstOrFail()->paid_at);
    }

    public function test_invalid_payment_payload_is_rejected(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.payments.store', $project), [
                'title' => '',
                'amount' => 0,
                'type' => 'cheque',
                'status' => 'inexistente',
            ])
            ->assertSessionHasErrors(['title', 'amount', 'type', 'status']);

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_admin_can_correct_a_payment(): void
    {
        $project = Project::factory()->create();
        $payment = $this->payment($project);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.projects.payments.update', [$project, $payment]), $this->payload([
                'title' => 'Parcela corrigida',
                'amount' => 2500.5,
                'type' => 'boleto',
            ]))
            ->assertRedirect();

        $payment->refresh();
        $this->assertSame('Parcela corrigida', $payment->title);
        $this->assertSame('2500.50', $payment->amount);
        $this->assertSame('boleto', $payment->type);
    }

    public function test_updating_a_payment_to_paid_stamps_paid_at(): void
    {
        $project = Project::factory()->create();
        $payment = $this->payment($project);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.projects.payments.update', [$project, $payment]), $this->payload([
                'status' => 'paid',
            ]));

        $this->assertNotNull($payment->fresh()->paid_at);
    }

    public function test_moving_a_payment_back_to_pending_clears_paid_at(): void
    {
        $project = Project::factory()->create();
        $payment = $this->payment($project, ['status' => 'paid', 'paid_at' => now()]);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.projects.payments.update', [$project, $payment]), $this->payload([
                'status' => 'pending',
            ]));

        $payment->refresh();
        $this->assertSame('pending', $payment->status);
        $this->assertNull($payment->paid_at);
    }

    public function test_mark_paid_sets_the_status_and_the_paid_at(): void
    {
        $project = Project::factory()->create();
        $payment = $this->payment($project);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.payments.mark-paid', [$project, $payment]))
            ->assertRedirect();

        $payment->refresh();
        $this->assertSame('paid', $payment->status);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_admin_can_soft_delete_a_payment(): void
    {
        $project = Project::factory()->create();
        $payment = $this->payment($project);

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.projects.payments.destroy', [$project, $payment]))
            ->assertRedirect();

        $this->assertSoftDeleted('payments', ['id' => $payment->id]);
    }

    // ── Efeito nos totais ────────────────────────────────────

    public function test_marking_a_payment_paid_moves_it_from_pending_to_received(): void
    {
        $project = Project::factory()->create();
        $paid = $this->payment($project, ['amount' => 400, 'status' => 'paid', 'paid_at' => now()]);
        $pending = $this->payment($project, ['amount' => 600]);

        $this->assertSame(400.0, (float) $project->payments()->where('status', 'paid')->sum('amount'));

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.projects.payments.mark-paid', [$project, $pending]));

        $this->assertSame(1000.0, (float) $project->payments()->where('status', 'paid')->sum('amount'));
        $this->assertSame(0.0, (float) $project->payments()->where('status', 'pending')->sum('amount'));
        $this->assertSame('paid', $paid->fresh()->status);
    }

    public function test_a_soft_deleted_payment_leaves_the_totals(): void
    {
        $project = Project::factory()->create();
        $this->payment($project, ['amount' => 700]);
        $removed = $this->payment($project, ['amount' => 300]);

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.projects.payments.destroy', [$project, $removed]));

        $this->assertSame(700.0, (float) $project->payments()->sum('amount'));
    }

    public function test_the_project_page_shows_the_pending_and_received_totals(): void
    {
        $project = Project::factory()->create();
        $this->payment($project, ['amount' => 1200, 'status' => 'paid', 'paid_at' => now()]);
        $this->payment($project, ['amount' => 800]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.show', $project))
            ->assertOk()
            ->assertSee(money(1200))
            ->assertSee(money(800));
    }

    // ── Autorização ──────────────────────────────────────────

    public function test_manager_cannot_reach_the_payment_routes(): void
    {
        $manager = User::factory()->manager()->create();
        $project = Project::factory()->create(['manager_id' => $manager->id]);

        $this->actingAs($manager)
            ->post(route('admin.projects.payments.store', $project), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_client_cannot_reach_the_payment_routes(): void
    {
        $client = Client::factory()->create();
        $clientUser = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => $client->id,
        ]);
        $project = Project::factory()->create(['client_id' => $client->id]);
        $payment = $this->payment($project);

        $this->actingAs($clientUser)
            ->post(route('admin.projects.payments.mark-paid', [$project, $payment]))
            ->assertForbidden();

        $this->assertSame('pending', $payment->fresh()->status);
    }

    /**
     * O financeiro trabalha a parte financeira de todas as obras, não só das
     * suas — nunca é gestor de nenhuma. Cobertura completa do papel em
     * tests/Feature/FinancialRoleTest.php.
     */
    public function test_financial_user_manages_payments_on_any_project(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->financial()->create())
            ->post(route('admin.projects.payments.store', $project), $this->payload())
            ->assertRedirect();

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_a_payment_from_another_project_cannot_be_updated(): void
    {
        $project = Project::factory()->create();
        $other = Project::factory()->create();
        $payment = $this->payment($other);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.projects.payments.update', [$project, $payment]), $this->payload([
                'title' => 'Invadida',
            ]))
            ->assertNotFound();

        $this->assertSame('Parcela existente', $payment->fresh()->title);
    }
}
