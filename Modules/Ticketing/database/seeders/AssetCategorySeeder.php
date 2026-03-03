<?php

namespace Modules\Ticketing\Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Seeder;
use Modules\Ticketing\Models\AssetCategory;
use Modules\Ticketing\Models\Checklist;
use Modules\Ticketing\Enums\AssetCategoryType;

class AssetCategorySeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan division ada
        $itDivision = Division::where('name', 'IT')->first() ?? Division::create(['name' => 'IT', 'description' => 'Information Technology', 'is_active' => true]);
        $tuDivision = Division::where('name', 'Tata Usaha')->first() ?? Division::create(['name' => 'Tata Usaha', 'description' => 'Bagian Umum / Tata Usaha', 'is_active' => true]);

        $categories = [
            // IT Assets with Checklists
            [
                'name' => 'Laptop',
                'type' => AssetCategoryType::Physic,
                'division_id' => $itDivision->id,
                'maintenance_count' => 4, // Kuartal (3 bulan sekali)
                'checklists' => [
                    'Kondisi Layar',
                    'Fungsi Keyboard',
                    'Fungsi Touchpad',
                    'Kondisi Fisik Unit',
                    'Kesehatan Baterai',
                    'Kelengkapan Charger',
                ]
            ],
            [
                'name' => 'Printer',
                'type' => AssetCategoryType::Physic,
                'division_id' => $itDivision->id,
                'maintenance_count' => 3, // 4 bulan sekali
                'checklists' => [
                    'Ketersediaan Tinta/Toner',
                    'Kebersihan Roller & Feed Tray',
                    'Kestabilan Koneksi (USB/Lan/Wifi)',
                    'Kualitas Hasil Cetak',
                ]
            ],
            
            // TU Assets with Checklists
            [
                'name' => 'Air Conditioner',
                'type' => AssetCategoryType::Physic,
                'division_id' => $tuDivision->id,
                'maintenance_count' => 2, // 6 bulan sekali
                'checklists' => [
                    'Kebersihan Filter Udara',
                    'Kondisi Unit Outdoor',
                    'Kondisi Unit Indoor',
                    'Fungsi Remote Control',
                    'Suhu Udara & Kapasitas Freon',
                ]
            ],
            [
                'name' => 'Mobil',
                'type' => AssetCategoryType::Physic,
                'division_id' => $tuDivision->id,
                'maintenance_count' => 3, // 4 bulan sekali
                'checklists' => [
                    'Kondisi Mesin & Cairan (Oli/Air)',
                    'Tekanan Ban & Serep',
                    'Fungsi Rem & Kopling',
                    'Kebersihan Interior & AC Mobil',
                    'Fungsi Lampu & Kelistrikan',
                ]
            ],

            // Physic Assets without Checklists
            [
                'name' => 'Meja',
                'type' => AssetCategoryType::Physic,
                'division_id' => $tuDivision->id,
                'maintenance_count' => 0,
                'checklists' => []
            ],
            [
                'name' => 'Kursi',
                'type' => AssetCategoryType::Physic,
                'division_id' => $tuDivision->id,
                'maintenance_count' => 0,
                'checklists' => []
            ],

            // Other existing ones
            [
                'name' => 'PC Desktop',
                'type' => AssetCategoryType::Physic,
                'division_id' => $itDivision->id,
                'maintenance_count' => 4, // Kuartal (3 bulan sekali)
                'checklists' => ['Kondisi CPU', 'Kondisi Monitor', 'Keyboard & Mouse']
            ],
            [
                'name' => 'Switch Networking',
                'type' => AssetCategoryType::Physic,
                'division_id' => $itDivision->id,
                'maintenance_count' => 2, // 6 bulan sekali
                'checklists' => ['Power Status', 'Kondisi Port']
            ],
            [
                'name' => 'Email Account',
                'type' => AssetCategoryType::Digital,
                'division_id' => $itDivision->id,
                'maintenance_count' => 0,
                'checklists' => []
            ],
            [
                'name' => 'VPS Server',
                'type' => AssetCategoryType::Digital,
                'division_id' => $itDivision->id,
                'maintenance_count' => 0,
                'checklists' => []
            ],
        ];

        foreach ($categories as $item) {
            $assetCategory = AssetCategory::updateOrCreate(
                ['name' => $item['name']],
                [
                    'type' => $item['type'],
                    'division_id' => $item['division_id'],
                    'maintenance_count' => $item['maintenance_count'],
                ]
            );

            // Create checklists
            if (!empty($item['checklists'])) {
                foreach ($item['checklists'] as $label) {
                    Checklist::updateOrCreate(
                        [
                            'asset_category_id' => $assetCategory->id,
                            'label' => $label
                        ],
                        [
                            'description' => 'Standard maintenance checklist for ' . $item['name']
                        ]
                    );
                }
            }
        }
    }
}
