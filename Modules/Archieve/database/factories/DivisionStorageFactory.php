<?php

namespace Modules\Archieve\Database\Factories;

use App\Models\Division;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Archieve\Models\DivisionStorage;

class DivisionStorageFactory extends Factory
{
    protected $model = DivisionStorage::class;

    public function definition(): array
    {
        return [
            'division_id' => Division::factory(),
            'max_size' => 1073741824, // 1GB
            'used_size' => 0,
        ];
    }
}
