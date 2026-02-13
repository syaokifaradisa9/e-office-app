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
            'kategori_arsip' => [
                'module' => 'Sistem Arsip Dokumen',
                'label' => 'Kategori Arsip',
                'keywords' => ['kategori_arsip'],
            ],
            'klasifikasi_arsip' => [
                'module' => 'Sistem Arsip Dokumen',
                'label' => 'Klasifikasi Dokumen',
                'keywords' => ['klasifikasi_arsip'],
            ],
            'penyimpanan_divisi' => [
                'module' => 'Sistem Arsip Dokumen',
                'label' => 'Penyimpanan Divisi',
                'keywords' => ['penyimpanan_divisi'],
            ],
            'arsip_lihat' => [
                'module' => 'Sistem Arsip Dokumen',
                'label' => 'Lihat Arsip Digital',
                'keywords' => ['lihat_semua_arsip', 'lihat_arsip_divisi', 'lihat_arsip_pribadi'],
                'exclusive' => true,
                'columns' => 3,
            ],
            'arsip_kelola' => [
                'module' => 'Sistem Arsip Dokumen',
                'label' => 'Kelola Arsip Digital',
                'keywords' => ['kelola_semua_arsip', 'kelola_arsip_divisi'],
                'exclusive' => true,
                'columns' => 2,
            ],
            'pencarian_dokumen' => [
                'module' => 'Sistem Arsip Dokumen',
                'label' => 'Akses Pencarian Dokumen',
                'keywords' => ['pencarian_dokumen_keseluruhan', 'pencarian_dokumen_divisi', 'pencarian_dokumen_pribadi'],
                'exclusive' => true,
            ],
            'dashboard_arsip' => [
                'module' => 'Sistem Arsip Dokumen',
                'label' => 'Dashboard Arsip',
                'keywords' => ['dashboard_arsip'],
            ],
            'laporan_arsip' => [
                'module' => 'Sistem Arsip Dokumen',
                'label' => 'Laporan Arsip',
                'keywords' => ['laporan_arsip'],
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
            'master_keperluan_kunjungan' => [
                'module' => 'Pengunjung',
                'label' => 'Data Master Keperluan Kunjungan',
                'keywords' => ['lihat_master_manajemen_pengunjung', 'kelola_master_manajemen_pengunjung'],
            ],
            'manajemen_kunjungan' => [
                'module' => 'Pengunjung',
                'label' => 'Manajemen Sistem Kunjungan',
                'keywords' => ['lihat_data_pengunjung', 'konfirmasi_kunjungan', 'lihat_ulasan_pengunjung', 'lihat_laporan_pengunjung', 'lihat_dashboard_pengunjung', 'buat_undangan_tamu'],
            ],
            'pertanyaan_feedback' => [
                'module' => 'Pengunjung',
                'label' => 'Pertanyaan Feedback',
                'keywords' => ['lihat_pertanyaan_feedback', 'kelola_pertanyaan_feedback'],
            ],
            'kritik_saran' => [
                'module' => 'Pengunjung',
                'label' => 'Kritik dan Saran',
                'keywords' => ['lihat_kritik_saran_pengunjung', 'kelola_kritik_saran_pengunjung'],
            ],
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
            $moduleOrder = ['Data Master' => 1, 'Sistem Manajemen Gudang' => 2, 'Sistem Arsip Dokumen' => 3, 'Pengunjung' => 4, 'Lainnya' => 99];
            $aModuleOrder = $moduleOrder[$a['module']] ?? 50;
            $bModuleOrder = $moduleOrder[$b['module']] ?? 50;

            if ($aModuleOrder !== $bModuleOrder) {
                return $aModuleOrder - $bModuleOrder;
            }

            // Custom order within "Sistem Arsip Dokumen"
            if ($a['module'] === 'Sistem Arsip Dokumen') {
                $order = [
                    'Dashboard Arsip' => 1,
                    'Kategori Arsip' => 2,
                    'Klasifikasi Dokumen' => 3,
                    'Penyimpanan Divisi' => 4,
                    'Lihat Arsip Digital' => 5,
                    'Kelola Arsip Digital' => 6,
                    'Akses Pencarian Dokumen' => 7,
                    'Laporan Arsip' => 8,
                ];
                
                $aOrder = $order[$a['label']] ?? 99;
                $bOrder = $order[$b['label']] ?? 99;

                if ($aOrder !== $bOrder) {
                    return $aOrder - $bOrder;
                }
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
