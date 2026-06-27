<?php

namespace Database\Factories;

use App\Models\Photo;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Photo> */
class PhotoFactory extends Factory
{
    protected $model = Photo::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'caption' => fake()->sentence(),
            'path' => 'projects/'.fake()->numberBetween(1, 999).'/photos/test.jpg',
            'media_type' => 'image',
            'taken_at' => now()->toDateString(),
        ];
    }
}
