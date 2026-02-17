<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Inventory\Database\Seeders\InventoryDatabaseSeeder;
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
            UserSeeder::class,
            InventoryDatabaseSeeder::class,
            CategoryContextSeeder::class,
            DocumentClassificationSeeder::class,
            VisitorManagementDatabaseSeeder::class,
        ]);
    }
}
