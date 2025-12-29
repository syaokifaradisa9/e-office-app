import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';

import Button from '@/components/buttons/Button';
import FormInput from '@/components/forms/FormInput';
import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';

interface Purpose {
    id: number;
    name: string;
    description: string | null;
    is_active: boolean;
}

interface Props {
    purpose?: Purpose;
}

export default function PurposeCreate({ purpose }: Props) {
    const isEdit = !!purpose;

    const { data, setData, post, put, processing, errors } = useForm({
        name: purpose?.name || '',
        description: purpose?.description || '',
        is_active: purpose?.is_active ?? true,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            put(`/visitor/purposes/${purpose.id}/update`);
        } else {
            post('/visitor/purposes/store');
        }
    };

    return (
        <RootLayout title={isEdit ? 'Edit Keperluan Kunjungan' : 'Tambah Keperluan Kunjungan'} backPath="/visitor/purposes">
            <ContentCard
                title={isEdit ? 'Edit Keperluan Kunjungan' : 'Tambah Keperluan Kunjungan Baru'}
                backPath="/visitor/purposes"
                mobileFullWidth
            >
                <p className="mb-6 text-sm text-gray-500 dark:text-slate-400">Isi informasi keperluan kunjungan di bawah ini</p>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <FormInput
                        name="name"
                        label="Nama Keperluan"
                        placeholder="Contoh: Kunjungan Dinas"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        error={errors.name}
                        required
                    />

                    <div className="space-y-1.5">
                        <label className="text-sm font-medium text-gray-700 dark:text-slate-300">Deskripsi</label>
                        <textarea
                            placeholder="Deskripsi singkat tentang keperluan ini (opsional)"
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
                            Keperluan Aktif
                        </label>
                    </div>

                    <div className="flex justify-end gap-3 border-t border-gray-200 pt-6 dark:border-slate-700">
                        <Button href="/visitor/purposes" label="Batal" variant="secondary" />
                        <Button type="submit" label={isEdit ? 'Simpan Perubahan' : 'Simpan'} icon={<Save className="h-4 w-4" />} isLoading={processing} />
                    </div>
                </form>
            </ContentCard>
        </RootLayout>
    );
}
