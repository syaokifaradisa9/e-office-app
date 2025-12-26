<?php

namespace Modules\Archieve\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Archieve\Models\CategoryContext;

class CategoryContextFactory extends Factory
{
    protected $model = CategoryContext::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
        ];
    }
}
