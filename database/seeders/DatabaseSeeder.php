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
            \Modules\Archieve\Database\Seeders\ArchievePermissionSeeder::class,
            \Modules\Inventory\Database\Seeders\InventoryPermissionSeeder::class,
            \Modules\Ticketing\Database\Seeders\TicketingPermissionSeeder::class,
            \Modules\VisitorManagement\Database\Seeders\VisitorPermissionSeeder::class,
            
            // User Seeder (Depends on Roles/Permissions)
            UserSeeder::class,
            EmployeeSeeder::class,
            
            // Module Specific Seeders
            \Modules\Ticketing\Database\Seeders\AssetCategorySeeder::class,
            \Modules\Ticketing\Database\Seeders\AssetItemSeeder::class,
            \Modules\Archieve\Database\Seeders\ArchieveCategorySeeder::class,
            \Modules\Archieve\Database\Seeders\DocumentClassificationSeeder::class,
            \Modules\Archieve\Database\Seeders\DocumentSeeder::class,
            
            // Inventory module data
            \Modules\Inventory\Database\Seeders\InventoryDatabaseSeeder::class,
            
            // Visitor management data
            \Modules\VisitorManagement\Database\Seeders\VisitorManagementDatabaseSeeder::class,
        ]);
    }
}
