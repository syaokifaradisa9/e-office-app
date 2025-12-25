<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Enums\InventoryPermission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $basePermissions = [
            'lihat_divisi',
            'kelola_divisi',
            'lihat_jabatan',
            'kelola_jabatan',
            'lihat_pengguna',
            'kelola_pengguna',
            'lihat_role',
            'kelola_role',
        ];

        $inventoryPermissions = InventoryPermission::values();
        $allowedPermissions = array_unique(array_merge($basePermissions, $inventoryPermissions));

        // Delete permissions that are NOT in the allowed list
        DB::table('permissions')
            ->whereNotIn('name', $allowedPermissions)
            ->delete();

        // Also clean up any roles and sync permissions for superadmin
        $role = DB::table('roles')->where('name', 'superadmin')->first();
        if ($role) {
            DB::table('role_has_permissions')->where('role_id', $role->id)->delete();
            
            $permissionIds = DB::table('permissions')->whereIn('name', $allowedPermissions)->pluck('id');
            
            foreach ($permissionIds as $id) {
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $id,
                    'role_id' => $role->id,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse action needed for data cleanup
    }
};
