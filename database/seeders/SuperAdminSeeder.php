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
        // 1. Ensure all permissions from Enums and base are created
        $basePermissions = [
            'lihat_divisi', 'kelola_divisi', 'lihat_jabatan', 'kelola_jabatan',
            'lihat_pengguna', 'kelola_pengguna', 'lihat_role', 'kelola_role'
        ];
        
        // Modules usually have their own seeders, but we ensure they exist here too
        $inventoryPermissions = InventoryPermission::values();
        $visitorPermissions = VisitorUserPermission::values();
        
        // Also include Archieve permissions (which are currently snake_case in its seeder)
        // We'll just fetch all from DB at the end to be safe, but let's create these first.
        foreach (array_merge($basePermissions, $inventoryPermissions, $visitorPermissions) as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // 2. Create/Get Role
        $role = Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'web']);
        
        // 3. Assign ALL permissions in the system to Superadmin
        $allPermissions = Permission::all();
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
