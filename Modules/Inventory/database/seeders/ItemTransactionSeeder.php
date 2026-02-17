<?php

namespace Modules\Inventory\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Inventory\Enums\ItemTransactionType;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\ItemTransaction;

class ItemTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $items = Item::all();
        $users = User::all();

        if ($items->isEmpty()) {
            $this->command->warn('Tidak ada item. Jalankan ItemSeeder terlebih dahulu.');
            return;
        }

        if ($users->isEmpty()) {
            $this->command->warn('Tidak ada user. Jalankan UserSeeder terlebih dahulu.');
            return;
        }

        $transactions = [
            [
                'type' => ItemTransactionType::In,
                'description' => 'Pengadaan barang bulanan kantor',
                'min_qty' => 50,
                'max_qty' => 100,
            ],
            [
                'type' => ItemTransactionType::Out,
                'description' => 'Pengambilan barang rutin divisi',
                'min_qty' => 5,
                'max_qty' => 20,
            ],
            [
                'type' => ItemTransactionType::ConversionIn,
                'description' => 'Hasil konversi dari satuan box ke rim',
                'min_qty' => 10,
                'max_qty' => 30,
            ],
            [
                'type' => ItemTransactionType::ConversionOut,
                'description' => 'Konversi satuan untuk distribusi',
                'min_qty' => 1,
                'max_qty' => 5,
            ],
            [
                'type' => ItemTransactionType::StockOpnameMore,
                'description' => 'Kelebihan stok hasil pengecekan fisik',
                'min_qty' => 1,
                'max_qty' => 10,
            ],
            [
                'type' => ItemTransactionType::StockOpnameLess,
                'description' => 'Kekurangan stok hasil pengecekan fisik',
                'min_qty' => 1,
                'max_qty' => 5,
            ],
            [
                'type' => ItemTransactionType::StockOpname,
                'description' => 'Sinkronisasi stok rutin',
                'min_qty' => 0,
                'max_qty' => 0,
            ],
        ];

        // Create 30 random transactions
        for ($i = 0; $i < 30; $i++) {
            $config = collect($transactions)->random();
            $item = $items->random();
            $user = $users->random();

            ItemTransaction::create([
                'date' => now()->subDays(rand(0, 30)),
                'type' => $config['type'],
                'item_id' => $item->id,
                'quantity' => rand($config['min_qty'], $config['max_qty']),
                'user_id' => $user->id,
                'description' => $config['description'],
            ]);
        }

        $this->command->info('ItemTransaction seeder berhasil dijalankan.');
    }
}
