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
            // Gudang BHP (Inventory)
            'inv_category' => [
                'module' => 'Gudang BHP',
                'label' => 'Kategori Barang',
                'keywords' => ['Kategori Barang', 'Data Kategori'],
            ],
            'inv_item' => [
                'module' => 'Gudang BHP',
                'label' => 'Data Barang',
                'keywords' => ['Barang Gudang'],
            ],
            'inv_order' => [
                'module' => 'Gudang BHP',
                'label' => 'Permintaan Barang',
                'keywords' => ['Permintaan Barang', 'Serah Terima Barang', 'Terima Barang'],
            ],
            'inv_opname' => [
                'module' => 'Gudang BHP',
                'label' => 'Stock Opname',
                'keywords' => ['Stock Opname'],
            ],
            'inv_monitoring' => [
                'module' => 'Gudang BHP',
                'label' => 'Monitoring & Stok',
                'keywords' => ['Transaksi Barang', 'Stok Divisi', 'Stok Keseluruhan', 'Konversi Stok', 'Pengeluaran Stok'],
            ],
            'inv_dashboard' => [
                'module' => 'Gudang BHP',
                'label' => 'Dashboard Gudang',
                'keywords' => ['Dashboard Gudang'],
            ],
            'inv_report' => [
                'module' => 'Gudang BHP',
                'label' => 'Laporan Gudang',
                'keywords' => ['Laporan Gudang'],
            ],

            // Arsiparis (Archieve)
            'arsip_kategori' => [
                'module' => 'Arsiparis',
                'label' => 'Kategori Arsip',
                'keywords' => ['kategori_arsip'],
            ],
            'arsip_klasifikasi' => [
                'module' => 'Arsiparis',
                'label' => 'Klasifikasi Arsip',
                'keywords' => ['klasifikasi_arsip'],
            ],
            'arsip_dokumen' => [
                'module' => 'Arsiparis',
                'label' => 'Dokumen Arsip',
                'keywords' => ['arsip_divisi', 'semua_arsip', 'arsip_pribadi'],
            ],
            'arsip_penyimpanan' => [
                'module' => 'Arsiparis',
                'label' => 'Penyimpanan Divisi',
                'keywords' => ['penyimpanan_divisi'],
            ],
            'arsip_dashboard' => [
                'module' => 'Arsiparis',
                'label' => 'Dashboard Arsip',
                'keywords' => ['dashboard_arsip'],
            ],
            'arsip_laporan' => [
                'module' => 'Arsiparis',
                'label' => 'Laporan Arsip',
                'keywords' => ['laporan_arsip'],
            ],
            'arsip_pencarian' => [
                'module' => 'Arsiparis',
                'label' => 'Pencarian Dokumen',
                'keywords' => ['pencarian_dokumen'],
            ],

            // Kunjungan (Visitor Management)
            'visitor_data' => [
                'module' => 'Kunjungan',
                'label' => 'Data Pengunjung',
                'keywords' => ['data_pengunjung', 'konfirmasi_kunjungan', 'undangan_tamu'],
            ],
            'visitor_master' => [
                'module' => 'Kunjungan',
                'label' => 'Keperluan Kunjungan',
                'keywords' => ['master_manajemen_pengunjung'],
            ],
            'visitor_feedback' => [
                'module' => 'Kunjungan',
                'label' => 'Feedback & Kritik',
                'keywords' => ['pertanyaan_feedback', 'kritik_saran_pengunjung'],
            ],
            'visitor_stats' => [
                'module' => 'Kunjungan',
                'label' => 'Statistik & Laporan',
                'keywords' => ['laporan_pengunjung', 'dashboard_pengunjung'],
            ],

            // Ticketing
            'ticketing_asset' => [
                'module' => 'Ticketing',
                'label' => 'Asset Model',
                'keywords' => ['Asset Model'],
            ],
            'ticketing_checklist' => [
                'module' => 'Ticketing',
                'label' => 'Checklist',
                'keywords' => ['Checklist'],
            ],
            'ticketing_asset_item' => [
                'module' => 'Ticketing',
                'label' => 'Asset',
                'keywords' => ['Asset'],
            ],
            'maintenance' => [
                'module' => 'Ticketing',
                'label' => 'Maintenance',
                'keywords' => ['Maintenance'],
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
                'Gudang BHP' => 2, 
                'Arsiparis' => 3, 
                'Kunjungan' => 4, 
                'Ticketing' => 5, 
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
