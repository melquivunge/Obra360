<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Plan;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * O que se garante aqui não é que a validação recusa — isso já está coberto
 * noutros testes — mas que quem preencheu mal o formulário *vê* a explicação e
 * não perde o que escreveu. E que a explicação aparece num sítio só: o erro de
 * um formulário não pode acender o formulário do lado.
 */
class ValidationFeedbackTest extends TestCase
{
    use RefreshDatabase;

    private const RESUMO = 'Não foi possível salvar. Corrija o seguinte:';

    /**
     * Quantas vezes um texto aparece na página — é assim que se demonstra que
     * o resumo não se repete por todos os formulários da mesma página.
     */
    private function occurrences(string $html, string $needle): int
    {
        return substr_count($html, $needle);
    }

    // ── Obra ──────────────────────────────────────────────────

    public function test_an_invalid_project_shows_the_error_and_keeps_what_was_typed(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())
            ->from(route('admin.projects.create'))
            ->followingRedirects()
            ->post(route('admin.projects.store'), [
                'name' => '',
                'location' => 'Rua do Sol, 14',
                'status' => 'planejada',
                'type' => 'construcao',
            ]);

        $response->assertOk()
            ->assertSee(self::RESUMO)
            ->assertSee('O campo cliente é obrigatório.')
            ->assertSee('O campo nome é obrigatório.')
            ->assertSee('value="Rua do Sol, 14"', false);
    }

    public function test_the_new_project_modal_reopens_with_the_error(): void
    {
        Client::factory()->create();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->from(route('admin.projects.index'))
            ->followingRedirects()
            ->post(route('admin.projects.store'), [
                '_form' => 'nova_obra',
                'name' => '',
                'location' => 'Bairro Azul',
                'status' => 'planejada',
                'type' => 'construcao',
            ]);

        $response->assertOk()
            ->assertSee(self::RESUMO)
            ->assertSee('O campo nome é obrigatório.')
            ->assertSee('value="Bairro Azul"', false)
            // Sem isto a página recarregava com o modal fechado e a mensagem escondida.
            ->assertSee("window.obra360.openModal('modal-nova-obra')", false);
    }

    // ── Cliente ───────────────────────────────────────────────

    public function test_an_invalid_client_shows_the_error_and_keeps_what_was_typed(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())
            ->from(route('admin.clients.create'))
            ->followingRedirects()
            ->post(route('admin.clients.store'), [
                'name' => '',
                'city' => 'Benguela',
                'email' => 'isto-nao-e-um-email',
            ]);

        $response->assertOk()
            ->assertSee(self::RESUMO)
            ->assertSee('O campo nome é obrigatório.')
            ->assertSee('O campo e-mail deve ser um endereço de e-mail válido.')
            ->assertSee('value="Benguela"', false);
    }

    public function test_the_new_client_modal_reopens_with_the_error(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())
            ->from(route('admin.clients.index'))
            ->followingRedirects()
            ->post(route('admin.clients.store'), [
                '_form' => 'create_client',
                'name' => '',
                'city' => 'Lobito',
            ]);

        $response->assertOk()
            ->assertSee(self::RESUMO)
            ->assertSee('O campo nome é obrigatório.')
            ->assertSee('value="Lobito"', false)
            ->assertSee("window.obra360.openModal('modal-create-client')", false);
    }

    // ── Utilizador ────────────────────────────────────────────

    public function test_an_invalid_user_shows_the_error_and_keeps_what_was_typed(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())
            ->from(route('users.create'))
            ->followingRedirects()
            ->post(route('users.store'), [
                'name' => 'Ana Tavares',
                'email' => 'isto-nao-e-um-email',
                'role' => 'manager',
                'password' => 'segredo123',
                'password_confirmation' => 'outra-coisa',
            ]);

        $response->assertOk()
            ->assertSee(self::RESUMO)
            ->assertSee('Insira um e-mail válido.')
            ->assertSee('As senhas não coincidem.')
            ->assertSee('value="Ana Tavares"', false);
    }

    public function test_the_new_user_modal_reopens_with_the_error(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())
            ->from(route('users.index'))
            ->followingRedirects()
            ->post(route('users.store'), [
                '_form' => 'create_user',
                'name' => 'Ana Tavares',
                'email' => '',
                'role' => 'manager',
                'password' => 'segredo123',
                'password_confirmation' => 'segredo123',
            ]);

        $response->assertOk()
            ->assertSee(self::RESUMO)
            ->assertSee('value="Ana Tavares"', false)
            ->assertSee("window.obra360.openModal('modal-create-user')", false);
    }

    // ── Definições ────────────────────────────────────────────

    public function test_invalid_settings_show_the_error_and_keep_what_was_typed(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())
            ->from(route('admin.settings.index'))
            ->followingRedirects()
            ->put(route('admin.settings.update'), [
                'company_name' => '',
                'company_email' => 'nao-e-email',
                'company_phone' => '+244 900 000 000',
            ]);

        $response->assertOk()
            ->assertSee(self::RESUMO)
            ->assertSee('O campo nome da empresa é obrigatório.')
            ->assertSee('value="+244 900 000 000"', false);
    }

    // ── Os modais da página da obra ───────────────────────────

    public function test_the_phase_modal_reopens_on_its_own_tab_with_the_error(): void
    {
        $project = Project::factory()->create();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->from(route('admin.projects.show', $project))
            ->followingRedirects()
            ->post(route('admin.projects.phases.store', $project), [
                '_form' => 'nova_fase',
                'name' => '',
                'status' => 'planejada',
                'percentage' => 10,
                'description' => 'Betonagem da laje',
            ]);

        $response->assertOk()
            ->assertSee(self::RESUMO)
            ->assertSee('O campo nome é obrigatório.')
            ->assertSee('Betonagem da laje')
            ->assertSee('window.obra360.openModal("modal-fase")', false)
            // O separador das fases tem de estar aberto quando a página volta.
            ->assertSee("projectShow({$project->id}, 'fases')", false);
    }

    public function test_the_expense_modal_reopens_on_its_own_tab_with_the_error(): void
    {
        $project = Project::factory()->create();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->from(route('admin.projects.show', $project))
            ->followingRedirects()
            ->post(route('admin.projects.expenses.store', $project), [
                '_form' => 'despesa_nova',
                'category' => 'material',
                'description' => 'Cimento e areia',
                'amount' => '',
                'spent_at' => '2026-05-04',
            ]);

        $response->assertOk()
            ->assertSee(self::RESUMO)
            ->assertSee('O campo valor é obrigatório.')
            ->assertSee('Cimento e areia')
            ->assertSee('novaDespesa();', false)
            ->assertSee("projectShow({$project->id}, 'despesas')", false);
    }

    public function test_the_payment_modal_reopens_on_its_own_tab_with_the_error(): void
    {
        $project = Project::factory()->create();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->from(route('admin.projects.show', $project))
            ->followingRedirects()
            ->post(route('admin.projects.payments.store', $project), [
                '_form' => 'nova_cobranca',
                'title' => 'Segunda tranche',
                'amount' => '',
                'type' => 'pix',
                'status' => 'pending',
            ]);

        $response->assertOk()
            ->assertSee(self::RESUMO)
            ->assertSee('O campo valor é obrigatório.')
            ->assertSee('value="Segunda tranche"', false)
            ->assertSee('window.obra360.openModal("modal-pagamento")', false)
            ->assertSee("projectShow({$project->id}, 'pagamentos')", false);
    }

    public function test_the_plan_version_modal_reopens_on_the_plans_tab_with_the_error(): void
    {
        $project = Project::factory()->create(['type' => 'arquitetura']);
        $plan = Plan::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->from(route('admin.projects.show', $project))
            ->followingRedirects()
            ->post(route('admin.projects.plans.versions.store', [$project, $plan]), [
                '_form' => 'nova_versao_'.$plan->id,
                'change_note' => 'Corrigida a cota do piso 0',
            ]);

        $response->assertOk()
            ->assertSee(self::RESUMO)
            ->assertSee('Corrigida a cota do piso 0')
            ->assertSee('novaVersao('.$plan->id.',', false)
            ->assertSee("projectShow({$project->id}, 'plantas')", false);
    }

    public function test_an_inline_phase_edit_reopens_with_the_error(): void
    {
        $project = Project::factory()->create();
        $phase = ProjectPhase::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->from(route('admin.projects.show', $project))
            ->followingRedirects()
            ->put(route('admin.projects.phases.update', [$project, $phase]), [
                '_form' => 'editar_fase_'.$phase->id,
                'name' => '',
                'status' => 'em_andamento',
                'percentage' => 40,
            ]);

        $response->assertOk()
            ->assertSee(self::RESUMO)
            ->assertSee('O campo nome é obrigatório.')
            // O formulário em linha vinha fechado; o erro voltava para dentro dele.
            ->assertSee('editing: true', false)
            ->assertSee("projectShow({$project->id}, 'fases')", false);
    }

    // ── Um erro só acende o formulário de onde veio ───────────

    public function test_an_error_only_lights_up_the_form_it_came_from(): void
    {
        $project = Project::factory()->create();
        ProjectPhase::factory()->create(['project_id' => $project->id]);

        $html = $this->actingAs(User::factory()->admin()->create())
            ->from(route('admin.projects.show', $project))
            ->followingRedirects()
            ->post(route('admin.projects.phases.store', $project), [
                '_form' => 'nova_fase',
                'name' => '',
                'status' => 'planejada',
                'percentage' => 0,
            ])
            ->assertOk()
            ->getContent();

        // A página tem mais de dez formulários e vários campos «nome». O resumo
        // aparece uma vez — no modal da nova fase — e o erro por campo também.
        $this->assertSame(1, $this->occurrences($html, self::RESUMO));
        $this->assertSame(2, $this->occurrences($html, 'O campo nome é obrigatório.'));
        // E a edição em linha da fase existente continua fechada.
        $this->assertStringContainsString('editing: false', $html);
    }

    public function test_an_error_in_the_link_user_form_does_not_light_up_the_client_form(): void
    {
        $client = Client::factory()->create();
        User::factory()->create(['role' => 'client']);

        $html = $this->actingAs(User::factory()->admin()->create())
            ->from(route('admin.clients.edit', $client))
            ->followingRedirects()
            ->post(route('admin.clients.link-user', $client), [
                '_form' => 'vincular_utilizador',
                'user_id' => '',
            ])
            ->assertOk()
            ->getContent();

        $this->assertSame(1, $this->occurrences($html, self::RESUMO));
        $this->assertStringContainsString('O campo usuário é obrigatório.', $html);
        // O nome do cliente continua no campo dele, intacto.
        $this->assertStringContainsString('value="'.$client->name.'"', $html);
    }

    // ── Português e nomes de campo legíveis ───────────────────

    public function test_the_messages_are_in_portuguese_and_name_the_field_the_way_the_label_does(): void
    {
        $html = $this->actingAs(User::factory()->admin()->create())
            ->from(route('admin.projects.create'))
            ->followingRedirects()
            ->post(route('admin.projects.store'), [
                'name' => 'Residência Alfa',
                'status' => 'inexistente',
                'type' => 'construcao',
            ])
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('O campo cliente é obrigatório.', $html);
        $this->assertStringContainsString('O valor escolhido para status é inválido.', $html);
        // Nem o nome da coluna, nem a frase original do framework.
        $this->assertStringNotContainsString('client id', $html);
        $this->assertStringNotContainsString('field is required', $html);
    }
}
