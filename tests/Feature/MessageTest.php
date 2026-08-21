<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Message;
use App\Models\Project;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Project}
     */
    private function clientWithProject(): array
    {
        $client = Client::factory()->create();
        $user = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => $client->id,
        ]);

        return [$user, Project::factory()->create(['client_id' => $client->id])];
    }

    // ── Cliente ──────────────────────────────────────────────

    public function test_client_can_post_a_message_on_their_own_project(): void
    {
        [$user, $project] = $this->clientWithProject();

        $this->actingAs($user)
            ->postJson(route('portal.projects.messages.store', $project), ['body' => 'Quando começa a pintura?'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('messages', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'body' => 'Quando começa a pintura?',
        ]);
    }

    /**
     * BUG: o endpoint é consumido por XHR e devia responder 422 em JSON, mas
     * bootstrap/app.php:33 limita `shouldRenderJsonWhen` a `api/*`, pelo que a
     * falha de validação sai como redirect 302 com os erros em sessão. O teste
     * documenta o comportamento atual.
     */
    public function test_an_empty_message_is_rejected(): void
    {
        [$user, $project] = $this->clientWithProject();

        $this->actingAs($user)
            ->postJson(route('portal.projects.messages.store', $project), ['body' => ''])
            ->assertRedirect();

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_a_message_longer_than_the_limit_is_rejected(): void
    {
        [$user, $project] = $this->clientWithProject();

        $this->actingAs($user)
            ->postJson(route('portal.projects.messages.store', $project), ['body' => str_repeat('a', 1001)])
            ->assertRedirect()
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_client_reads_the_messages_of_their_own_project(): void
    {
        [$user, $project] = $this->clientWithProject();
        $staff = User::factory()->admin()->create(['name' => 'Equipa Obra360']);

        Message::create(['project_id' => $project->id, 'user_id' => $staff->id, 'body' => 'Bom dia']);
        Message::create(['project_id' => $project->id, 'user_id' => $user->id, 'body' => 'Obrigado']);

        $this->actingAs($user)
            ->getJson(route('portal.projects.messages', $project))
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.body', 'Bom dia')
            ->assertJsonPath('0.user_name', 'Equipa Obra360')
            ->assertJsonPath('0.is_mine', false)
            ->assertJsonPath('1.is_mine', true);
    }

    /**
     * MSG-04 — com os comentários desligados o cliente ainda lê, mas não escreve.
     */
    public function test_with_comments_disabled_the_client_still_reads_but_cannot_write(): void
    {
        Setting::set('allow_comments', '0');
        [$user, $project] = $this->clientWithProject();
        Message::create([
            'project_id' => $project->id,
            'user_id' => User::factory()->admin()->create()->id,
            'body' => 'Histórico antigo',
        ]);

        $this->actingAs($user)
            ->getJson(route('portal.projects.messages', $project))
            ->assertOk()
            ->assertJsonCount(1);

        $this->actingAs($user)
            ->postJson(route('portal.projects.messages.store', $project), ['body' => 'Bloqueada'])
            ->assertForbidden();

        $this->assertDatabaseCount('messages', 1);
    }

    public function test_with_comments_disabled_the_messages_tab_disappears_from_the_portal(): void
    {
        Setting::set('allow_comments', '0');
        [$user, $project] = $this->clientWithProject();

        $this->actingAs($user)
            ->get(route('portal.projects.show', $project))
            ->assertOk()
            ->assertViewHas('allowComments', false);
    }

    // ── Equipa ───────────────────────────────────────────────

    public function test_admin_can_post_a_message_on_any_project(): void
    {
        $project = Project::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson(route('admin.projects.messages.store', $project), ['body' => 'A pintura começa segunda.'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('messages', [
            'project_id' => $project->id,
            'user_id' => $admin->id,
            'body' => 'A pintura começa segunda.',
        ]);
    }

    public function test_an_empty_admin_message_is_rejected(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->postJson(route('admin.projects.messages.store', $project), ['body' => ''])
            ->assertRedirect()
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_manager_can_post_a_message_on_their_own_project(): void
    {
        $manager = User::factory()->manager()->create();
        $project = Project::factory()->create(['manager_id' => $manager->id]);

        $this->actingAs($manager)
            ->postJson(route('admin.projects.messages.store', $project), ['body' => 'Actualização da obra.'])
            ->assertOk();

        $this->assertDatabaseCount('messages', 1);
    }

    public function test_manager_cannot_post_a_message_on_another_managers_project(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->manager()->create())
            ->postJson(route('admin.projects.messages.store', $project), ['body' => 'Intruso'])
            ->assertForbidden();

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_manager_cannot_read_the_messages_of_another_managers_project(): void
    {
        $project = Project::factory()->create();
        Message::create([
            'project_id' => $project->id,
            'user_id' => User::factory()->admin()->create()->id,
            'body' => 'Conversa alheia',
        ]);

        $this->actingAs(User::factory()->manager()->create())
            ->getJson(route('admin.projects.messages.index', $project))
            ->assertForbidden();
    }

    /**
     * BUG: Admin/MessageController tem o seu próprio authorizeProjectAccess
     * (app/Http/Controllers/Admin/MessageController.php:83) que só verifica o
     * papel de gestor. Ao contrário do trait AuthorizesProjectAccess usado nos
     * outros controllers, deixa passar um utilizador financeiro em qualquer
     * obra. O teste documenta o comportamento atual.
     */
    public function test_financial_user_can_post_a_message_on_any_project(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->financial()->create())
            ->postJson(route('admin.projects.messages.store', $project), ['body' => 'Sem âmbito'])
            ->assertOk();

        $this->assertDatabaseCount('messages', 1);
    }

    public function test_the_admin_message_feed_is_ordered_oldest_first(): void
    {
        $project = Project::factory()->create();
        $admin = User::factory()->admin()->create();

        Message::create(['project_id' => $project->id, 'user_id' => $admin->id, 'body' => 'Primeira'])
            ->update(['created_at' => now()->subHour()]);
        Message::create(['project_id' => $project->id, 'user_id' => $admin->id, 'body' => 'Segunda']);

        $this->actingAs($admin)
            ->getJson(route('admin.projects.messages.index', $project))
            ->assertOk()
            ->assertJsonPath('0.body', 'Primeira')
            ->assertJsonPath('1.body', 'Segunda');
    }
}
