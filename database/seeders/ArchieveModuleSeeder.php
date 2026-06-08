<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Modules\Archieve\Enums\ArchieveUserPermission;

class ArchieveModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Get all module permissions
        $archievePermissions = ArchieveUserPermission::values();
        
        // Ensure permissions exist
        foreach ($archievePermissions as $permissionName) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        // 2. Assign to Superadmin & Pimpinan
        $superadminRole = Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'web']);
        $superadminRole->givePermissionTo($archievePermissions);

        $pimpinanRole = Role::firstOrCreate(['name' => 'Pimpinan', 'guard_name' => 'web']);
        $pimpinanRole->givePermissionTo($archievePermissions);

        // 3. Pegawai Role & specific permissions
        $pegawaiRole = Role::firstOrCreate(['name' => 'Pegawai', 'guard_name' => 'web']);
        $pegawaiRole->givePermissionTo([
            'lihat_arsip_pribadi', 
            'lihat_kategori_arsip', 
            'lihat_klasifikasi_arsip', 
            'pencarian_dokumen_pribadi'
        ]);

        // 4. Create Admin Arsip Role & User
        $adminArsipRole = Role::firstOrCreate(['name' => 'Admin Arsip', 'guard_name' => 'web']);
        $adminArsipRole->syncPermissions($archievePermissions);

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

        // 5. Admin Divisi specific permissions
        $adminDivisiRole = Role::firstOrCreate(['name' => 'Admin Divisi', 'guard_name' => 'web']);
        $adminDivisiRole->givePermissionTo([
            'lihat_dashboard_arsip_divisi',
            'lihat_arsip_divisi',
            'kelola_arsip_divisi',
            'pencarian_dokumen_divisi',
        ]);
    }
}
