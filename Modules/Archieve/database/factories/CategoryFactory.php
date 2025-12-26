<?php

namespace Modules\Archieve\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Archieve\Models\Category;
use Modules\Archieve\Models\CategoryContext;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'context_id' => CategoryContext::factory(),
            'description' => $this->faker->sentence(),
        ];
    }
}
