<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Plan> */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => 'Planta baixa — piso '.fake()->numberBetween(0, 4),
            'discipline' => '2d',
            'status' => 'em_elaboracao',
        ];
    }

    public function threeDimensional(): static
    {
        return $this->state(fn (): array => ['discipline' => '3d']);
    }
}
