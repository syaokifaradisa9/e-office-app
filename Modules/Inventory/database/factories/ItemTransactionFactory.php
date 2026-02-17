<?php

namespace Modules\Inventory\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Enums\ItemTransactionType;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\ItemTransaction;

class ItemTransactionFactory extends Factory
{
    protected $model = ItemTransaction::class;

    public function definition(): array
    {
        return [
            'date' => fake()->dateTimeBetween('-1 month', 'now'),
            'type' => fake()->randomElement(ItemTransactionType::cases()),
            'item_id' => Item::factory(),
            'quantity' => fake()->numberBetween(1, 20),
            'user_id' => User::factory(),
            'description' => fake()->sentence(),
        ];
    }

    public function incoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ItemTransactionType::In,
        ]);
    }

    public function outgoing(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ItemTransactionType::Out,
        ]);
    }

    public function stockOpnameMore(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ItemTransactionType::StockOpnameMore,
        ]);
    }

    public function stockOpnameLess(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ItemTransactionType::StockOpnameLess,
        ]);
    }

    public function conversion(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ItemTransactionType::Conversion,
        ]);
    }
}
