<?php

namespace Tests\Feature;

use App\Models\BudgetItem;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ADV-04: o orçamento deixa de ser um número só.
 *
 * As linhas são o **plano** por categoria e não substituem o total contratado
 * — comparam-se com o que foi realmente gasto, pelas mesmas categorias das
 * despesas. É dessa comparação que sai a pergunta útil: «orçámos tanto em
 * material e já vamos em quanto?».
 */
class BudgetItemTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    private User $financial;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create([
            'client_id' => Client::factory(),
            'budget' => 100000,
        ]);
        $this->financial = User::factory()->financial()->create();
    }

    private function line(string $category, float $amount): BudgetItem
    {
        return BudgetItem::factory()->create([
            'project_id' => $this->project->id,
            'category' => $category,
            'amount' => $amount,
        ]);
    }

    private function expense(string $category, float $amount): Expense
    {
        return Expense::factory()->create([
            'project_id' => $this->project->id,
            'category' => $category,
            'amount' => $amount,
        ]);
    }

    public function test_the_planned_total_is_the_sum_of_the_lines(): void
    {
        $this->line('material', 40000);
        $this->line('mao_de_obra', 25000);

        $this->assertSame(65000.0, $this->project->budgetPlanned());
    }

    public function test_a_project_without_lines_has_nothing_planned(): void
    {
        $this->assertSame(0.0, $this->project->budgetPlanned());
        $this->assertSame([], $this->project->budgetVersusSpent());
    }

    public function test_planned_and_spent_sit_side_by_side_by_category(): void
    {
        $this->line('material', 40000);
        $this->expense('material', 52000);

        $linhas = $this->project->budgetVersusSpent();

        $this->assertCount(1, $linhas);
        $this->assertSame('material', $linhas[0]['key']);
        $this->assertSame(40000.0, $linhas[0]['planned']);
        $this->assertSame(52000.0, $linhas[0]['spent']);
        $this->assertSame(12000.0, $linhas[0]['diff']);
    }

    /**
     * Uma despesa numa categoria que ninguém orçou é precisamente o que
     * interessa ver — escondê-la por falta de plano esconderia o problema.
     */
    public function test_a_category_spent_but_never_planned_still_shows_up(): void
    {
        $this->line('material', 40000);
        $this->expense('transporte', 3000);

        $chaves = array_column($this->project->budgetVersusSpent(), 'key');

        $this->assertContains('transporte', $chaves);
    }

    public function test_a_category_planned_but_not_yet_spent_shows_up(): void
    {
        $this->line('licenca', 1500);

        $linha = $this->project->budgetVersusSpent()[0];

        $this->assertSame('licenca', $linha['key']);
        $this->assertSame(0.0, $linha['spent']);
        $this->assertSame(-1500.0, $linha['diff']);
    }

    public function test_the_difference_against_the_contracted_total_is_reported(): void
    {
        $this->line('material', 130000);

        // 130 mil planeados contra 100 mil contratados.
        $this->assertSame(30000.0, $this->project->budgetPlanDiff());
    }

    public function test_the_financial_role_adds_edits_and_removes_lines(): void
    {
        $this->actingAs($this->financial)
            ->post(route('admin.projects.budget-items.store', $this->project), [
                'category' => 'material',
                'description' => 'Cimento e areia',
                'amount' => 12000,
            ])->assertRedirect();

        $item = BudgetItem::firstOrFail();
        $this->assertSame('Cimento e areia', $item->description);

        $this->actingAs($this->financial)
            ->put(route('admin.projects.budget-items.update', [$this->project, $item]), [
                'category' => 'material',
                'description' => 'Cimento, areia e brita',
                'amount' => 15000,
            ])->assertRedirect();

        $this->assertSame('15000.00', $item->fresh()->amount);

        $this->actingAs($this->financial)
            ->delete(route('admin.projects.budget-items.destroy', [$this->project, $item]))
            ->assertRedirect();

        $this->assertDatabaseCount('budget_items', 0);
    }

    public function test_a_manager_cannot_write_budget_lines(): void
    {
        $this->actingAs(User::factory()->manager()->create())
            ->post(route('admin.projects.budget-items.store', $this->project), [
                'category' => 'material',
                'amount' => 100,
            ])->assertForbidden();
    }

    public function test_a_line_from_another_project_returns_404(): void
    {
        $outro = Project::factory()->create(['client_id' => Client::factory()]);
        $item = BudgetItem::factory()->create(['project_id' => $outro->id]);

        $this->actingAs($this->financial)
            ->delete(route('admin.projects.budget-items.destroy', [$this->project, $item]))
            ->assertNotFound();
    }

    public function test_an_unknown_category_is_refused(): void
    {
        $this->actingAs($this->financial)
            ->post(route('admin.projects.budget-items.store', $this->project), [
                'category' => 'inexistente',
                'amount' => 100,
            ])->assertSessionHasErrors('category');

        $this->assertDatabaseCount('budget_items', 0);
    }

    public function test_the_comparison_is_shown_on_the_expenses_tab(): void
    {
        $this->line('material', 40000);
        $this->expense('material', 52000);

        $this->actingAs($this->financial)
            ->get(route('admin.projects.show', $this->project).'?tab=despesas')
            ->assertOk()
            ->assertSee('Orçamento por categoria')
            ->assertSee('Material');
    }
}
