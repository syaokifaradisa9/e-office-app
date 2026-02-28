<?php

namespace Modules\Ticketing\Database\Factories;

use App\Models\Division;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ticketing\Models\AssetCategory;
use Modules\Ticketing\Enums\AssetCategoryType;

class AssetCategoryFactory extends Factory
{
    protected $model = AssetCategory::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'type' => $this->faker->randomElement(AssetCategoryType::cases()),
            'division_id' => Division::factory(),
            'maintenance_count' => $this->faker->numberBetween(1, 12),
        ];
    }
}
