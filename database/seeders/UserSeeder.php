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
        // Define master permissions
        $masterPermissions = [
            'lihat_divisi', 'kelola_divisi',
            'lihat_jabatan', 'kelola_jabatan',
            'lihat_pengguna', 'kelola_pengguna',
            'lihat_role', 'kelola_role',
        ];

        // 1. Create Superadmin Role & User
        $superadminRole = Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'web']);
        $superadminRole->syncPermissions($masterPermissions);

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
        $pimpinanRole->syncPermissions($masterPermissions);

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

        // 3. Create Admin Sistem Pengunjung Role & User
        $adminPengunjungRole = Role::firstOrCreate(['name' => 'Admin Sistem Pengunjung', 'guard_name' => 'web']);
        // Tidak diberikan master data permissions sama sekali
        $adminPengunjungRole->syncPermissions([]); 

        $adminPengunjung = User::updateOrCreate(
            ['email' => 'adminpengunjung@gmail.com'],
            [
                'name' => 'Admin Pengunjung e-Office',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $adminPengunjung->assignRole($adminPengunjungRole);
    }
}
