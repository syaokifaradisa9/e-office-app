<?php

namespace Modules\Ticketing\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Ticketing\Models\Maintenance;
use Modules\Ticketing\Models\AssetItem;
use Modules\Ticketing\Models\AssetCategory;
use Modules\Ticketing\Models\AssetItemRefinement;
use Modules\Ticketing\Enums\MaintenanceStatus;
use Carbon\Carbon;

class MaintenanceSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            return;
        }

        // Ambil semua kategori yang memiliki maintenance_count > 0
        $categories = AssetCategory::where('maintenance_count', '>', 0)->get();

        foreach ($categories as $category) {
            $count = $category->maintenance_count;
            $periodLength = 12 / $count; // Jarak bulan antar maintenance

            // Ambil semua asset item di kategori ini
            $assetItems = AssetItem::where('asset_category_id', $category->id)->get();

            foreach ($assetItems as $assetItem) {
                $user = $users->random();

                // Generate jadwal maintenance sesuai maintenance_count
                for ($i = 0; $i < $count; $i++) {
                    $targetMonth = 1 + ($i * $periodLength);
                    $estimationDate = Carbon::create(now()->year, (int)$targetMonth, 1)->startOfDay();

                    // Tentukan status berdasarkan apakah tanggal sudah lewat atau belum
                    if ($estimationDate->lt(now())) {
                        // Jadwal sudah lewat: random antara FINISH dan CONFIRMED
                        $statuses = [
                            MaintenanceStatus::FINISH,
                            MaintenanceStatus::CONFIRMED,
                        ];
                        $status = $statuses[array_rand($statuses)];

                        $maintenance = Maintenance::create([
                            'asset_item_id' => $assetItem->id,
                            'user_id' => $user->id,
                            'status' => $status,
                            'estimation_date' => $estimationDate,
                            'actual_date' => $estimationDate->clone()->addDays(rand(0, 5)),
                            'note' => 'Pengecekan rutin ' . $category->name . ' - Periode ' . ($i + 1),
                            'checklist_results' => [
                                ['item' => 'Kebersihan Luar', 'status' => 'OK'],
                                ['item' => 'Fungsi Utama', 'status' => 'OK'],
                            ],
                        ]);
                    } else {
                        // Jadwal belum lewat: PENDING
                        Maintenance::create([
                            'asset_item_id' => $assetItem->id,
                            'user_id' => $user->id,
                            'status' => MaintenanceStatus::PENDING,
                            'estimation_date' => $estimationDate,
                            'note' => 'Jadwal maintenance rutin ' . $category->name . ' - Periode ' . ($i + 1),
                            'checklist_results' => null,
                        ]);
                    }
                }
            }
        }

        // Tambahkan beberapa maintenance dengan status REFINEMENT untuk variasi
        $allAssets = AssetItem::whereHas('assetCategory', fn ($q) => $q->where('maintenance_count', '>', 0))->get();

        if ($allAssets->count() >= 2) {
            $refinementAsset = $allAssets->random();
            $user = $users->random();

            $maintenance = Maintenance::create([
                'asset_item_id' => $refinementAsset->id,
                'user_id' => $user->id,
                'status' => MaintenanceStatus::REFINEMENT,
                'estimation_date' => Carbon::now()->subDays(rand(5, 15)),
                'actual_date' => Carbon::now()->subDays(rand(1, 5)),
                'note' => 'Ditemukan kerusakan pada saat pengecekan rutin.',
                'checklist_results' => [
                    ['item' => 'Kebersihan Luar', 'status' => 'OK'],
                    ['item' => 'Fungsi Utama', 'status' => 'Bad'],
                ],
            ]);

            AssetItemRefinement::create([
                'maintenance_id' => $maintenance->id,
                'date' => Carbon::now()->subDays(rand(0, 2)),
                'description' => 'Pembersihan mendalam dan penggantian komponen',
                'note' => 'Perlu penanganan khusus',
                'result' => 'Pending sparepart',
            ]);
        }

        // --- SEED SPECIFIC DATA FOR SUPER ADMIN ---
        $superAdmin = User::where('email', 'superadmin@gmail.com')->first();
        if ($superAdmin) {
            $adminAssets = AssetItem::whereHas('assetCategory', fn ($q) => $q->where('maintenance_count', '>', 0))
                ->limit(3)
                ->get();

            // Assign some assets to Super Admin
            foreach ($adminAssets as $asset) {
                $asset->users()->syncWithoutDetaching([$superAdmin->id]);
            }

            foreach ($adminAssets as $asset) {
                Maintenance::create([
                    'asset_item_id' => $asset->id,
                    'user_id' => $superAdmin->id,
                    'status' => MaintenanceStatus::PENDING,
                    'estimation_date' => Carbon::now()->addDays(rand(1, 10)),
                    'note' => 'Maintenance rutin aset Super Admin.',
                    'checklist_results' => [['item' => 'Cek Fisik', 'status' => 'OK']],
                ]);
            }
        }
    }
}
