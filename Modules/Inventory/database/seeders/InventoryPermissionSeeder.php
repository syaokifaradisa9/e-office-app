<?php

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class InventoryPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Allowed inventory permissions as per user request
        $allPermissions = [
            'lihat_kategori',
            'kelola_kategori',
            'lihat_barang',
            'kelola_barang',
            'konversi_barang',
            'keluarkan_stok',
            'lihat_permintaan_barang_divisi',
            'lihat_semua_permintaan_barang',
            'buat_permintaan_barang',
            'konfirmasi_permintaan_barang',
            'serah_terima_barang',
            'terima_barang',
        ];

        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }
}
