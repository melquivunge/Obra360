<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Project;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectFinancialsTest extends TestCase
{
    use RefreshDatabase;

    private function projectWithExpenses(?float $budget = 10000): Project
    {
        $project = Project::factory()->create(['budget' => $budget]);

        Expense::factory()->create([
            'project_id' => $project->id, 'category' => 'material',
            'amount' => 1000, 'spent_at' => '2026-01-15',
        ]);
        Expense::factory()->create([
            'project_id' => $project->id, 'category' => 'material',
            'amount' => 500, 'spent_at' => '2026-02-10',
        ]);
        Expense::factory()->create([
            'project_id' => $project->id, 'category' => 'transporte',
            'amount' => 500, 'spent_at' => '2026-02-20',
        ]);

        return $project;
    }

    public function test_total_spent_sums_every_expense(): void
    {
        $this->assertSame(2000.0, $this->projectWithExpenses()->totalSpent());
    }

    public function test_remaining_balance_and_percent_used(): void
    {
        $project = $this->projectWithExpenses(10000);

        $this->assertSame(8000.0, $project->remainingBalance());
        $this->assertSame(20.0, $project->budgetUsedPercent());
    }

    public function test_percent_used_is_zero_when_budget_is_null_or_zero(): void
    {
        $this->assertSame(0.0, $this->projectWithExpenses(null)->budgetUsedPercent());
        $this->assertSame(0.0, $this->projectWithExpenses(0)->budgetUsedPercent());
    }

    public function test_soft_deleted_expense_leaves_the_total(): void
    {
        $project = $this->projectWithExpenses();
        $project->expenses()->where('category', 'transporte')->first()->delete();

        $this->assertSame(1500.0, $project->fresh()->totalSpent());
    }

    public function test_expenses_grouped_by_category_ordered_by_amount(): void
    {
        $byCategory = $this->projectWithExpenses()->expensesByCategory();

        $this->assertCount(2, $byCategory);
        $this->assertSame('material', $byCategory[0]['key']);
        $this->assertSame('Material', $byCategory[0]['label']);
        $this->assertSame(1500.0, $byCategory[0]['total']);
        $this->assertSame(75.0, $byCategory[0]['percent']);
        $this->assertSame('transporte', $byCategory[1]['key']);
        $this->assertSame(500.0, $byCategory[1]['total']);
    }

    public function test_expenses_grouped_by_month_oldest_first(): void
    {
        $byMonth = $this->projectWithExpenses()->expensesByMonth();

        $this->assertCount(2, $byMonth);
        $this->assertSame('2026-01', $byMonth[0]['month']);
        $this->assertSame(1000.0, $byMonth[0]['total']);
        $this->assertSame('2026-02', $byMonth[1]['month']);
        $this->assertSame(1000.0, $byMonth[1]['total']);
    }

    public function test_money_helper_follows_the_configured_currency(): void
    {
        Setting::set('currency', 'BRL');
        $this->assertSame('R$ 1.234,50', money(1234.5));

        Setting::set('currency', 'EUR');
        $this->assertSame('€ 1.234,50', money(1234.5));

        Setting::set('currency', 'AOA');
        $this->assertSame('Kz 1.234,50', money(1234.5));
    }
}
