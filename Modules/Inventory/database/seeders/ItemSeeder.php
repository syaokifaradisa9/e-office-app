<?php

namespace Modules\Inventory\Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Seeder;
use Modules\Inventory\Models\CategoryItem;
use Modules\Inventory\Models\Item;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $categories = CategoryItem::all();
        $divisions = Division::all();

        if ($categories->isEmpty()) {
            $this->command->warn('Tidak ada kategori. Jalankan CategoryItemSeeder terlebih dahulu.');
            return;
        }

        if ($divisions->isEmpty()) {
            $this->command->warn('Tidak ada divisi. Jalankan DivisionSeeder terlebih dahulu.');
            return;
        }

        // Gudang (tanpa division_id) — stok utama
        $warehouseItems = [
            // Alat Tulis Kantor
            ['category' => 'Alat Tulis Kantor', 'name' => 'Kertas HVS A4 70gr (Rim)', 'unit_of_measure' => 'rim', 'stock' => 200, 'description' => 'Kertas HVS ukuran A4 70 gram per rim (500 lembar)'],
            ['category' => 'Alat Tulis Kantor', 'name' => 'Kertas HVS A4 70gr (Lembar)', 'unit_of_measure' => 'lembar', 'stock' => 100000, 'description' => 'Kertas HVS ukuran A4 70 gram satuan lembar', 'reference' => 'Kertas HVS A4 70gr (Rim)', 'multiplier' => 500],
            ['category' => 'Alat Tulis Kantor', 'name' => 'Pulpen Hitam', 'unit_of_measure' => 'pcs', 'stock' => 150, 'description' => 'Pulpen tinta hitam standar'],
            ['category' => 'Alat Tulis Kantor', 'name' => 'Pulpen Biru', 'unit_of_measure' => 'pcs', 'stock' => 120, 'description' => 'Pulpen tinta biru standar'],
            ['category' => 'Alat Tulis Kantor', 'name' => 'Pulpen Merah', 'unit_of_measure' => 'pcs', 'stock' => 80, 'description' => 'Pulpen tinta merah standar'],
            ['category' => 'Alat Tulis Kantor', 'name' => 'Pensil 2B', 'unit_of_measure' => 'pcs', 'stock' => 100, 'description' => 'Pensil 2B untuk menulis dan menggambar'],
            ['category' => 'Alat Tulis Kantor', 'name' => 'Penghapus', 'unit_of_measure' => 'pcs', 'stock' => 60, 'description' => 'Penghapus pensil putih'],
            ['category' => 'Alat Tulis Kantor', 'name' => 'Spidol Whiteboard Hitam', 'unit_of_measure' => 'pcs', 'stock' => 50, 'description' => 'Spidol untuk papan tulis putih warna hitam'],
            ['category' => 'Alat Tulis Kantor', 'name' => 'Spidol Whiteboard Biru', 'unit_of_measure' => 'pcs', 'stock' => 40, 'description' => 'Spidol untuk papan tulis putih warna biru'],
            ['category' => 'Alat Tulis Kantor', 'name' => 'Spidol Whiteboard Merah', 'unit_of_measure' => 'pcs', 'stock' => 30, 'description' => 'Spidol untuk papan tulis putih warna merah'],
            ['category' => 'Alat Tulis Kantor', 'name' => 'Binder Clip Kecil (Kotak)', 'unit_of_measure' => 'kotak', 'stock' => 40, 'description' => 'Binder clip ukuran kecil, 12 pcs per kotak'],
            ['category' => 'Alat Tulis Kantor', 'name' => 'Binder Clip Sedang (Kotak)', 'unit_of_measure' => 'kotak', 'stock' => 30, 'description' => 'Binder clip ukuran sedang, 12 pcs per kotak'],
            ['category' => 'Alat Tulis Kantor', 'name' => 'Stapler', 'unit_of_measure' => 'pcs', 'stock' => 25, 'description' => 'Stapler standar ukuran sedang'],
            ['category' => 'Alat Tulis Kantor', 'name' => 'Isi Stapler No. 10', 'unit_of_measure' => 'kotak', 'stock' => 50, 'description' => 'Isi stapler nomor 10, 1000 pcs per kotak'],
            ['category' => 'Alat Tulis Kantor', 'name' => 'Map Plastik', 'unit_of_measure' => 'pcs', 'stock' => 100, 'description' => 'Map plastik transparan ukuran folio'],
            ['category' => 'Alat Tulis Kantor', 'name' => 'Amplop Putih Besar', 'unit_of_measure' => 'pcs', 'stock' => 200, 'description' => 'Amplop putih ukuran besar untuk dokumen'],
            ['category' => 'Alat Tulis Kantor', 'name' => 'Sticky Notes', 'unit_of_measure' => 'pak', 'stock' => 60, 'description' => 'Sticky notes warna kuning 3x3 inch'],
            ['category' => 'Alat Tulis Kantor', 'name' => 'Correction Pen', 'unit_of_measure' => 'pcs', 'stock' => 40, 'description' => 'Tip-ex pen untuk koreksi tulisan'],

            // Elektronik
            ['category' => 'Elektronik', 'name' => 'Toner Printer HP LaserJet', 'unit_of_measure' => 'pcs', 'stock' => 15, 'description' => 'Toner cartridge untuk printer HP LaserJet'],
            ['category' => 'Elektronik', 'name' => 'Tinta Printer Epson Hitam', 'unit_of_measure' => 'botol', 'stock' => 20, 'description' => 'Tinta refill warna hitam untuk printer Epson'],
            ['category' => 'Elektronik', 'name' => 'Tinta Printer Epson Warna', 'unit_of_measure' => 'set', 'stock' => 10, 'description' => 'Set tinta refill warna (C, M, Y) untuk printer Epson'],
            ['category' => 'Elektronik', 'name' => 'Mouse USB', 'unit_of_measure' => 'pcs', 'stock' => 20, 'description' => 'Mouse optik kabel USB standar'],
            ['category' => 'Elektronik', 'name' => 'Keyboard USB', 'unit_of_measure' => 'pcs', 'stock' => 15, 'description' => 'Keyboard kabel USB standar'],
            ['category' => 'Elektronik', 'name' => 'Flashdisk 32GB', 'unit_of_measure' => 'pcs', 'stock' => 25, 'description' => 'USB flash drive kapasitas 32GB'],
            ['category' => 'Elektronik', 'name' => 'Kabel LAN Cat6 (Meter)', 'unit_of_measure' => 'meter', 'stock' => 500, 'description' => 'Kabel jaringan LAN kategori 6'],
            ['category' => 'Elektronik', 'name' => 'Stop Kontak 4 Lubang', 'unit_of_measure' => 'pcs', 'stock' => 10, 'description' => 'Stop kontak listrik 4 lubang dengan kabel 3 meter'],

            // Furnitur
            ['category' => 'Furnitur', 'name' => 'Kursi Kantor Staff', 'unit_of_measure' => 'pcs', 'stock' => 10, 'description' => 'Kursi kantor staff dengan sandaran dan roda'],
            ['category' => 'Furnitur', 'name' => 'Meja Kerja 120x60', 'unit_of_measure' => 'pcs', 'stock' => 5, 'description' => 'Meja kerja kantor ukuran 120x60 cm'],
            ['category' => 'Furnitur', 'name' => 'Lemari Arsip 4 Laci', 'unit_of_measure' => 'pcs', 'stock' => 8, 'description' => 'Lemari arsip besi 4 laci dengan kunci'],
            ['category' => 'Furnitur', 'name' => 'Rak Buku 5 Tingkat', 'unit_of_measure' => 'pcs', 'stock' => 6, 'description' => 'Rak buku kayu 5 tingkat'],
            ['category' => 'Furnitur', 'name' => 'Whiteboard 120x90', 'unit_of_measure' => 'pcs', 'stock' => 4, 'description' => 'Papan tulis putih ukuran 120x90 cm dengan dudukan'],

            // Perlengkapan Kebersihan
            ['category' => 'Perlengkapan Kebersihan', 'name' => 'Sapu Ijuk', 'unit_of_measure' => 'pcs', 'stock' => 15, 'description' => 'Sapu ijuk untuk lantai'],
            ['category' => 'Perlengkapan Kebersihan', 'name' => 'Pel Lantai', 'unit_of_measure' => 'pcs', 'stock' => 10, 'description' => 'Pel lantai dengan gagang aluminium'],
            ['category' => 'Perlengkapan Kebersihan', 'name' => 'Ember Pel', 'unit_of_measure' => 'pcs', 'stock' => 8, 'description' => 'Ember pel dengan pemeras'],
            ['category' => 'Perlengkapan Kebersihan', 'name' => 'Cairan Pembersih Lantai', 'unit_of_measure' => 'botol', 'stock' => 30, 'description' => 'Cairan pembersih lantai 1 liter'],
            ['category' => 'Perlengkapan Kebersihan', 'name' => 'Tisu Toilet (Roll)', 'unit_of_measure' => 'roll', 'stock' => 100, 'description' => 'Tisu toilet gulung standar'],
            ['category' => 'Perlengkapan Kebersihan', 'name' => 'Sabun Cuci Tangan', 'unit_of_measure' => 'botol', 'stock' => 25, 'description' => 'Sabun cuci tangan cair 500ml'],
            ['category' => 'Perlengkapan Kebersihan', 'name' => 'Trash Bag Hitam (Pak)', 'unit_of_measure' => 'pak', 'stock' => 20, 'description' => 'Kantung sampah hitam 60x90cm, 20 lembar per pak'],

            // Perlengkapan Dapur
            ['category' => 'Perlengkapan Dapur', 'name' => 'Gelas Kaca', 'unit_of_measure' => 'pcs', 'stock' => 50, 'description' => 'Gelas kaca bening standar 250ml'],
            ['category' => 'Perlengkapan Dapur', 'name' => 'Gelas Plastik Sekali Pakai', 'unit_of_measure' => 'pak', 'stock' => 30, 'description' => 'Gelas plastik 220ml, 50 pcs per pak'],
            ['category' => 'Perlengkapan Dapur', 'name' => 'Kopi Sachet', 'unit_of_measure' => 'kotak', 'stock' => 20, 'description' => 'Kopi instan sachet, 10 sachet per kotak'],
            ['category' => 'Perlengkapan Dapur', 'name' => 'Teh Celup', 'unit_of_measure' => 'kotak', 'stock' => 15, 'description' => 'Teh celup, 25 kantung per kotak'],
            ['category' => 'Perlengkapan Dapur', 'name' => 'Gula Pasir', 'unit_of_measure' => 'kg', 'stock' => 10, 'description' => 'Gula pasir putih kemasan 1 kg'],
            ['category' => 'Perlengkapan Dapur', 'name' => 'Air Mineral Galon', 'unit_of_measure' => 'galon', 'stock' => 15, 'description' => 'Air mineral kemasan galon 19 liter'],
            ['category' => 'Perlengkapan Dapur', 'name' => 'Tissue Makan (Pak)', 'unit_of_measure' => 'pak', 'stock' => 25, 'description' => 'Tissue makan 50 lembar per pak'],
        ];

        // Simpan mapping item name → id untuk reference
        $itemMap = [];

        foreach ($warehouseItems as $itemData) {
            $category = $categories->firstWhere('name', $itemData['category']);
            if (!$category) {
                continue;
            }

            $data = [
                'category_id' => $category->id,
                'division_id' => null, // Stok gudang utama
                'name' => $itemData['name'],
                'unit_of_measure' => $itemData['unit_of_measure'],
                'stock' => $itemData['stock'],
                'description' => $itemData['description'] ?? null,
                'multiplier' => $itemData['multiplier'] ?? null,
            ];

            $item = Item::firstOrCreate(
                ['name' => $itemData['name'], 'division_id' => null],
                $data
            );

            $itemMap[$item->name] = $item->id;

            // Set reference jika ada
            if (isset($itemData['reference']) && isset($itemMap[$itemData['reference']])) {
                $referenceId = $itemMap[$itemData['reference']];
                $item->update([
                    'reference_item_id' => $referenceId,
                    'main_reference_item_id' => $referenceId,
                ]);
            }
        }

        // Barang per divisi (stok yang sudah didistribusikan)
        $divisionItems = [
            'Tata Usaha' => [
                ['name' => 'Kertas HVS A4 70gr (Rim)', 'stock' => 10],
                ['name' => 'Pulpen Hitam', 'stock' => 15],
                ['name' => 'Pulpen Biru', 'stock' => 10],
                ['name' => 'Stapler', 'stock' => 3],
                ['name' => 'Map Plastik', 'stock' => 20],
                ['name' => 'Amplop Putih Besar', 'stock' => 30],
            ],
            'Keuangan' => [
                ['name' => 'Kertas HVS A4 70gr (Rim)', 'stock' => 15],
                ['name' => 'Pulpen Hitam', 'stock' => 12],
                ['name' => 'Binder Clip Kecil (Kotak)', 'stock' => 5],
                ['name' => 'Map Plastik', 'stock' => 15],
                ['name' => 'Sticky Notes', 'stock' => 8],
            ],
            'IT' => [
                ['name' => 'Kertas HVS A4 70gr (Rim)', 'stock' => 5],
                ['name' => 'Mouse USB', 'stock' => 5],
                ['name' => 'Keyboard USB', 'stock' => 3],
                ['name' => 'Flashdisk 32GB', 'stock' => 5],
                ['name' => 'Kabel LAN Cat6 (Meter)', 'stock' => 100],
            ],
            'Pelayanan' => [
                ['name' => 'Kertas HVS A4 70gr (Rim)', 'stock' => 8],
                ['name' => 'Pulpen Hitam', 'stock' => 10],
                ['name' => 'Pulpen Biru', 'stock' => 8],
                ['name' => 'Amplop Putih Besar', 'stock' => 20],
            ],
        ];

        foreach ($divisionItems as $divisionName => $items) {
            $division = $divisions->firstWhere('name', $divisionName);
            if (!$division) {
                continue;
            }

            foreach ($items as $itemData) {
                // Cari item gudang utama untuk referensi
                $mainItem = Item::where('name', $itemData['name'])
                    ->whereNull('division_id')
                    ->first();

                if (!$mainItem) {
                    continue;
                }

                Item::firstOrCreate(
                    ['name' => $itemData['name'], 'division_id' => $division->id],
                    [
                        'category_id' => $mainItem->category_id,
                        'division_id' => $division->id,
                        'name' => $itemData['name'],
                        'unit_of_measure' => $mainItem->unit_of_measure,
                        'stock' => $itemData['stock'],
                        'description' => $mainItem->description,
                        'main_reference_item_id' => $mainItem->id,
                    ]
                );
            }
        }

        $this->command->info('Item seeder berhasil dijalankan.');
    }
}
