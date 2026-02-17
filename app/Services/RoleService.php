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
            'permintaan' => [
                'module' => 'Sistem Manajemen Gudang',
                'label' => 'Permintaan Barang',
                'keywords' => ['permintaan', 'serah_terima_barang', 'terima_barang', 'Permintaan Barang', 'Serah Terima Barang', 'Terima Barang'],
            ],
            'stok' => [
                'module' => 'Sistem Manajemen Gudang',
                'label' => 'Data Stok',
                'keywords' => ['lihat_stok_divisi', 'lihat_semua_stok', 'konversi_stok_barang', 'pengeluaran_stok_barang', 'Stok Divisi', 'Stok Keseluruhan', 'Konversi Stok', 'Pengeluaran Stok'],
            ],
            'divisi' => [
                'module' => 'Data Master',
                'label' => 'Divisi',
                'keywords' => ['lihat_divisi', 'kelola_divisi'],
            ],
            'jabatan' => [
                'module' => 'Data Master',
                'label' => 'Jabatan',
                'keywords' => ['lihat_jabatan', 'kelola_jabatan'],
            ],
            'pengguna' => [
                'module' => 'Data Master',
                'label' => 'Pengguna',
                'keywords' => ['lihat_pengguna', 'kelola_pengguna'],
            ],
            'role' => [
                'module' => 'Data Master',
                'label' => 'Role & Permission',
                'keywords' => ['lihat_role', 'kelola_role'],
            ],
            'kategori' => [
                'module' => 'Sistem Manajemen Gudang',
                'label' => 'Kategori Barang',
                'keywords' => ['kategori', 'Kategori'],
            ],
            'barang' => [
                'module' => 'Sistem Manajemen Gudang',
                'label' => 'Barang',
                'keywords' => ['barang', 'Barang'],
            ],
            'transaksi' => [
                'module' => 'Sistem Manajemen Gudang',
                'label' => 'Monitor Transaksi',
                'keywords' => ['transaksi_barang', 'Transaksi Barang'],
            ],
            'stok_opname' => [
                'module' => 'Sistem Manajemen Gudang',
                'label' => 'Stok Opname',
                'keywords' => ['stock_opname', 'stok_opname', 'opname', 'Stock Opname'],
                'columns' => 2,
            ],
            'laporan' => [
                'module' => 'Sistem Manajemen Gudang',
                'label' => 'Laporan Gudang',
                'keywords' => ['laporan_gudang', 'Laporan Gudang'],
            ],
            'dashboard' => [
                'module' => 'Sistem Manajemen Gudang',
                'label' => 'Dashboard Gudang',
                'keywords' => ['dashboard_gudang', 'Dashboard Gudang'],
            ],
            // Visitor Management
        ];

        foreach ($permissions as $permission) {
            $assigned = false;

            foreach ($groupingRules as $groupKey => $rule) {
                $hasKeyword = false;

                foreach ($rule['keywords'] as $keyword) {
                    if (str_contains(strtolower($permission->name), strtolower($keyword))) {
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

        // Sort groups by module then by custom priority or label
        uasort($grouped, function ($a, $b) use ($groupingRules) {
            $moduleOrder = ['Data Master' => 1, 'Sistem Manajemen Gudang' => 2, 'Lainnya' => 99];
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
