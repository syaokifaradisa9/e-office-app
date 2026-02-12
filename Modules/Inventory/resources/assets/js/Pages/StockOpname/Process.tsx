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
    physical_stock: number | null;
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
    const { data, setData, post, processing, errors, transform } = useForm({
        items: items.map((item) => {
            const existingItem = opname.items.find((i) => i.item_id === item.id);
            return {
                item_id: item.id,
                system_stock: item.stock,
                // Requirement 9: Default physical_stock to null (empty)
                physical_stock: existingItem ? existingItem.physical_stock : null as number | null,
                notes: existingItem?.notes || '',
            };
        }),
        confirm: false,
    });

    const handleItemChange = (index: number, field: string, value: string | number | null) => {
        const newItems = [...data.items];
        newItems[index] = { ...newItems[index], [field]: value };
        setData('items', newItems);
    };

    const handleSaveDraft = () => {
        transform((data) => ({
            ...data,
            confirm: false,
        }));
        post(`/inventory/stock-opname/${type}/${opname.id}/process`);
    };

    const handleConfirm = () => {
        transform((data) => ({
            ...data,
            confirm: true,
        }));
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
                                            value={data.items[index]?.physical_stock?.toString() ?? ''}
                                            onChange={(e) => {
                                                const val = e.target.value;
                                                handleItemChange(index, 'physical_stock', val === '' ? null : parseInt(val));
                                            }}
                                            className="w-24"
                                            placeholder="-"
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

                                </div>

                                <div>
                                    <label className="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Stok Fisik</label>
                                    <FormInput
                                        type="number"
                                        name={`physical_stock_mobile_${index}`}
                                        value={data.items[index]?.physical_stock?.toString() ?? ''}
                                        onChange={(e) => {
                                            const val = e.target.value;
                                            handleItemChange(index, 'physical_stock', val === '' ? null : parseInt(val));
                                        }}
                                        className="w-full"
                                        placeholder="-"
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
                            onClick={handleSaveDraft}
                            label="Simpan Sebagai Draf"
                            icon={<Save className="size-4" />}
                            disabled={processing || items.length === 0}
                            variant="secondary"
                            className="w-full md:w-auto"
                        />
                        <Button
                            onClick={handleConfirm}
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
