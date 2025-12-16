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

        // ===================================
        // DATA MASTER MODULE
        // ===================================

        // Pengguna
        $penggunaPermissions = [
            'lihat_pengguna',
            'kelola_pengguna',
            'hapus_pengguna',
        ];

        // Role
        $rolePermissions = [
            'lihat_role',
            'kelola_role',
            'hapus_role',
        ];

        // Divisi
        $divisiPermissions = [
            'lihat_divisi',
            'kelola_divisi',
            'hapus_divisi',
        ];

        // Jabatan
        $jabatanPermissions = [
            'lihat_jabatan',
            'kelola_jabatan',
            'hapus_jabatan',
        ];

        // Profil
        $profilPermissions = [
            'edit_profil',
            'ubah_password',
        ];

        // Dashboard
        $dashboardPermissions = [
            'lihat_dashboard',
        ];

        // Combine all permissions
        $allPermissions = array_merge(
            $dashboardPermissions,
            $penggunaPermissions,
            $rolePermissions,
            $divisiPermissions,
            $jabatanPermissions,
            $profilPermissions
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
