<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Expense;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private function project(?User $manager = null): Project
    {
        $project = Project::factory()->create([
            'manager_id' => $manager?->id ?? User::factory()->manager()->create()->id,
        ]);

        Expense::factory()->create([
            'project_id' => $project->id, 'category' => 'material',
            'amount' => 750, 'spent_at' => '2026-03-05',
        ]);
        ProjectPhase::factory()->create(['project_id' => $project->id]);

        return $project;
    }

    public static function reportTypes(): array
    {
        return [['geral'], ['financeiro'], ['fotos'], ['fases']];
    }

    #[DataProvider('reportTypes')]
    public function test_admin_can_open_every_report_on_screen(string $type): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.reports.show', [$this->project(), $type]));

        $response->assertOk();
    }

    #[DataProvider('reportTypes')]
    public function test_admin_can_download_every_report_as_pdf(string $type): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.reports.pdf', [$this->project(), $type]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_unknown_report_type_is_404(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.reports.show', [$this->project(), 'inventado']))
            ->assertNotFound();
    }

    public function test_financial_report_shows_category_and_month_totals(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.reports.show', [$this->project(), 'financeiro']))
            ->assertOk()
            ->assertSee('Material')
            ->assertSee('750,00');
    }

    public function test_manager_cannot_open_a_report_for_another_managers_project(): void
    {
        $project = $this->project();
        $intruder = User::factory()->manager()->create();

        $this->actingAs($intruder)
            ->get(route('admin.reports.show', [$project, 'geral']))
            ->assertForbidden();
    }

    public function test_manager_can_open_a_report_for_their_own_project(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get(route('admin.reports.show', [$this->project($manager), 'geral']))
            ->assertOk();
    }

    public function test_client_cannot_reach_reports_at_all(): void
    {
        $client = Client::factory()->create();
        $clientUser = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => $client->id,
        ]);

        $this->actingAs($clientUser)
            ->get(route('admin.reports.show', [$this->project(), 'geral']))
            ->assertForbidden();

        $this->actingAs($clientUser)
            ->get(route('admin.reports.index'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.reports.index'))->assertRedirect(route('login'));
    }
}
