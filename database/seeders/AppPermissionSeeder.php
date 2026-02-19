<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AppPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'lihat_divisi', 'kelola_divisi', 
            'lihat_jabatan', 'kelola_jabatan',
            'lihat_pengguna', 'kelola_pengguna', 
            'lihat_role', 'kelola_role'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // 1. Superadmin & Pimpinan (Full Master Access)
        $fullMasterRoles = ['Superadmin', 'Pimpinan'];
        foreach ($fullMasterRoles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->givePermissionTo($permissions);
        }

        // 2. Admin Sistem Pengunjung
        // Diinstruksikan untuk tidak diberikan akses ke Divisi, Jabatan, Pegawai, dan Role.
        // Maka kita pastikan Role ini ada, tapi tidak diberi permission dari list $permissions di atas.
        Role::firstOrCreate(['name' => 'Admin Sistem Pengunjung', 'guard_name' => 'web']);
    }
}
