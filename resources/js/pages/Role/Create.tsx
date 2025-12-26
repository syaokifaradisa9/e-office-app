import { useForm } from '@inertiajs/react';
import { useState, useMemo, useEffect } from 'react';
import { Save, Shield, Search, CheckSquare, Square, ChevronDown, ChevronRight, Database, Warehouse, Check, X, Filter } from 'lucide-react';

import Button from '../../components/buttons/Button';
import FormInput from '../../components/forms/FormInput';
import ContentCard from '../../components/layouts/ContentCard';
import RootLayout from '../../components/layouts/RootLayout';

interface Permission {
    id: number;
    name: string;
}

interface Role {
    id: number;
    name: string;
    permissions: Permission[];
}

interface PermissionGroup {
    module: string;
    label: string;
    permissions: string[];
}

interface Props {
    role?: Role;
    permissionsGrouped: Record<string, PermissionGroup>;
}

// Groups where only ONE permission can be selected (radio behavior)
const EXCLUSIVE_GROUPS = ['dashboard_gudang', 'monitoring_stok', 'monitoring_transaksi', 'laporan_gudang'];

export default function RoleCreate({ role, permissionsGrouped }: Props) {
    const isEdit = !!role;
    const [searchQuery, setSearchQuery] = useState('');
    const [expandedGroups, setExpandedGroups] = useState<Set<string>>(new Set(Object.keys(permissionsGrouped)));
    const [activeTab, setActiveTab] = useState('');

    const isExclusiveGroup = (groupKey: string) => EXCLUSIVE_GROUPS.includes(groupKey);

    const { data, setData, post, put, processing, errors } = useForm({
        name: role?.name || '',
        permissions: role?.permissions.map((p) => p.name) || ([] as string[]),
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            put(`/role/${role.id}/update`);
        } else {
            post('/role/store');
        }
    };

    const handlePermissionChange = (permission: string, checked: boolean) => {
        if (checked) {
            setData('permissions', [...data.permissions, permission]);
        } else {
            setData(
                'permissions',
                data.permissions.filter((p) => p !== permission),
            );
        }
    };

    // Handler for exclusive groups (radio behavior - only one can be selected)
    const handleExclusivePermissionChange = (permission: string, groupPermissions: string[]) => {
        // Remove all other permissions in this group, then add the selected one
        const filteredPermissions = data.permissions.filter((p) => !groupPermissions.includes(p));
        setData('permissions', [...filteredPermissions, permission]);
    };

    const handleGroupChange = (permissions: string[], checked: boolean) => {
        if (checked) {
            const newPermissions = [...new Set([...data.permissions, ...permissions])];
            setData('permissions', newPermissions);
        } else {
            setData(
                'permissions',
                data.permissions.filter((p) => !permissions.includes(p)),
            );
        }
    };

    const isGroupChecked = (permissions: string[]) => {
        return permissions.length > 0 && permissions.every((p) => data.permissions.includes(p));
    };

    const isGroupPartiallyChecked = (permissions: string[]) => {
        return permissions.some((p) => data.permissions.includes(p)) && !isGroupChecked(permissions);
    };

    const toggleGroup = (groupKey: string) => {
        const newExpanded = new Set(expandedGroups);
        if (newExpanded.has(groupKey)) {
            newExpanded.delete(groupKey);
        } else {
            newExpanded.add(groupKey);
        }
        setExpandedGroups(newExpanded);
    };

    const handleSelectAll = () => {
        const allPermissions = Object.values(permissionsGrouped).flatMap((g) => g.permissions);
        setData('permissions', allPermissions);
    };

    const handleDeselectAll = () => {
        setData('permissions', []);
    };

    // Filter groups based on search
    const filteredGroups = useMemo(() => {
        if (!searchQuery.trim()) return permissionsGrouped;

        const filtered: Record<string, PermissionGroup> = {};
        const query = searchQuery.toLowerCase();

        Object.entries(permissionsGrouped).forEach(([key, group]) => {
            const matchingPermissions = group.permissions.filter(
                (p) => p.toLowerCase().includes(query) || group.label.toLowerCase().includes(query),
            );

            if (matchingPermissions.length > 0) {
                filtered[key] = {
                    ...group,
                    permissions: matchingPermissions,
                };
            }
        });

        return filtered;
    }, [permissionsGrouped, searchQuery]);

    const groupedByModule = useMemo(() => {
        const moduleGroups: Record<string, { key: string; group: PermissionGroup }[]> = {};

        Object.entries(filteredGroups).forEach(([key, group]) => {
            const moduleName = group.module || 'Lainnya';
            if (!moduleGroups[moduleName]) {
                moduleGroups[moduleName] = [];
            }
            moduleGroups[moduleName].push({ key, group });
        });

        return moduleGroups;
    }, [filteredGroups]);

    // Available modules for tabs
    const availableModules = useMemo(() => Object.keys(groupedByModule), [groupedByModule]);

    // Set default active tab
    useEffect(() => {
        if (availableModules.length > 0 && !activeTab) {
            setActiveTab(availableModules[0]);
        }
    }, [availableModules, activeTab]);

    const totalPermissions = Object.values(permissionsGrouped).reduce((acc, g) => acc + g.permissions.length, 0);

    const getModuleIcon = (moduleName: string) => {
        switch (moduleName) {
            case 'Data Master':
            case 'DATA MASTER':
                return <Database className="size-5" />;
            case 'Sistem Manajemen Gudang':
                return <Warehouse className="size-5" />;
            case 'Sistem Arsip Dokumen':
                return <Shield className="size-5 text-indigo-500" />;
            default:
                return <Shield className="size-5" />;
        }
    };

    const handleExpandAll = () => {
        setExpandedGroups(new Set(Object.keys(permissionsGrouped)));
    };

    const handleCollapseAll = () => {
        setExpandedGroups(new Set());
    };

    const getModuleColor = (moduleName: string) => {
        switch (moduleName) {
            case 'Data Master':
                return 'from-blue-500 to-blue-600';
            case 'Sistem Manajemen Gudang':
                return 'from-emerald-500 to-emerald-600';
            case 'Sistem Arsip Dokumen':
                return 'from-indigo-500 to-indigo-600';
            default:
                return 'from-slate-500 to-slate-600';
        }
    };

    const formatPermissionLabel = (permission: string) => {
        const overrides: Record<string, string> = {
            lihat_divisi: 'Lihat Data Divisi',
            kelola_divisi: 'Kelola Data Divisi',
            lihat_jabatan: 'Lihat Data Jabatan',
            kelola_jabatan: 'Kelola Data Jabatan',
            lihat_pengguna: 'Lihat Data Pengguna',
            kelola_pengguna: 'Kelola Data Pengguna',
            lihat_role: 'Lihat Data Role',
            kelola_role: 'Kelola Data Role',
            lihat_kategori: 'Lihat Data Kategori Barang Gudang',
            kelola_kategori: 'Kelola Data Kategori Barang Gudang',
            lihat_barang: 'Lihat Data Barang Gudang',
            kelola_barang: 'Kelola Data Barang Gudang',
            konversi_stok_barang: 'Konversi Stok Barang',
            pengeluaran_stok_barang: 'Pengeluaran Stok Barang',
            konversi_barang_gudang: 'Konversi Barang Gudang',
            pengeluaran_barang_gudang: 'Pengeluaran Barang Gudang',
            lihat_permintaan_barang_divisi: 'Lihat Data Permintaan Barang Divisi',
            lihat_semua_permintaan_barang: 'Lihat Data Permintaan Barang Keseluruhan',
            buat_permintaan_barang: 'Pengajuan Permintaan Barang',
            konfirmasi_permintaan_barang: 'Konfirmasi Permintaan Barang',
            serah_terima_barang: 'Penyerahan Barang',
            terima_barang: 'Penerimaan Barang',
            lihat_stok_divisi: 'Lihat Data Stok Divisi',
            lihat_semua_stok: 'Lihat Data Stok Keseluruhan',
            lihat_dashboard_gudang_utama: 'Lihat Dashboard Gudang Utama',
            lihat_dashboard_gudang_divisi: 'Lihat Dashboard Gudang Divisi',
            lihat_dashboard_gudang_keseluruhan: 'Lihat Dashboard Gudang Keseluruhan',
            lihat_stock_opname_gudang: 'Lihat Stock Opname Gudang',
            lihat_stock_opname_divisi: 'Lihat Stock Opname Divisi',
            lihat_semua_stock_opname: 'Lihat Semua Stock Opname',
            kelola_stock_opname_gudang: 'Kelola Stock Opname Gudang',
            kelola_stock_opname_divisi: 'Kelola Stock Opname Divisi',
            konfirmasi_stock_opname: 'Konfirmasi Stock Opname',
            monitor_transaksi_barang: 'Monitor Transaksi Barang Divisi',
            monitor_semua_transaksi_barang: 'Monitor Semua Transaksi Barang',
            lihat_laporan_gudang_divisi: 'Lihat Laporan Gudang Divisi',
            lihat_laporan_gudang_semua: 'Lihat Semua Laporan Gudang',
            lihat_kategori_arsip: 'Lihat Data Kategori Arsip',
            kelola_kategori_arsip: 'Kelola Data Kategori Arsip',
            lihat_arsip_divisi: 'Lihat Arsip Divisi',
            kelola_arsip_divisi: 'Kelola Arsip Divisi',
            lihat_arsip_pribadi: 'Lihat Arsip Pribadi',
            lihat_semua_arsip: 'Lihat Semua Arsip Digital',
            kelola_semua_arsip: 'Kelola Semua Arsip Digital',
        };

        if (overrides[permission]) {
            return overrides[permission];
        }

        return permission
            .replace(/_/g, ' ')
            .split(' ')
            .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
            .join(' ');
    };

    return (
        <RootLayout title={isEdit ? 'Edit Role' : 'Tambah Role'}>
            <ContentCard title={isEdit ? 'Edit Role' : 'Tambah Role Baru'} backPath="/role" mobileFullWidth>
                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Role Name */}
                    <div className="max-w-md">
                        <FormInput
                            name="name"
                            label="Nama Role"
                            placeholder="Contoh: Admin, Manager, Staff"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            error={errors.name}
                            required
                        />
                    </div>

                    {/* Permissions Section */}
                    <div className="space-y-6">
                        {/* Header Filter & Quick Actions */}
                        <div className="space-y-4 rounded-xl bg-slate-50 p-4 dark:bg-slate-900/50">
                            <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                {/* Search and Filter */}
                                <div className="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center">
                                    <div className="relative flex-1">
                                        <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                                        <input
                                            type="text"
                                            placeholder="Cari permission..."
                                            value={searchQuery}
                                            onChange={(e) => setSearchQuery(e.target.value)}
                                            className="w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-9 pr-4 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                        />
                                    </div>
                                    <div className="relative w-full sm:w-48">
                                        <Filter className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                                        <select className="w-full appearance-none rounded-lg border border-slate-200 bg-white py-2.5 pl-9 pr-8 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                            <option>Semua</option>
                                        </select>
                                        <ChevronDown className="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                                    </div>
                                </div>

                                {/* Quick Actions */}
                                <div className="flex flex-wrap items-center gap-3">
                                    <div className="flex items-center gap-2">
                                        <span className="text-sm font-medium text-slate-500">Aksi Cepat:</span>
                                        <div className="flex rounded-lg bg-slate-200 p-0.5 dark:bg-slate-800">
                                            <button
                                                type="button"
                                                onClick={handleExpandAll}
                                                className="px-3 py-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white"
                                            >
                                                Buka Semua
                                            </button>
                                            <button
                                                type="button"
                                                onClick={handleCollapseAll}
                                                className="px-3 py-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white"
                                            >
                                                Tutup Semua
                                            </button>
                                        </div>
                                    </div>

                                    <div className="h-6 w-px bg-slate-300 dark:bg-slate-700" />

                                    <div className="flex items-center gap-2">
                                        <button
                                            type="button"
                                            onClick={handleSelectAll}
                                            className="flex items-center gap-1.5 rounded-lg border border-emerald-500/50 bg-emerald-500/10 px-3 py-2 text-xs font-bold text-emerald-600 transition-colors hover:bg-emerald-500/20 dark:text-emerald-400"
                                        >
                                            <Check className="size-3.5 stroke-[3px]" /> Pilih Semua
                                        </button>
                                        <button
                                            type="button"
                                            onClick={handleDeselectAll}
                                            className="flex items-center gap-1.5 rounded-lg border border-rose-500/50 bg-rose-500/10 px-3 py-2 text-xs font-bold text-rose-600 transition-colors hover:bg-rose-500/20 dark:text-rose-400"
                                        >
                                            <X className="size-3.5 stroke-[3px]" /> Hapus Semua
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Module Tabs */}
                        <div className="border-b border-slate-200 dark:border-slate-800">
                            <div className="flex flex-wrap gap-1">
                                {availableModules.map((module) => {
                                    const displayLabels: Record<string, string> = {
                                        'Data Master': 'Modules Data Master',
                                        'Sistem Manajemen Gudang': 'Module Gudang',
                                        'Sistem Arsip Dokumen': 'Module Arsip',
                                    };
                                    const label = displayLabels[module] || module;

                                    return (
                                        <button
                                            key={module}
                                            type="button"
                                            onClick={() => setActiveTab(module)}
                                            className={`px-6 py-4 text-sm font-bold transition-all relative border-b-2 ${activeTab === module
                                                ? 'text-primary border-primary'
                                                : 'text-slate-500 border-transparent hover:text-slate-700 hover:border-slate-300'
                                                }`}
                                        >
                                            <div className="flex items-center gap-2">
                                                {getModuleIcon(module)}
                                                {label}
                                                <span className={`ml-1 text-[10px] rounded-full px-1.5 py-0.5 ${activeTab === module ? 'bg-primary/10' : 'bg-slate-100 dark:bg-slate-800'}`}>
                                                    {groupedByModule[module]?.length || 0}
                                                </span>
                                            </div>
                                        </button>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Permission Groups by Module */}
                        <div className="space-y-6 min-h-[400px]">
                            {activeTab && groupedByModule[activeTab] && (
                                <div className="space-y-6">
                                    {/* Permission Groups Grid */}
                                    <div className="grid gap-6 md:grid-cols-2">
                                        {groupedByModule[activeTab].map(({ key, group }) => {
                                            const isExpanded = expandedGroups.has(key);
                                            const checkedCount = group.permissions.filter((p) => data.permissions.includes(p)).length;
                                            const progress = Math.round((checkedCount / group.permissions.length) * 100);
                                            const allChecked = checkedCount === group.permissions.length;

                                            return (
                                                <div
                                                    key={key}
                                                    className="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-all duration-300 hover:border-primary/30 hover:shadow-md dark:border-slate-800 dark:bg-slate-900/50"
                                                >
                                                    {/* Card Header */}
                                                    <div className="flex items-center justify-between p-4">
                                                        <div className="flex flex-1 cursor-pointer items-center gap-3" onClick={() => toggleGroup(key)}>
                                                            <div className={`flex size-8 items-center justify-center rounded-lg bg-slate-100 transition-colors group-hover:bg-primary/10 dark:bg-slate-800`}>
                                                                {isExpanded ? (
                                                                    <ChevronDown className="size-4 text-slate-500 group-hover:text-primary" />
                                                                ) : (
                                                                    <ChevronRight className="size-4 text-slate-500 group-hover:text-primary" />
                                                                )}
                                                            </div>
                                                            <div>
                                                                <h5 className="text-sm font-bold text-slate-800 dark:text-white">
                                                                    {group.label}
                                                                    {isExclusiveGroup(key) && (
                                                                        <span className="ml-1.5 text-[10px] font-normal text-amber-500">(pilih salah satu)</span>
                                                                    )}
                                                                </h5>
                                                                <p className="text-[11px] font-medium text-slate-500">
                                                                    {checkedCount}/{group.permissions.length} dipilih
                                                                </p>
                                                            </div>
                                                        </div>

                                                        <div className="flex items-center gap-3">
                                                            {allChecked ? (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => handleGroupChange(group.permissions, false)}
                                                                    className="rounded-lg bg-rose-500/10 px-3 py-1 text-[11px] font-bold text-rose-600 transition-colors hover:bg-rose-500/20 dark:text-rose-400"
                                                                >
                                                                    Hapus
                                                                </button>
                                                            ) : (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => handleGroupChange(group.permissions, true)}
                                                                    className="rounded-lg bg-emerald-500/10 px-3 py-1 text-[11px] font-bold text-emerald-600 transition-colors hover:bg-emerald-500/20 dark:text-emerald-400"
                                                                >
                                                                    Pilih
                                                                </button>
                                                            )}

                                                            <div className={`flex min-w-[45px] items-center justify-center rounded-full bg-slate-100 px-2 py-1 text-[11px] font-bold dark:bg-slate-800 ${progress === 100 ? 'text-emerald-500' : progress > 0 ? 'text-blue-500' : 'text-slate-400'
                                                                }`}>
                                                                {progress}%
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {/* Card Content (Permissions Grid) */}
                                                    {isExpanded && (
                                                        <div className="border-t border-slate-100 p-4 dark:border-slate-800">
                                                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                                {group.permissions.map((permission) => (
                                                                    <div
                                                                        key={permission}
                                                                        className={`flex items-center justify-between rounded-xl border border-slate-100 p-2.5 transition-all dark:border-slate-800 ${data.permissions.includes(permission)
                                                                            ? 'border-emerald-500/20 bg-emerald-500/5 dark:bg-emerald-500/10'
                                                                            : 'bg-slate-50 dark:bg-slate-800/30'
                                                                            }`}
                                                                    >
                                                                        <span className={`text-[12px] font-semibold leading-tight ${data.permissions.includes(permission) ? 'text-slate-800 dark:text-emerald-400' : 'text-slate-600 dark:text-slate-400'
                                                                            }`}>
                                                                            {formatPermissionLabel(permission)}
                                                                        </span>

                                                                        {/* Custom Toggle Switch */}
                                                                        <button
                                                                            type="button"
                                                                            onClick={() => {
                                                                                if (isExclusiveGroup(key)) {
                                                                                    handleExclusivePermissionChange(permission, group.permissions);
                                                                                } else {
                                                                                    handlePermissionChange(permission, !data.permissions.includes(permission));
                                                                                }
                                                                            }}
                                                                            className={`relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-slate-900 ${data.permissions.includes(permission) ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700'
                                                                                }`}
                                                                        >
                                                                            <span
                                                                                className={`pointer-events-none inline-block size-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out ${data.permissions.includes(permission) ? 'translate-x-4' : 'translate-x-0'
                                                                                    }`}
                                                                            />
                                                                        </button>
                                                                    </div>
                                                                ))}
                                                            </div>
                                                        </div>
                                                    )}
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}
                        </div>

                        {Object.keys(filteredGroups).length === 0 && (
                            <div className="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 py-16 dark:border-slate-800">
                                <div className="flex size-16 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                                    <Search className="size-8 text-slate-400" />
                                </div>
                                <h4 className="mt-4 text-sm font-bold text-slate-800 dark:text-white">Tidak ada permission ditemukan</h4>
                                <p className="mt-1 text-xs text-slate-500">Coba kata kunci pencarian yang berbeda</p>
                            </div>
                        )}

                        {errors.permissions && (
                            <div className="rounded-lg bg-rose-500/10 p-3 text-sm font-medium text-rose-600 dark:text-rose-400">
                                {errors.permissions}
                            </div>
                        )}
                    </div>

                    {/* Actions */}
                    <div className="flex justify-end gap-3 border-t border-slate-200 pt-6 dark:border-slate-700">
                        <Button href="/role" label="Batal" variant="secondary" />
                        <Button type="submit" label={isEdit ? 'Simpan Perubahan' : 'Simpan Role'} icon={<Save className="h-4 w-4" />} isLoading={processing} />
                    </div>
                </form>
            </ContentCard>
        </RootLayout>
    );
}
