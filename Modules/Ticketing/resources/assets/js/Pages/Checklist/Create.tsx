import RootLayout from '@/components/layouts/RootLayout';
import ContentCard from '@/components/layouts/ContentCard';
import FormInput from '@/components/forms/FormInput';
import FormTextArea from '@/components/forms/FormTextArea';
import Button from '@/components/buttons/Button';
import { useForm } from '@inertiajs/react';
import { Save, X } from 'lucide-react';

interface AssetCategoryInfo {
    id: number;
    name: string;
}

interface Props {
    assetCategory: AssetCategoryInfo;
}

export default function ChecklistCreate({ assetCategory }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        label: '',
        description: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/ticketing/asset-categories/${assetCategory.id}/checklists/store`);
    };

    return (
        <RootLayout title={`Tambah Checklist - ${assetCategory.name}`} backPath={`/ticketing/asset-categories/${assetCategory.id}/checklists`}>
            <ContentCard
                title="Tambah Checklist Baru"
                subtitle={`Tambahkan item checklist untuk Kategori Asset: ${assetCategory.name}`}
                backPath={`/ticketing/asset-categories/${assetCategory.id}/checklists`}
                mobileFullWidth
            >
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid grid-cols-1 gap-6">
                        <FormInput
                            name="label"
                            label="Keterangan"
                            placeholder="Masukkan keterangan checklist..."
                            value={data.label}
                            onChange={(e) => setData('label', e.target.value)}
                            error={errors.label}
                            required
                        />
                        <FormTextArea
                            name="description"
                            label="Deskripsi"
                            placeholder="Masukkan deskripsi tambahan (opsional)..."
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            error={errors.description}
                            rows={4}
                        />
                    </div>

                    <div className="flex justify-end gap-3 border-t border-slate-100 pt-6 dark:border-slate-800">
                        <Button
                            href={`/ticketing/asset-categories/${assetCategory.id}/checklists`}
                            label="Batal"
                            variant="secondary"
                            icon={<X className="size-4" />}
                        />
                        <Button
                            type="submit"
                            label="Simpan Checklist"
                            icon={<Save className="size-4" />}
                            isLoading={processing}
                        />
                    </div>
                </form>
            </ContentCard>
        </RootLayout>
    );
}
