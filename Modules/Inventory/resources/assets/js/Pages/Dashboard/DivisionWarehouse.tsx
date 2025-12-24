import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import Button from '@/components/buttons/Button';
import { usePage, Link } from '@inertiajs/react';
import { Package, Truck, PackageCheck, AlertCircle, ArrowRight, Clock, CheckCircle } from 'lucide-react';

interface Order {
    id: number;
    user?: { name: string };
    division?: { name: string };
    carts_count?: number;
    carts_sum_quantity?: number;
    created_at: string;
}

interface PageProps {
    statistics: Record<string, number>;
    deliveredOrders: Order[];
    error?: string;
    [key: string]: unknown;
}

export default function DivisionWarehouseDashboard() {
    const { statistics = {}, deliveredOrders = [], error } = usePage<PageProps>().props;

    if (error) {
        return (
            <RootLayout title="Dashboard Gudang Divisi">
                <ContentCard title="Dashboard Gudang Divisi">
                    <div className="flex flex-col items-center justify-center py-12">
                        <AlertCircle className="mb-4 size-12 text-red-500" />
                        <p className="text-lg font-medium text-slate-800 dark:text-slate-200">{error}</p>
                        <p className="text-sm text-slate-500">Hubungi administrator untuk mengatur divisi Anda.</p>
                    </div>
                </ContentCard>
            </RootLayout>
        );
    }

    const statCards = [
        {
            label: 'Pending',
            value: statistics.Pending || 0,
            icon: <Clock className="size-6" />,
            textColor: 'text-yellow-600',
            bgColor: 'bg-yellow-50 dark:bg-yellow-900/20',
        },
        {
            label: 'Dikonfirmasi',
            value: statistics.Confirmed || 0,
            icon: <CheckCircle className="size-6" />,
            textColor: 'text-blue-600',
            bgColor: 'bg-blue-50 dark:bg-blue-900/20',
        },
        {
            label: 'Siap Diterima',
            value: statistics.Delivered || 0,
            icon: <Truck className="size-6" />,
            textColor: 'text-purple-600',
            bgColor: 'bg-purple-50 dark:bg-purple-900/20',
        },
        {
            label: 'Selesai',
            value: statistics.Finished || 0,
            icon: <PackageCheck className="size-6" />,
            textColor: 'text-green-600',
            bgColor: 'bg-green-50 dark:bg-green-900/20',
        },
    ];

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        });
    };

    const OrderCard = ({ order }: { order: Order }) => (
        <div className="flex items-center justify-between rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
            <div className="flex items-center gap-3">
                <div className="flex size-10 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30">
                    <Package className="size-5 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                    <div className="font-medium text-slate-800 dark:text-slate-200">
                        Order #{order.id}
                    </div>
                    <div className="text-sm text-slate-500 dark:text-slate-400">
                        Dibuat oleh: {order.user?.name}
                    </div>
                    <div className="text-xs text-slate-400 dark:text-slate-500">
                        {order.carts_count} item • {order.carts_sum_quantity} qty • {formatDate(order.created_at)}
                    </div>
                </div>
            </div>
            <Button
                href={`/inventory/warehouse-orders/${order.id}/receive`}
                variant="success"
                size="sm"
                label="Terima"
                icon={<ArrowRight className="size-4" />}
            />
        </div>
    );

    return (
        <RootLayout title="Dashboard Gudang Divisi">
            <ContentCard title="Dashboard Gudang Divisi" description="Pantau status permintaan barang divisi Anda">
                {/* Statistics Cards */}
                <div className="mb-8 grid grid-cols-2 gap-4 md:grid-cols-4">
                    {statCards.map((stat, index) => (
                        <div key={index} className={`rounded-xl p-4 ${stat.bgColor}`}>
                            <div className={`mb-2 ${stat.textColor}`}>{stat.icon}</div>
                            <div className={`text-3xl font-bold ${stat.textColor}`}>{stat.value}</div>
                            <div className="text-sm text-slate-600 dark:text-slate-400">{stat.label}</div>
                        </div>
                    ))}
                </div>

                {/* Delivered Orders - Ready to Receive */}
                <div>
                    <div className="mb-4 flex items-center justify-between">
                        <h3 className="flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-200">
                            <Truck className="size-5 text-purple-500" />
                            Barang Siap Diterima
                        </h3>
                        <Link
                            href="/inventory/warehouse-orders?status=Delivered"
                            className="text-sm text-primary hover:underline"
                        >
                            Lihat Semua
                        </Link>
                    </div>
                    <div className="space-y-3">
                        {deliveredOrders.length === 0 ? (
                            <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-slate-300 py-12 dark:border-slate-700">
                                <PackageCheck className="mb-3 size-10 text-green-400" />
                                <p className="text-sm font-medium text-slate-600 dark:text-slate-400">Semua barang sudah diterima!</p>
                                <p className="text-xs text-slate-400">Tidak ada barang yang menunggu untuk diterima</p>
                            </div>
                        ) : (
                            <div className="grid gap-3 md:grid-cols-2">
                                {deliveredOrders.map((order) => (
                                    <OrderCard key={order.id} order={order} />
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </ContentCard>
        </RootLayout>
    );
}
