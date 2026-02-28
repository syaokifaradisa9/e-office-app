<?php

namespace Modules\Ticketing\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Ticketing\Enums\TicketingPermission;
use Spatie\Permission\Models\Permission;

class TicketingPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $allPermissions = TicketingPermission::values();

        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }
}
