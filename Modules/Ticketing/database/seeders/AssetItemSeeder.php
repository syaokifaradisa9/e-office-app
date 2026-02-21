<?php

namespace Modules\Ticketing\Database\Seeders;

use App\Models\User;
use App\Models\Division;
use Illuminate\Database\Seeder;
use Modules\Ticketing\Models\AssetItem;
use Modules\Ticketing\Models\AssetCategory;

class AssetItemSeeder extends Seeder
{
    public function run(): void
    {
        // Get Divisions
        $itDivision = Division::where('name', 'IT')->first();
        $tuDivision = Division::where('name', 'Tata Usaha')->first();

        // Get Users for specific divisions
        $itUser = User::where('division_id', $itDivision?->id)->first() ?? User::where('email', 'superadmin@gmail.com')->first();
        $tuUser = User::where('division_id', $tuDivision?->id)->first() ?? User::where('email', 'pimpinan@gmail.com')->first();

        // Define items to seed
        $items = [
            [
                'model_name' => 'Laptop',
                'merk' => 'Dell',
                'model' => 'Latitude 5420',
                'serial_number' => 'DEL-LT-001',
                'division_id' => $itDivision?->id,
                'users' => [$itUser?->id],
                'attributes' => ['Processor' => 'Intel i7', 'RAM' => '16GB', 'Storage' => '512GB SSD']
            ],
            [
                'model_name' => 'Laptop',
                'merk' => 'MacBook',
                'model' => 'Pro M2',
                'serial_number' => 'MAC-LT-002',
                'division_id' => $itDivision?->id,
                'users' => [$itUser?->id],
                'attributes' => ['Processor' => 'Apple M2', 'RAM' => '16GB', 'Storage' => '512GB SSD']
            ],
            [
                'model_name' => 'Printer',
                'merk' => 'Epson',
                'model' => 'L3210',
                'serial_number' => 'EPS-PR-001',
                'division_id' => $itDivision?->id,
                'users' => [],
                'attributes' => ['Type' => 'Ink Tank', 'Color' => 'Yes']
            ],
            [
                'model_name' => 'Air Conditioner',
                'merk' => 'Daikin',
                'model' => 'FTKQ25',
                'serial_number' => 'DAI-AC-001',
                'division_id' => $tuDivision?->id,
                'users' => [],
                'attributes' => ['Capacity' => '1 PK', 'Type' => 'Inverter']
            ],
            [
                'model_name' => 'Mobil',
                'merk' => 'Toyota',
                'model' => 'Innova Zenix',
                'serial_number' => 'B 1234 ABC',
                'division_id' => $tuDivision?->id,
                'users' => [$tuUser?->id],
                'attributes' => ['Year' => '2024', 'Color' => 'Black', 'Fuel' => 'Gasoline']
            ],
            [
                'model_name' => 'Meja',
                'merk' => 'IKEA',
                'model' => 'Bekant',
                'serial_number' => 'IKE-MJ-001',
                'division_id' => $tuDivision?->id,
                'users' => [$tuUser?->id],
                'attributes' => ['Material' => 'Wood', 'Size' => '160x80cm']
            ],
            [
                'model_name' => 'Kursi',
                'merk' => 'Informa',
                'model' => 'Ergonomic X',
                'serial_number' => 'INF-KS-001',
                'division_id' => $tuDivision?->id,
                'users' => [$tuUser?->id],
                'attributes' => ['Material' => 'Mesh', 'Feature' => 'Headrest']
            ],
        ];

        foreach ($items as $item) {
            $assetCategory = AssetCategory::where('name', $item['model_name'])->first();

            if ($assetCategory) {
                $assetItem = AssetItem::updateOrCreate(
                    ['serial_number' => $item['serial_number']],
                    [
                        'asset_category_id' => $assetCategory->id,
                        'merk' => $item['merk'],
                        'model' => $item['model'],
                        'division_id' => $item['division_id'] ?? $assetCategory->division_id,
                        'another_attributes' => $item['attributes'],
                    ]
                );

                if (!empty($item['users'])) {
                    // Filter out null IDs
                    $userIds = array_filter($item['users']);
                    if (!empty($userIds)) {
                        $assetItem->users()->sync($userIds);
                    }
                }
            }
        }
    }
}
