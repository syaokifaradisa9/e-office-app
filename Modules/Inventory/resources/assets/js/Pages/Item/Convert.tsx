import { useForm } from '@inertiajs/react';
import { ArrowRightLeft } from 'lucide-react';

import Button from '@/components/buttons/Button';
import FormInput from '@/components/forms/FormInput';
import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';

interface Category {
    id: number;
    name: string;
}

interface Item {
    id: number;
    name: string;
    category?: Category | null;
    unit_of_measure: string;
    stock: number;
    multiplier: number | null;
    reference_item_id: number | null;
}

interface TargetItem {
    id: number;
    name: string;
    unit_of_measure: string;
    stock: number;
    multiplier: number | null;
}

interface Props {
    item: Item;
    targetItems: TargetItem[];
}

export default function ItemConvert({ item, targetItems }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        quantity: '1',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/inventory/items/${item.id}/convert`);
    };

    const multiplier = item.multiplier || 1;
    const resultQuantity = parseInt(data.quantity || '0') * multiplier;

    return (
        <RootLayout title="Konversi Stok" backPath="/inventory/items">
            <ContentCard title="Konversi Stok" mobileFullWidth>
                <p className="mb-6 text-sm text-gray-500 dark:text-slate-400">
                    Konversi stok dari satuan besar ke satuan kecil
                </p>

                <div className="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-slate-700 dark:bg-slate-800/50">
                    <h3 className="mb-3 text-sm font-medium text-gray-700 dark:text-slate-300">Informasi Barang</h3>
                    <div className="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span className="text-gray-500 dark:text-slate-400">Nama Barang:</span>
                            <p className="font-medium text-gray-900 dark:text-white">{item.name}</p>
                        </div>
                        <div>
                            <span className="text-gray-500 dark:text-slate-400">Kategori:</span>
                            <p className="font-medium text-gray-900 dark:text-white">{item.category?.name || '-'}</p>
                        </div>
                        <div>
                            <span className="text-gray-500 dark:text-slate-400">Satuan:</span>
                            <p className="font-medium text-gray-900 dark:text-white">{item.unit_of_measure}</p>
                        </div>
                        <div>
                            <span className="text-gray-500 dark:text-slate-400">Stok Saat Ini:</span>
                            <p className="font-semibold text-green-600">{item.stock} {item.unit_of_measure}</p>
                        </div>
                        <div>
                            <span className="text-gray-500 dark:text-slate-400">Multiplier:</span>
                            <p className="font-medium text-gray-900 dark:text-white">{multiplier}x</p>
                        </div>
                    </div>
                </div>

                {multiplier <= 1 ? (
                    <div className="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-900 dark:bg-yellow-900/20">
                        <p className="text-sm text-yellow-800 dark:text-yellow-200">
                            Barang ini tidak dapat dikonversi karena tidak memiliki multiplier (nilai konversi).
                        </p>
                    </div>
                ) : (
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <FormInput
                            name="quantity"
                            label={`Jumlah yang Dikonversi (${item.unit_of_measure})`}
                            type="number"
                            min="1"
                            max={item.stock.toString()}
                            placeholder="Masukkan jumlah"
                            value={data.quantity}
                            onChange={(e) => setData('quantity', e.target.value)}
                            error={errors.quantity}
                            required
                        />

                        <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-900/20">
                            <h4 className="mb-2 text-sm font-medium text-blue-800 dark:text-blue-200">Hasil Konversi:</h4>
                            <div className="flex items-center gap-3">
                                <span className="text-lg font-semibold text-blue-900 dark:text-blue-100">
                                    {data.quantity || 0} {item.unit_of_measure}
                                </span>
                                <ArrowRightLeft className="size-5 text-blue-600" />
                                <span className="text-lg font-semibold text-green-600">
                                    {resultQuantity} unit
                                </span>
                            </div>
                            <p className="mt-2 text-xs text-blue-700 dark:text-blue-300">
                                1 {item.unit_of_measure} = {multiplier} unit
                            </p>
                        </div>

                        <div className="flex justify-end gap-3 border-t border-gray-200 pt-6 dark:border-slate-700">
                            <Button href="/inventory/items" label="Batal" variant="secondary" />
                            <Button
                                type="submit"
                                label="Konversi Stok"
                                icon={<ArrowRightLeft className="h-4 w-4" />}
                                isLoading={processing}
                                disabled={parseInt(data.quantity) > item.stock || parseInt(data.quantity) <= 0}
                            />
                        </div>
                    </form>
                )}
            </ContentCard>
        </RootLayout>
    );
}
