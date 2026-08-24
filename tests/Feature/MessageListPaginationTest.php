<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\MessageController;
use App\Models\Message;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A lista de conversas carregava todas as obras visíveis, com a mensagem de
 * cada uma, e só depois as ordenava em PHP com `sortByDesc()`. Sem tecto e sem
 * paginação, o custo crescia com o número de obras.
 *
 * Estes testes fixam o comportamento que interessa: a lista pagina, continua
 * ordenada pela mensagem mais recente — inclusive **entre** páginas — e a
 * ordenação feita em SQL dá o mesmo resultado que a que era feita em PHP.
 */
class MessageListPaginationTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    /** Contador para dar a cada obra um `created_at` distinto e previsível. */
    private int $created = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = User::factory()->admin()->create();
    }

    /**
     * `created_at` não é atribuível em massa nestes modelos; escrever a data
     * pela via normal seria ignorado em silêncio e todos os registos ficariam
     * com o mesmo instante — o que faria estes testes provar nada.
     */
    private function stampedAt(Model $model, string $when): void
    {
        $model->forceFill(['created_at' => $when, 'updated_at' => $when])->saveQuietly();
    }

    /**
     * Uma obra com mensagens nas idades pedidas (em minutos, do presente para
     * trás). Cada obra fica com um `created_at` próprio e crescente, para que o
     * desempate entre obras sem mensagens seja igualmente determinístico.
     *
     * @param  array<int, int>  $minutesAgo
     */
    private function projectWithMessages(string $name, array $minutesAgo): Project
    {
        $project = Project::factory()->create(['name' => $name]);
        $this->stampedAt($project, now()->subDay()->addMinutes(++$this->created)->toDateTimeString());

        foreach ($minutesAgo as $minutes) {
            $message = Message::create([
                'project_id' => $project->id,
                'user_id' => $this->staff->id,
                'body' => "Mensagem de {$name} há {$minutes} min",
            ]);
            $this->stampedAt($message, now()->subMinutes($minutes)->toDateTimeString());
        }

        return $project->refresh();
    }

    /**
     * @return array<int, int>
     */
    private function idsOnPage(int $page): array
    {
        $response = $this->actingAs($this->staff)
            ->get(route('admin.messages.index', ['page' => $page]))
            ->assertOk();

        return $response->viewData('projects')->pluck('id')->all();
    }

    public function test_the_conversation_list_is_paginated(): void
    {
        $perPage = MessageController::PROJECTS_PER_PAGE;
        $total = $perPage + 5;

        for ($i = 1; $i <= $total; $i++) {
            $this->projectWithMessages(sprintf('Obra %02d', $i), [$i]);
        }

        $response = $this->actingAs($this->staff)
            ->get(route('admin.messages.index'))
            ->assertOk();

        $projects = $response->viewData('projects');

        $this->assertSame($perPage, $projects->count());
        $this->assertSame($total, $projects->total());
        $this->assertTrue($projects->hasPages());
    }

    /**
     * O ponto do teste: a ordem tem de valer para a lista inteira, não só
     * dentro de uma página. Se a ordenação acontecesse depois de cortar a
     * página — como acontecia ao ordenar em PHP sobre uma consulta paginada —,
     * a obra mais antiga da página 1 poderia ser mais recente que a primeira
     * da página 2 e este teste apanhava-o.
     */
    public function test_the_order_by_latest_message_holds_between_pages(): void
    {
        $perPage = MessageController::PROJECTS_PER_PAGE;
        $total = $perPage + 5;

        // A obra 1 tem a mensagem mais recente (há 1 minuto); a obra 25, a mais
        // antiga. A ordem esperada é exatamente 1, 2, 3, … , 25.
        $expected = [];
        for ($i = 1; $i <= $total; $i++) {
            $expected[] = $this->projectWithMessages(sprintf('Obra %02d', $i), [$i])->id;
        }

        $this->assertSame(array_slice($expected, 0, $perPage), $this->idsOnPage(1));
        $this->assertSame(array_slice($expected, $perPage), $this->idsOnPage(2));
    }

    /**
     * A ordenação passou do PHP para o SQL; o resultado tem de ser o mesmo,
     * incluindo o desempate e o lugar das obras sem mensagens.
     */
    public function test_the_sql_order_matches_the_php_order_it_replaced(): void
    {
        $this->projectWithMessages('Alfa', [90, 12]);
        $this->projectWithMessages('Bravo', [200]);
        $this->projectWithMessages('Charlie', [5, 400]);
        $this->projectWithMessages('Delta', []);
        $this->projectWithMessages('Echo', [60]);
        $this->projectWithMessages('Foxtrot', []);
        $this->projectWithMessages('Golf', [1]);

        // A ordenação antiga, tal e qual: tudo carregado por `latest()` e
        // ordenado em memória pela data da última mensagem.
        $expected = Project::latest()->get()
            ->sortByDesc(fn (Project $p) => $p->messages()->reorder()->max('created_at'))
            ->pluck('id')
            ->values()
            ->all();

        $this->assertSame($expected, $this->idsOnPage(1));
    }

    public function test_a_project_without_messages_falls_to_the_end(): void
    {
        $silent = $this->projectWithMessages('Sem conversa', []);
        $talkative = $this->projectWithMessages('Com conversa', [10]);

        $this->assertSame([$talkative->id, $silent->id], $this->idsOnPage(1));
    }

    /**
     * A pré-visualização da lista é a ÚLTIMA mensagem da obra. A relação
     * `messages()` ordena da mais antiga para a mais recente, e sem a limpar o
     * `latest()` do carregamento não tinha efeito nenhum: a lista mostrava — e
     * ordenava-se por — a primeira mensagem de sempre.
     */
    public function test_the_preview_shows_the_last_message_and_not_the_first(): void
    {
        $project = Project::factory()->create(['name' => 'Obra da conversa']);

        $first = Message::create([
            'project_id' => $project->id,
            'user_id' => $this->staff->id,
            'body' => 'PRIMEIRAMENSAGEMDESEMPRE',
        ]);
        $this->stampedAt($first, now()->subYear()->toDateTimeString());

        Message::create([
            'project_id' => $project->id,
            'user_id' => $this->staff->id,
            'body' => 'ULTIMAMENSAGEMDETODAS',
        ]);

        $this->actingAs($this->staff)
            ->get(route('admin.messages.index'))
            ->assertOk()
            ->assertSee('ULTIMAMENSAGEMDETODAS')
            ->assertDontSee('PRIMEIRAMENSAGEMDESEMPRE');
    }

    /** A autorização não muda com a paginação: o gestor continua a ver só as suas obras. */
    public function test_a_manager_only_sees_their_own_projects(): void
    {
        $manager = User::factory()->manager()->create();

        $mine = Project::factory()->create(['name' => 'ObraDoGestor', 'manager_id' => $manager->id]);
        $theirs = Project::factory()->create(['name' => 'ObraDeOutro']);

        $response = $this->actingAs($manager)
            ->get(route('admin.messages.index'))
            ->assertOk();

        $this->assertSame([$mine->id], $response->viewData('projects')->pluck('id')->all());
        $response->assertDontSee('ObraDeOutro');
        $this->assertNotNull($theirs->id);
    }
}
