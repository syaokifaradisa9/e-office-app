import { useForm } from '@inertiajs/react';
import { LogOut } from 'lucide-react';

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
}

interface Props {
    item: Item;
}

export default function ItemIssue({ item }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        quantity: '1',
        description: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/inventory/items/${item.id}/issue`);
    };

    const remainingStock = item.stock - parseInt(data.quantity || '0');

    return (
        <RootLayout title="Pengeluaran Barang Gudang" backPath="/inventory/items">
            <ContentCard title="Pengeluaran Barang Gudang" mobileFullWidth>
                <p className="mb-6 text-sm text-gray-500 dark:text-slate-400">
                    Keluarkan stok barang dari gudang
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
                    </div>
                </div>

                {item.stock <= 0 ? (
                    <div className="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-900/20">
                        <p className="text-sm text-red-800 dark:text-red-200">
                            Stok barang habis. Tidak dapat mengeluarkan stok.
                        </p>
                    </div>
                ) : (
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <FormInput
                            name="quantity"
                            label={`Jumlah yang Dikeluarkan (${item.unit_of_measure})`}
                            type="number"
                            min="1"
                            max={item.stock.toString()}
                            placeholder="Masukkan jumlah"
                            value={data.quantity}
                            onChange={(e) => setData('quantity', e.target.value)}
                            error={errors.quantity}
                            required
                        />

                        <div className="space-y-1.5">
                            <label className="text-sm font-medium text-gray-700 dark:text-slate-300">
                                Keterangan <span className="text-red-500">*</span>
                            </label>
                            <textarea
                                placeholder="Contoh: Untuk keperluan rapat, diambil oleh Budi"
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                rows={3}
                                className="w-full rounded-lg border border-gray-400/50 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 dark:border-slate-600 dark:bg-slate-800/40 dark:text-slate-200 dark:placeholder:text-slate-400"
                                required
                            />
                            {errors.description && <p className="text-sm text-red-500">{errors.description}</p>}
                        </div>

                        <div className="rounded-lg border border-orange-200 bg-orange-50 p-4 dark:border-orange-900 dark:bg-orange-900/20">
                            <h4 className="mb-2 text-sm font-medium text-orange-800 dark:text-orange-200">Ringkasan:</h4>
                            <div className="space-y-1 text-sm">
                                <p className="text-orange-700 dark:text-orange-300">
                                    Stok yang dikeluarkan: <span className="font-semibold">{data.quantity || 0} {item.unit_of_measure}</span>
                                </p>
                                <p className="text-orange-700 dark:text-orange-300">
                                    Sisa stok: <span className={`font-semibold ${remainingStock < 0 ? 'text-red-600' : remainingStock <= 10 ? 'text-yellow-600' : 'text-green-600'}`}>
                                        {remainingStock < 0 ? 'Tidak mencukupi' : `${remainingStock} ${item.unit_of_measure}`}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div className="flex justify-end gap-3 border-t border-gray-200 pt-6 dark:border-slate-700">
                            <Button href="/inventory/items" label="Batal" variant="secondary" />
                            <Button
                                type="submit"
                                label="Keluarkan Barang Gudang"
                                icon={<LogOut className="h-4 w-4" />}
                                isLoading={processing}
                                disabled={remainingStock < 0 || parseInt(data.quantity) <= 0 || !data.description}
                            />
                        </div>
                    </form>
                )}
            </ContentCard>
        </RootLayout>
    );
}
