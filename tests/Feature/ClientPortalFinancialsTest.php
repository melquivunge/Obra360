<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * FIN-05 — o resumo financeiro do portal depende de duas chaves: a definição
 * global `show_client_financials` e o campo `show_financials` da obra.
 */
class ClientPortalFinancialsTest extends TestCase
{
    use RefreshDatabase;

    private User $clientUser;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $client = Client::factory()->create();
        $this->clientUser = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => $client->id,
        ]);
        $this->project = Project::factory()->create([
            'client_id' => $client->id,
            'show_financials' => true,
            'budget' => 90000,
        ]);

        Expense::factory()->create([
            'project_id' => $this->project->id,
            'category' => 'material',
            'description' => 'DespesaSecretaAlfa',
            'amount' => 13579,
            'spent_at' => '2026-05-01',
        ]);
        Payment::factory()->create([
            'project_id' => $this->project->id,
            'client_id' => $client->id,
            'title' => 'CobrancaSecretaBeta',
            'amount' => 2468,
        ]);
    }

    private function portalPage(): TestResponse
    {
        return $this->actingAs($this->clientUser)->get(route('portal.projects.show', $this->project));
    }

    public function test_the_financial_summary_is_visible_by_default(): void
    {
        $this->portalPage()
            ->assertOk()
            ->assertViewHas('canViewFinancials', true)
            ->assertSee('Total orçado')
            ->assertSee('Saldo restante')
            ->assertSee(money(13579));
    }

    public function test_disabling_the_global_setting_hides_the_financial_summary(): void
    {
        Setting::set('show_client_financials', '0');

        $this->portalPage()
            ->assertOk()
            ->assertViewHas('canViewFinancials', false)
            ->assertDontSee('Total orçado')
            ->assertDontSee('Saldo restante')
            ->assertDontSee(money(13579));
    }

    public function test_disabling_the_global_setting_keeps_the_expenses_out_of_the_view(): void
    {
        Setting::set('show_client_financials', '0');

        $response = $this->portalPage()->assertOk();

        $this->assertTrue($response->viewData('expenses')->isEmpty());
        $this->assertTrue($response->viewData('payments')->isEmpty());
        $this->assertSame(0, $response->viewData('totalSpent'));
    }

    public function test_the_payments_of_the_project_are_hidden_with_the_summary(): void
    {
        Setting::set('show_client_financials', '0');

        $this->portalPage()
            ->assertOk()
            ->assertDontSee('CobrancaSecretaBeta')
            ->assertDontSee(money(2468));
    }

    public function test_a_project_flagged_as_not_showing_financials_hides_the_summary(): void
    {
        $this->project->update(['show_financials' => false]);

        $this->portalPage()
            ->assertOk()
            ->assertViewHas('canViewFinancials', false)
            ->assertDontSee('Total orçado')
            ->assertDontSee(money(13579));
    }

    public function test_setting_the_flag_back_to_one_shows_the_summary_again(): void
    {
        Setting::set('show_client_financials', '0');
        $this->portalPage()->assertViewHas('canViewFinancials', false);

        Setting::set('show_client_financials', '1');
        $this->portalPage()->assertViewHas('canViewFinancials', true);
    }

    public function test_the_setting_does_not_affect_the_admin_panel(): void
    {
        Setting::set('show_client_financials', '0');

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.show', $this->project))
            ->assertOk()
            ->assertSee('DespesaSecretaAlfa');
    }
}
