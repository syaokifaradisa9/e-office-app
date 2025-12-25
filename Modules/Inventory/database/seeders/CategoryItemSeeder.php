<?php

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inventory\Models\CategoryItem;

class CategoryItemSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Alat Tulis Kantor', 'description' => 'Pena, pensil, kertas, dan perlengkapan tulis lainnya', 'is_active' => true],
            ['name' => 'Elektronik', 'description' => 'Peralatan elektronik seperti komputer, printer, dll', 'is_active' => true],
            ['name' => 'Furnitur', 'description' => 'Meja, kursi, lemari, dan perabot kantor', 'is_active' => true],
            ['name' => 'Perlengkapan Kebersihan', 'description' => 'Sapu, pel, pembersih, dan perlengkapan kebersihan', 'is_active' => true],
            ['name' => 'Perlengkapan Dapur', 'description' => 'Gelas, piring, sendok, dan perlengkapan dapur', 'is_active' => true],
        ];

        foreach ($categories as $category) {
            CategoryItem::firstOrCreate(['name' => $category['name']], $category);
        }
    }
}
