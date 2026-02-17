<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

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
    }
}
