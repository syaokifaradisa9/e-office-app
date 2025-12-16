<?php

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class InventoryPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // ===================================
        // SISTEM MANAJEMEN GUDANG MODULE
        // ===================================

        // Dashboard Gudang
        $dashboardGudangPermissions = [
            'lihat_dashboard_gudang_utama',
            'lihat_dashboard_gudang_divisi',
        ];

        // Kategori Barang
        $kategoriBarangPermissions = [
            'lihat_kategori_barang',
            'kelola_kategori_barang',
        ];

        // Barang
        $barangPermissions = [
            'lihat_barang',
            'kelola_barang',
            'keluarkan_stok',
        ];

        // Permintaan Barang
        $permintaanBarangPermissions = [
            'lihat_permintaan_barang',
            'lihat_semua_permintaan_barang',
            'buat_permintaan_barang',
            'konfirmasi_permintaan_barang',
            'serah_terima_barang',
            'terima_barang',
        ];

        // Stock Opname
        $stockOpnamePermissions = [
            'lihat_stock_opname_gudang',
            'lihat_stock_opname_divisi',
            'lihat_semua_stock_opname',
            'kelola_stock_opname_gudang',
            'kelola_stock_opname_divisi',
            'konfirmasi_stock_opname',
        ];

        // Monitoring Gudang
        $monitoringPermissions = [
            'monitor_transaksi_barang',
            'monitor_semua_transaksi_barang',
            'monitor_stok',
            'monitor_semua_stok',
        ];

        // Laporan Gudang
        $laporanPermissions = [
            'lihat_laporan_gudang_divisi',
            'lihat_laporan_gudang_semua',
        ];

        // Combine all inventory permissions
        $allPermissions = array_merge(
            $dashboardGudangPermissions,
            $kategoriBarangPermissions,
            $barangPermissions,
            $permintaanBarangPermissions,
            $stockOpnamePermissions,
            $monitoringPermissions,
            $laporanPermissions
        );

        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }
}
