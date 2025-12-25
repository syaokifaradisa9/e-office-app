<?php

namespace Modules\Inventory\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\StockOpname;
use Modules\Inventory\Models\StockOpnameItem;

class StockOpnameItemFactory extends Factory
{
    protected $model = StockOpnameItem::class;

    public function definition(): array
    {
        $systemStock = fake()->numberBetween(10, 100);
        $physicalStock = fake()->numberBetween(5, 105);

        return [
            'stock_opname_id' => StockOpname::factory(),
            'item_id' => Item::factory(),
            'system_stock' => $systemStock,
            'physical_stock' => $physicalStock,
            'difference' => $physicalStock - $systemStock,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function withDifference(int $difference): static
    {
        return $this->state(function (array $attributes) use ($difference) {
            $systemStock = $attributes['system_stock'] ?? 50;

            return [
                'system_stock' => $systemStock,
                'physical_stock' => $systemStock + $difference,
                'difference' => $difference,
            ];
        });
    }

    public function noDifference(): static
    {
        return $this->state(function (array $attributes) {
            $stock = fake()->numberBetween(10, 100);

            return [
                'system_stock' => $stock,
                'physical_stock' => $stock,
                'difference' => 0,
            ];
        });
    }
}
