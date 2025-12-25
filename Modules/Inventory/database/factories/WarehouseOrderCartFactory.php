<?php

namespace Modules\Inventory\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\WarehouseOrder;
use Modules\Inventory\Models\WarehouseOrderCart;

class WarehouseOrderCartFactory extends Factory
{
    protected $model = WarehouseOrderCart::class;

    public function definition(): array
    {
        return [
            'warehouse_order_id' => WarehouseOrder::factory(),
            'item_id' => Item::factory(),
            'quantity' => fake()->numberBetween(1, 10),
            'delivered_quantity' => null,
            'received_quantity' => null,
        ];
    }

    public function delivered(?int $quantity = null): static
    {
        return $this->state(fn (array $attributes) => [
            'delivered_quantity' => $quantity ?? $attributes['quantity'],
        ]);
    }

    public function received(?int $quantity = null): static
    {
        return $this->state(fn (array $attributes) => [
            'delivered_quantity' => $quantity ?? $attributes['quantity'],
            'received_quantity' => $quantity ?? $attributes['quantity'],
        ]);
    }
}
