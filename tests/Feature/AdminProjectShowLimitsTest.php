<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ProjectController;
use App\Models\Document;
use App\Models\Expense;
use App\Models\Message;
use App\Models\Payment;
use App\Models\Photo;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\ProjectUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * A página da obra carregava `updates`, `messages`, `expenses`, `payments`,
 * `phases` e `documents` sem limite nenhum: numa obra com um ano de histórico
 * crescia sem tecto.
 *
 * Agora cada separador é paginado, com o seu próprio parâmetro de página. O que
 * estes testes fixam é que o tecto existe **e** que nada ficou escondido: o
 * gestor continua a chegar ao histórico antigo, e os totais do dinheiro
 * continuam a ser os totais — não os da página visível.
 */
class AdminProjectShowLimitsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->project = Project::factory()->create(['name' => 'Obra do histórico longo']);
    }

    private function show(array $query = []): TestResponse
    {
        return $this->actingAs($this->admin)
            ->get(route('admin.projects.show', array_merge(['project' => $this->project], $query)))
            ->assertOk();
    }

    // ── Histórico ────────────────────────────────────────────

    public function test_the_history_tab_is_capped_and_the_rest_stays_one_page_away(): void
    {
        $perPage = ProjectController::RECORDS_PER_PAGE;
        $total = $perPage + 3;

        for ($i = 1; $i <= $total; $i++) {
            ProjectUpdate::log($this->project->id, sprintf('Atualizacao%03d', $i));
        }

        $first = $this->show()->viewData('updates');
        $this->assertSame($perPage, $first->count());
        $this->assertSame($total, $first->total());

        $second = $this->show(['historico_pagina' => 2])->viewData('updates');
        $this->assertSame(3, $second->count());

        // Nada se perdeu pelo caminho: as duas páginas cobrem o total.
        $seen = $first->pluck('id')->merge($second->pluck('id'))->unique();
        $this->assertSame($total, $seen->count());
    }

    /**
     * O separador viaja nos links da paginação. Sem ele a página seguinte
     * abriria em «geral» e o gestor caía num sítio que não pediu.
     */
    public function test_the_history_pagination_links_carry_the_tab(): void
    {
        for ($i = 0; $i <= ProjectController::RECORDS_PER_PAGE; $i++) {
            ProjectUpdate::log($this->project->id, "Atualizacao {$i}");
        }

        $updates = $this->show()->viewData('updates');

        $this->assertStringContainsString('tab=historico', urldecode($updates->nextPageUrl()));
        $this->assertStringContainsString('historico_pagina=2', urldecode($updates->nextPageUrl()));
    }

    // ── Dinheiro ─────────────────────────────────────────────

    /**
     * Os totais das cobranças passaram a ser somados em SQL. Se fossem somados
     * a partir da coleção paginada, este teste apanhava a diferença.
     */
    public function test_the_payment_totals_cover_every_page_not_just_the_visible_one(): void
    {
        $perPage = ProjectController::RECORDS_PER_PAGE;
        $total = $perPage + 4;

        for ($i = 1; $i <= $total; $i++) {
            Payment::factory()->create([
                'project_id' => $this->project->id,
                'client_id' => $this->project->client_id,
                'amount' => 100,
                'status' => $i % 2 === 0 ? 'paid' : 'pending',
            ]);
        }

        $paid = intdiv($total, 2);
        $pending = $total - $paid;

        $response = $this->show();

        $this->assertSame($perPage, $response->viewData('payments')->count());
        $this->assertSame((float) ($paid * 100), $response->viewData('totalPaid'));
        $this->assertSame((float) ($pending * 100), $response->viewData('totalPending'));
    }

    public function test_the_expense_total_still_covers_every_page(): void
    {
        $perPage = ProjectController::RECORDS_PER_PAGE;
        $total = $perPage + 2;

        Expense::factory()->count($total)->create([
            'project_id' => $this->project->id,
            'amount' => 50,
        ]);

        $response = $this->show();

        $this->assertSame($perPage, $response->viewData('expenses')->count());
        $this->assertSame((float) ($total * 50), $response->viewData('totalSpent'));
    }

    // ── Mensagens ────────────────────────────────────────────

    /**
     * A conversa carrega as mensagens mais recentes — o mesmo tecto que o
     * portal do cliente já tinha —, mas apresentadas por ordem de leitura.
     */
    public function test_the_conversation_keeps_the_most_recent_messages_in_reading_order(): void
    {
        $perPage = ProjectController::MESSAGES_PER_PAGE;
        $total = $perPage + 3;

        for ($i = 1; $i <= $total; $i++) {
            $message = Message::create([
                'project_id' => $this->project->id,
                'user_id' => $this->admin->id,
                'body' => sprintf('Mensagem %03d', $i),
            ]);
            $message->forceFill([
                'created_at' => now()->subMinutes($total - $i),
                'updated_at' => now()->subMinutes($total - $i),
            ])->saveQuietly();
        }

        $messages = $this->show()->viewData('messages');

        $this->assertSame($perPage, $messages->count());
        // A primeira da página é a mais antiga das que cabem; a última é a mais
        // recente de todas.
        $this->assertSame(sprintf('Mensagem %03d', $total - $perPage + 1), $messages->first()->body);
        $this->assertSame(sprintf('Mensagem %03d', $total), $messages->last()->body);
    }

    public function test_the_older_messages_are_one_click_away(): void
    {
        $perPage = ProjectController::MESSAGES_PER_PAGE;
        $total = $perPage + 3;

        for ($i = 1; $i <= $total; $i++) {
            $message = Message::create([
                'project_id' => $this->project->id,
                'user_id' => $this->admin->id,
                'body' => sprintf('Mensagem %03d', $i),
            ]);
            $message->forceFill([
                'created_at' => now()->subMinutes($total - $i),
                'updated_at' => now()->subMinutes($total - $i),
            ])->saveQuietly();
        }

        $this->show()->assertSee('Ver mensagens anteriores');

        // A «página seguinte» desta conversa é a das mensagens anteriores.
        $older = $this->show(['mensagens_pagina' => 2])->viewData('messages');

        $this->assertSame(3, $older->count());
        $this->assertSame('Mensagem 001', $older->first()->body);
    }

    // ── Fotos e restantes separadores ────────────────────────

    /** Os filtros da galeria têm de sobreviver à mudança de página. */
    public function test_the_gallery_pagination_keeps_the_filters(): void
    {
        $phase = ProjectPhase::factory()->create(['project_id' => $this->project->id]);

        Photo::factory()->count(ProjectController::PHOTOS_PER_PAGE + 1)->create([
            'project_id' => $this->project->id,
            'phase_id' => $phase->id,
        ]);
        Photo::factory()->create(['project_id' => $this->project->id, 'phase_id' => null]);

        $photos = $this->show(['tab' => 'fotos', 'photo_phase' => $phase->id])->viewData('photos');

        $this->assertSame(ProjectController::PHOTOS_PER_PAGE, $photos->count());
        // O filtro continua a valer: a foto sem fase não entra na contagem.
        $this->assertSame(ProjectController::PHOTOS_PER_PAGE + 1, $photos->total());

        $next = urldecode($photos->nextPageUrl());
        $this->assertStringContainsString('photo_phase='.$phase->id, $next);
        $this->assertStringContainsString('tab=fotos', $next);
    }

    /**
     * O tecto não pode ter apagado nada da página: o que o gestor precisa de
     * ver continua lá, em cada separador.
     */
    public function test_the_manager_still_sees_what_the_page_is_for(): void
    {
        $manager = User::factory()->manager()->create();
        $project = Project::factory()->create([
            'name' => 'Obra do gestor',
            'manager_id' => $manager->id,
        ]);

        ProjectPhase::factory()->create(['project_id' => $project->id, 'name' => 'FaseVisivel']);
        Expense::factory()->create(['project_id' => $project->id, 'description' => 'DespesaVisivel']);
        Payment::factory()->create([
            'project_id' => $project->id,
            'client_id' => $project->client_id,
            'title' => 'CobrancaVisivel',
        ]);
        Document::factory()->create(['project_id' => $project->id, 'name' => 'DocumentoVisivel']);
        ProjectUpdate::log($project->id, 'AtualizacaoVisivel');
        Message::create([
            'project_id' => $project->id,
            'user_id' => $manager->id,
            'body' => 'MensagemVisivel',
        ]);

        $this->actingAs($manager)
            ->get(route('admin.projects.show', $project))
            ->assertOk()
            ->assertSee('FaseVisivel')
            ->assertSee('DespesaVisivel')
            ->assertSee('CobrancaVisivel')
            ->assertSee('DocumentoVisivel')
            ->assertSee('AtualizacaoVisivel')
            ->assertSee('MensagemVisivel');
    }

    /** Sem registos que cheguem para paginar, a página não mostra paginação nenhuma. */
    public function test_a_quiet_project_has_no_pagination_at_all(): void
    {
        ProjectUpdate::log($this->project->id, 'Única atualização');

        $response = $this->show();

        $this->assertFalse($response->viewData('updates')->hasPages());
        $this->assertFalse($response->viewData('messages')->hasPages());
        $response->assertDontSee('Ver mensagens anteriores');
    }
}
