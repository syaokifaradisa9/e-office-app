import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { useEffect } from 'react';

import Button from '@/components/buttons/Button';
import FormInput from '@/components/forms/FormInput';
import FormSelect from '@/components/forms/FormSelect';
import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';

interface Classification {
    id: number;
    parent_id: number | null;
    code: string;
    name: string;
    description: string | null;
}

interface Props {
    classification?: Classification;
    classifications: Classification[];
}

export default function ClassificationCreate({ classification, classifications }: Props) {
    const isEdit = !!classification;

    // Get parent_id from URL if creating sub-classification
    const queryParams = new URLSearchParams(window.location.search);
    const defaultParentId = queryParams.get('parent_id') || '';

    const { data, setData, post, put, processing, errors } = useForm({
        code: classification?.code || '',
        name: classification?.name || '',
        parent_id: classification?.parent_id?.toString() || defaultParentId,
        description: classification?.description || '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            put(`/archieve/classifications/${classification.id}`);
        } else {
            post('/archieve/classifications');
        }
    };

    const parentOptions = classifications
        .filter((c) => !isEdit || c.id !== classification.id) // Prevent self-referencing
        .map((c) => ({
            value: c.id.toString(),
            label: `[${c.code}] ${c.name}`,
        }));

    return (
        <RootLayout title={isEdit ? 'Edit Klasifikasi Dokumen' : 'Tambah Klasifikasi Dokumen'}>
            <ContentCard
                title={isEdit ? 'Edit Klasifikasi Dokumen' : 'Tambah Klasifikasi Dokumen Baru'}
                subtitle={isEdit ? 'Perbarui informasi detail untuk klasifikasi dokumen ini' : 'Buat klasifikasi dokumen baru untuk mengatur tata kelola dokumen di sistem'}
                backPath="/archieve/classifications"
                mobileFullWidth
            >
                <p className="mb-6 text-sm text-gray-500 dark:text-slate-400">Isi informasi klasifikasi dokumen di bawah ini. Anda dapat membuat sub-klasifikasi dengan memilih Induk.</p>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <FormInput
                            name="code"
                            label="Kode Klasifikasi"
                            placeholder="Contoh: HK.01.01"
                            value={data.code}
                            onChange={(e) => setData('code', e.target.value)}
                            error={errors.code}
                            required
                        />

                        <FormSelect
                            name="parent_id"
                            label="Induk Klasifikasi (Opsional)"
                            placeholder="Pilih induk jika ini adalah sub-klasifikasi"
                            options={parentOptions}
                            value={data.parent_id}
                            onChange={(e) => setData('parent_id', e.target.value)}
                            error={errors.parent_id}
                        />
                    </div>

                    <FormInput
                        name="name"
                        label="Nama Klasifikasi"
                        placeholder="Contoh: Peraturan Perundang-undangan"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        error={errors.name}
                        required
                    />

                    <div className="space-y-1.5">
                        <label className="text-sm font-medium text-gray-700 dark:text-slate-300">Deskripsi</label>
                        <textarea
                            placeholder="Masukkan deskripsi klasifikasi (opsional)"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            rows={4}
                            className="w-full rounded-lg border border-gray-400/50 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 dark:border-slate-600 dark:bg-slate-800/40 dark:text-slate-200 dark:placeholder:text-slate-400"
                        />
                        {errors.description && <p className="text-sm text-red-500">{errors.description}</p>}
                    </div>

                    <div className="flex justify-end gap-3 border-t border-gray-200 pt-6 dark:border-slate-700">
                        <Button href="/archieve/classifications" label="Batal" variant="secondary" />
                        <Button type="submit" label={isEdit ? 'Simpan Perubahan' : 'Simpan'} icon={<Save className="h-4 w-4" />} isLoading={processing} />
                    </div>
                </form>
            </ContentCard>
        </RootLayout>
    );
}
