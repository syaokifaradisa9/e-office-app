import React, { useEffect, useCallback, memo } from 'react';
import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import Button from '@/components/buttons/Button';
import { useForm } from '@inertiajs/react';
import FormInput from '@/components/forms/FormInput';
import { ClipboardCheck, CheckCircle2 } from 'lucide-react';
import GeneralTable from '@/components/tables/GeneralTable';

interface Item {
    id: number;
    name: string;
    unit_of_measure: string;
}

interface OpnameItem {
    item_id: number;
    system_stock: number;
    physical_stock: number;
    final_stock: number;
    notes: string | null;
    final_notes: string | null;
    item: Item;
}

interface StockOpname {
    id: number;
    opname_date: string;
    notes: string | null;
    division: { name: string } | null;
    items: OpnameItem[];
}

interface Props {
    type: 'warehouse' | 'division';
    opname: StockOpname;
}

export default function StockOpnameFinalize({ type = 'warehouse', opname }: Props) {
    const storageKey = `so_finalize_${opname.id}`;

    const { data, setData, post, processing, errors } = useForm({
        items: (() => {
            const savedData = localStorage.getItem(storageKey);
            if (savedData) {
                try {
                    return JSON.parse(savedData);
                } catch (e) {
                    console.error("Error parsing local SO finalize data", e);
                }
            }

            return opname.items.map((item) => ({
                item_id: item.item_id,
                final_stock: item.physical_stock, // Default to physical stock
                final_notes: '',
            }));
        })()
    });

    // Performance Optimized Autosave (Debounced)
    useEffect(() => {
        const timer = setTimeout(() => {
            localStorage.setItem(storageKey, JSON.stringify(data.items));
        }, 1000);

        return () => clearTimeout(timer);
    }, [data.items, storageKey]);

    const handleItemChange = useCallback((index: number, field: string, value: string | number) => {
        setData((prevData: any) => {
            const newItems = [...prevData.items];
            newItems[index] = { ...newItems[index], [field]: value };
            return { ...prevData, items: newItems };
        });
    }, [setData]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/inventory/stock-opname/${type}/${opname.id}/finalize`, {
            onSuccess: () => localStorage.removeItem(storageKey)
        });
    };

    const formatDate = (dateString: string) => {
        if (!dateString) return '-';

        const datePart = dateString.includes('T') ? dateString.split('T')[0] : dateString;
        const date = new Date(datePart.replace(/-/g, '/'));

        if (isNaN(date.getTime())) return dateString;

        return date.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    };

    const title = 'Finalisasi Stok Opname';
    const subtitle = `Penyesuaian akhir stok gudang setelah melakukan stock opname (${opname.division?.name || 'Gudang Utama'} | ${formatDate(opname.opname_date)})`;
    const backPath = `/inventory/stock-opname/${type}`;

    return (
        <RootLayout title={title} backPath={backPath}>
            <ContentCard title={title} subtitle={subtitle} backPath={backPath} mobileFullWidth bodyClassName="p-1 md:p-6">
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="rounded-lg bg-blue-50 p-4 text-sm text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                        <p className="flex items-center gap-2 font-medium">
                            <span>Informasi:</span>
                        </p>
                        <p className="mt-1">
                            Langkah ini adalah penyesuaian stok akhir sistem. Silakan masukkan stok final yang akan dijadikan stok baru.
                        </p>
                    </div>

                    {/* Desktop View */}
                    <div className="hidden md:block">
                        <GeneralTable
                            headers={[
                                { label: 'Barang' },
                                { label: 'Stok Sistem' },
                                { label: 'Stok Fisik' },
                                { label: 'Selisih' },
                                { label: 'STOK FINAL (BARU)', className: 'text-primary font-bold' },
                                { label: 'Catatan Penyesuaian' }
                            ]}
                            items={opname.items}
                            columns={[
                                {
                                    render: (item: OpnameItem) => (
                                        <div>
                                            <div className="font-medium text-gray-900 dark:text-white">{item.item.name}</div>
                                            <div className="text-xs text-gray-500">{item.item.unit_of_measure}</div>
                                            {item.notes && <div className="mt-1 text-[10px] italic text-gray-400 bg-gray-50 dark:bg-gray-800 px-1 rounded inline-block">SO: {item.notes}</div>}
                                        </div>
                                    ),
                                },
                                {
                                    render: (item: OpnameItem) => <span className="text-gray-500">{item.system_stock}</span>,
                                },
                                {
                                    render: (item: OpnameItem) => <span className="font-medium text-orange-600">{item.physical_stock}</span>,
                                },
                                {
                                    render: (item: OpnameItem) => {
                                        const diff = item.physical_stock - item.system_stock;
                                        return (
                                            <span className={`font-medium ${diff < 0 ? 'text-red-600' : diff > 0 ? 'text-green-600' : 'text-gray-500'}`}>
                                                {diff > 0 ? `+${diff}` : diff}
                                            </span>
                                        );
                                    },
                                },
                                {
                                    render: (item: OpnameItem, index: number) => (
                                        <FinalizeStockInput
                                            index={index}
                                            value={data.items[index]?.final_stock}
                                            onChange={handleItemChange}
                                            error={(errors as any)[`items.${index}.final_stock`]}
                                        />
                                    ),
                                },
                                {
                                    render: (item: OpnameItem, index: number) => (
                                        <FinalizeNotesInput
                                            index={index}
                                            value={data.items[index]?.final_notes}
                                            onChange={handleItemChange}
                                            error={(errors as any)[`items.${index}.final_notes`]}
                                        />
                                    ),
                                },
                            ]}
                        />
                    </div>

                    {/* Mobile View */}
                    <div className="space-y-4 md:hidden">
                        {opname.items.map((item, index) => (
                            <div
                                key={item.item_id}
                                className="space-y-4 rounded-lg border-2 border-primary/20 bg-white p-4 shadow-sm dark:bg-gray-800"
                            >
                                <div className="border-b pb-2">
                                    <div className="font-bold text-gray-900 dark:text-white">{item.item.name}</div>
                                    <div className="text-xs text-gray-500">{item.item.unit_of_measure}</div>
                                    {item.notes && (
                                        <div className="mt-1 text-xs text-gray-400 italic">
                                            SO: {item.notes}
                                        </div>
                                    )}
                                </div>

                                <div className="grid grid-cols-3 gap-2 text-center">
                                    <div className="rounded bg-gray-50 p-2 dark:bg-gray-700/50">
                                        <div className="text-[10px] uppercase text-gray-500">Sistem</div>
                                        <div className="font-medium text-xs">{item.system_stock}</div>
                                    </div>
                                    <div className="rounded bg-orange-50 p-2 dark:bg-orange-900/10">
                                        <div className="text-[10px] uppercase text-orange-500">Fisik</div>
                                        <div className="font-medium text-xs text-orange-600">{item.physical_stock}</div>
                                    </div>
                                    <div className="rounded bg-gray-50 p-2 dark:bg-gray-700/50">
                                        <div className="text-[10px] uppercase text-gray-500">Selisih</div>
                                        {(() => {
                                            const diff = item.physical_stock - item.system_stock;
                                            return (
                                                <div className={`font-medium text-xs ${diff < 0 ? 'text-red-600' : diff > 0 ? 'text-green-600' : 'text-gray-500'}`}>
                                                    {diff > 0 ? `+${diff}` : diff}
                                                </div>
                                            );
                                        })()}
                                    </div>
                                </div>

                                <div>
                                    <label className="mb-1 block text-sm font-bold text-primary">STOK FINAL (BARU)</label>
                                    <FinalizeMobileInput
                                        index={index}
                                        value={data.items[index]}
                                        onChange={handleItemChange}
                                    />
                                </div>
                            </div>
                        ))}
                    </div>

                    <div className="flex justify-end pt-4">
                        <Button
                            type="submit"
                            label="Finalisasi & Update Saldo Stok"
                            icon={<CheckCircle2 className="size-4" />}
                            disabled={processing}
                            className="w-full md:w-auto"
                        />
                    </div>
                </form>
            </ContentCard>
        </RootLayout>
    );
}

// Optimized Desktop Components
const FinalizeStockInput = memo(({ index, value, onChange, error }: any) => {
    return (
        <FormInput
            type="number"
            name={`final_stock_${index}`}
            value={value?.toString() || '0'}
            onChange={(e) => onChange(index, 'final_stock', parseInt(e.target.value) || 0)}
            className="w-24 font-bold border-primary"
            error={error}
        />
    );
});

const FinalizeNotesInput = memo(({ index, value, onChange, error }: any) => {
    return (
        <FormInput
            type="text"
            name={`final_notes_${index}`}
            value={value || ''}
            onChange={(e) => onChange(index, 'final_notes', e.target.value)}
            placeholder="Alasan penyesuaian..."
            error={error}
        />
    );
});

// Optimized Mobile Component
const FinalizeMobileInput = memo(({ index, value, onChange }: any) => {
    return (
        <div className="space-y-4">
            <FormInput
                type="number"
                name={`final_stock_mobile_${index}`}
                value={value?.final_stock?.toString() || '0'}
                onChange={(e) => onChange(index, 'final_stock', parseInt(e.target.value) || 0)}
                className="w-full border-primary font-bold"
            />
            <div>
                <label className="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Catatan Penyesuaian</label>
                <FormInput
                    type="text"
                    name={`final_notes_mobile_${index}`}
                    value={value?.final_notes || ''}
                    onChange={(e) => onChange(index, 'final_notes', e.target.value)}
                    placeholder="Alasan penyesuaian..."
                    className="w-full"
                />
            </div>
        </div>
    );
});
