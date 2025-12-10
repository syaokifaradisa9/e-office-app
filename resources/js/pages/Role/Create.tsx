import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';

import Button from '../../components/buttons/Button';
import FormInput from '../../components/forms/FormInput';
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
    label: string;
    permissions: string[];
}

interface Props {
    role?: Role;
    permissionsGrouped: Record<string, PermissionGroup>;
}

export default function RoleCreate({ role, permissionsGrouped }: Props) {
    const isEdit = !!role;

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
        return permissions.every((p) => data.permissions.includes(p));
    };

    const isGroupPartiallyChecked = (permissions: string[]) => {
        return permissions.some((p) => data.permissions.includes(p)) && !isGroupChecked(permissions);
    };

    return (
        <RootLayout title={isEdit ? 'Edit Role' : 'Tambah Role'} backPath="/role">
            <div className="mx-auto max-w-4xl">
                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div className="border-b border-gray-200 px-6 py-4 dark:border-slate-700">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-white">{isEdit ? 'Edit Role' : 'Tambah Role Baru'}</h2>
                        <p className="mt-1 text-sm text-gray-500 dark:text-slate-400">Isi informasi role dan pilih permissions</p>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6 p-6">
                        <FormInput name="name" label="Nama Role" placeholder="Masukkan nama role" value={data.name} onChange={(e) => setData('name', e.target.value)} error={errors.name} required />

                        <div className="space-y-4">
                            <label className="text-sm font-medium text-gray-700 dark:text-slate-300">Permissions</label>

                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {Object.entries(permissionsGrouped).map(([key, group]) => (
                                    <div key={key} className="rounded-lg border border-gray-200 p-4 dark:border-slate-600">
                                        <div className="mb-3 flex items-center gap-2">
                                            <input
                                                type="checkbox"
                                                id={`group-${key}`}
                                                checked={isGroupChecked(group.permissions)}
                                                ref={(el) => {
                                                    if (el) el.indeterminate = isGroupPartiallyChecked(group.permissions);
                                                }}
                                                onChange={(e) => handleGroupChange(group.permissions, e.target.checked)}
                                                className="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                            />
                                            <label htmlFor={`group-${key}`} className="text-sm font-semibold text-gray-900 dark:text-white">
                                                {group.label}
                                            </label>
                                        </div>
                                        <div className="space-y-2 pl-6">
                                            {group.permissions.map((permission) => (
                                                <div key={permission} className="flex items-center gap-2">
                                                    <input
                                                        type="checkbox"
                                                        id={permission}
                                                        checked={data.permissions.includes(permission)}
                                                        onChange={(e) => handlePermissionChange(permission, e.target.checked)}
                                                        className="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                                    />
                                                    <label htmlFor={permission} className="text-sm text-gray-600 dark:text-slate-400">
                                                        {permission}
                                                    </label>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>

                            {errors.permissions && <p className="text-sm text-red-500">{errors.permissions}</p>}
                        </div>

                        <div className="flex justify-end gap-3 border-t border-gray-200 pt-6 dark:border-slate-700">
                            <Button href="/role" label="Batal" variant="secondary" />
                            <Button type="submit" label={isEdit ? 'Simpan Perubahan' : 'Simpan'} icon={<Save className="h-4 w-4" />} isLoading={processing} />
                        </div>
                    </form>
                </div>
            </div>
        </RootLayout>
    );
}
