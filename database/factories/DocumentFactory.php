<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Document> */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->words(3, true).'.pdf',
            'type' => 'contrato',
            'visibility' => 'interno',
            'path' => 'projects/'.fake()->numberBetween(1, 999).'/documents/test.pdf',
        ];
    }
}
