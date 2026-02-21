<?php

namespace Modules\Ticketing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ticketing\Models\AssetCategory;
use Modules\Ticketing\Models\Checklist;

class ChecklistFactory extends Factory
{
    protected $model = Checklist::class;

    public function definition(): array
    {
        return [
            'asset_category_id' => AssetCategory::factory(),
            'label' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->sentence(8),
        ];
    }
}
