import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import Button from '@/components/buttons/Button';
import { useForm } from '@inertiajs/react';
import FormInput from '@/components/forms/FormInput';
import { Save, Check } from 'lucide-react';
import GeneralTable from '@/components/tables/GeneralTable';

interface Item {
    id: number;
    name: string;
    stock: number;
    unit_of_measure: string;
}

interface OpnameItem {
    item_id: number;
    system_stock: number;
    physical_stock: number;
    notes: string;
    item?: {
        name: string;
        unit_of_measure: string;
    };
}

interface StockOpname {
    id: number;
    opname_date: string;
    notes: string | null;
    status: string;
    items: OpnameItem[];
}

interface Props {
    items: Item[];
    type: 'warehouse' | 'division';
    opname: StockOpname;
}

export default function StockOpnameProcess({ items = [], type = 'warehouse', opname }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        items: items.map((item) => {
            const existingItem = opname.items.find((i) => i.item_id === item.id);
            return {
                item_id: item.id,
                system_stock: item.stock,
                physical_stock: existingItem ? existingItem.physical_stock : item.stock,
                notes: existingItem?.notes || '',
            };
        }),
        status: opname.status || 'Proses'
    });

    const handleItemChange = (index: number, field: keyof OpnameItem, value: string | number) => {
        const newItems = [...data.items];
        newItems[index] = { ...newItems[index], [field]: value };
        setData('items', newItems);
    };

    const handleSubmit = (status: string) => {
        setData('status', status);
        post(`/inventory/stock-opname/${type}/${opname.id}/process`);
    };

    const title = 'Proses Stok Opname';
    const subtitle = `Tanggal: ${opname.opname_date} | ${opname.notes || 'Tanpa Catatan'}`;
    const backPath = `/inventory/stock-opname/${type}`;

    return (
        <RootLayout title={title} backPath={backPath}>
            <ContentCard title={title} subtitle={subtitle} backPath={backPath}>
                <div className="space-y-6">
                    {/* Desktop View */}
                    <div className="hidden md:block">
                        <GeneralTable
                            headers={[{ label: 'Barang' }, { label: 'Stok Sistem' }, { label: 'Stok Fisik' }, { label: 'Catatan Item' }]}
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
                                    render: (item: Item) => <span className="font-medium">{item.stock}</span>,
                                },
                                {
                                    render: (item: Item, index: number) => (
                                        <FormInput
                                            type="number"
                                            name={`physical_stock_${index}`}
                                            value={data.items[index]?.physical_stock?.toString() || '0'}
                                            onChange={(e) => handleItemChange(index, 'physical_stock', parseInt(e.target.value) || 0)}
                                            className="w-24"
                                            error={errors[`items.${index}.physical_stock`]}
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
                                            error={errors[`items.${index}.notes`]}
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
                                    <div className="text-right">
                                        <div className="text-xs text-gray-500 uppercase">Stok Sistem</div>
                                        <div className="font-bold text-gray-900 dark:text-white">{item.stock}</div>
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
                        <div className="rounded-b-lg border border-t-0 py-8 text-center text-sm text-gray-500">
                            Tidak ada barang ditemukan.
                        </div>
                    )}

                    <div className="flex flex-col gap-3 pt-4 md:flex-row md:justify-end">
                        <Button
                            onClick={() => handleSubmit('Proses')}
                            label="Simpan Sebagai Draf"
                            icon={<Save className="size-4" />}
                            disabled={processing || items.length === 0}
                            variant="secondary"
                            className="w-full md:w-auto"
                        />
                        <Button
                            onClick={() => handleSubmit('Confirmed')}
                            label="Konfirmasi Hasil Opname"
                            icon={<Check className="size-4" />}
                            disabled={processing || items.length === 0}
                            className="w-full md:w-auto"
                        />
                    </div>
                </div>
            </ContentCard>
        </RootLayout>
    );
}
