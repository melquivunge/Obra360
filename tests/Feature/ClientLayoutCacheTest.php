<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Message;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * O layout do portal do cliente guardava modelos Eloquent em cache. Com
 * `CACHE_STORE=database` o modelo é serializado; depois de um deploy a
 * desserialização devolvia `__PHP_Incomplete_Class` e o `route()` rebentava
 * com 500 para todos os clientes.
 *
 * Estes testes fixam as três garantias: a página rende, a cache só guarda
 * dados primitivos, e um valor corrompido não parte o layout.
 */
class ClientLayoutCacheTest extends TestCase
{
    use RefreshDatabase;

    private User $clientUser;

    private Client $client;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = Client::factory()->create();
        $this->clientUser = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => $this->client->id,
        ]);
        $this->project = Project::factory()->create(['client_id' => $this->client->id]);

        $staff = User::factory()->manager()->create();

        Message::create([
            'project_id' => $this->project->id,
            'user_id' => $staff->id,
            'body' => 'A betonagem começa amanhã.',
        ]);

        ProjectUpdate::create([
            'project_id' => $this->project->id,
            'title' => 'Fase de fundações concluída',
            'type' => 'fase',
            'happened_at' => now(),
            'user_id' => $staff->id,
        ]);
    }

    private function cacheKeys(): array
    {
        $bucket = (int) (now()->timestamp / 30);

        return [
            "client_first_project_{$this->client->id}",
            "client_unread_msgs_{$this->clientUser->id}_{$bucket}",
            "client_unread_count_{$this->clientUser->id}_{$bucket}",
            "client_updates_{$this->client->id}",
        ];
    }

    /**
     * Só escalares, null e arrays destes — nada que precise de uma classe
     * existir para poder ser desserializado.
     */
    private function assertIsPlainData(mixed $value, string $key): void
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                $this->assertIsPlainData($item, $key);
            }

            return;
        }

        $this->assertTrue(
            $value === null || is_scalar($value),
            "A chave de cache [{$key}] guarda um valor não primitivo: ".get_debug_type($value)
        );
    }

    /**
     * Devolve uma instância de __PHP_Incomplete_Class, exatamente o que a
     * tabela `cache` produz quando a classe serializada já não existe.
     */
    private function incompleteClass(): object
    {
        $class = 'App\\Models\\ClasseQueNaoExiste';

        return unserialize(sprintf('O:%d:"%s":1:{s:2:"id";i:1;}', strlen($class), $class));
    }

    public function test_the_client_layout_renders_without_errors(): void
    {
        $this->actingAs($this->clientUser)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee('Mensagens');
    }

    public function test_the_layout_cache_never_stores_eloquent_models(): void
    {
        $this->actingAs($this->clientUser)->get(route('portal.dashboard'))->assertOk();

        $found = 0;

        foreach ($this->cacheKeys() as $key) {
            $value = Cache::get($key);

            if ($value === null) {
                continue;
            }

            $found++;
            $this->assertIsPlainData($value, $key);
        }

        $this->assertGreaterThan(0, $found, 'Nenhuma chave de cache foi escrita — o teste não estaria a provar nada.');
    }

    public function test_the_cached_first_project_is_an_array_of_scalars(): void
    {
        $this->actingAs($this->clientUser)->get(route('portal.dashboard'))->assertOk();

        $cached = Cache::get("client_first_project_{$this->client->id}");

        $this->assertIsArray($cached);
        $this->assertSame($this->project->id, $cached['id']);
        $this->assertSame($this->project->name, $cached['name']);
    }

    public function test_a_corrupted_cache_value_does_not_break_the_page(): void
    {
        // Reproduz o que a tabela `cache` devolve em produção quando a classe
        // serializada já não existe: uma instância de __PHP_Incomplete_Class.
        $incomplete = $this->incompleteClass();
        $this->assertInstanceOf(\__PHP_Incomplete_Class::class, $incomplete);

        foreach ($this->cacheKeys() as $key) {
            Cache::put($key, $incomplete, 120);
        }

        $this->actingAs($this->clientUser)
            ->get(route('portal.dashboard'))
            ->assertOk();
    }

    public function test_a_corrupted_cache_value_is_replaced_by_fresh_data(): void
    {
        $key = "client_first_project_{$this->client->id}";
        Cache::put($key, $this->incompleteClass(), 120);

        $this->actingAs($this->clientUser)->get(route('portal.dashboard'))->assertOk();

        $this->assertIsArray(Cache::get($key));
        $this->assertSame($this->project->name, Cache::get($key)['name']);
    }

    public function test_the_messages_link_still_points_to_the_first_project(): void
    {
        $this->actingAs($this->clientUser)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee(route('portal.projects.messages', $this->project), false);
    }

    public function test_a_client_without_projects_still_gets_the_layout(): void
    {
        $orphanClient = Client::factory()->create();
        $orphanUser = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => $orphanClient->id,
        ]);

        $this->actingAs($orphanUser)
            ->get(route('portal.dashboard'))
            ->assertOk();
    }
}
