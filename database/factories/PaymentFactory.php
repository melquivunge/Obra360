<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Payment;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'client_id' => Client::factory(),
            'title' => fake()->words(3, true),
            'amount' => fake()->randomFloat(2, 100, 5000),
            'type' => 'pix',
            'status' => 'pending',
        ];
    }
}
