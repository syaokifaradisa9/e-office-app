<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // User Permissions
        $userPermissions = [
            'view_users',
            'delete_users',
            'manage_users',
        ];

        // Role Permissions
        $rolePermissions = [
            'view_roles',
            'delete_roles',
            'manage_roles',
        ];

        // Division Permissions
        $divisionPermissions = [
            'view_divisions',
            'delete_divisions',
            'manage_divisions',
        ];

        // Position Permissions
        $positionPermissions = [
            'view_positions',
            'delete_positions',
            'manage_positions',
        ];

        // Profile Permissions
        $profilePermissions = [
            'edit_profile',
            'change_password',
        ];

        // Dashboard Permissions
        $dashboardPermissions = [
            'view_dashboard',
        ];

        // Combine all permissions
        $allPermissions = array_merge(
            $dashboardPermissions,
            $userPermissions,
            $rolePermissions,
            $divisionPermissions,
            $positionPermissions,
            $profilePermissions
        );

        // Create permissions
        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }
}
