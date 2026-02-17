import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import Button from '@/components/buttons/Button';
import { useForm } from '@inertiajs/react';
import FormInput from '@/components/forms/FormInput';
import FormTextArea from '@/components/forms/FormTextArea';
import { Save } from 'lucide-react';
import FormSelect from '@/components/forms/FormSelect';

interface Division {
    id: number;
    name: string;
}

interface StockOpname {
    id: number;
    opname_date: string;
    division_id: number | null;
    notes: string | null;
}

interface Props {
    type: 'warehouse' | 'division';
    divisions?: Division[];
    opname?: StockOpname;
}

export default function StockOpnameCreate({ type = 'warehouse', divisions = [], opname }: Props) {
    const isEdit = !!opname;

    const { data, setData, post, put, processing, errors } = useForm({
        opname_date: opname?.opname_date
            ? new Date(opname.opname_date).toISOString().split('T')[0]
            : new Date().toISOString().split('T')[0],
        division_id: opname
            ? (opname.division_id === null ? 'warehouse' : opname.division_id.toString())
            : (['warehouse', 'all'].includes(type) ? 'warehouse' : (divisions.length > 0 ? divisions[0].id.toString() : 'warehouse')),
        notes: opname?.notes || '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEdit && opname) {
            put(`/inventory/stock-opname/${type}/${opname.id}/update`);
        } else {
            post(`/inventory/stock-opname/${type}/store`);
        }
    };

    const title = isEdit ? 'Edit Stok Opname' : 'Buat Stok Opname';

    const divName = divisions.find(d => d.id.toString() === data.division_id.toString())?.name || 'Gudang Pusat';
    const subtitle = "Inisialisasi pemeriksaan stok fisik barang pada divisi atau gudang pusat";
    const backPath = `/inventory/stock-opname/${type}`;

    const divisionOptions = [
        { label: 'Gudang Utama (Pusat)', value: 'warehouse' },
        ...divisions.map(d => ({ label: d.name, value: d.id.toString() }))
    ];

    return (
        <RootLayout title={title} backPath={backPath}>
            <ContentCard title={title} subtitle={subtitle} backPath={backPath} mobileFullWidth bodyClassName="p-1 md:p-6">
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <FormInput
                            type="date"
                            label="Tanggal Opname"
                            name="opname_date"
                            value={data.opname_date}
                            onChange={(e) => setData('opname_date', e.target.value)}
                            error={errors.opname_date}
                            required
                        />

                        <FormSelect
                            label="Divisi / Unit"
                            name="division_id"
                            value={data.division_id.toString()}
                            onChange={(e) => setData('division_id', e.target.value)}
                            error={errors.division_id}
                            options={divisionOptions}
                            required
                        />
                    </div>

                    <FormTextArea
                        label="Catatan"
                        name="notes"
                        value={data.notes}
                        onChange={(e) => setData('notes', e.target.value)}
                        error={errors.notes}
                        placeholder="Tambahkan catatan jika diperlukan..."
                    />

                    <div className="flex justify-end pt-4">
                        <Button
                            type="submit"
                            label={isEdit ? "Update Stok Opname" : "Inisialisasi Stok Opname"}
                            icon={<Save className="size-4" />}
                            disabled={processing}
                            className="w-full md:w-auto"
                        />
                    </div>
                </form>
            </ContentCard>
        </RootLayout>
    );
}
