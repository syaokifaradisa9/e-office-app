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
        $permissions = [
            // Division Permissions
            'lihat_divisi',
            'kelola_divisi',
            
            // Position Permissions
            'lihat_jabatan',
            'kelola_jabatan',
            
            // User Permissions
            'lihat_pengguna',
            'kelola_pengguna',
            
            // Role Permissions
            'lihat_role',
            'kelola_role',
            
            // Profile Permissions
            'lihat_profil',
            'kelola_profil',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }
}
