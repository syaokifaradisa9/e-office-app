import { useForm } from '@inertiajs/react';
import { PackageCheck } from 'lucide-react';

import Button from '@/components/buttons/Button';
import FormFile from '@/components/forms/FormFile';
import FormInput from '@/components/forms/FormInput';
import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import GeneralTable from '@/components/tables/GeneralTable';

interface Category {
    id: number;
    name: string;
}

interface Item {
    id: number;
    name: string;
    unit_of_measure: string;
    category?: Category;
}

interface Cart {
    id: number;
    item: Item;
    quantity: number;
    delivered_quantity?: number;
}

interface Division {
    id: number;
    name: string;
}

interface User {
    id: number;
    name: string;
}

interface Order {
    id: number;
    order_number: string;
    description?: string;
    notes?: string;
    created_at: string;
    division: Division;
    user: User;
    carts: Cart[];
}

interface Props {
    order: Order;
}

export default function WarehouseOrderDelivery({ order }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        delivery_date: new Date().toISOString().split('T')[0],
        delivery_images: null as any,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(`/inventory/warehouse-orders/${order.id}/delivery`, {
            forceFormData: true,
        });
    }

    const formatDate = (dateString: string) => {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const headers = [
        { label: 'Kategori', className: 'w-1/4' },
        { label: 'Nama Barang', className: 'w-1/3' },
        { label: 'Quantity', className: 'w-1/6 text-center' },
        { label: 'Satuan', className: 'w-1/6 text-center' },
    ];

    const columns = [
        {
            render: (cart: Cart) => cart.item?.category?.name || '-',
            className: 'align-middle',
        },
        {
            render: (cart: Cart) => (
                <span className="font-medium text-gray-900 dark:text-white">{cart.item?.name || 'Unknown Item'}</span>
            ),
            className: 'align-middle',
        },
        {
            render: (cart: Cart) => (
                <div className="text-center font-bold text-gray-900 dark:text-white">{cart.quantity}</div>
            ),
            className: 'align-middle',
        },
        {
            render: (cart: Cart) => (
                <div className="text-center text-gray-500 dark:text-gray-400">{cart.item?.unit_of_measure || '-'}</div>
            ),
            className: 'align-middle',
        },
    ];

    return (
        <RootLayout title="Penyerahan Barang" backPath="/inventory/warehouse-orders">
            <ContentCard title="Penyerahan Barang" subtitle="Proses penyerahan barang yang telah disetujui kepada divisi pemohon" backPath="/inventory/warehouse-orders" mobileFullWidth bodyClassName="p-1 md:p-6">
                <div className="space-y-8">
                    {/* Order Details Header */}
                    <div className="grid grid-cols-2 gap-4 rounded-xl bg-gray-50 p-4 dark:bg-slate-800/50 md:grid-cols-3 md:gap-6 md:p-6">
                        <div className="space-y-1">
                            <p className="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400">Pemohon</p>
                            <p className="text-sm font-semibold text-gray-900 dark:text-white">{order.user?.name || '-'}</p>
                            <p className="text-xs text-gray-500 dark:text-slate-400">{order.division?.name || '-'}</p>
                        </div>
                        <div className="space-y-1 text-right md:text-left">
                            <p className="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400">Nomor Order</p>
                            <p className="text-sm font-mono font-bold text-blue-600 dark:text-blue-400">{order.order_number || '-'}</p>
                            <p className="text-xs text-gray-500 dark:text-slate-400">{formatDate(order.created_at)}</p>
                        </div>
                        <div className="col-span-2 space-y-1 md:col-span-1">
                            <p className="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400">Keperluan</p>
                            <p className="text-sm leading-relaxed text-gray-700 dark:text-slate-300 line-clamp-2 md:line-clamp-none">
                                {order.description || '-'}
                            </p>
                        </div>
                        {order.notes && (
                            <div className="col-span-2 space-y-1 border-t border-gray-200 pt-3 dark:border-slate-700 md:col-span-3">
                                <p className="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400">Catatan</p>
                                <p className="text-xs italic text-gray-600 dark:text-slate-400">{order.notes}</p>
                            </div>
                        )}
                    </div>

                    {/* Items List */}
                    <div>
                        <h3 className="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-900 dark:text-white">
                            Daftar Barang
                        </h3>
                        {/* Desktop Table */}
                        <div className="hidden overflow-hidden rounded-lg border border-gray-200 dark:border-slate-700 md:block">
                            <GeneralTable headers={headers} columns={columns} items={order.carts || []} />
                        </div>
                        {/* Mobile Card List */}
                        <div className="space-y-3 md:hidden">
                            {order.carts && order.carts.length > 0 ? (
                                order.carts.map((cart, index) => (
                                    <div key={index} className="relative flex items-center justify-between overflow-hidden rounded-xl border border-gray-100 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-800/80">
                                        <div className="min-w-0 flex-1">
                                            <div className="font-bold text-gray-900 dark:text-white">
                                                {cart.item?.name || 'Unknown Item'}
                                            </div>
                                            <div className="mt-0.5 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                                <span className="rounded bg-gray-100 px-1.5 py-0.5 dark:bg-slate-700">
                                                    {cart.item?.category?.name || '-'}
                                                </span>
                                            </div>
                                        </div>
                                        <div className="flex-shrink-0 pl-4 text-right">
                                            <div className="text-lg font-black text-blue-600 dark:text-blue-400">
                                                {cart.quantity}
                                            </div>
                                            <div className="text-[10px] font-bold uppercase text-gray-400 dark:text-slate-500">
                                                {cart.item?.unit_of_measure || '-'}
                                            </div>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <div className="rounded-xl border border-dashed border-gray-200 p-8 text-center text-sm text-gray-500 dark:border-slate-700 dark:text-gray-400">
                                    Tidak ada barang
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Delivery Form */}
                    <div>
                        <h3 className="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-900 dark:text-white">
                            Form Penyerahan
                        </h3>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <FormInput
                                name="delivery_date"
                                label="Tanggal Penyerahan"
                                type="date"
                                value={data.delivery_date}
                                onChange={(e) => setData('delivery_date', e.target.value)}
                                error={errors.delivery_date}
                                required
                            />

                            <FormFile
                                label="Bukti Foto Penyerahan (Multiple)"
                                name="delivery_images"
                                multiple={true}
                                accept="image/*"
                                capture="environment"
                                onChange={(e) => setData('delivery_images', e.target.files as any)}
                                error={errors.delivery_images}
                                required
                            />

                            <div className="pt-4">
                                <Button
                                    label="Simpan & Serahkan"
                                    type="submit"
                                    isLoading={processing}
                                    icon={<PackageCheck className="size-4" />}
                                    className="w-full"
                                />
                            </div>
                        </form>
                    </div>
                </div>
            </ContentCard>
        </RootLayout>
    );
}
