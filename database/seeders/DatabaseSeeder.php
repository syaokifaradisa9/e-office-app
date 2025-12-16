<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Inventory\Database\Seeders\CategoryItemSeeder;
use Modules\Inventory\Database\Seeders\InventoryPermissionSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            InventoryPermissionSeeder::class,
            RoleSeeder::class,
            DivisionSeeder::class,
            PositionSeeder::class,
            UserSeeder::class,
            CategoryItemSeeder::class,
        ]);
    }
}
