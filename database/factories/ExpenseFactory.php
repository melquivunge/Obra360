<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Expense> */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'category' => fake()->randomElement(array_keys(Expense::CATEGORIES)),
            'description' => fake()->words(3, true),
            'amount' => fake()->randomFloat(2, 50, 5000),
            'spent_at' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
        ];
    }
}
