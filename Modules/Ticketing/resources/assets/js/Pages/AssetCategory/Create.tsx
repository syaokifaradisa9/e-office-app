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

interface AssetCategory {
    id: number;
    name: string;
    type: string;
    division_id: number | null;
    maintenance_count: number;
}

interface Props {
    assetCategory?: AssetCategory;
    divisions: Division[];
}

export default function AssetCategoryCreate({ assetCategory, divisions }: Props) {
    const isEdit = !!assetCategory;

    const { data, setData, post, put, processing, errors } = useForm({
        name: assetCategory?.name || '',
        type: assetCategory?.type || '',
        division_id: assetCategory?.division_id?.toString() || '',
        maintenance_count: assetCategory?.maintenance_count?.toString() || '0',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            put(`/ticketing/asset-categories/${assetCategory.id}/update`);
        } else {
            post('/ticketing/asset-categories/store');
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
        <RootLayout title={isEdit ? 'Edit Kategori Asset' : 'Tambah Kategori Asset'} backPath="/ticketing/asset-categories">
            <ContentCard
                title={isEdit ? 'Edit Kategori Asset' : 'Tambah Kategori Asset Baru'}
                subtitle={isEdit ? 'Perbarui informasi detail kategori aset' : 'Daftarkan kategori aset baru ke dalam sistem untuk pengelolaan aset divisi'}
                backPath="/ticketing/asset-categories"
                mobileFullWidth
                bodyClassName="p-4 md:p-6"
            >
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <FormInput
                            name="name"
                            label="Nama Kategori Asset"
                            placeholder="Masukkan nama kategori aset"
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
                            helpText="Tentukan berapa kali maintenance rutin yang dilakukan untuk kategori aset ini dalam satu tahun."
                            value={data.maintenance_count}
                            onChange={(e) => setData('maintenance_count', e.target.value)}
                            error={errors.maintenance_count}
                            required
                        />
                    </div>

                    <div className="flex justify-end gap-3 border-t border-gray-100 pt-6 dark:border-slate-800">
                        <Button href="/ticketing/asset-categories" label="Batal" variant="secondary" />
                        <Button
                            type="submit"
                            label={isEdit ? 'Simpan Perubahan' : 'Simpan Kategori Asset'}
                            icon={<Save className="size-4" />}
                            isLoading={processing}
                        />
                    </div>
                </form>
            </ContentCard>
        </RootLayout>
    );
}
