<?php

namespace Modules\Ticketing\Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Seeder;
use Modules\Ticketing\Models\AssetModel;
use Modules\Ticketing\Models\Checklist;
use Modules\Ticketing\Enums\AssetModelType;

class AssetModelSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan division ada
        $itDivision = Division::where('name', 'IT')->first() ?? Division::create(['name' => 'IT', 'description' => 'Information Technology', 'is_active' => true]);
        $tuDivision = Division::where('name', 'Tata Usaha')->first() ?? Division::create(['name' => 'Tata Usaha', 'description' => 'Bagian Umum / Tata Usaha', 'is_active' => true]);

        $models = [
            // IT Assets with Checklists
            [
                'name' => 'Laptop',
                'type' => AssetModelType::Physic,
                'division_id' => $itDivision->id,
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
                'type' => AssetModelType::Physic,
                'division_id' => $itDivision->id,
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
                'type' => AssetModelType::Physic,
                'division_id' => $tuDivision->id,
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
                'type' => AssetModelType::Physic,
                'division_id' => $tuDivision->id,
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
                'type' => AssetModelType::Physic,
                'division_id' => $tuDivision->id,
                'checklists' => []
            ],
            [
                'name' => 'Kursi',
                'type' => AssetModelType::Physic,
                'division_id' => $tuDivision->id,
                'checklists' => []
            ],

            // Other existing ones
            [
                'name' => 'PC Desktop',
                'type' => AssetModelType::Physic,
                'division_id' => $itDivision->id,
                'checklists' => ['Kondisi CPU', 'Kondisi Monitor', 'Keyboard & Mouse']
            ],
            [
                'name' => 'Switch Networking',
                'type' => AssetModelType::Physic,
                'division_id' => $itDivision->id,
                'checklists' => ['Power Status', 'Kondisi Port']
            ],
            [
                'name' => 'Email Account',
                'type' => AssetModelType::Digital,
                'division_id' => $itDivision->id,
                'checklists' => []
            ],
            [
                'name' => 'VPS Server',
                'type' => AssetModelType::Digital,
                'division_id' => $itDivision->id,
                'checklists' => []
            ],
        ];

        foreach ($models as $item) {
            $assetModel = AssetModel::updateOrCreate(
                ['name' => $item['name']],
                [
                    'type' => $item['type'],
                    'division_id' => $item['division_id'],
                ]
            );

            // Create checklists
            if (!empty($item['checklists'])) {
                foreach ($item['checklists'] as $label) {
                    Checklist::updateOrCreate(
                        [
                            'asset_model_id' => $assetModel->id,
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
