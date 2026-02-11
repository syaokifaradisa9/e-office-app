<?php

namespace Modules\Inventory\Database\Seeders;

use App\Models\Division;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Modules\Inventory\Enums\WarehouseOrderStatus;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\WarehouseOrder;
use Modules\Inventory\Models\WarehouseOrderCart;

class WarehouseOrderSeeder extends Seeder
{
    public function run(): void
    {
        $divisions = Division::all();
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('Tidak ada user. Jalankan UserSeeder terlebih dahulu.');
            return;
        }

        if ($divisions->isEmpty()) {
            $this->command->warn('Tidak ada divisi. Jalankan DivisionSeeder terlebih dahulu.');
            return;
        }

        $warehouseItems = Item::whereNull('division_id')->get();

        if ($warehouseItems->isEmpty()) {
            $this->command->warn('Tidak ada barang gudang. Jalankan ItemSeeder terlebih dahulu.');
            return;
        }

        $orders = [
            // === Permintaan yang sudah selesai (Finished) ===
            [
                'user_email' => 'ahmad.fauzi@example.com',
                'division' => 'Tata Usaha',
                'order_number' => 'WO-2026-0001',
                'description' => 'Permintaan ATK bulanan divisi Tata Usaha',
                'notes' => null,
                'status' => WarehouseOrderStatus::Finished,
                'accepted_date' => Carbon::parse('2026-01-05'),
                'delivery_date' => Carbon::parse('2026-01-07'),
                'delivered_by_email' => 'ryan.hidayat@example.com',
                'receipt_date' => Carbon::parse('2026-01-07'),
                'received_by_email' => 'ahmad.fauzi@example.com',
                'items' => [
                    ['name' => 'Kertas HVS A4 70gr (Rim)', 'quantity' => 10, 'delivered_quantity' => 10, 'received_quantity' => 10],
                    ['name' => 'Pulpen Hitam', 'quantity' => 15, 'delivered_quantity' => 15, 'received_quantity' => 15],
                    ['name' => 'Pulpen Biru', 'quantity' => 10, 'delivered_quantity' => 10, 'received_quantity' => 10],
                    ['name' => 'Map Plastik', 'quantity' => 20, 'delivered_quantity' => 20, 'received_quantity' => 20],
                ],
                'created_at' => Carbon::parse('2026-01-03'),
            ],
            [
                'user_email' => 'dewi.lestari@example.com',
                'division' => 'Keuangan',
                'order_number' => 'WO-2026-0002',
                'description' => 'Kebutuhan ATK awal tahun divisi Keuangan',
                'notes' => null,
                'status' => WarehouseOrderStatus::Finished,
                'accepted_date' => Carbon::parse('2026-01-06'),
                'delivery_date' => Carbon::parse('2026-01-08'),
                'delivered_by_email' => 'ryan.hidayat@example.com',
                'receipt_date' => Carbon::parse('2026-01-08'),
                'received_by_email' => 'dewi.lestari@example.com',
                'items' => [
                    ['name' => 'Kertas HVS A4 70gr (Rim)', 'quantity' => 15, 'delivered_quantity' => 15, 'received_quantity' => 15],
                    ['name' => 'Pulpen Hitam', 'quantity' => 12, 'delivered_quantity' => 12, 'received_quantity' => 12],
                    ['name' => 'Binder Clip Kecil (Kotak)', 'quantity' => 5, 'delivered_quantity' => 5, 'received_quantity' => 5],
                    ['name' => 'Sticky Notes', 'quantity' => 8, 'delivered_quantity' => 8, 'received_quantity' => 8],
                ],
                'created_at' => Carbon::parse('2026-01-04'),
            ],

            // === Permintaan yang sudah dikirim, menunggu penerimaan (Delivery) ===
            [
                'user_email' => 'hendra.kusuma@example.com',
                'division' => 'Teknik',
                'order_number' => 'WO-2026-0003',
                'description' => 'Permintaan perlengkapan teknik bulan Januari',
                'notes' => 'Mohon segera dikirim karena stok sudah menipis',
                'status' => WarehouseOrderStatus::Delivery,
                'accepted_date' => Carbon::parse('2026-01-20'),
                'delivery_date' => Carbon::parse('2026-01-22'),
                'delivered_by_email' => 'fajar.nugroho@example.com',
                'receipt_date' => null,
                'received_by_email' => null,
                'items' => [
                    ['name' => 'Pulpen Hitam', 'quantity' => 10, 'delivered_quantity' => 10, 'received_quantity' => null],
                    ['name' => 'Flashdisk 32GB', 'quantity' => 3, 'delivered_quantity' => 3, 'received_quantity' => null],
                    ['name' => 'Stop Kontak 4 Lubang', 'quantity' => 2, 'delivered_quantity' => 2, 'received_quantity' => null],
                ],
                'created_at' => Carbon::parse('2026-01-18'),
            ],

            // === Permintaan yang sudah dikonfirmasi/disetujui (Confirmed) ===
            [
                'user_email' => 'mega.sari@example.com',
                'division' => 'Pelayanan',
                'order_number' => 'WO-2026-0004',
                'description' => 'Kebutuhan ATK divisi Pelayanan bulan Februari',
                'notes' => null,
                'status' => WarehouseOrderStatus::Confirmed,
                'accepted_date' => Carbon::parse('2026-02-03'),
                'delivery_date' => null,
                'delivered_by_email' => null,
                'receipt_date' => null,
                'received_by_email' => null,
                'items' => [
                    ['name' => 'Kertas HVS A4 70gr (Rim)', 'quantity' => 8, 'delivered_quantity' => null, 'received_quantity' => null],
                    ['name' => 'Pulpen Hitam', 'quantity' => 10, 'delivered_quantity' => null, 'received_quantity' => null],
                    ['name' => 'Pulpen Biru', 'quantity' => 8, 'delivered_quantity' => null, 'received_quantity' => null],
                    ['name' => 'Amplop Putih Besar', 'quantity' => 20, 'delivered_quantity' => null, 'received_quantity' => null],
                ],
                'created_at' => Carbon::parse('2026-02-01'),
            ],

            // === Permintaan yang masih pending ===
            [
                'user_email' => 'ryan.hidayat@example.com',
                'division' => 'IT',
                'order_number' => 'WO-2026-0005',
                'description' => 'Kebutuhan peralatan IT bulan Februari',
                'notes' => 'Untuk penggantian peralatan yang rusak',
                'status' => WarehouseOrderStatus::Pending,
                'accepted_date' => null,
                'delivery_date' => null,
                'delivered_by_email' => null,
                'receipt_date' => null,
                'received_by_email' => null,
                'items' => [
                    ['name' => 'Mouse USB', 'quantity' => 5, 'delivered_quantity' => null, 'received_quantity' => null],
                    ['name' => 'Keyboard USB', 'quantity' => 3, 'delivered_quantity' => null, 'received_quantity' => null],
                    ['name' => 'Kabel LAN Cat6 (Meter)', 'quantity' => 100, 'delivered_quantity' => null, 'received_quantity' => null],
                    ['name' => 'Toner Printer HP LaserJet', 'quantity' => 2, 'delivered_quantity' => null, 'received_quantity' => null],
                ],
                'created_at' => Carbon::parse('2026-02-08'),
            ],
            [
                'user_email' => 'agus.prabowo@example.com',
                'division' => 'Kepegawaian',
                'order_number' => 'WO-2026-0006',
                'description' => 'Permintaan perlengkapan kantor divisi Kepegawaian',
                'notes' => null,
                'status' => WarehouseOrderStatus::Pending,
                'accepted_date' => null,
                'delivery_date' => null,
                'delivered_by_email' => null,
                'receipt_date' => null,
                'received_by_email' => null,
                'items' => [
                    ['name' => 'Kertas HVS A4 70gr (Rim)', 'quantity' => 5, 'delivered_quantity' => null, 'received_quantity' => null],
                    ['name' => 'Stapler', 'quantity' => 2, 'delivered_quantity' => null, 'received_quantity' => null],
                    ['name' => 'Isi Stapler No. 10', 'quantity' => 5, 'delivered_quantity' => null, 'received_quantity' => null],
                    ['name' => 'Correction Pen', 'quantity' => 5, 'delivered_quantity' => null, 'received_quantity' => null],
                    ['name' => 'Pensil 2B', 'quantity' => 10, 'delivered_quantity' => null, 'received_quantity' => null],
                ],
                'created_at' => Carbon::parse('2026-02-10'),
            ],

            // === Permintaan yang ditolak (Rejected) ===
            [
                'user_email' => 'eko.prasetyo@example.com',
                'division' => 'Teknik',
                'order_number' => 'WO-2026-0007',
                'description' => 'Permintaan furnitur tambahan divisi Teknik',
                'notes' => 'Ditolak: Stok furnitur terbatas, akan diproses bulan depan',
                'status' => WarehouseOrderStatus::Rejected,
                'accepted_date' => null,
                'delivery_date' => null,
                'delivered_by_email' => null,
                'receipt_date' => null,
                'received_by_email' => null,
                'items' => [
                    ['name' => 'Kursi Kantor Staff', 'quantity' => 3, 'delivered_quantity' => null, 'received_quantity' => null],
                    ['name' => 'Meja Kerja 120x60', 'quantity' => 2, 'delivered_quantity' => null, 'received_quantity' => null],
                ],
                'created_at' => Carbon::parse('2026-01-25'),
            ],

            // === Permintaan yang sedang direvisi (Revision) ===
            [
                'user_email' => 'siti.rahayu@example.com',
                'division' => 'Tata Usaha',
                'order_number' => 'WO-2026-0008',
                'description' => 'Permintaan perlengkapan kebersihan dan dapur',
                'notes' => 'Mohon revisi jumlah, terlalu banyak untuk kebutuhan 1 bulan',
                'status' => WarehouseOrderStatus::Revision,
                'accepted_date' => null,
                'delivery_date' => null,
                'delivered_by_email' => null,
                'receipt_date' => null,
                'received_by_email' => null,
                'items' => [
                    ['name' => 'Cairan Pembersih Lantai', 'quantity' => 10, 'delivered_quantity' => null, 'received_quantity' => null],
                    ['name' => 'Sabun Cuci Tangan', 'quantity' => 8, 'delivered_quantity' => null, 'received_quantity' => null],
                    ['name' => 'Tisu Toilet (Roll)', 'quantity' => 30, 'delivered_quantity' => null, 'received_quantity' => null],
                    ['name' => 'Kopi Sachet', 'quantity' => 5, 'delivered_quantity' => null, 'received_quantity' => null],
                    ['name' => 'Gula Pasir', 'quantity' => 3, 'delivered_quantity' => null, 'received_quantity' => null],
                    ['name' => 'Air Mineral Galon', 'quantity' => 5, 'delivered_quantity' => null, 'received_quantity' => null],
                ],
                'created_at' => Carbon::parse('2026-02-05'),
            ],

            // === Permintaan terbaru (Pending) ===
            [
                'user_email' => 'rudi.hartono@example.com',
                'division' => 'Keuangan',
                'order_number' => 'WO-2026-0009',
                'description' => 'Permintaan ATK dan tinta printer divisi Keuangan',
                'notes' => 'Tinta printer sudah habis, mohon diprioritaskan',
                'status' => WarehouseOrderStatus::Pending,
                'accepted_date' => null,
                'delivery_date' => null,
                'delivered_by_email' => null,
                'receipt_date' => null,
                'received_by_email' => null,
                'items' => [
                    ['name' => 'Tinta Printer Epson Hitam', 'quantity' => 3, 'delivered_quantity' => null, 'received_quantity' => null],
                    ['name' => 'Tinta Printer Epson Warna', 'quantity' => 1, 'delivered_quantity' => null, 'received_quantity' => null],
                    ['name' => 'Kertas HVS A4 70gr (Rim)', 'quantity' => 10, 'delivered_quantity' => null, 'received_quantity' => null],
                    ['name' => 'Binder Clip Sedang (Kotak)', 'quantity' => 5, 'delivered_quantity' => null, 'received_quantity' => null],
                ],
                'created_at' => Carbon::parse('2026-02-11'),
            ],
            [
                'user_email' => 'andi.pratama@example.com',
                'division' => 'Pelayanan',
                'order_number' => 'WO-2026-0010',
                'description' => 'Permintaan perlengkapan dapur dan kebersihan ruang pelayanan',
                'notes' => null,
                'status' => WarehouseOrderStatus::Pending,
                'accepted_date' => null,
                'delivery_date' => null,
                'delivered_by_email' => null,
                'receipt_date' => null,
                'received_by_email' => null,
                'items' => [
                    ['name' => 'Gelas Plastik Sekali Pakai', 'quantity' => 5, 'delivered_quantity' => null, 'received_quantity' => null],
                    ['name' => 'Kopi Sachet', 'quantity' => 3, 'delivered_quantity' => null, 'received_quantity' => null],
                    ['name' => 'Teh Celup', 'quantity' => 3, 'delivered_quantity' => null, 'received_quantity' => null],
                    ['name' => 'Gula Pasir', 'quantity' => 2, 'delivered_quantity' => null, 'received_quantity' => null],
                    ['name' => 'Tissue Makan (Pak)', 'quantity' => 5, 'delivered_quantity' => null, 'received_quantity' => null],
                    ['name' => 'Trash Bag Hitam (Pak)', 'quantity' => 3, 'delivered_quantity' => null, 'received_quantity' => null],
                ],
                'created_at' => Carbon::parse('2026-02-11'),
            ],
        ];

        foreach ($orders as $orderData) {
            $user = $users->firstWhere('email', $orderData['user_email']);
            $division = $divisions->firstWhere('name', $orderData['division']);

            if (!$user || !$division) {
                continue;
            }

            $deliveredBy = $orderData['delivered_by_email']
                ? $users->firstWhere('email', $orderData['delivered_by_email'])?->id
                : null;

            $receivedBy = $orderData['received_by_email']
                ? $users->firstWhere('email', $orderData['received_by_email'])?->id
                : null;

            $order = WarehouseOrder::firstOrCreate(
                ['order_number' => $orderData['order_number']],
                [
                    'user_id' => $user->id,
                    'division_id' => $division->id,
                    'order_number' => $orderData['order_number'],
                    'description' => $orderData['description'],
                    'notes' => $orderData['notes'],
                    'status' => $orderData['status'],
                    'accepted_date' => $orderData['accepted_date'],
                    'delivery_date' => $orderData['delivery_date'],
                    'delivered_by' => $deliveredBy,
                    'receipt_date' => $orderData['receipt_date'],
                    'received_by' => $receivedBy,
                    'created_at' => $orderData['created_at'],
                    'updated_at' => $orderData['created_at'],
                ]
            );

            // Buat cart items untuk order ini
            foreach ($orderData['items'] as $cartItem) {
                $item = $warehouseItems->firstWhere('name', $cartItem['name']);
                if (!$item) {
                    continue;
                }

                WarehouseOrderCart::firstOrCreate(
                    [
                        'warehouse_order_id' => $order->id,
                        'item_id' => $item->id,
                    ],
                    [
                        'quantity' => $cartItem['quantity'],
                        'delivered_quantity' => $cartItem['delivered_quantity'],
                        'received_quantity' => $cartItem['received_quantity'],
                        'notes' => null,
                    ]
                );
            }
        }

        $this->command->info('Warehouse order seeder berhasil dijalankan.');
    }
}
