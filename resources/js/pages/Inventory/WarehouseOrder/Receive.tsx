import { useForm } from '@inertiajs/react';
import { ClipboardCheck } from 'lucide-react';

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
    division: Division;
    user: User;
    carts: Cart[];
    created_at: string;
}

interface Props {
    order: Order;
}

export default function WarehouseOrderReceive({ order }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        receipt_date: new Date().toISOString().split('T')[0],
        receipt_images: null as FileList | null,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(`/inventory/warehouse-orders/${order.id}/receive`, {
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
                <div className="text-center font-bold text-gray-900 dark:text-white">
                    {cart.delivered_quantity || cart.quantity}
                </div>
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
        <RootLayout title="Penerimaan Barang" backPath="/inventory/warehouse-orders">
            <ContentCard title="Penerimaan Barang" backPath="/inventory/warehouse-orders">
                <div className="space-y-8">
                    {/* Order Details */}
                    <div className="space-y-2 border-b border-gray-100 pb-6 text-sm dark:border-gray-700">
                        <div className="grid grid-cols-[140px_10px_1fr] items-start md:grid-cols-[200px_20px_1fr]">
                            <div className="font-medium text-gray-700 dark:text-gray-300">Pemohon</div>
                            <div className="text-gray-500 dark:text-gray-400">:</div>
                            <div className="font-medium text-gray-900 dark:text-white">{order.user?.name || '-'}</div>
                        </div>
                        <div className="grid grid-cols-[140px_10px_1fr] items-start md:grid-cols-[200px_20px_1fr]">
                            <div className="font-medium text-gray-700 dark:text-gray-300">Divisi</div>
                            <div className="text-gray-500 dark:text-gray-400">:</div>
                            <div className="font-medium text-gray-900 dark:text-white">
                                {order.division?.name || '-'}
                            </div>
                        </div>
                        <div className="grid grid-cols-[140px_10px_1fr] items-start md:grid-cols-[200px_20px_1fr]">
                            <div className="font-medium text-gray-700 dark:text-gray-300">Nomor Order</div>
                            <div className="text-gray-500 dark:text-gray-400">:</div>
                            <div className="font-medium text-gray-900 dark:text-white">
                                {order.order_number || '-'}
                            </div>
                        </div>
                        <div className="grid grid-cols-[140px_10px_1fr] items-start md:grid-cols-[200px_20px_1fr]">
                            <div className="font-medium text-gray-700 dark:text-gray-300">Tanggal Permintaan</div>
                            <div className="text-gray-500 dark:text-gray-400">:</div>
                            <div className="font-medium text-gray-900 dark:text-white">
                                {formatDate(order.created_at)}
                            </div>
                        </div>
                        <div className="grid grid-cols-[140px_10px_1fr] items-start md:grid-cols-[200px_20px_1fr]">
                            <div className="font-medium text-gray-700 dark:text-gray-300">Keperluan</div>
                            <div className="text-gray-500 dark:text-gray-400">:</div>
                            <div className="font-medium leading-relaxed text-gray-900 dark:text-white">
                                {order.description || '-'}
                            </div>
                        </div>
                        {order.notes && (
                            <div className="grid grid-cols-[140px_10px_1fr] items-start md:grid-cols-[200px_20px_1fr]">
                                <div className="font-medium text-gray-700 dark:text-gray-300">Catatan</div>
                                <div className="text-gray-500 dark:text-gray-400">:</div>
                                <div className="font-medium leading-relaxed text-gray-900 dark:text-white">
                                    {order.notes}
                                </div>
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
                        <div className="divide-y divide-gray-200 overflow-hidden rounded-lg border border-gray-200 dark:divide-slate-700 dark:border-slate-700 md:hidden">
                            {order.carts && order.carts.length > 0 ? (
                                order.carts.map((cart, index) => (
                                    <div key={index} className="bg-white p-4 dark:bg-slate-800">
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0 flex-1">
                                                <div className="truncate font-medium text-gray-900 dark:text-white">
                                                    {cart.item?.name || 'Unknown Item'}
                                                </div>
                                                <div className="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                                                    {cart.item?.category?.name || '-'}
                                                </div>
                                            </div>
                                            <div className="flex-shrink-0 text-right">
                                                <div className="font-bold text-gray-900 dark:text-white">
                                                    {cart.delivered_quantity || cart.quantity}
                                                </div>
                                                <div className="text-sm text-gray-500 dark:text-gray-400">
                                                    {cart.item?.unit_of_measure || '-'}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <div className="p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Tidak ada barang
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Receipt Form */}
                    <div>
                        <h3 className="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-900 dark:text-white">
                            Form Penerimaan
                        </h3>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <FormInput
                                name="receipt_date"
                                label="Tanggal Penerimaan"
                                type="date"
                                value={data.receipt_date}
                                onChange={(e) => setData('receipt_date', e.target.value)}
                                error={errors.receipt_date}
                                required
                            />

                            <FormFile
                                label="Bukti Foto Penerimaan (Multiple)"
                                name="receipt_images"
                                multiple={true}
                                accept="image/*"
                                onChange={(e) => setData('receipt_images', e.target.files)}
                                error={errors.receipt_images}
                                required
                            />

                            <div className="pt-4">
                                <Button
                                    label="Simpan & Terima"
                                    type="submit"
                                    isLoading={processing}
                                    icon={<ClipboardCheck className="size-4" />}
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
