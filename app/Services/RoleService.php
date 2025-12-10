<?php

namespace App\Services;

use App\DataTransferObjects\RoleDTO;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleService
{
    private const CACHE_KEY = 'roles_all';

    private const PERMISSIONS_CACHE_KEY = 'permissions_all';

    public function getAll()
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return Role::with('permissions')->get();
        });
    }

    public function getAllPermissions()
    {
        return Cache::rememberForever(self::PERMISSIONS_CACHE_KEY, function () {
            return Permission::all();
        });
    }

    public function getPermissionsGrouped(): array
    {
        $permissions = $this->getAllPermissions();
        $grouped = [];

        foreach ($permissions as $permission) {
            $parts = explode('_', $permission->name);
            $groupKey = end($parts); // e.g., 'users', 'divisions', 'roles'

            if (! isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'label' => ucfirst($groupKey),
                    'permissions' => [],
                ];
            }

            $grouped[$groupKey]['permissions'][] = $permission->name;
        }

        return $grouped;
    }

    public function store(RoleDTO $dto): Role
    {
        $role = Role::create([
            'name' => $dto->name,
            'guard_name' => 'web',
        ]);

        if (! empty($dto->permissions)) {
            $role->syncPermissions($dto->permissions);
        }

        $this->clearCache();

        return $role;
    }

    public function update(Role $role, RoleDTO $dto): Role
    {
        $role->update([
            'name' => $dto->name,
        ]);

        $role->syncPermissions($dto->permissions ?? []);

        $this->clearCache();

        return $role;
    }

    public function delete(Role $role): bool
    {
        $result = $role->delete();
        $this->clearCache();

        return $result;
    }

    private function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::PERMISSIONS_CACHE_KEY);
    }
}
