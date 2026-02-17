<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Inventory\Database\Seeders\CategoryItemSeeder;
use Modules\Inventory\Database\Seeders\InventoryPermissionSeeder;
use Modules\Inventory\Database\Seeders\ItemSeeder;
use Modules\Inventory\Database\Seeders\WarehouseOrderSeeder;
use Modules\Archieve\Database\Seeders\CategoryContextSeeder;
use Modules\Archieve\Database\Seeders\DocumentClassificationSeeder;
use Modules\VisitorManagement\Database\Seeders\VisitorManagementDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            DivisionSeeder::class,
            InventoryModuleSeeder::class,
            CategoryItemSeeder::class,
            ItemSeeder::class,
            WarehouseOrderSeeder::class,
            CategoryContextSeeder::class,
            DocumentClassificationSeeder::class,
            VisitorManagementDatabaseSeeder::class,
        ]);
    }
}
