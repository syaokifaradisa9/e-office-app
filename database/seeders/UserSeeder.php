<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Division;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. All permissions for Superadmin
        $allPermissions = Permission::all();

        // 2. Create Roles
        $superadminRole = Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'web']);
        $superadminRole->syncPermissions($allPermissions);

        $pimpinanRole = Role::firstOrCreate(['name' => 'Pimpinan', 'guard_name' => 'web']);
        $pimpinanPermissions = [
            'lihat_divisi', 'kelola_divisi', 'lihat_jabatan', 'kelola_jabatan',
            'lihat_pengguna', 'kelola_pengguna', 'lihat_role', 'kelola_role',
            'Lihat Data Checklist', 'Kelola Data Checklist', 'Lihat Dashboard Ticketing Keseluruhan',
            'Lihat Data Asset Keseluruhan', 'Kelola Data Asset', 'Hapus Data Asset',
            'Lihat Data Kategori Asset Keseluruhan', 'Kelola Data Kategori Asset', 'Hapus Data Kategori Asset',
            'Lihat Data Ticket Keseluruhan', 'Konfirmasi Ticketing', 'Proses Ticketing',
            'Perbaikan Ticketing', 'Penyelesaian Ticketing', 'Pemberian Feedback Ticketing',
            'Lihat Laporan Ticketing Keseluruhan', 'Lihat Jadwal Maintenance Keseluruhan',
            'Konfirmasi Proses Maintenance', 'Proses Maintenance',
        ];
        $pimpinanRole->syncPermissions(Permission::whereIn('name', $pimpinanPermissions)->get());

        $adminDivisiRole = Role::firstOrCreate(['name' => 'Admin Divisi', 'guard_name' => 'web']);
        $adminDivisiPermissions = [
            'Lihat Dashboard Ticketing Divisi', 'Lihat Data Asset Divisi', 'Kelola Data Asset',
            'Hapus Data Asset', 'Lihat Data Kategori Asset Divisi', 'Kelola Data Kategori Asset',
            'Hapus Data Kategori Asset', 'Lihat Data Ticket Divisi', 'Pemberian Feedback Ticketing',
            'Lihat Laporan Ticketing Divisi', 'Lihat Jadwal Maintenance Divisi',
        ];
        $adminDivisiRole->syncPermissions(Permission::whereIn('name', $adminDivisiPermissions)->get());

        $pegawaiRole = Role::firstOrCreate(['name' => 'Pegawai', 'guard_name' => 'web']);
        $pegawaiPermissions = [
            'Lihat Dashboard Ticketing Pribadi', 'Lihat Data Asset Pribadi',
            'Lihat Data Ticket Pribadi', 'Pemberian Feedback Ticketing',
        ];
        $pegawaiRole->syncPermissions(Permission::whereIn('name', $pegawaiPermissions)->get());

        // 3. Create Fixed Users (Superadmin & Pimpinan)
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

        // 4. Create Users per Division (Admin Divisi & Pegawai)
        $divisions = Division::all();

        foreach ($divisions as $division) {
            $divisionSlug = Str::slug($division->name, '.');

            // Create 1 Admin Divisi for this division
            $adminUser = User::updateOrCreate(
                ['email' => "admin.{$divisionSlug}@gmail.com"],
                [
                    'name' => "Admin {$division->name}",
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'division_id' => $division->id,
                ]
            );
            $adminUser->syncRoles([$adminDivisiRole->name]);

            // Create 5 Pegawai for this division
            for ($i = 1; $i <= 5; $i++) {
                $pegawaiUser = User::updateOrCreate(
                    ['email' => "pegawai.{$divisionSlug}.{$i}@gmail.com"],
                    [
                        'name' => "Pegawai {$division->name} {$i}",
                        'password' => Hash::make('password'),
                        'email_verified_at' => now(),
                        'is_active' => true,
                        'division_id' => $division->id,
                    ]
                );
                $pegawaiUser->syncRoles([$pegawaiRole->name]);
            }
        }

        $this->command->info('UserSeeder: Superadmin, Pimpinan, Admin Divisi (per division), and Pegawai (5 per division) created.');
    }
}
