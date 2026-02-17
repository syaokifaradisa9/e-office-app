<?php

namespace Modules\Inventory\Database\Factories;

use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Enums\WarehouseOrderStatus;
use Modules\Inventory\Models\WarehouseOrder;

class WarehouseOrderFactory extends Factory
{
    protected $model = WarehouseOrder::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'division_id' => fn() => Division::inRandomOrder()->first()?->id ?? Division::factory(),
            'order_number' => date('ym').str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'description' => fake()->sentence(),
            'notes' => fake()->optional()->sentence(),
            'status' => WarehouseOrderStatus::Pending,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WarehouseOrderStatus::Pending,
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WarehouseOrderStatus::Confirmed,
            'accepted_date' => now(),
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WarehouseOrderStatus::Delivered,
            'accepted_date' => now()->subDays(2),
            'delivery_date' => now(),
            'delivered_by' => User::factory(),
        ]);
    }

    public function finished(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WarehouseOrderStatus::Finished,
            'accepted_date' => now()->subDays(3),
            'delivery_date' => now()->subDay(),
            'delivered_by' => User::factory(),
            'receipt_date' => now(),
            'received_by' => User::factory(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WarehouseOrderStatus::Rejected,
        ]);
    }

    public function revision(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WarehouseOrderStatus::Revision,
        ]);
    }
}
