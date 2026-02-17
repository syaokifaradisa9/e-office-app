<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define shared permissions for Superadmin and Pimpinan based on screenshots
        $sharedPermissions = [
            // Data Master
            'lihat_divisi', 'kelola_divisi',
            'lihat_jabatan', 'kelola_jabatan',
            'lihat_pengguna', 'kelola_pengguna',
            'lihat_role', 'kelola_role',
            
            // Arsiparis (Archieve)
            'lihat_dashboard_arsip_keseluruhan',
            'lihat_semua_arsip', 'kelola_semua_arsip',
            'lihat_kategori_arsip', 'kelola_kategori_arsip',
            'lihat_klasifikasi_arsip', 'kelola_klasifikasi_arsip',
            'lihat_laporan_arsip_keseluruhan',
            'pencarian_dokumen_keseluruhan',
            'lihat_penyimpanan_divisi', 'kelola_penyimpanan_divisi',
        ];

        // 1. Create Superadmin Role & User
        $superadminRole = Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'web']);
        $superadminRole->syncPermissions($sharedPermissions);

        $superadmin = User::updateOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'name' => 'Superadmin e-Office',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $superadmin->assignRole($superadminRole);

        // 2. Create Pimpinan Role & User
        $pimpinanRole = Role::firstOrCreate(['name' => 'Pimpinan', 'guard_name' => 'web']);
        $pimpinanRole->syncPermissions($sharedPermissions);

        $pimpinan = User::updateOrCreate(
            ['email' => 'pimpinan@gmail.com'],
            [
                'name' => 'Pimpinan e-Office',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $pimpinan->assignRole($pimpinanRole);
        // 3. Create Pegawai Role & User
        $pegawaiRole = Role::firstOrCreate(['name' => 'Pegawai', 'guard_name' => 'web']);
        
        // Pegawai permissions: Limited view access
        $pegawaiPermissions = [
            'lihat_arsip_pribadi', 
            'lihat_kategori_arsip', 
            'lihat_klasifikasi_arsip', 
            'pencarian_dokumen_pribadi'
        ];
        
        $pegawaiRole->syncPermissions($pegawaiPermissions);

        $pegawai = User::updateOrCreate(
            ['email' => 'pegawai@gmail.com'],
            [
                'name' => 'Pegawai e-Office',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $pegawai->assignRole($pegawaiRole);
        // 4. Create Admin Arsip Role & User
        $adminArsipRole = Role::firstOrCreate(['name' => 'Admin Arsip', 'guard_name' => 'web']);
        
        // Admin Arsip permissions: Manage everything related to Archieve but not Data Master
        $adminArsipPermissions = [
            'lihat_kategori_arsip', 'kelola_kategori_arsip',
            'lihat_klasifikasi_arsip', 'kelola_klasifikasi_arsip',
            'lihat_penyimpanan_divisi', 'kelola_penyimpanan_divisi',
            'lihat_dashboard_arsip_keseluruhan', 'lihat_laporan_arsip_keseluruhan',
            'lihat_semua_arsip', 'kelola_semua_arsip',
            'pencarian_dokumen_keseluruhan'
        ];
        
        $adminArsipRole->syncPermissions($adminArsipPermissions);

        $adminArsip = User::updateOrCreate(
            ['email' => 'adminarsip@gmail.com'],
            [
                'name' => 'Admin Arsip e-Office',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $adminArsip->assignRole($adminArsipRole);

        // 5. Create Admin Arsip Divisi Role & User
        $adminArsipDivisiRole = Role::firstOrCreate(['name' => 'Admin Arsip Divisi', 'guard_name' => 'web']);
        
        $adminArsipDivisiPermissions = [
            'lihat_dashboard_arsip_divisi',
            'lihat_arsip_divisi', 'kelola_arsip_divisi',
            'lihat_kategori_arsip',
            'lihat_klasifikasi_arsip',
            'lihat_laporan_arsip_divisi',
            'pencarian_dokumen_divisi'
        ];
        
        $adminArsipDivisiRole->syncPermissions($adminArsipDivisiPermissions);

        // Assign to first division (Tata Usaha)
        $divisionId = \App\Models\Division::where('name', 'Tata Usaha')->first()?->id ?? 1;

        $adminArsipDivisi = User::updateOrCreate(
            ['email' => 'admindivisi@gmail.com'],
            [
                'name' => 'Admin Arsip Divisi (Tata Usaha)',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
                'division_id' => $divisionId,
            ]
        );
        $adminArsipDivisi->assignRole($adminArsipDivisiRole);
    }
}
