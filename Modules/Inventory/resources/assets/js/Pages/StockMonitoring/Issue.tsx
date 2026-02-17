import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import Button from '@/components/buttons/Button';
import FormInput from '@/components/forms/FormInput';
import FormTextArea from '@/components/forms/FormTextArea';
import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { LogOut } from 'lucide-react';

interface Item {
    id: number;
    name: string;
    stock: number;
    unit_of_measure: string;
}

interface PageProps {
    item: Item;
    backPath?: string;
    errors?: Record<string, string>;
    [key: string]: unknown;
}

export default function Issue() {
    const { item, backPath = '/inventory/stock-monitoring', errors = {} } = usePage<PageProps>().props;
    const [quantity, setQuantity] = useState(1);
    const [description, setDescription] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        setIsSubmitting(true);

        router.post(
            `/inventory/stock-monitoring/${item.id}/issue`,
            { quantity, description },
            {
                onFinish: () => setIsSubmitting(false),
            }
        );
    }

    return (
        <RootLayout title="Pengeluaran Stok Barang" backPath={backPath}>
            <ContentCard title="Pengeluaran Stok Barang" subtitle="Pengeluaran stok barang sesuai kebutuhan divisi" backPath={backPath} mobileFullWidth bodyClassName="p-1 md:p-6">
                <form onSubmit={handleSubmit} className="w-full space-y-6">
                    {/* Item Info */}
                    <div className="rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800">
                        <h3 className="mb-3 font-medium text-slate-700 dark:text-slate-200">Informasi Barang</h3>
                        <div className="space-y-2 text-sm">
                            <div className="flex justify-between">
                                <span className="text-slate-500 dark:text-slate-400">Nama Barang:</span>
                                <span className="font-medium text-slate-800 dark:text-slate-200">{item.name}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-slate-500 dark:text-slate-400">Stok Saat Ini:</span>
                                <span className="font-medium text-green-600">
                                    {item.stock} {item.unit_of_measure}
                                </span>
                            </div>
                        </div>
                    </div>

                    <FormInput
                        label={`Jumlah ${item.unit_of_measure} yang dikeluarkan`}
                        type="number"
                        name="quantity"
                        value={quantity}
                        onChange={(e) => setQuantity(Math.max(1, Math.min(item.stock, parseInt(e.target.value) || 0)))}
                        min={1}
                        max={item.stock}
                        error={errors.quantity}
                        required
                    />

                    <FormTextArea
                        label="Keterangan / Alasan"
                        name="description"
                        value={description}
                        onChange={(e) => setDescription(e.target.value)}
                        placeholder="Contoh: Digunakan untuk keperluan event kantor"
                        error={errors.description}
                        rows={3}
                        required
                    />

                    {/* Preview */}
                    <div className="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900/50 dark:bg-red-900/20">
                        <div className="text-center">
                            <div className="text-sm text-red-600 dark:text-red-400">Sisa stok setelah pengeluaran:</div>
                            <div className="mt-1 text-2xl font-bold text-red-700 dark:text-red-300">
                                {item.stock - quantity} {item.unit_of_measure}
                            </div>
                        </div>
                    </div>

                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="secondary" label="Batal" href={backPath} />
                        <Button
                            type="submit"
                            variant="danger"
                            label={isSubmitting ? 'Memproses...' : 'Keluarkan Stok Barang'}
                            icon={<LogOut className="size-4" />}
                            disabled={isSubmitting || quantity < 1 || quantity > item.stock || !description.trim()}
                        />
                    </div>
                </form>
            </ContentCard>
        </RootLayout>
    );
}
