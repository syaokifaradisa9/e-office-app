<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Superadmin - Full access
        $superadmin = Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'web']);
        $superadmin->syncPermissions([
            // All permissions
            'view_dashboard',
            'view_users',
            'manage_users',
            'view_roles',
            'manage_roles',
            'edit_profile',
            'change_password',
        ]);

        // Admin - Limited admin access
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin->syncPermissions([
            'view_dashboard',
            'view_users',
            'edit_profile',
            'change_password',
        ]);

        // User - Basic access
        $user = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web']);
        $user->syncPermissions([
            'view_dashboard',
            'edit_profile',
            'change_password',
        ]);
    }
}
