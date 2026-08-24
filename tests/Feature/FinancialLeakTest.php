<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\ProjectUpdateNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * O valor de uma despesa vazava para o cliente mesmo com o financeiro
 * desligado.
 *
 * A causa não era a aba financeira — essa escondia-se corretamente. Era o
 * histórico: `ProjectUpdate::log()` embute o valor no título da entrada
 * («Despesa: Comissão — R$ 8.500,00»), e tanto a página da obra como o menu
 * lateral do portal liam o histórico sem filtro nenhum.
 *
 * A correção filtra na consulta e não na tela: um `@if` esquecido num bloco
 * novo voltaria a expor o valor, uma cláusula na consulta não.
 */
class FinancialLeakTest extends TestCase
{
    use RefreshDatabase;

    private User $clientUser;

    private User $financial;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $client = Client::factory()->create();
        $this->clientUser = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => $client->id,
        ]);
        $this->financial = User::factory()->financial()->create();
        $this->project = Project::factory()->create([
            'client_id' => $client->id,
            'budget' => 100000,
            'show_financials' => true,
        ]);
    }

    private function lancarDespesa(): void
    {
        $this->actingAs($this->financial)->post(route('admin.projects.expenses.store', $this->project), [
            'category' => 'material',
            'description' => 'Comissão do arquiteto',
            'amount' => 8500,
            'spent_at' => today()->toDateString(),
        ]);
    }

    public function test_the_amount_does_not_reach_the_client_when_the_global_setting_is_off(): void
    {
        $this->lancarDespesa();
        Setting::set('show_client_financials', '0');

        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.show', $this->project))
            ->assertOk()
            ->assertDontSee('8.500,00')
            ->assertDontSee('Comissão do arquiteto');
    }

    public function test_the_amount_does_not_reach_the_client_when_the_project_hides_financials(): void
    {
        $this->lancarDespesa();
        $this->project->update(['show_financials' => false]);

        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.show', $this->project))
            ->assertOk()
            ->assertDontSee('8.500,00');
    }

    public function test_the_sidebar_does_not_leak_the_amount_either(): void
    {
        $this->lancarDespesa();
        $this->project->update(['show_financials' => false]);

        // O menu lateral aparece em todas as páginas do portal, não só na obra.
        $this->actingAs($this->clientUser)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertDontSee('8.500,00');
    }

    public function test_turning_the_setting_off_takes_effect_immediately_despite_the_cache(): void
    {
        $this->lancarDespesa();

        // Primeira visita: aquece a cache do menu com o financeiro ligado.
        $this->actingAs($this->clientUser)->get(route('portal.dashboard'))->assertOk();

        Setting::set('show_client_financials', '0');

        $this->actingAs($this->clientUser)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertDontSee('8.500,00');
    }

    public function test_a_client_who_may_see_financials_still_sees_the_entry(): void
    {
        $this->lancarDespesa();

        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.show', $this->project))
            ->assertOk()
            ->assertSee('Comissão do arquiteto');
    }

    public function test_non_financial_history_is_never_hidden(): void
    {
        Setting::set('show_client_financials', '0');
        $this->lancarDespesa();

        ProjectUpdate::log($this->project->id, 'Fundações concluídas', 'fase');

        $this->actingAs($this->clientUser)
            ->get(route('portal.projects.show', $this->project))
            ->assertOk()
            ->assertSee('Fundações concluídas');
    }

    /*
     * O terceiro vector: a notificação. É o mais grave dos três porque sai da
     * aplicação — o título com o valor embutido vai para o email, o WhatsApp e
     * o push, e uma mensagem entregue não se retira. Esconder o histórico no
     * portal não serve de nada se o valor chegar ao telefone do cliente.
     */

    public function test_the_client_is_not_notified_of_an_expense_when_the_project_hides_financials(): void
    {
        Notification::fake();
        $this->project->update(['show_financials' => false]);

        $this->lancarDespesa();

        Notification::assertNotSentTo($this->clientUser, ProjectUpdateNotification::class);
        Notification::assertNothingSent();
    }

    public function test_the_client_is_not_notified_of_an_expense_when_the_global_setting_is_off(): void
    {
        Notification::fake();
        Setting::set('show_client_financials', '0');

        $this->lancarDespesa();

        Notification::assertNotSentTo($this->clientUser, ProjectUpdateNotification::class);
        Notification::assertNothingSent();
    }

    public function test_the_client_is_still_notified_of_an_expense_when_financials_are_visible(): void
    {
        Notification::fake();

        $this->lancarDespesa();

        Notification::assertSentTo($this->clientUser, ProjectUpdateNotification::class);
    }

    public function test_a_non_financial_update_notifies_even_with_financials_hidden(): void
    {
        Notification::fake();
        Setting::set('show_client_financials', '0');
        $this->project->update(['show_financials' => false]);

        ProjectUpdate::log($this->project->id, 'Fundações concluídas', 'fase');

        Notification::assertSentTo($this->clientUser, ProjectUpdateNotification::class);
    }
}
