<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Inventory\Database\Seeders\CategoryItemSeeder;
use Modules\Inventory\Database\Seeders\InventoryPermissionSeeder;
use Modules\Archieve\Database\Seeders\CategoryContextSeeder;
use Modules\Archieve\Database\Seeders\DocumentClassificationSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            DivisionSeeder::class,
            UserSeeder::class,
            CategoryItemSeeder::class,
            InventoryPermissionSeeder::class,
            CategoryContextSeeder::class,
            DocumentClassificationSeeder::class,
            RoleSeeder::class,
            SuperAdminSeeder::class,
        ]);
    }
}
