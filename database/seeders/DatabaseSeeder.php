<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Core Seeders
            DivisionSeeder::class,
            PositionSeeder::class,
            
            // Permissions Seeders (App & Modules)
            AppPermissionSeeder::class,
            \Modules\Inventory\Database\Seeders\InventoryPermissionSeeder::class,
            
            // User Seeder (Depends on Roles/Permissions)
            UserSeeder::class,
            EmployeeSeeder::class,
            
            // Module Specific User/Role Seeders
            InventoryModuleSeeder::class,
            
            // Inventory module data
            \Modules\Inventory\Database\Seeders\InventoryDatabaseSeeder::class,
        ]);
    }
}
