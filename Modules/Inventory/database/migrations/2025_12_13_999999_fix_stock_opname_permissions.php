<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ensure correct permissions exist
        $correctPermissions = [
            'lihat_stock_opname_divisi',
            'kelola_stock_opname_divisi',
        ];

        foreach ($correctPermissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // 2. Map rogue -> correct
        $mapping = [
            'lihat_stock_opname' => 'lihat_stock_opname_divisi',
            'kelola_stock_opname' => 'kelola_stock_opname_divisi',
        ];

        foreach ($mapping as $rogueName => $correctName) {
            $rogue = Permission::where('name', $rogueName)->first();
            $correct = Permission::where('name', $correctName)->first();

            if ($rogue && $correct) {
                // Find roles that have the rogue permission
                $roles = Role::permission($rogueName)->get();
                foreach ($roles as $role) {
                    // Assign correct permission
                    if (!$role->hasPermissionTo($correctName)) {
                        $role->givePermissionTo($correctName);
                    }
                    // Revoke rogue permission
                    $role->revokePermissionTo($rogueName);
                }
                
                // Delete rogue permission
                $rogue->delete();
            }
        }

        // 3. Clear Cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Cache::forget('roles_all');
        Cache::forget('permissions_all');
    }

    public function down(): void
    {
        // No down needed really, this is a fix
    }
};
