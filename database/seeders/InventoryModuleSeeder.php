<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Division;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Inventory\Enums\InventoryPermission;
use Modules\VisitorManagement\Enums\VisitorUserPermission;
use Modules\Archieve\Enums\ArchieveUserPermission;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class InventoryModuleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Base Permissions (Core System)
        $basePermissions = [
            'lihat_divisi', 'kelola_divisi', 
            'lihat_jabatan', 'kelola_jabatan',
            'lihat_pengguna', 'kelola_pengguna', 
            'lihat_role', 'kelola_role'
        ];

        // 2. Fetch Module Permissions from Enums
        $inventoryPermissions = InventoryPermission::values();
        $visitorPermissions = VisitorUserPermission::values();
        $archievePermissions = ArchieveUserPermission::values();

        // 3. Merge and Create All Permissions
        $allPermissionNames = array_merge(
            $basePermissions, 
            $inventoryPermissions, 
            $visitorPermissions, 
            $archievePermissions
        );

        foreach ($allPermissionNames as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // 4. Create Superadmin Role
        $superAdminRole = Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'web']);
        
        // Filter permissions for Superadmin (Core + Inventory only)
        $superAdminPermissions = array_merge($basePermissions, $inventoryPermissions);
        $superAdminRole->syncPermissions($superAdminPermissions);

        // 5. Create Superadmin User
        $superAdminUser = User::updateOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'name' => 'Superadmin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $superAdminUser->assignRole($superAdminRole);

        // 6. Create Admin Gudang Utama Role
        $adminGudangRole = Role::firstOrCreate(['name' => 'Admin Gudang Utama', 'guard_name' => 'web']);
        $adminGudangRole->syncPermissions([
            // Barang
            InventoryPermission::ManageItem->value,
            InventoryPermission::ConvertItemGudang->value,
            InventoryPermission::ViewItem->value,
            InventoryPermission::MonitorAllItemTransaction->value,
            InventoryPermission::MonitorItemTransaction->value,
            InventoryPermission::IssueItemGudang->value,
            // Dashboard
            InventoryPermission::ViewMainWarehouseDashboard->value,
            // Data Stok
            InventoryPermission::MonitorAllStock->value,
            // Kategori
            InventoryPermission::ManageCategory->value,
            InventoryPermission::ViewCategory->value,
            // Laporan
            InventoryPermission::ViewAllReport->value,
            // Permintaan
            InventoryPermission::ConfirmWarehouseOrder->value,
            InventoryPermission::ViewAllWarehouseOrder->value,
            InventoryPermission::HandoverItem->value,
            // Stok Opname
            InventoryPermission::FinalizeStockOpname->value,
            InventoryPermission::ViewWarehouseStockOpname->value,
            InventoryPermission::ProcessStockOpname->value,
        ]);

        // 7. Create Admin Gudang Utama User
        $adminGudangUser = User::updateOrCreate(
            ['email' => 'admin.gudang@example.com'],
            [
                'name' => 'Admin Gudang Utama',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $adminGudangUser->assignRole($adminGudangRole);

        // 8. Create Admin Gudang Divisi Role
        $adminDivisiRole = Role::firstOrCreate(['name' => 'Admin Gudang Divisi', 'guard_name' => 'web']);
        $adminDivisiRole->syncPermissions([
            // Barang
            InventoryPermission::ViewItem->value,
            InventoryPermission::MonitorItemTransaction->value,
            // Dashboard
            InventoryPermission::ViewDivisionWarehouseDashboard->value,
            // Data Stok
            InventoryPermission::MonitorStock->value,
            InventoryPermission::ConvertStock->value,
            InventoryPermission::IssueStock->value,
            // Kategori
            InventoryPermission::ViewCategory->value,
            // Laporan
            InventoryPermission::ViewDivisionReport->value,
            // Permintaan
            InventoryPermission::CreateWarehouseOrder->value,
            InventoryPermission::ViewWarehouseOrderDivisi->value,
            InventoryPermission::ReceiveItem->value,
            // Stok Opname
            InventoryPermission::ViewDivisionStockOpname->value,
            InventoryPermission::ProcessStockOpname->value,
            InventoryPermission::FinalizeStockOpname->value,
        ]);

        // 9. Create Admin Gudang Divisi User (Linked to IT Division)
        $itDivision = \App\Models\Division::where('name', 'IT')->first();
        $adminDivisiUser = User::updateOrCreate(
            ['email' => 'admin.it@example.com'],
            [
                'name' => 'Admin Gudang IT',
                'password' => Hash::make('password'),
                'division_id' => $itDivision?->id,
                'is_active' => true,
            ]
        );
        $adminDivisiUser->assignRole($adminDivisiRole);

        // 10. Create Pimpinan Role
        $pimpinanRole = Role::firstOrCreate(['name' => 'Pimpinan', 'guard_name' => 'web']);
        // Same as Superadmin (Core + Inventory only)
        $pimpinanRole->syncPermissions($superAdminPermissions);

        // 11. Create Pimpinan User
        $pimpinanUser = User::updateOrCreate(
            ['email' => 'pimpinan@example.com'],
            [
                'name' => 'Direktur Utama',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $pimpinanUser->assignRole($pimpinanRole);
    }
}
