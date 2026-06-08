<?php

namespace Modules\Inventory\Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Seeder;
use Modules\Inventory\Models\CategoryItem;
use Modules\Inventory\Models\Item;
use Carbon\Carbon;

class StokTertimbunSeeder extends Seeder
{
    public function run(): void
    {
        $categories = CategoryItem::all();
        $divisions = Division::all();

        if ($categories->isEmpty()) {
            $this->command->warn('Tidak ada kategori. Jalankan CategoryItemSeeder terlebih dahulu.');
            return;
        }

        $categoryIds = $categories->pluck('id')->toArray();
        $fourMonthsAgo = Carbon::now()->subMonths(4);

        // Gudang Utama (3 items)
        $gudangItems = [
            ['name' => 'Kertas Buram F4 (Rim)', 'unit' => 'rim', 'stock' => 50, 'desc' => 'Kertas buram F4 yang jarang dipakai'],
            ['name' => 'Pita Mesin TIK', 'unit' => 'pcs', 'stock' => 20, 'desc' => 'Pita untuk mesin tik lama'],
            ['name' => 'Tinta Stempel Ungu', 'unit' => 'botol', 'stock' => 15, 'desc' => 'Tinta stempel ungu'],
        ];

        foreach ($gudangItems as $gi) {
            $catId = $categoryIds[array_rand($categoryIds)];
            $item = Item::firstOrCreate(
                ['name' => $gi['name'], 'division_id' => null],
                [
                    'category_id' => $catId,
                    'unit_of_measure' => $gi['unit'],
                    'stock' => $gi['stock'],
                    'description' => $gi['desc'],
                ]
            );
            $item->created_at = $fourMonthsAgo;
            $item->updated_at = $fourMonthsAgo;
            $item->saveQuietly();
        }

        // Division Items (3 per division)
        $divItems = [
            'Tata Usaha' => [
                ['name' => 'Klip Kertas Jumbo', 'unit' => 'kotak', 'stock' => 10],
                ['name' => 'Map Snelhecter Merah Lama', 'unit' => 'pcs', 'stock' => 45],
                ['name' => 'Lem Kertas Cair Besar', 'unit' => 'botol', 'stock' => 8],
            ],
            'Keuangan' => [
                ['name' => 'Buku Kas Folio Lama', 'unit' => 'buku', 'stock' => 5],
                ['name' => 'Kalkulator Citizen 12 Digit Lama', 'unit' => 'pcs', 'stock' => 2],
                ['name' => 'Kertas Continuous Form 3 Ply', 'unit' => 'dus', 'stock' => 3],
            ],
            'IT' => [
                ['name' => 'Kabel VGA 1.5m', 'unit' => 'pcs', 'stock' => 10],
                ['name' => 'CD-R Blank', 'unit' => 'keping', 'stock' => 100],
                ['name' => 'Mouse PS/2', 'unit' => 'pcs', 'stock' => 12],
            ],
            'Pelayanan' => [
                ['name' => 'Kapas Steril 500g', 'unit' => 'bungkus', 'stock' => 15],
                ['name' => 'Kertas Antrean Termal Lama', 'unit' => 'roll', 'stock' => 20],
                ['name' => 'Tisu Basah Antiseptik Lama', 'unit' => 'pak', 'stock' => 30],
            ],
            // Default random names for any other division
            'Default' => [
                ['name' => 'Odner Bekas', 'unit' => 'pcs', 'stock' => 20],
                ['name' => 'Isi Pensil Mekanik 0.5', 'unit' => 'tube', 'stock' => 15],
                ['name' => 'Kotak Pensil Plastik', 'unit' => 'pcs', 'stock' => 5],
            ]
        ];

        if ($divisions->isNotEmpty()) {
            foreach ($divisions as $div) {
                $itemsToInsert = $divItems[$div->name] ?? $divItems['Default'];

                foreach ($itemsToInsert as $di) {
                    $catId = $categoryIds[array_rand($categoryIds)];
                    
                    // Buat master data nya di Gudang Utama terlebih dahulu
                    $globalItem = Item::firstOrCreate(
                        ['name' => $di['name'], 'division_id' => null],
                        [
                            'category_id' => $catId,
                            'unit_of_measure' => $di['unit'],
                            'stock' => $di['stock'] * 2,
                            'description' => 'Gudang Utama - ' . $di['name'],
                        ]
                    );
                    $globalItem->created_at = $fourMonthsAgo;
                    $globalItem->updated_at = $fourMonthsAgo;
                    $globalItem->saveQuietly();

                    // Buat untuk divisi
                    $divItem = Item::firstOrCreate(
                        ['name' => $di['name'], 'division_id' => $div->id],
                        [
                            'category_id' => $catId,
                            'unit_of_measure' => $di['unit'],
                            'stock' => $di['stock'],
                            'description' => 'Stok tertimbun di ' . $div->name,
                            'main_reference_item_id' => $globalItem->id,
                        ]
                    );
                    $divItem->created_at = $fourMonthsAgo;
                    $divItem->updated_at = $fourMonthsAgo;
                    $divItem->saveQuietly();
                }
            }
        }

        $this->command->info('Seeder Stok Tertimbun (Wajar) berhasil dijalankan.');
    }
}
