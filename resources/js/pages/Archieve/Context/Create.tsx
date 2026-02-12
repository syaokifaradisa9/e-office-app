import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';

import Button from '@/components/buttons/Button';
import FormInput from '@/components/forms/FormInput';
import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';

interface Context {
    id: number;
    name: string;
    description: string | null;
}

interface Props {
    context?: Context;
}

export default function ContextCreate({ context }: Props) {
    const isEdit = !!context;

    const { data, setData, post, put, processing, errors } = useForm({
        name: context?.name || '',
        description: context?.description || '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            put(`/archieve/contexts/${context.id}`);
        } else {
            post('/archieve/contexts');
        }
    };

    return (
        <RootLayout title={isEdit ? 'Edit Konteks Arsip' : 'Tambah Konteks Arsip'}>
            <ContentCard
                title={isEdit ? 'Edit Konteks Arsip' : 'Tambah Konteks Arsip Baru'}
                subtitle={isEdit ? 'Perbarui informasi detail untuk konteks kategori arsip ini' : 'Buat konteks arsip baru untuk membantu pengelompokan kategori dokumen'}
                backPath="/archieve/contexts"
                mobileFullWidth
            >
                <p className="mb-6 text-sm text-gray-500 dark:text-slate-400">Isi informasi konteks kategori arsip di bawah ini</p>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <FormInput
                        name="name"
                        label="Nama Konteks"
                        placeholder="Masukkan nama konteks (misal: Berdasarkan Fungsi, Berdasarkan Media, dll)"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        error={errors.name}
                        required
                    />

                    <div className="space-y-1.5">
                        <label className="text-sm font-medium text-gray-700 dark:text-slate-300">Deskripsi</label>
                        <textarea
                            placeholder="Masukkan deskripsi konteks (opsional)"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            rows={4}
                            className="w-full rounded-lg border border-gray-400/50 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 dark:border-slate-600 dark:bg-slate-800/40 dark:text-slate-200 dark:placeholder:text-slate-400"
                        />
                        {errors.description && <p className="text-sm text-red-500">{errors.description}</p>}
                    </div>

                    <div className="flex justify-end gap-3 border-t border-gray-200 pt-6 dark:border-slate-700">
                        <Button href="/archieve/contexts" label="Batal" variant="secondary" />
                        <Button type="submit" label={isEdit ? 'Simpan Perubahan' : 'Simpan'} icon={<Save className="h-4 w-4" />} isLoading={processing} />
                    </div>
                </form>
            </ContentCard>
        </RootLayout>
    );
}
