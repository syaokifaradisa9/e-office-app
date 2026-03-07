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
        // Order matters: specific modules first, generic "Data Master" last to avoid broad keyword matches
        $groupingRules = [
            // Ticketing
            'ticketing_asset_category' => [
                'module' => 'Ticketing',
                'label' => 'Kategori Asset',
                'keywords' => ['Kategori Asset'],
            ],
            'ticketing_checklist' => [
                'module' => 'Ticketing',
                'label' => 'Checklist',
                'keywords' => ['Checklist'],
            ],
            'ticketing_asset' => [
                'module' => 'Ticketing',
                'label' => 'Data Asset',
                'keywords' => ['Data Asset', 'Asset Pribadi', 'Asset Divisi', 'Asset Keseluruhan'],
            ],
            'maintenance' => [
                'module' => 'Ticketing',
                'label' => 'Maintenance',
                'keywords' => ['Maintenance'],
            ],
            'ticketing_dashboard' => [
                'module' => 'Ticketing',
                'label' => 'Dashboard',
                'keywords' => ['Dashboard Ticketing'],
            ],
            'ticketing_report' => [
                'module' => 'Ticketing',
                'label' => 'Laporan',
                'keywords' => ['Laporan Ticketing'],
            ],
            'ticketing_ticket' => [
                'module' => 'Ticketing',
                'label' => 'Lapor Kendala',
                'keywords' => ['Ticket'],
            ],


            // Data Master (Generic - Checked last)
            'divisi' => [
                'module' => 'Data Master',
                'label' => 'Divisi',
                'keywords' => ['lihat_divisi', 'kelola_divisi', 'lihat_data_divisi', 'kelola_data_divisi'],
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
        ];

        foreach ($permissions as $permission) {
            $assigned = false;
            $permNameLow = strtolower($permission->name);

            foreach ($groupingRules as $groupKey => $rule) {
                $hasKeyword = false;

                foreach ($rule['keywords'] as $keyword) {
                    if (str_contains($permNameLow, strtolower($keyword))) {
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
                            'exclusive' => $rule['exclusive'] ?? false,
                            'columns' => $rule['columns'] ?? 2,
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
                $getPriority = function ($perm) {
                    $lowered = strtolower($perm);
                    if (str_contains($lowered, 'lihat')) {
                        return 1;
                    }
                    if (str_contains($lowered, 'kelola') || str_contains($lowered, 'tambah') || str_contains($lowered, 'buat')) {
                        return 2;
                    }
                    if (str_contains($lowered, 'konfirmasi')) {
                        return 3;
                    }
                    if (str_contains($lowered, 'proses')) {
                        return 4;
                    }
                    if (str_contains($lowered, 'perbaikan')) {
                        return 5;
                    }
                    if (str_contains($lowered, 'penyelesaian')) {
                        return 6;
                    }
                    if (str_contains($lowered, 'pemberian') || str_contains($lowered, 'feedback')) {
                        return 7;
                    }
                    if (str_contains($lowered, 'hapus')) {
                        return 8;
                    }

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

        // Sort groups by module then by custom priority or label
        uasort($grouped, function ($a, $b) {
            $moduleOrder = [
                'Data Master' => 1, 
                'Ticketing' => 2, 
                'Lainnya' => 99
            ];
            $aModuleOrder = $moduleOrder[$a['module']] ?? 50;
            $bModuleOrder = $moduleOrder[$b['module']] ?? 50;

            if ($aModuleOrder !== $bModuleOrder) {
                return $aModuleOrder - $bModuleOrder;
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
