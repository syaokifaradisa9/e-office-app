<?php

namespace Modules\Inventory\Database\Factories;

use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Enums\StockOpnameStatus;
use Modules\Inventory\Models\StockOpname;

class StockOpnameFactory extends Factory
{
    protected $model = StockOpname::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'division_id' => null,
            'opname_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'notes' => fake()->optional()->sentence(),
            'status' => StockOpnameStatus::Pending,
        ];
    }

    public function warehouse(): static
    {
        return $this->state(fn (array $attributes) => [
            'division_id' => null,
        ]);
    }

    public function division(?int $divisionId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'division_id' => $divisionId ?? Division::factory(),
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StockOpnameStatus::StockOpname,
            'confirmed_at' => now(),
        ]);
    }
}
