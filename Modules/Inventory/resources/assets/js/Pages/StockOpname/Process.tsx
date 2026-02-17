import React, { useEffect, useCallback, memo } from 'react';
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
    division?: { name: string } | null;
    items: OpnameItem[];
}

interface Props {
    items: Item[];
    type: 'warehouse' | 'division';
    opname: StockOpname;
}

export default function StockOpnameProcess({ items = [], type = 'warehouse', opname }: Props) {
    const storageKey = `so_process_${opname.id}`;

    const { data, setData, post, processing, errors, transform } = useForm({
        items: (() => {
            // Priority 1: Local Storage (Draft on device)
            const savedData = localStorage.getItem(storageKey);
            if (savedData) {
                try {
                    return JSON.parse(savedData);
                } catch (e) {
                    console.error("Error parsing local SO data", e);
                }
            }

            // Priority 2: Server Data
            return items.map((item) => {
                const existingItem = opname.items.find((i) => i.item_id === item.id);
                return {
                    item_id: item.id,
                    system_stock: item.stock,
                    physical_stock: existingItem ? existingItem.physical_stock : null as number | null,
                    notes: existingItem?.notes || '',
                };
            });
        })(),
        confirm: false,
    });

    // Performance Optimized Autosave (Debounced)
    useEffect(() => {
        const timer = setTimeout(() => {
            localStorage.setItem(storageKey, JSON.stringify(data.items));
        }, 1000); // Save after 1s of inactivity

        return () => clearTimeout(timer);
    }, [data.items, storageKey]);

    // Memoized change handler to prevent re-creation on every render
    const handleItemChange = useCallback((index: number, field: string, value: string | number | null) => {
        setData((prevData: any) => {
            const newItems = [...prevData.items];
            newItems[index] = { ...newItems[index], [field]: value };
            return { ...prevData, items: newItems };
        });
    }, [setData]);

    const handleSaveDraft = () => {
        transform((data) => ({
            ...data,
            confirm: false,
        }));
        post(`/inventory/stock-opname/${type}/${opname.id}/process`, {
            onSuccess: () => localStorage.removeItem(storageKey)
        });
    };

    const handleConfirm = () => {
        transform((data) => ({
            ...data,
            confirm: true,
        }));
        post(`/inventory/stock-opname/${type}/${opname.id}/process`, {
            onSuccess: () => localStorage.removeItem(storageKey)
        });
    };

    const formatDate = (dateString: string) => {
        if (!dateString) return '-';

        // Extract the date part only if it contains 'T' (ISO format)
        const datePart = dateString.includes('T') ? dateString.split('T')[0] : dateString;

        // Replace '-' with '/' for broad browser compatibility (especially mobile)
        const date = new Date(datePart.replace(/-/g, '/'));

        if (isNaN(date.getTime())) return dateString;

        return date.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    };

    const title = 'Proses Stok Opname';
    const locationName = opname.division?.name || 'Gudang Utama';
    const subtitle = `Input hasil perhitungan fisik pada ${locationName} (Tanggal: ${formatDate(opname.opname_date)}) untuk memverifikasi kecocokan antara stok di lapangan dengan data sistem.`;
    const backPath = `/inventory/stock-opname/${type}`;

    return (
        <RootLayout title={title} backPath={backPath}>
            <ContentCard title={title} subtitle={subtitle} backPath={backPath} mobileFullWidth bodyClassName="p-1 md:p-6">
                <div className="space-y-6">
                    {/* Desktop View */}
                    <div className="hidden md:block">
                        <GeneralTable
                            headers={[{ label: 'Barang' }, { label: 'Stok Fisik' }, { label: 'Catatan Item' }]}
                            items={items}
                            columns={[
                                {
                                    render: (item: Item, index: number) => (
                                        <div>
                                            <div className="font-medium text-gray-900 dark:text-white">{item.name}</div>
                                            <div className="text-xs text-gray-500">{item.unit_of_measure}</div>
                                        </div>
                                    ),
                                },
                                {
                                    render: (item: Item, index: number) => (
                                        <OpnamePhysicalInput
                                            index={index}
                                            value={data.items[index]?.physical_stock}
                                            onChange={handleItemChange}
                                            error={(errors as any)[`items.${index}.physical_stock`]}
                                        />
                                    ),
                                },
                                {
                                    render: (item: Item, index: number) => (
                                        <OpnameNotesInput
                                            index={index}
                                            value={data.items[index]?.notes}
                                            onChange={handleItemChange}
                                            error={(errors as any)[`items.${index}.notes`]}
                                        />
                                    ),
                                }
                            ]}
                        />
                    </div>

                    {/* Mobile View */}
                    <div className="space-y-4 md:hidden">
                        {items.map((item, index) => (
                            <OpnameMobileRow
                                key={item.id}
                                item={item}
                                index={index}
                                value={data.items[index]}
                                onChange={handleItemChange}
                            />
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

// Optimized Cell Components for Desktop
const OpnamePhysicalInput = memo(({ index, value, onChange, error }: any) => {
    return (
        <FormInput
            type="number"
            name={`physical_stock_${index}`}
            value={value?.toString() ?? ''}
            onChange={(e) => {
                const val = e.target.value;
                onChange(index, 'physical_stock', val === '' ? null : parseInt(val));
            }}
            className="w-24"
            placeholder="-"
            error={error}
        />
    );
});

const OpnameNotesInput = memo(({ index, value, onChange, error }: any) => {
    return (
        <FormInput
            type="text"
            name={`notes_${index}`}
            value={value || ''}
            onChange={(e) => onChange(index, 'notes', e.target.value)}
            placeholder="Catatan..."
            error={error}
        />
    );
});

// Optimized Mobile Row Component
const OpnameMobileRow = memo(({ item, index, value, onChange }: any) => {
    return (
        <div className="space-y-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div>
                <div className="font-medium text-gray-900 dark:text-white">{item.name}</div>
                <div className="text-xs text-gray-500">{item.unit_of_measure}</div>
            </div>

            <div>
                <label className="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Stok Fisik</label>
                <FormInput
                    type="number"
                    name={`physical_stock_mobile_${index}`}
                    value={value?.physical_stock?.toString() ?? ''}
                    onChange={(e) => {
                        const val = e.target.value;
                        onChange(index, 'physical_stock', val === '' ? null : parseInt(val));
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
                    value={value?.notes || ''}
                    onChange={(e) => onChange(index, 'notes', e.target.value)}
                    placeholder="Catatan..."
                    className="w-full"
                />
            </div>
        </div>
    );
});
