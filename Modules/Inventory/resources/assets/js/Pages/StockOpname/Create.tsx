import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import Button from '@/components/buttons/Button';
import { useForm } from '@inertiajs/react';
import FormInput from '@/components/forms/FormInput';
import FormTextArea from '@/components/forms/FormTextArea';
import { Save } from 'lucide-react';
import GeneralTable from '@/components/tables/GeneralTable';

interface Item {
    id: number;
    name: string;
    stock: number;
    unit_of_measure: string;
}

interface StockOpname {
    id: number;
    opname_date: string;
    notes: string | null;
    items: {
        item_id: number;
        physical_stock: number;
        notes: string | null;
    }[];
}

interface Props {
    items: Item[];
    type: 'warehouse' | 'division';
    opname?: StockOpname;
}

interface OpnameItem {
    item_id: number;
    system_stock: number;
    physical_stock: number;
    notes: string;
}

export default function StockOpnameCreate({ items = [], type = 'warehouse', opname }: Props) {
    const isEdit = !!opname;

    const { data, setData, post, put, processing, errors } = useForm({
        opname_date: opname?.opname_date
            ? new Date(opname.opname_date).toISOString().split('T')[0]
            : new Date().toISOString().split('T')[0],
        notes: opname?.notes || '',
        items: items.map((item) => {
            const existingItem = opname?.items.find((i) => i.item_id === item.id);
            return {
                item_id: item.id,
                system_stock: item.stock,
                physical_stock: existingItem ? existingItem.physical_stock : item.stock,
                notes: existingItem?.notes || '',
            };
        }),
    });

    const handleItemChange = (index: number, field: keyof OpnameItem, value: string | number) => {
        const newItems = [...data.items];
        newItems[index] = { ...newItems[index], [field]: value };
        setData('items', newItems);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEdit && opname) {
            put(`/inventory/stock-opname/${type}/${opname.id}/update`);
        } else {
            post(`/inventory/stock-opname/${type}/store`);
        }
    };

    const title = isEdit
        ? type === 'warehouse'
            ? 'Edit Stok Opname Gudang'
            : 'Edit Stok Opname Divisi'
        : type === 'warehouse'
            ? 'Buat Stok Opname Gudang'
            : 'Buat Stok Opname Divisi';

    const subtitle = type === 'warehouse' ? 'Gudang Utama' : 'Divisi Anda';
    const backPath = `/inventory/stock-opname/${type}`;

    return (
        <RootLayout title={title} backPath={backPath}>
            <ContentCard title={title} subtitle={subtitle} backPath={backPath}>
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="space-y-4">
                        <FormInput
                            type="date"
                            label="Tanggal Opname"
                            name="opname_date"
                            value={data.opname_date}
                            onChange={(e) => setData('opname_date', e.target.value)}
                            error={errors.opname_date}
                        />
                        <FormTextArea
                            label="Catatan"
                            name="notes"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            error={errors.notes}
                        />
                    </div>

                    {/* Desktop View */}
                    <div className="hidden md:block">
                        <GeneralTable
                            headers={[{ label: 'Barang' }, { label: 'Stok Fisik' }, { label: 'Catatan Item' }]}
                            items={items}
                            columns={[
                                {
                                    render: (item: Item) => (
                                        <div>
                                            <div className="font-medium text-gray-900 dark:text-white">{item.name}</div>
                                            <div className="text-xs text-gray-500">{item.unit_of_measure}</div>
                                        </div>
                                    ),
                                },
                                {
                                    render: (item: Item, index: number) => (
                                        <FormInput
                                            type="number"
                                            name={`physical_stock_${index}`}
                                            value={data.items[index]?.physical_stock?.toString() || '0'}
                                            onChange={(e) => handleItemChange(index, 'physical_stock', parseInt(e.target.value) || 0)}
                                            className="w-24"
                                        />
                                    ),
                                },
                                {
                                    render: (item: Item, index: number) => (
                                        <FormInput
                                            type="text"
                                            name={`notes_${index}`}
                                            value={data.items[index]?.notes || ''}
                                            onChange={(e) => handleItemChange(index, 'notes', e.target.value)}
                                            placeholder="Catatan..."
                                        />
                                    ),
                                },
                            ]}
                        />
                    </div>

                    {/* Mobile View */}
                    <div className="space-y-4 md:hidden">
                        {items.map((item, index) => (
                            <div
                                key={item.id}
                                className="space-y-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                            >
                                <div className="flex items-start justify-between">
                                    <div>
                                        <div className="font-medium text-gray-900 dark:text-white">{item.name}</div>
                                        <div className="text-xs text-gray-500">{item.unit_of_measure}</div>
                                    </div>
                                </div>

                                <div>
                                    <label className="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Stok Fisik</label>
                                    <FormInput
                                        type="number"
                                        name={`physical_stock_mobile_${index}`}
                                        value={data.items[index]?.physical_stock?.toString() || '0'}
                                        onChange={(e) => handleItemChange(index, 'physical_stock', parseInt(e.target.value) || 0)}
                                        className="w-full"
                                    />
                                </div>

                                <div>
                                    <label className="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Catatan Item</label>
                                    <FormInput
                                        type="text"
                                        name={`notes_mobile_${index}`}
                                        value={data.items[index]?.notes || ''}
                                        onChange={(e) => handleItemChange(index, 'notes', e.target.value)}
                                        placeholder="Catatan..."
                                        className="w-full"
                                    />
                                </div>
                            </div>
                        ))}
                    </div>

                    {items.length === 0 && (
                        <div className="rounded-b-lg border border-t-0 py-4 text-center text-sm text-gray-500">
                            Tidak ada barang ditemukan untuk stok opname ini.
                        </div>
                    )}

                    <Button
                        type="submit"
                        label="Simpan Stok Opname"
                        icon={<Save className="size-4" />}
                        disabled={processing || items.length === 0}
                        className="w-full"
                    />
                </form>
            </ContentCard>
        </RootLayout>
    );
}
