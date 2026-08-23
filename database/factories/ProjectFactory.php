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
            // Por omissão a fábrica produz obras de construção, como as que já
            // existiam antes de haver tipos (TIPO-02).
            'type' => 'construcao',
            'manager_id' => User::factory()->manager(),
            'status' => 'planejada',
            'progress' => 0,
            'show_financials' => true,
        ];
    }

    /**
     * Estado para os serviços de projecto — arquitectura por omissão.
     */
    public function ofType(string $type): static
    {
        return $this->state(fn () => ['type' => $type]);
    }

    public function architecture(): static
    {
        return $this->ofType('arquitetura');
    }

    public function interiorDesign(): static
    {
        return $this->ofType('design_interiores');
    }
}
