import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import Button from '@/components/buttons/Button';
import FormInput from '@/components/forms/FormInput';
import FormSelect from '@/components/forms/FormSelect';
import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';

interface Division {
    id: number;
    name: string;
}

interface AssetModel {
    id: number;
    name: string;
    type: string;
    division_id: number | null;
    maintenance_count: number;
}

interface Props {
    assetModel?: AssetModel;
    divisions: Division[];
}

export default function AssetModelCreate({ assetModel, divisions }: Props) {
    const isEdit = !!assetModel;

    const { data, setData, post, put, processing, errors } = useForm({
        name: assetModel?.name || '',
        type: assetModel?.type || '',
        division_id: assetModel?.division_id?.toString() || '',
        maintenance_count: assetModel?.maintenance_count?.toString() || '0',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            put(`/ticketing/asset-models/${assetModel.id}/update`);
        } else {
            post('/ticketing/asset-models/store');
        }
    };

    const typeOptions = [
        { value: 'Physic', label: 'Fisik' },
        { value: 'Digital', label: 'Digital' },
    ];

    const divisionOptions = divisions.map((d) => ({
        value: d.id.toString(),
        label: d.name,
    }));

    return (
        <RootLayout title={isEdit ? 'Edit Asset Model' : 'Tambah Asset Model'} backPath="/ticketing/asset-models">
            <ContentCard
                title={isEdit ? 'Edit Asset Model' : 'Tambah Asset Model Baru'}
                subtitle={isEdit ? 'Perbarui informasi detail asset model' : 'Daftarkan asset model baru ke dalam sistem untuk pengelolaan aset divisi'}
                backPath="/ticketing/asset-models"
                mobileFullWidth
                bodyClassName="p-4 md:p-6"
            >
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <FormInput
                            name="name"
                            label="Nama Asset Model"
                            placeholder="Masukkan nama asset model"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            error={errors.name}
                            required
                        />

                        <FormSelect
                            name="type"
                            label="Tipe Asset"
                            placeholder="Pilih tipe"
                            options={typeOptions}
                            value={data.type}
                            onChange={(e) => setData('type', e.target.value)}
                            error={errors.type}
                            required
                        />

                        <FormSelect
                            name="division_id"
                            label="Divisi Penanggungjawab Maintenance"
                            placeholder="Pilih divisi penanggungjawab (Opsional)"
                            options={divisionOptions}
                            value={data.division_id}
                            onChange={(e) => setData('division_id', e.target.value)}
                            error={errors.division_id}
                        />

                        <FormInput
                            name="maintenance_count"
                            type="number"
                            label="Jumlah Maintenance (Per Tahun)"
                            placeholder="Contoh: 4"
                            helpText="Tentukan berapa kali maintenance rutin yang dilakukan untuk model aset ini dalam satu tahun."
                            value={data.maintenance_count}
                            onChange={(e) => setData('maintenance_count', e.target.value)}
                            error={errors.maintenance_count}
                            required
                        />
                    </div>

                    <div className="flex justify-end gap-3 border-t border-gray-100 pt-6 dark:border-slate-800">
                        <Button href="/ticketing/asset-models" label="Batal" variant="secondary" />
                        <Button
                            type="submit"
                            label={isEdit ? 'Simpan Perubahan' : 'Simpan Asset Model'}
                            icon={<Save className="size-4" />}
                            isLoading={processing}
                        />
                    </div>
                </form>
            </ContentCard>
        </RootLayout>
    );
}
