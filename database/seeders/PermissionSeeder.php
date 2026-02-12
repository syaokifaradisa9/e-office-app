<?php

namespace Database\Seeders;

use App\Enums\DivisionRolePermission;
use App\Enums\PositionRolePermission;
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
            ...DivisionRolePermission::values(),
            
            // Position Permissions
            ...PositionRolePermission::values(),
            
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

            // Archieve Dashboard Permissions
            'lihat_dashboard_arsip_divisi',
            'lihat_dashboard_arsip_keseluruhan',

            // Archieve Report Permissions
            'lihat_laporan_arsip_divisi',
            'lihat_laporan_arsip_keseluruhan',

            // Archieve Document Permissions
            'lihat_arsip_dokumen',
            'kelola_arsip_dokumen',
            'cari_arsip_dokumen',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }
}
