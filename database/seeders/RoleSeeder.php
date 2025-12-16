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
            'lihat_dashboard',
            // Pengguna
            'lihat_pengguna',
            'kelola_pengguna',
            'hapus_pengguna',
            // Role
            'lihat_role',
            'kelola_role',
            'hapus_role',
            // Divisi
            'lihat_divisi',
            'kelola_divisi',
            'hapus_divisi',
            // Jabatan
            'lihat_jabatan',
            'kelola_jabatan',
            'hapus_jabatan',
            // Profil
            'edit_profil',
            'ubah_password',
            // Sistem Manajemen Gudang - Full access
            'lihat_dashboard_gudang_utama',
            'lihat_dashboard_gudang_divisi',
            'lihat_kategori_barang',
            'kelola_kategori_barang',
            'lihat_barang',
            'kelola_barang',
            'keluarkan_stok',
            'lihat_permintaan_barang',
            'lihat_semua_permintaan_barang',
            'buat_permintaan_barang',
            'konfirmasi_permintaan_barang',
            'serah_terima_barang',
            'terima_barang',
            'lihat_stock_opname_gudang',
            'lihat_stock_opname_divisi',
            'lihat_semua_stock_opname',
            'kelola_stock_opname_gudang',
            'kelola_stock_opname_divisi',
            'konfirmasi_stock_opname',
            'monitor_transaksi_barang',
            'monitor_semua_transaksi_barang',
            'monitor_stok',
            'monitor_semua_stok',
            'lihat_laporan_gudang_divisi',
            'lihat_laporan_gudang_semua',
        ]);

        // Admin - Limited admin access
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin->syncPermissions([
            'lihat_dashboard',
            'lihat_pengguna',
            'kelola_pengguna',
            'lihat_role',
            'lihat_divisi',
            'kelola_divisi',
            'lihat_jabatan',
            'kelola_jabatan',
            'edit_profil',
            'ubah_password',
            // Gudang
            'lihat_dashboard_gudang_utama',
            'lihat_kategori_barang',
            'lihat_barang',
            'lihat_permintaan_barang',
            'buat_permintaan_barang',
            'lihat_stock_opname_divisi',
            'monitor_stok',
        ]);

        // User - Basic access
        $user = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web']);
        $user->syncPermissions([
            'lihat_dashboard',
            'edit_profil',
            'ubah_password',
            'buat_permintaan_barang',
            'terima_barang',
        ]);

        // Kepala Gudang - Warehouse Manager
        $kepalaGudang = Role::firstOrCreate(['name' => 'Kepala Gudang', 'guard_name' => 'web']);
        $kepalaGudang->syncPermissions([
            'lihat_dashboard',
            'edit_profil',
            'ubah_password',
            // Full gudang access
            'lihat_dashboard_gudang_utama',
            'lihat_dashboard_gudang_divisi',
            'lihat_kategori_barang',
            'kelola_kategori_barang',
            'lihat_barang',
            'kelola_barang',
            'keluarkan_stok',
            'lihat_permintaan_barang',
            'lihat_semua_permintaan_barang',
            'konfirmasi_permintaan_barang',
            'serah_terima_barang',
            'lihat_stock_opname_gudang',
            'lihat_semua_stock_opname',
            'kelola_stock_opname_gudang',
            'konfirmasi_stock_opname',
            'monitor_transaksi_barang',
            'monitor_semua_transaksi_barang',
            'monitor_stok',
            'monitor_semua_stok',
            'lihat_laporan_gudang_divisi',
            'lihat_laporan_gudang_semua',
        ]);

        // Staff Gudang - Warehouse Staff
        $staffGudang = Role::firstOrCreate(['name' => 'Staff Gudang', 'guard_name' => 'web']);
        $staffGudang->syncPermissions([
            'lihat_dashboard',
            'edit_profil',
            'ubah_password',
            'lihat_dashboard_gudang_utama',
            'lihat_kategori_barang',
            'lihat_barang',
            'keluarkan_stok',
            'lihat_permintaan_barang',
            'serah_terima_barang',
            'lihat_stock_opname_gudang',
            'kelola_stock_opname_gudang',
            'monitor_stok',
        ]);
    }
}
