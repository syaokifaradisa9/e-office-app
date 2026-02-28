<?php

namespace Modules\Ticketing\Database\Factories;

use App\Models\Division;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ticketing\Models\AssetItem;
use Modules\Ticketing\Models\AssetCategory;
use Modules\Ticketing\Enums\AssetItemStatus;

class AssetItemFactory extends Factory
{
    protected $model = AssetItem::class;

    public function definition(): array
    {
        return [
            'asset_category_id' => AssetCategory::factory(),
            'merk' => $this->faker->company(),
            'model' => $this->faker->word(),
            'serial_number' => $this->faker->unique()->uuid(),
            'division_id' => Division::factory(),
            'another_attributes' => null,
            'last_maintenance_date' => null,
            'status' => AssetItemStatus::Available->value,
        ];
    }
}
