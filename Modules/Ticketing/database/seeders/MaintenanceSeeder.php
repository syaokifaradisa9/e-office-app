<?php

namespace Modules\Ticketing\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Ticketing\Models\Maintenance;
use Modules\Ticketing\Models\AssetItem;
use Modules\Ticketing\Models\AssetItemRefinement;
use Modules\Ticketing\Enums\MaintenanceStatus;
use Carbon\Carbon;

class MaintenanceSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $assets = AssetItem::all();

        if ($users->isEmpty() || $assets->isEmpty()) {
            return;
        }

        for ($i = 0;$i < 15;$i++) {
            $user = $users->random();
            $asset = $assets->random();
            
            // Randomly pick a status, with bias towards PENDING and FINISH
            $statuses = [
                MaintenanceStatus::PENDING, 
                MaintenanceStatus::PENDING,
                MaintenanceStatus::REFINEMENT,
                MaintenanceStatus::FINISH, 
                MaintenanceStatus::CONFIRMED
            ];
            $status = $statuses[array_rand($statuses)];

            // Estimation dates: Some in the past (overdue), some in this month, some in the future
            $dateChoices = [
                Carbon::now()->subDays(rand(5, 30)), // Overdue
                Carbon::now()->addDays(rand(0, 10)), // This month
                Carbon::now()->addDays(rand(20, 45)), // Future
            ];
            $estimationDate = $dateChoices[array_rand($dateChoices)]->clone(); // clone to avoid mutation

            $maintenance = Maintenance::create([
                'asset_item_id' => $asset->id,
                'user_id' => $user->id,
                'status' => $status,
                'estimation_date' => $estimationDate,
                'actual_date' => in_array($status, [MaintenanceStatus::FINISH, MaintenanceStatus::CONFIRMED]) ? $estimationDate->clone()->addDays(rand(0, 2)) : null,
                'note' => 'Pengecekan rutin kebersihan dan performa perangkat.',
                'checklist_results' => [
                    ['item' => 'Kebersihan Luar', 'status' => 'OK'],
                    ['item' => 'Fungsi Utama', 'status' => 'OK'],
                ],
            ]);

            // Add Refinement if status is refinement
            if ($status === MaintenanceStatus::REFINEMENT) {
                AssetItemRefinement::create([
                    'maintenance_id' => $maintenance->id,
                    'date' => Carbon::now()->subDays(rand(0, 2)),
                    'description' => 'Pembersihan mendalam dan ganti pasta',
                    'note' => 'Perlu penanganan khusus di lab IT',
                    'result' => 'Pending sparepart',
                ]);
            }
        }

        // --- SEED SPECIFIC DATA FOR SUPER ADMIN ---
        $superAdmin = User::where('email', 'superadmin@gmail.com')->first();
        if ($superAdmin) {
            $adminAssets = AssetItem::limit(3)->get();
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
