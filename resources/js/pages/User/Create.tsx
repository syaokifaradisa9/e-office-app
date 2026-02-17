import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';

import Button from '../../components/buttons/Button';
import FormInput from '../../components/forms/FormInput';
import ContentCard from '../../components/layouts/ContentCard';
import RootLayout from '../../components/layouts/RootLayout';

interface Division {
    id: number;
    name: string;
}

interface Position {
    id: number;
    name: string;
}

interface Role {
    id: number;
    name: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    address: string | null;
    division_id: number | null;
    position_id: number | null;
    is_active: boolean;
    roles: Role[];
}

interface Props {
    user?: User;
    divisions: Division[];
    positions: Position[];
    roles: Role[];
}

export default function UserCreate({ user, divisions, positions, roles }: Props) {
    const isEdit = !!user;

    const { data, setData, post, put, processing, errors } = useForm({
        name: user?.name || '',
        email: user?.email || '',
        password: '',
        password_confirmation: '',
        phone: user?.phone || '',
        address: user?.address || '',
        division_id: user?.division_id?.toString() || '',
        position_id: user?.position_id?.toString() || '',
        role_id: user?.roles[0]?.id?.toString() || '',
        is_active: user?.is_active ?? true,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            put(`/user/${user.id}/update`);
        } else {
            post('/user/store');
        }
    };

    return (
        <RootLayout title={isEdit ? 'Edit Pengguna' : 'Tambah Pengguna'} backPath="/user">
            <ContentCard
                title={isEdit ? 'Edit Pengguna' : 'Tambah Pengguna Baru'}
                mobileFullWidth
                bodyClassName="p-1 md:p-6"
            >
                <p className="mb-6 text-sm text-gray-500 dark:text-slate-400">Isi informasi pengguna di bawah ini</p>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <FormInput name="name" label="Nama Lengkap" placeholder="Masukkan nama lengkap" value={data.name} onChange={(e) => setData('name', e.target.value)} error={errors.name} required />

                    <FormInput name="email" label="Email" type="email" placeholder="Masukkan email" value={data.email} onChange={(e) => setData('email', e.target.value)} error={errors.email} required />

                    <div className="grid gap-6 sm:grid-cols-2">
                        <FormInput
                            name="password"
                            label={isEdit ? 'Password Baru (opsional)' : 'Password'}
                            type="password"
                            placeholder="Masukkan password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            error={errors.password}
                            required={!isEdit}
                        />
                        <FormInput
                            name="password_confirmation"
                            label="Konfirmasi Password"
                            type="password"
                            placeholder="Konfirmasi password"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            required={!isEdit && data.password !== ''}
                        />
                    </div>

                    <FormInput name="phone" label="Nomor Telepon" placeholder="Masukkan nomor telepon (opsional)" value={data.phone} onChange={(e) => setData('phone', e.target.value)} error={errors.phone} />

                    <div className="space-y-1.5">
                        <label className="text-sm font-medium text-gray-700 dark:text-slate-300">Alamat</label>
                        <textarea
                            placeholder="Masukkan alamat (opsional)"
                            value={data.address}
                            onChange={(e) => setData('address', e.target.value)}
                            rows={3}
                            className="w-full rounded-lg border border-gray-400/50 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 dark:border-slate-600 dark:bg-slate-800/40 dark:text-slate-200 dark:placeholder:text-slate-400"
                        />
                        {errors.address && <p className="text-sm text-red-500">{errors.address}</p>}
                    </div>

                    <div className="grid gap-6 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <label className="text-sm font-medium text-gray-700 dark:text-slate-300">Divisi</label>
                            <select
                                value={data.division_id}
                                onChange={(e) => setData('division_id', e.target.value)}
                                className="w-full rounded-lg border border-gray-400/50 bg-white px-4 py-3 text-sm text-gray-900 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 dark:border-slate-600 dark:bg-slate-800/40 dark:text-slate-200"
                            >
                                <option value="">Pilih Divisi</option>
                                {divisions.map((division) => (
                                    <option key={division.id} value={division.id}>
                                        {division.name}
                                    </option>
                                ))}
                            </select>
                            {errors.division_id && <p className="text-sm text-red-500">{errors.division_id}</p>}
                        </div>

                        <div className="space-y-1.5">
                            <label className="text-sm font-medium text-gray-700 dark:text-slate-300">Jabatan</label>
                            <select
                                value={data.position_id}
                                onChange={(e) => setData('position_id', e.target.value)}
                                className="w-full rounded-lg border border-gray-400/50 bg-white px-4 py-3 text-sm text-gray-900 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 dark:border-slate-600 dark:bg-slate-800/40 dark:text-slate-200"
                            >
                                <option value="">Pilih Jabatan</option>
                                {positions.map((position) => (
                                    <option key={position.id} value={position.id}>
                                        {position.name}
                                    </option>
                                ))}
                            </select>
                            {errors.position_id && <p className="text-sm text-red-500">{errors.position_id}</p>}
                        </div>
                    </div>

                    <div className="space-y-1.5">
                        <label className="text-sm font-medium text-gray-700 dark:text-slate-300">Role</label>
                        <select
                            value={data.role_id}
                            onChange={(e) => setData('role_id', e.target.value)}
                            className="w-full rounded-lg border border-gray-400/50 bg-white px-4 py-3 text-sm text-gray-900 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 dark:border-slate-600 dark:bg-slate-800/40 dark:text-slate-200"
                        >
                            <option value="">Pilih Role</option>
                            {roles.map((role) => (
                                <option key={role.id} value={role.id}>
                                    {role.name}
                                </option>
                            ))}
                        </select>
                        {errors.role_id && <p className="text-sm text-red-500">{errors.role_id}</p>}
                    </div>

                    <div className="flex items-center gap-3">
                        <input
                            type="checkbox"
                            id="is_active"
                            checked={data.is_active}
                            onChange={(e) => setData('is_active', e.target.checked)}
                            className="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                        />
                        <label htmlFor="is_active" className="text-sm font-medium text-gray-700 dark:text-slate-300">
                            Pengguna Aktif
                        </label>
                    </div>

                    <div className="flex justify-end gap-3 border-t border-gray-200 pt-6 dark:border-slate-700">
                        <Button href="/user" label="Batal" variant="secondary" />
                        <Button type="submit" label={isEdit ? 'Simpan Perubahan' : 'Simpan'} icon={<Save className="h-4 w-4" />} isLoading={processing} />
                    </div>
                </form>
            </ContentCard>
        </RootLayout>
    );
}
