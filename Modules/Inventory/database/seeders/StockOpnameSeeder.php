<?php

namespace Modules\Inventory\Database\Seeders;

use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Inventory\Enums\StockOpnameStatus;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\StockOpname;
use Modules\Inventory\Models\StockOpnameItem;

class StockOpnameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $divisions = Division::all();
        $items = Item::all();

        if ($items->isEmpty()) {
            return;
        }

        // Delete existing data to ensure exactly 1 pending
        StockOpnameItem::query()->delete();
        StockOpname::query()->delete();

        // 1. Create Stock Opnames
        $this->createWarehouseOpnames($users, $items);
        $this->createDivisionOpnames($users, $divisions, $items);
    }

    private function createWarehouseOpnames($users, $items)
    {
        // 1. Create 1 Finish for current month (So it doesn't block the UI by default)
        $opnameFinishCurrent = StockOpname::create([
            'user_id' => $users->random()->id,
            'division_id' => null,
            'opname_date' => now(),
            'notes' => 'Opname Gudang Rutin - Selesai Bulan Ini',
            'status' => StockOpnameStatus::Finish,
            'confirmed_at' => now(),
        ]);
        $this->seedItems($opnameFinishCurrent, $items, 5, true);

        // 2. Create 4 Finish for previous 4 months
        for ($i = 1; $i <= 4; $i++) {
            $date = now()->subMonths($i)->startOfMonth()->addDays(fake()->numberBetween(0, 25));
            $opnameFinish = StockOpname::create([
                'user_id' => $users->random()->id,
                'division_id' => null,
                'opname_date' => $date,
                'notes' => 'Opname Gudang - Selesai (Bulan T-' . $i . ')',
                'status' => StockOpnameStatus::Finish,
                'confirmed_at' => $date->copy()->addDays(1),
            ]);
            $this->seedItems($opnameFinish, $items, 8, true);
        }
    }

    private function createDivisionOpnames($users, $divisions, $items)
    {
        if ($divisions->isEmpty()) return;

        foreach ($divisions as $division) {
            // 1. Create 1 Finish for current month
            $opnameFinishCurrent = StockOpname::create([
                'user_id' => ($users->where('division_id', $division->id)->isEmpty() ? $users->random()->id : $users->where('division_id', $division->id)->random()->id),
                'division_id' => $division->id,
                'opname_date' => now(),
                'notes' => 'Opname Divisi ' . $division->name . ' - Selesai Bulan Ini',
                'status' => StockOpnameStatus::Finish,
                'confirmed_at' => now(),
            ]);
            $this->seedItems($opnameFinishCurrent, $items, fake()->numberBetween(3, 7), true);

            // 2. Create 4 Finish for previous 4 months
            for ($i = 1; $i <= 4; $i++) {
                $date = now()->subMonths($i)->startOfMonth()->addDays(fake()->numberBetween(0, 25));
                $opnameFinish = StockOpname::create([
                    'user_id' => ($users->where('division_id', $division->id)->isEmpty() ? $users->random()->id : $users->where('division_id', $division->id)->random()->id),
                    'division_id' => $division->id,
                    'opname_date' => $date,
                    'notes' => 'Opname Divisi ' . $division->name . ' - Selesai (Bulan T-' . $i . ')',
                    'status' => StockOpnameStatus::Finish,
                    'confirmed_at' => $date->copy()->addDays(1),
                ]);
                $this->seedItems($opnameFinish, $items, fake()->numberBetween(3, 7), true);
            }
        }
    }

    private function seedItems(StockOpname $opname, $items, $count, $withPhysical)
    {
        $selectedItems = $items->random(min($count, $items->count()));

        foreach ($selectedItems as $item) {
            $systemStock = fake()->numberBetween(10, 100);
            $physicalStock = $withPhysical ? $systemStock + fake()->numberBetween(-5, 5) : null;
            
            StockOpnameItem::create([
                'stock_opname_id' => $opname->id,
                'item_id' => $item->id,
                'system_stock' => $systemStock,
                'physical_stock' => $physicalStock,
                'notes' => $physicalStock !== $systemStock && !is_null($physicalStock) ? 'Selisih karena barang rusak/hilang' : null,
            ]);
        }
    }
}
