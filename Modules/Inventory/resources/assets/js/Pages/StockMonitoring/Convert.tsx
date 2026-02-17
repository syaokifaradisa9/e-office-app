import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import Button from '@/components/buttons/Button';
import FormInput from '@/components/forms/FormInput';
import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { RefreshCw, ArrowRight } from 'lucide-react';

interface Item {
    id: number;
    name: string;
    stock: number;
    unit_of_measure: string;
    multiplier: number;
    reference_item?: {
        id: number;
        name: string;
        unit_of_measure: string;
    } | null;
}

interface PageProps {
    item: Item;
    errors?: Record<string, string>;
    [key: string]: unknown;
}

export default function Convert() {
    const { item, errors = {} } = usePage<PageProps>().props;
    const [quantity, setQuantity] = useState(1);
    const [isSubmitting, setIsSubmitting] = useState(false);

    const resultQuantity = quantity * item.multiplier;

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        setIsSubmitting(true);

        router.post(
            `/inventory/stock-monitoring/${item.id}/convert`,
            { quantity },
            {
                onFinish: () => setIsSubmitting(false),
            }
        );
    }

    return (
        <RootLayout title="Konversi Stok Barang" backPath="/inventory/stock-monitoring">
            <ContentCard
                title="Konversi Stok Barang"
                subtitle="Konversi stok barang ke satuan yang lebih kecil"
                backPath="/inventory/stock-monitoring"
                mobileFullWidth
                bodyClassName="p-1 md:p-6"
            >
                <form onSubmit={handleSubmit} className="max-w-lg space-y-6">
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
                                <span className="font-medium text-slate-800 dark:text-slate-200">
                                    {item.stock} {item.unit_of_measure}
                                </span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-slate-500 dark:text-slate-400">Konversi:</span>
                                <span className="font-medium text-slate-800 dark:text-slate-200">
                                    1 {item.unit_of_measure} = {item.multiplier} {item.reference_item?.unit_of_measure}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Quantity Input */}
                    <FormInput
                        label={`Jumlah ${item.unit_of_measure} yang akan dikonversi`}
                        type="number"
                        name="quantity"
                        value={quantity}
                        onChange={(e) => setQuantity(Math.max(1, Math.min(item.stock, parseInt(e.target.value) || 0)))}
                        min={1}
                        max={item.stock}
                        error={errors.quantity}
                        required
                    />

                    {/* Conversion Preview */}
                    <div className="flex items-center justify-center gap-4 rounded-lg border border-primary/30 bg-primary/5 p-4">
                        <div className="text-center">
                            <div className="text-2xl font-bold text-slate-800 dark:text-slate-200">{quantity}</div>
                            <div className="text-sm text-slate-500">{item.unit_of_measure}</div>
                        </div>
                        <ArrowRight className="size-6 text-primary" />
                        <div className="text-center">
                            <div className="text-2xl font-bold text-primary">{resultQuantity}</div>
                            <div className="text-sm text-slate-500">{item.reference_item?.unit_of_measure}</div>
                        </div>
                    </div>

                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="secondary" label="Batal" href="/inventory/stock-monitoring" />
                        <Button
                            type="submit"
                            label={isSubmitting ? 'Memproses...' : 'Konversi Stok Barang'}
                            icon={<RefreshCw className="size-4" />}
                            disabled={isSubmitting || quantity < 1 || quantity > item.stock}
                        />
                    </div>
                </form>
            </ContentCard>
        </RootLayout>
    );
}
