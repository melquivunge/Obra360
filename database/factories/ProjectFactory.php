<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Project> */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'client_id' => Client::factory(),
            'manager_id' => User::factory()->manager(),
            'status' => 'planejada',
            'progress' => 0,
            'show_financials' => true,
        ];
    }
}
