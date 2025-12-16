import { useForm } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import { Save, Shield, Search, CheckSquare, Square, ChevronDown, ChevronRight, Database, Warehouse } from 'lucide-react';

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

    // Group permissions by module
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

    const totalPermissions = Object.values(permissionsGrouped).reduce((acc, g) => acc + g.permissions.length, 0);

    const getModuleIcon = (moduleName: string) => {
        switch (moduleName) {
            case 'Data Master':
                return <Database className="size-5" />;
            case 'Sistem Manajemen Gudang':
                return <Warehouse className="size-5" />;
            default:
                return <Shield className="size-5" />;
        }
    };

    const getModuleColor = (moduleName: string) => {
        switch (moduleName) {
            case 'Data Master':
                return 'from-blue-500 to-blue-600';
            case 'Sistem Manajemen Gudang':
                return 'from-emerald-500 to-emerald-600';
            default:
                return 'from-slate-500 to-slate-600';
        }
    };

    const formatPermissionLabel = (permission: string) => {
        const overrides: Record<string, string> = {
            'lihat_semua_stock_opname': 'Lihat Stock Opname Keseluruhan',
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
                    <div className="space-y-4">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div className="flex items-center gap-2">
                                <Shield className="size-5 text-primary" />
                                <h3 className="text-lg font-semibold text-slate-800 dark:text-slate-200">Permissions</h3>
                                <span className="rounded-full bg-primary/10 px-2 py-0.5 text-sm text-primary">
                                    {data.permissions.length} / {totalPermissions}
                                </span>
                            </div>

                            <div className="flex flex-wrap items-center gap-2">
                                {/* Search */}
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                                    <input
                                        type="text"
                                        placeholder="Cari permission..."
                                        value={searchQuery}
                                        onChange={(e) => setSearchQuery(e.target.value)}
                                        className="rounded-lg border border-slate-200 bg-white py-2 pl-9 pr-4 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                    />
                                </div>

                                <Button
                                    type="button"
                                    variant="secondary"
                                    label="Pilih Semua"
                                    icon={<CheckSquare className="size-4" />}
                                    onClick={handleSelectAll}
                                    className="!py-2 !px-3 text-xs"
                                />
                                <Button
                                    type="button"
                                    variant="secondary"
                                    label="Hapus Semua"
                                    icon={<Square className="size-4" />}
                                    onClick={handleDeselectAll}
                                    className="!py-2 !px-3 text-xs"
                                />
                            </div>
                        </div>

                        {/* Permission Groups by Module */}
                        <div className="space-y-6">
                            {Object.entries(groupedByModule).map(([moduleName, groups]) => (
                                <div key={moduleName} className="space-y-3">
                                    {/* Module Header */}
                                    <div className="flex items-center gap-3">
                                        <div className={`flex size-8 items-center justify-center rounded-lg bg-gradient-to-r ${getModuleColor(moduleName)} text-white`}>
                                            {getModuleIcon(moduleName)}
                                        </div>
                                        <h4 className="text-lg font-semibold text-slate-800 dark:text-slate-200">{moduleName}</h4>
                                        <div className="h-px flex-1 bg-slate-200 dark:bg-slate-700" />
                                    </div>

                                    {/* Permission Groups */}
                                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                        {groups.map(({ key, group }) => {
                                            const isExpanded = expandedGroups.has(key);
                                            const checkedCount = group.permissions.filter((p) => data.permissions.includes(p)).length;

                                            return (
                                                <div
                                                    key={key}
                                                    className="overflow-hidden rounded-xl border border-slate-200 bg-white transition-all dark:border-slate-700 dark:bg-slate-800"
                                                >
                                                    {/* Group Header */}
                                                    <div
                                                        className={`flex cursor-pointer items-center justify-between px-4 py-3 transition-colors ${isGroupChecked(group.permissions) ? 'bg-primary/5 dark:bg-primary/10' : 'hover:bg-slate-50 dark:hover:bg-slate-700/50'
                                                            }`}
                                                        onClick={() => toggleGroup(key)}
                                                    >
                                                        <div className="flex items-center gap-3">
                                                            {/* Hide group checkbox for exclusive groups */}
                                                            {!isExclusiveGroup(key) && (
                                                                <input
                                                                    type="checkbox"
                                                                    id={`group-${key}`}
                                                                    data-dusk={`permission-group-${key}`}
                                                                    checked={isGroupChecked(group.permissions)}
                                                                    ref={(el) => {
                                                                        if (el) el.indeterminate = isGroupPartiallyChecked(group.permissions);
                                                                    }}
                                                                    onChange={(e) => {
                                                                        e.stopPropagation();
                                                                        handleGroupChange(group.permissions, e.target.checked);
                                                                    }}
                                                                    onClick={(e) => e.stopPropagation()}
                                                                    className="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary"
                                                                />
                                                            )}
                                                            <label
                                                                htmlFor={`group-${key}`}
                                                                className="cursor-pointer text-sm font-semibold text-slate-800 dark:text-white"
                                                                onClick={(e) => e.stopPropagation()}
                                                            >
                                                                {group.label}
                                                                {isExclusiveGroup(key) && (
                                                                    <span className="ml-2 text-xs font-normal text-slate-500">(pilih salah satu)</span>
                                                                )}
                                                            </label>
                                                        </div>
                                                        <div className="flex items-center gap-2">
                                                            <span className="text-xs text-slate-500">
                                                                {checkedCount}/{isExclusiveGroup(key) ? '1' : group.permissions.length}
                                                            </span>
                                                            {isExpanded ? (
                                                                <ChevronDown className="size-4 text-slate-400" />
                                                            ) : (
                                                                <ChevronRight className="size-4 text-slate-400" />
                                                            )}
                                                        </div>
                                                    </div>

                                                    {/* Group Permissions */}
                                                    {isExpanded && (
                                                        <div className="border-t border-slate-100 px-4 py-3 dark:border-slate-700">
                                                            <div className="space-y-2">
                                                                {group.permissions.map((permission) => (
                                                                    <label
                                                                        key={permission}
                                                                        className="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 transition-colors hover:bg-slate-50 dark:hover:bg-slate-700/50"
                                                                    >
                                                                        <input
                                                                            type={isExclusiveGroup(key) ? 'radio' : 'checkbox'}
                                                                            name={isExclusiveGroup(key) ? `exclusive-${key}` : undefined}
                                                                            data-dusk={`permission-${permission}`}
                                                                            checked={data.permissions.includes(permission)}
                                                                            onChange={(e) => {
                                                                                if (isExclusiveGroup(key)) {
                                                                                    handleExclusivePermissionChange(permission, group.permissions);
                                                                                } else {
                                                                                    handlePermissionChange(permission, e.target.checked);
                                                                                }
                                                                            }}
                                                                            className="h-4 w-4 border-slate-300 text-primary focus:ring-primary"
                                                                        />
                                                                        <span className="text-sm text-slate-600 dark:text-slate-400">
                                                                            {formatPermissionLabel(permission)}
                                                                        </span>
                                                                    </label>
                                                                ))}
                                                            </div>
                                                        </div>
                                                    )}
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            ))}
                        </div>

                        {Object.keys(filteredGroups).length === 0 && (
                            <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 py-12 dark:border-slate-600">
                                <Search className="mb-2 size-8 text-slate-400" />
                                <p className="text-sm text-slate-500">Tidak ada permission yang cocok dengan pencarian</p>
                            </div>
                        )}

                        {errors.permissions && <p className="text-sm text-red-500">{errors.permissions}</p>}
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
