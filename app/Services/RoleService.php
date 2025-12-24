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

        // Define grouping rules for permissions with module separation
        $groupingRules = [
            'divisi' => [
                'module' => 'Data Master',
                'label' => 'Divisi',
                'keywords' => ['divisi'],
            ],
            'jabatan' => [
                'module' => 'Data Master',
                'label' => 'Jabatan',
                'keywords' => ['jabatan'],
            ],
            'pengguna' => [
                'module' => 'Data Master',
                'label' => 'Pengguna',
                'keywords' => ['pengguna'],
            ],
            'role' => [
                'module' => 'Data Master',
                'label' => 'Role & Permission',
                'keywords' => ['role'],
            ],
            'kategori' => [
                'module' => 'Sistem Manajemen Gudang',
                'label' => 'Kategori Barang',
                'keywords' => ['kategori'],
            ],
            'barang' => [
                'module' => 'Sistem Manajemen Gudang',
                'label' => 'Barang',
                'keywords' => ['barang', 'keluarkan_stok'],
            ],
        ];

        foreach ($permissions as $permission) {
            $assigned = false;

            foreach ($groupingRules as $groupKey => $rule) {
                $hasKeyword = false;

                foreach ($rule['keywords'] as $keyword) {
                    if (str_contains($permission->name, $keyword)) {
                        $hasKeyword = true;
                        break;
                    }
                }

                if ($hasKeyword) {
                    if (! isset($grouped[$groupKey])) {
                        $grouped[$groupKey] = [
                            'module' => $rule['module'],
                            'label' => $rule['label'],
                            'permissions' => [],
                        ];
                    }
                    $grouped[$groupKey]['permissions'][] = $permission->name;
                    $assigned = true;
                    break;
                }
            }

            // Fallback: assign to "Lainnya" group
            if (! $assigned) {
                $groupKey = 'lainnya';
                if (! isset($grouped[$groupKey])) {
                    $grouped[$groupKey] = [
                        'module' => 'Lainnya',
                        'label' => 'Lainnya',
                        'permissions' => [],
                    ];
                }
                $grouped[$groupKey]['permissions'][] = $permission->name;
            }
        }

        // Sort permissions within each group
        foreach ($grouped as &$group) {
            usort($group['permissions'], function ($a, $b) {
                // Priority: Lihat (1), Kelola (2), Others (99)
                $getPriority = function ($perm) {
                    if (str_contains($perm, 'lihat')) return 1;
                    if (str_contains($perm, 'kelola')) return 2;
                    return 99;
                };

                $pA = $getPriority($a);
                $pB = $getPriority($b);

                if ($pA !== $pB) {
                    return $pA - $pB;
                }

                return strcmp($a, $b);
            });
        }
        unset($group);

        // Sort groups by module then by label
        uasort($grouped, function ($a, $b) {
            $moduleOrder = ['Data Master' => 1, 'Sistem Manajemen Gudang' => 2, 'Lainnya' => 99];
            $aOrder = $moduleOrder[$a['module']] ?? 50;
            $bOrder = $moduleOrder[$b['module']] ?? 50;

            if ($aOrder !== $bOrder) {
                return $aOrder - $bOrder;
            }

            return strcmp($a['label'], $b['label']);
        });

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
