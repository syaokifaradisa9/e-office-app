import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';

import Button from '@/components/buttons/Button';
import FormInput from '@/components/forms/FormInput';
import FormSelect from '@/components/forms/FormSelect';
import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';

interface Category {
    id: number;
    name: string;
    context_id: number;
    description: string | null;
}

interface Context {
    id: number;
    name: string;
}

interface Props {
    category?: Category;
    contexts: Context[];
}

export default function CategoryCreate({ category, contexts }: Props) {
    const isEdit = !!category;

    const { data, setData, post, put, processing, errors } = useForm({
        name: category?.name || '',
        context_id: category?.context_id?.toString() || '',
        description: category?.description || '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            put(`/archieve/categories/${category.id}`);
        } else {
            post('/archieve/categories');
        }
    };

    const typeOptions = contexts.map((c) => ({
        value: c.id.toString(),
        label: c.name,
    }));

    return (
        <RootLayout title={isEdit ? 'Edit Kategori Arsip' : 'Tambah Kategori Arsip'}>
            <ContentCard
                title={isEdit ? 'Edit Kategori Arsip' : 'Tambah Kategori Arsip Baru'}
                subtitle={isEdit ? 'Perbarui informasi detail untuk kategori arsip ini' : 'Buat kategori arsip baru untuk melengkapi sistem pengarsipan Anda'}
                backPath="/archieve/categories"
                mobileFullWidth
            >
                <p className="mb-6 text-sm text-gray-500 dark:text-slate-400">Isi informasi kategori arsip di bawah ini</p>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <FormSelect
                        name="context_id"
                        label="Konteks Kategori"
                        placeholder="Pilih konteks kategori"
                        options={typeOptions}
                        value={data.context_id}
                        onChange={(e) => setData('context_id', e.target.value)}
                        error={errors.context_id}
                        required
                    />

                    <FormInput
                        name="name"
                        label="Nama Kategori"
                        placeholder="Masukkan nama kategori (misal: Arsip Aktif, Arsip Asli, dll)"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        error={errors.name}
                        required
                    />

                    <div className="space-y-1.5">
                        <label className="text-sm font-medium text-gray-700 dark:text-slate-300">Deskripsi</label>
                        <textarea
                            placeholder="Masukkan deskripsi kategori (opsional)"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            rows={4}
                            className="w-full rounded-lg border border-gray-400/50 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 dark:border-slate-600 dark:bg-slate-800/40 dark:text-slate-200 dark:placeholder:text-slate-400"
                        />
                        {errors.description && <p className="text-sm text-red-500">{errors.description}</p>}
                    </div>

                    <div className="flex justify-end gap-3 border-t border-gray-200 pt-6 dark:border-slate-700">
                        <Button href="/archieve/categories" label="Batal" variant="secondary" />
                        <Button type="submit" label={isEdit ? 'Simpan Perubahan' : 'Simpan'} icon={<Save className="h-4 w-4" />} isLoading={processing} />
                    </div>
                </form>
            </ContentCard>
        </RootLayout>
    );
}
