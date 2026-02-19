import RootLayout from '@/components/layouts/RootLayout';
import ContentCard from '@/components/layouts/ContentCard';
import FormInput from '@/components/forms/FormInput';
import FormTextArea from '@/components/forms/FormTextArea';
import Button from '@/components/buttons/Button';
import { useForm } from '@inertiajs/react';
import { Save, X } from 'lucide-react';

interface AssetModelInfo {
    id: number;
    name: string;
}

interface Checklist {
    id: number;
    label: string;
    description: string | null;
}

interface Props {
    assetModel: AssetModelInfo;
    checklist: Checklist;
}

export default function ChecklistEdit({ assetModel, checklist }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        label: checklist.label,
        description: checklist.description || '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/ticketing/asset-models/${assetModel.id}/checklists/${checklist.id}/update`);
    };

    return (
        <RootLayout title={`Edit Checklist - ${assetModel.name}`} backPath={`/ticketing/asset-models/${assetModel.id}/checklists`}>
            <ContentCard
                title="Edit Checklist"
                subtitle={`Perbarui item checklist untuk Asset Model: ${assetModel.name}`}
                backPath={`/ticketing/asset-models/${assetModel.id}/checklists`}
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
                            href={`/ticketing/asset-models/${assetModel.id}/checklists`}
                            label="Batal"
                            variant="secondary"
                            icon={<X className="size-4" />}
                        />
                        <Button
                            type="submit"
                            label="Perbarui Checklist"
                            icon={<Save className="size-4" />}
                            isLoading={processing}
                        />
                    </div>
                </form>
            </ContentCard>
        </RootLayout>
    );
}
