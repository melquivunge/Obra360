<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Tectos de escrita da área interna. O objetivo é duplo: o limite tem de
 * existir e disparar contra pedidos em catadupa, mas tem de ser largo o
 * suficiente para não travar quem está a lançar um lote de trabalho real.
 */
class InternalWriteRateLimitTest extends TestCase
{
    use RefreshDatabase;

    /** Limites declarados, por nome de rota. Ver o comentário em routes/web.php. */
    private const LIMITES = [
        'admin.projects.photos.store' => 'throttle:20,1',
        'admin.projects.documents.store' => 'throttle:30,1',
        'admin.projects.store' => 'throttle:120,1',
        'admin.projects.update' => 'throttle:120,1',
        'admin.projects.destroy' => 'throttle:120,1',
        'admin.projects.phases.store' => 'throttle:120,1',
        'admin.projects.expenses.store' => 'throttle:120,1',
        'admin.projects.payments.store' => 'throttle:120,1',
        'admin.projects.updates.store' => 'throttle:120,1',
        'admin.projects.messages.store' => 'throttle:120,1',
    ];

    public function test_every_internal_write_route_declares_a_throttle(): void
    {
        foreach (self::LIMITES as $name => $expected) {
            $this->assertContains(
                $expected,
                Route::getRoutes()->getByName($name)->gatherMiddleware(),
                "A rota {$name} devia declarar {$expected}.",
            );
        }
    }

    public function test_no_internal_write_route_is_left_without_a_throttle(): void
    {
        $semTecto = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $nome = $route->getName() ?? '';

            // Área interna: tudo sob admin.* mais a gestão de utilizadores.
            if (! str_starts_with($nome, 'admin.') && ! str_starts_with($nome, 'users.')) {
                continue;
            }

            $escrita = array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']);

            if ($escrita === []) {
                continue;
            }

            $tem = array_filter(
                $route->gatherMiddleware(),
                fn ($m) => is_string($m) && str_starts_with($m, 'throttle:'),
            );

            if ($tem === []) {
                $semTecto[] = $route->getName();
            }
        }

        $this->assertSame([], $semTecto, 'Rotas de escrita da área interna sem limite de taxa.');
    }

    /**
     * O upload de fotos é o pedido mais caro (N ficheiros por pedido, até
     * 50 MB cada, mais geração de miniaturas) e é o de tecto mais apertado.
     * Os pedidos são inválidos de propósito: o throttle corre antes da
     * validação, por isso contam à mesma e o teste não escreve ficheiros.
     */
    public function test_photo_uploads_are_blocked_after_the_limit(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $url = route('admin.projects.photos.store', $project);

        for ($i = 1; $i <= 20; $i++) {
            $this->actingAs($admin)->post($url)->assertStatus(302);
        }

        $this->actingAs($admin)->post($url)->assertStatus(429);
    }

    /**
     * O teste que mais importa: um utilizador a lançar um lote de despesas
     * seguidas — o padrão real de quem fecha as contas de uma obra — nunca
     * pode apanhar com um 429.
     */
    public function test_a_real_batch_of_expenses_is_never_blocked(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $url = route('admin.projects.expenses.store', $project);

        for ($i = 1; $i <= 60; $i++) {
            $this->actingAs($admin)->post($url, [
                'category' => 'material',
                'description' => 'Cimento — saco '.$i,
                'amount' => 25.5,
                'spent_at' => now()->toDateString(),
            ])->assertStatus(302);
        }

        $this->assertSame(60, Expense::where('project_id', $project->id)->count());
    }

    /** O mesmo para as fases: criar o plano de uma obra de raiz são muitos POSTs seguidos. */
    public function test_a_real_batch_of_phases_is_never_blocked(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $url = route('admin.projects.phases.store', $project);

        for ($i = 1; $i <= 40; $i++) {
            $this->actingAs($admin)->post($url, [
                'name' => 'Fase '.$i,
                'status' => 'pendente',
                'percentage' => 0,
            ])->assertStatus(302);
        }

        $this->assertSame(40, $project->phases()->count());
    }
}
