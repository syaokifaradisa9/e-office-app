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
        // 1. Get all core permissions
        $corePermissions = [
            'lihat_divisi', 'kelola_divisi',
            'lihat_jabatan', 'kelola_jabatan',
            'lihat_pengguna', 'kelola_pengguna',
            'lihat_role', 'kelola_role',
        ];

        // 2. Create Superadmin Role & User
        $superadminRole = Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'web']);
        $superadminRole->syncPermissions($corePermissions);

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

        // 3. Create Pimpinan Role & User (Has View All Access normally)
        $pimpinanRole = Role::firstOrCreate(['name' => 'Pimpinan', 'guard_name' => 'web']);
        $pimpinanRole->syncPermissions($corePermissions);

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

        // 4. Create Pegawai Role & User
        $pegawaiRole = Role::firstOrCreate(['name' => 'Pegawai', 'guard_name' => 'web']);

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

        // 5. Create Division Specific Admin (e.g. Tata Usaha)
        $divisionId = \App\Models\Division::where('name', 'Tata Usaha')->first()?->id ?? 1;

        $adminDivisiRole = Role::firstOrCreate(['name' => 'Admin Divisi', 'guard_name' => 'web']);

        $adminDivisi = User::updateOrCreate(
            ['email' => 'admindivisi@gmail.com'],
            [
                'name' => 'Admin Divisi (Tata Usaha)',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
                'division_id' => $divisionId,
            ]
        );
        
        $adminDivisi->assignRole($adminDivisiRole);
    }
}
