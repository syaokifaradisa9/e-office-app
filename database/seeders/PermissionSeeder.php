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

        // Dashboard Permissions
        $dashboardPermissions = [
            'view_dashboard',               // Melihat Dashboard
        ];

        // User Permissions
        $userPermissions = [
            'view_users',                   // Melihat Pengguna
            'manage_users',                 // Mengelola Pengguna
        ];

        // Role Permissions
        $rolePermissions = [
            'view_roles',                   // Lihat Role
            'manage_roles',                 // Mengelola Role
        ];

        // Profile Permissions
        $profilePermissions = [
            'edit_profile',                 // Edit Profil
            'change_password',              // Ubah Password
        ];

        // Combine all permissions
        $allPermissions = array_merge(
            $dashboardPermissions,
            $userPermissions,
            $rolePermissions,
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
