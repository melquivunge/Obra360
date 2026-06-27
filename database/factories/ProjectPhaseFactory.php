<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectPhase;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProjectPhase> */
class ProjectPhaseFactory extends Factory
{
    protected $model = ProjectPhase::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->words(2, true),
            'status' => 'pendente',
            'percentage' => 0,
            'sort_order' => 0,
        ];
    }
}
