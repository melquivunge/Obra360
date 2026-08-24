<?php

namespace Database\Factories;

use App\Models\BudgetItem;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BudgetItem> */
class BudgetItemFactory extends Factory
{
    protected $model = BudgetItem::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'category' => 'material',
            'description' => fake()->words(3, true),
            'amount' => fake()->randomFloat(2, 100, 50000),
        ];
    }
}
