<?php

namespace Modules\Archieve\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Archieve\Enums\ArchieveUserPermission;
use Spatie\Permission\Models\Permission;

class ArchievePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = ArchieveUserPermission::values();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }
}
