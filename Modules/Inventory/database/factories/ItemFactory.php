<?php

namespace Modules\Inventory\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Models\CategoryItem;
use Modules\Inventory\Models\Item;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        return [
            'division_id' => null,
            'category_id' => CategoryItem::factory(),
            'name' => fake()->words(2, true),
            'unit_of_measure' => fake()->randomElement(['pcs', 'box', 'rim', 'pack', 'unit', 'kg', 'liter']),
            'stock' => fake()->numberBetween(10, 100),
            'description' => fake()->sentence(),
            'multiplier' => 1,
        ];
    }

    public function forDivision(int $divisionId): static
    {
        return $this->state(fn (array $attributes) => [
            'division_id' => $divisionId,
        ]);
    }

    public function withMultiplier(int $multiplier): static
    {
        return $this->state(fn (array $attributes) => [
            'multiplier' => $multiplier,
        ]);
    }

    public function withReference(Item $item): static
    {
        return $this->state(fn (array $attributes) => [
            'reference_item_id' => $item->id,
        ]);
    }

    public function withMainReference(Item $item): static
    {
        return $this->state(fn (array $attributes) => [
            'main_reference_item_id' => $item->id,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
        ]);
    }
}
