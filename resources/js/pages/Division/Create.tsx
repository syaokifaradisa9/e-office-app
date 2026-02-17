import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';

import Button from '../../components/buttons/Button';
import FormInput from '../../components/forms/FormInput';
import ContentCard from '../../components/layouts/ContentCard';
import RootLayout from '../../components/layouts/RootLayout';

interface Division {
    id: number;
    name: string;
    description: string | null;
    is_active: boolean;
}

interface Props {
    division?: Division;
}

export default function DivisionCreate({ division }: Props) {
    const isEdit = !!division;

    const { data, setData, post, put, processing, errors } = useForm({
        name: division?.name || '',
        description: division?.description || '',
        is_active: division?.is_active ?? true,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            put(`/division/${division.id}/update`);
        } else {
            post('/division/store');
        }
    };

    return (
        <RootLayout title={isEdit ? 'Edit Divisi' : 'Tambah Divisi'} backPath="/division">
            <ContentCard
                title={isEdit ? 'Edit Divisi' : 'Tambah Divisi Baru'}
                subtitle="Isi informasi divisi di bawah ini"
                backPath="/division"
                mobileFullWidth
                bodyClassName="p-1 md:p-6"
            >

                <form onSubmit={handleSubmit} className="space-y-6">
                    <FormInput name="name" label="Nama Divisi" placeholder="Masukkan nama divisi" value={data.name} onChange={(e) => setData('name', e.target.value)} error={errors.name} required />

                    <div className="space-y-1.5">
                        <label className="text-sm font-medium text-gray-700 dark:text-slate-300">Deskripsi</label>
                        <textarea
                            placeholder="Masukkan deskripsi divisi (opsional)"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            rows={4}
                            className="w-full rounded-lg border border-gray-400/50 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 dark:border-slate-600 dark:bg-slate-800/40 dark:text-slate-200 dark:placeholder:text-slate-400"
                        />
                        {errors.description && <p className="text-sm text-red-500">{errors.description}</p>}
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
                            Divisi Aktif
                        </label>
                    </div>

                    <div className="flex justify-end gap-3 border-t border-gray-200 pt-6 dark:border-slate-700">
                        <Button href="/division" label="Batal" variant="secondary" />
                        <Button type="submit" label={isEdit ? 'Simpan Perubahan' : 'Simpan'} icon={<Save className="h-4 w-4" />} isLoading={processing} />
                    </div>
                </form>
            </ContentCard>
        </RootLayout>
    );
}
