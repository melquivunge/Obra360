<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\PlanVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PlanVersion> */
class PlanVersionFactory extends Factory
{
    protected $model = PlanVersion::class;

    public function definition(): array
    {
        return [
            'plan_id' => Plan::factory(),
            'version' => 1,
            'path' => 'projects/1/plans/1/planta.pdf',
            'original_name' => 'planta.pdf',
            'format' => 'pdf',
            'size' => 1024 * 512,
        ];
    }

    /** PLAN-08: uma versão que vive fora do servidor. */
    public function external(string $url = 'https://drive.example.com/planta'): static
    {
        return $this->state(fn (): array => [
            'path' => null,
            'original_name' => null,
            'format' => null,
            'size' => null,
            'external_url' => $url,
        ]);
    }

    public function superseded(): static
    {
        return $this->state(fn (): array => ['superseded_at' => now()]);
    }
}
