<?php

namespace Modules\Archieve\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Archieve\Models\DocumentClassification;

class DocumentClassificationFactory extends Factory
{
    protected $model = DocumentClassification::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->bothify('??.###'),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'parent_id' => null,
        ];
    }
}
