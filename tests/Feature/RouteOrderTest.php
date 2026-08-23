<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * O Laravel casa as rotas pela ordem de declaração. Um segmento literal
 * declarado depois de um parâmetro no mesmo sítio nunca é alcançado: o
 * parâmetro apanha-o primeiro, o route-model binding falha e a resposta é 404.
 * Estes testes fixam a ordem correta em routes/web.php.
 */
class RouteOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_the_new_project_form(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.create'))
            ->assertOk();
    }

    public function test_manager_can_open_the_new_project_form(): void
    {
        $this->actingAs(User::factory()->manager()->create())
            ->get(route('admin.projects.create'))
            ->assertOk();
    }

    public function test_financial_user_cannot_open_the_new_project_form(): void
    {
        $this->actingAs(User::factory()->financial()->create())
            ->get(route('admin.projects.create'))
            ->assertForbidden();
    }

    public function test_a_guest_is_redirected_from_the_new_project_form(): void
    {
        $this->get(route('admin.projects.create'))->assertRedirect(route('login'));
    }

    /**
     * Garante que /admin/obras/nova resolve mesmo para projects.create e não
     * para projects.show — é a causa raiz do 404, independente do middleware.
     */
    public function test_the_new_project_url_resolves_to_the_create_route(): void
    {
        $matched = Route::getRoutes()->match(Request::create('/admin/obras/nova', 'GET'));

        $this->assertSame('admin.projects.create', $matched->getName());
    }

    /**
     * Guarda de regressão para o ficheiro inteiro: nenhuma rota pode ser
     * ensombrada por outra declarada antes dela para o mesmo método. Cobre
     * qualquer literal futuro colocado a seguir a um parâmetro.
     */
    public function test_no_route_is_shadowed_by_an_earlier_one(): void
    {
        $routes = array_values(Route::getRoutes()->getRoutes());
        $shadowed = [];

        foreach ($routes as $i => $route) {
            // Substitui cada parâmetro por um valor concreto para poder testar
            // o URI desta rota contra os padrões das rotas anteriores.
            $concrete = $this->fillParameters($route->uri());

            foreach ($route->methods() as $method) {
                if (in_array($method, ['HEAD', 'OPTIONS'], true)) {
                    continue;
                }

                for ($j = 0; $j < $i; $j++) {
                    $earlier = $routes[$j];

                    if (! in_array($method, $earlier->methods(), true)) {
                        continue;
                    }

                    if (preg_match($this->uriPattern($earlier->uri()), $concrete)) {
                        $shadowed[] = sprintf(
                            '[%s] %s (%s) é apanhada por %s (%s)',
                            $method,
                            $route->uri(),
                            $route->getName() ?: 'sem nome',
                            $earlier->uri(),
                            $earlier->getName() ?: 'sem nome',
                        );
                        break;
                    }
                }
            }
        }

        $this->assertSame([], $shadowed, 'Rotas inalcançáveis por ordem de declaração.');
    }

    private function fillParameters(string $uri): string
    {
        return implode('/', array_map(
            fn (string $segment): string => str_starts_with($segment, '{') ? 'xpto' : $segment,
            explode('/', $uri),
        ));
    }

    private function uriPattern(string $uri): string
    {
        return '#^'.implode('/', array_map(
            fn (string $segment): string => str_starts_with($segment, '{') ? '[^/]+' : preg_quote($segment, '#'),
            explode('/', $uri),
        )).'$#';
    }
}
