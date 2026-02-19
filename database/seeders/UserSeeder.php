<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Modules\Archieve\Enums\ArchieveUserPermission;
use Modules\Inventory\Enums\InventoryPermission;
use Modules\Ticketing\Enums\TicketingPermission;
use Modules\VisitorManagement\Enums\VisitorUserPermission;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Get all module permissions
        $allPermissions = array_merge(
            [
                'lihat_divisi', 'kelola_divisi',
                'lihat_jabatan', 'kelola_jabatan',
                'lihat_pengguna', 'kelola_pengguna',
                'lihat_role', 'kelola_role',
            ],
            ArchieveUserPermission::values(),
            InventoryPermission::values(),
            TicketingPermission::values(),
            VisitorUserPermission::values()
        );

        // 2. Create Superadmin Role & User
        $superadminRole = Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'web']);
        $superadminRole->syncPermissions($allPermissions);

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
        // For Pimpinan, we might want only specific permissions or all "View" permissions
        $pimpinanRole->syncPermissions($allPermissions);

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

        // 5. Create Admin Arsip Role & User
        $adminArsipRole = Role::firstOrCreate(['name' => 'Admin Arsip', 'guard_name' => 'web']);
        $adminArsipPermissions = ArchieveUserPermission::values();
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

        // 6. Create Division Specific Admin (e.g. Tata Usaha)
        $divisionId = \App\Models\Division::where('name', 'Tata Usaha')->first()?->id ?? 1;

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
        // We can use a generic "Admin Divisi" role or specific module roles
        $adminDivisiRole = Role::firstOrCreate(['name' => 'Admin Divisi', 'guard_name' => 'web']);
        // Assign some basic division permissions
        $adminDivisiRole->syncPermissions([
            'lihat_dashboard_arsip_divisi',
            'lihat_arsip_divisi',
            'kelola_arsip_divisi',
            'pencarian_dokumen_divisi',
            InventoryPermission::ViewDivisionReport->value,
            InventoryPermission::MonitorStock->value,
        ]);
        $adminDivisi->assignRole($adminDivisiRole);
    }
}
