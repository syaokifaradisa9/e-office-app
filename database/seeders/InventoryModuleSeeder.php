<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Modules\Inventory\Enums\InventoryPermission;

class InventoryModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $inventoryPermissions = InventoryPermission::values();

        // Ensure permissions exist
        foreach ($inventoryPermissions as $permissionName) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        // Superadmin & Pimpinan
        $superadminRole = Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'web']);
        $superadminRole->givePermissionTo($inventoryPermissions);

        $pimpinanRole = Role::firstOrCreate(['name' => 'Pimpinan', 'guard_name' => 'web']);
        $pimpinanRole->givePermissionTo($inventoryPermissions);

        // Admin Divisi specific permissions
        $adminDivisiRole = Role::firstOrCreate(['name' => 'Admin Divisi', 'guard_name' => 'web']);
        $adminDivisiRole->givePermissionTo([
            InventoryPermission::ViewDivisionWarehouseDashboard->value,
            InventoryPermission::ViewWarehouseOrderDivisi->value,
            InventoryPermission::CreateWarehouseOrder->value,
            InventoryPermission::ReceiveItem->value,
            InventoryPermission::ViewDivisionStockOpname->value,
            InventoryPermission::CreateStockOpname->value,
            InventoryPermission::ProcessStockOpname->value,
            InventoryPermission::MonitorItemTransaction->value,
            InventoryPermission::MonitorStock->value,
            InventoryPermission::IssueStock->value,
            InventoryPermission::ConvertStock->value,
            InventoryPermission::ViewDivisionReport->value,
        ]);

        // Admin Gudang user & role
        $adminGudangRole = Role::firstOrCreate(['name' => 'Admin Gudang', 'guard_name' => 'web']);
        // Assign all inventory permissions to Admin Gudang
        $adminGudangRole->givePermissionTo($inventoryPermissions);

        $adminGudang = \App\Models\User::updateOrCreate(
            ['email' => 'admingudang@gmail.com'],
            [
                'name' => 'Admin Gudang Utama',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $adminGudang->assignRole($adminGudangRole);
    }
}
