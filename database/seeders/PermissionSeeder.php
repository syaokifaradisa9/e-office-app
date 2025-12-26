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

            // Archieve Module Permissions
            'lihat_kategori_arsip',
            'kelola_kategori_arsip',
            'lihat_klasifikasi_arsip',
            'kelola_klasifikasi_arsip',
            'lihat_penyimpanan_divisi',
            'kelola_penyimpanan_divisi',
            'lihat_semua_arsip',
            'kelola_semua_arsip',
            'lihat_arsip_divisi',
            'kelola_arsip_divisi',
            'lihat_arsip_pribadi',
            'pencarian_dokumen_keseluruhan',
            'pencarian_dokumen_divisi',
            'pencarian_dokumen_pribadi',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }
}
