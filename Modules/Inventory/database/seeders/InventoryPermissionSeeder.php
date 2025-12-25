<?php

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inventory\Enums\InventoryPermission;
use Spatie\Permission\Models\Permission;

class InventoryPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Get all permissions from Enum
        $allPermissions = InventoryPermission::values();

        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }


    }
}
