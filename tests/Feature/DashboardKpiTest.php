<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardKpiTest extends TestCase
{
    use RefreshDatabase;

    public function test_late_projects_are_listed(): void
    {
        Project::factory()->create([
            'name' => 'Obra Atrasada',
            'expected_end_date' => now()->subDays(10)->toDateString(),
            'status' => 'em_andamento',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Em atraso (1)')
            ->assertSee('Obra Atrasada');
    }

    public function test_a_finished_project_past_its_date_is_not_late(): void
    {
        Project::factory()->create([
            'name' => 'Obra Entregue',
            'expected_end_date' => now()->subDays(30)->toDateString(),
            'status' => 'concluida',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Em atraso');
    }

    public function test_a_project_without_an_expected_date_is_never_late(): void
    {
        Project::factory()->create([
            'expected_end_date' => null,
            'status' => 'em_andamento',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Em atraso');
    }

    public function test_projects_at_or_above_eighty_percent_are_near_completion(): void
    {
        Project::factory()->create(['name' => 'Quase Pronta', 'progress' => 85, 'status' => 'em_andamento']);
        Project::factory()->create(['name' => 'A Meio', 'progress' => 40, 'status' => 'em_andamento']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Perto da conclusão (1)')
            ->assertSee('Quase Pronta');
    }

    public function test_manager_only_sees_their_own_projects_in_the_alerts(): void
    {
        $manager = User::factory()->manager()->create();

        Project::factory()->create([
            'name' => 'Do Gestor', 'manager_id' => $manager->id,
            'expected_end_date' => now()->subDay()->toDateString(), 'status' => 'em_andamento',
        ]);
        Project::factory()->create([
            'name' => 'De Outro', 'manager_id' => User::factory()->manager()->create()->id,
            'expected_end_date' => now()->subDay()->toDateString(), 'status' => 'em_andamento',
        ]);

        $this->actingAs($manager)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Do Gestor')
            ->assertDontSee('De Outro');
    }
}
