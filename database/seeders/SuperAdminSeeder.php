<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Inventory\Enums\InventoryPermission;
use Modules\VisitorManagement\Enums\VisitorUserPermission;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Base Permissions
        $permissions = [
            'lihat_divisi',
            'kelola_divisi',
            'lihat_jabatan',
            'kelola_jabatan',
            'lihat_pengguna',
            'kelola_pengguna',
            'lihat_role',
            'kelola_role',
        ];

        // 2. Add all Module Permissions from Enums
        $inventoryPermissions = InventoryPermission::values();
        $visitorPermissions = VisitorUserPermission::values();
        $allPermissions = array_unique(array_merge($permissions, $inventoryPermissions, $visitorPermissions));

        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // 3. Create Role and Assign Permissions
        $role = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $role->syncPermissions($allPermissions);

        // 4. Create SuperAdmin User
        $user = User::updateOrCreate(
            ['email' => 'syaokifaradisa09@gmail.com'],
            [
                'name' => 'Muhammad Syaoki Faradisa, S.Kom',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $user->assignRole($role);
    }
}
