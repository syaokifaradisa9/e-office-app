<?php

namespace Modules\Inventory\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Models\CategoryItem;

class CategoryItemFactory extends Factory
{
    protected $model = CategoryItem::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word().' '.fake()->randomElement(['ATK', 'Elektronik', 'Kebersihan', 'Peralatan']),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
