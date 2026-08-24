<?php

namespace Database\Factories;

use App\Models\PlanComment;
use App\Models\PlanVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PlanComment> */
class PlanCommentFactory extends Factory
{
    protected $model = PlanComment::class;

    public function definition(): array
    {
        return [
            'plan_version_id' => PlanVersion::factory(),
            'user_id' => User::factory(),
            'body' => fake()->sentence(),
        ];
    }
}
