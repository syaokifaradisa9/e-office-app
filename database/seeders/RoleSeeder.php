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
            // Dashboard
            'view_dashboard',
            // Users
            'view_users',
            'delete_users',
            'manage_users',
            // Roles
            'view_roles',
            'delete_roles',
            'manage_roles',
            // Divisions
            'view_divisions',
            'delete_divisions',
            'manage_divisions',
            // Positions
            'view_positions',
            'delete_positions',
            'manage_positions',
            // Profile
            'edit_profile',
            'change_password',
        ]);

        // Admin - Limited admin access
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin->syncPermissions([
            'view_dashboard',
            'view_users',
            'manage_users',
            'view_roles',
            'view_divisions',
            'manage_divisions',
            'view_positions',
            'manage_positions',
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
