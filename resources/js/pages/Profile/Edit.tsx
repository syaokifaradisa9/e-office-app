import { useForm, usePage } from '@inertiajs/react';
import { Save } from 'lucide-react';

import Button from '../../components/buttons/Button';
import FormInput from '../../components/forms/FormInput';
import ContentCard from '../../components/layouts/ContentCard';
import RootLayout from '../../components/layouts/RootLayout';

interface AuthUser {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    address: string | null;
}

interface PageProps {
    auth?: {
        user: AuthUser;
    };
    [key: string]: unknown;
}

export default function ProfileEdit() {
    const { auth } = usePage<PageProps>().props;
    const user = auth?.user;

    const { data, setData, put, processing, errors } = useForm({
        name: user?.name || '',
        email: user?.email || '',
        phone: user?.phone || '',
        address: user?.address || '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put('/profile/update');
    };

    return (
        <RootLayout title="Edit Profil">
            <ContentCard
                title="Edit Profil"
                mobileFullWidth
                bodyClassName="p-1 md:p-6"
            >
                <p className="mb-6 text-sm text-gray-500 dark:text-slate-400">Perbarui informasi profil Anda</p>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <FormInput name="name" label="Nama Lengkap" placeholder="Masukkan nama lengkap" value={data.name} onChange={(e) => setData('name', e.target.value)} error={errors.name} required />

                    <FormInput name="email" label="Email" type="email" placeholder="Masukkan email" value={data.email} onChange={(e) => setData('email', e.target.value)} error={errors.email} required />

                    <FormInput name="phone" label="Nomor Telepon" placeholder="Masukkan nomor telepon (opsional)" value={data.phone} onChange={(e) => setData('phone', e.target.value)} error={errors.phone} />

                    <div className="space-y-1.5">
                        <label className="text-sm font-medium text-gray-700 dark:text-slate-300">Alamat</label>
                        <textarea
                            placeholder="Masukkan alamat (opsional)"
                            value={data.address}
                            onChange={(e) => setData('address', e.target.value)}
                            rows={4}
                            className="w-full rounded-lg border border-gray-400/50 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 dark:border-slate-600 dark:bg-slate-800/40 dark:text-slate-200 dark:placeholder:text-slate-400"
                        />
                        {errors.address && <p className="text-sm text-red-500">{errors.address}</p>}
                    </div>

                    <div className="flex justify-end gap-3 border-t border-gray-200 pt-6 dark:border-slate-700">
                        <Button type="submit" label="Simpan Perubahan" icon={<Save className="h-4 w-4" />} isLoading={processing} />
                    </div>
                </form>
            </ContentCard>
        </RootLayout>
    );
}
