import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import Button from '@/components/buttons/Button';
import { usePage, Link } from '@inertiajs/react';
import { Package, Clock, CheckCircle, Truck, PackageCheck, AlertCircle, ArrowRight } from 'lucide-react';

interface Order {
    id: number;
    user?: { name: string };
    division?: { name: string };
    items_count?: number;
    carts_sum_quantity?: number;
    created_at: string;
}

interface PageProps {
    statistics: Record<string, number>;
    pendingOrders: Order[];
    confirmedOrders: Order[];
    deliveredOrders: Order[];
    [key: string]: unknown;
}

export default function DashboardIndex() {
    const { statistics = {}, pendingOrders = [], confirmedOrders = [], deliveredOrders = [] } = usePage<PageProps>().props;

    const statCards = [
        {
            label: 'Pending',
            value: statistics.Pending || 0,
            icon: <Clock className="size-6" />,
            color: 'bg-yellow-500',
            textColor: 'text-yellow-600',
            bgColor: 'bg-yellow-50 dark:bg-yellow-900/20',
        },
        {
            label: 'Dikonfirmasi',
            value: statistics.Confirmed || 0,
            icon: <CheckCircle className="size-6" />,
            color: 'bg-blue-500',
            textColor: 'text-blue-600',
            bgColor: 'bg-blue-50 dark:bg-blue-900/20',
        },
        {
            label: 'Dikirim',
            value: statistics.Delivered || 0,
            icon: <Truck className="size-6" />,
            color: 'bg-purple-500',
            textColor: 'text-purple-600',
            bgColor: 'bg-purple-50 dark:bg-purple-900/20',
        },
        {
            label: 'Selesai',
            value: statistics.Finished || 0,
            icon: <PackageCheck className="size-6" />,
            color: 'bg-green-500',
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

    const OrderCard = ({ order, actionLabel, actionHref }: { order: Order; actionLabel: string; actionHref: string }) => (
        <div className="flex items-center justify-between rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
            <div className="flex items-center gap-3">
                <div className="flex size-10 items-center justify-center rounded-lg bg-primary/10">
                    <Package className="size-5 text-primary" />
                </div>
                <div>
                    <div className="font-medium text-slate-800 dark:text-slate-200">
                        Order #{order.id}
                    </div>
                    <div className="text-sm text-slate-500 dark:text-slate-400">
                        {order.user?.name} • {order.division?.name}
                    </div>
                    <div className="text-xs text-slate-400 dark:text-slate-500">
                        {order.items_count} item • {order.carts_sum_quantity} qty • {formatDate(order.created_at)}
                    </div>
                </div>
            </div>
            <Button
                href={actionHref}
                variant="primary"
                size="sm"
                label={actionLabel}
                icon={<ArrowRight className="size-4" />}
            />
        </div>
    );

    return (
        <RootLayout title="Dashboard Inventory">
            <ContentCard title="Dashboard Inventory">
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

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Pending Orders */}
                    <div>
                        <div className="mb-4 flex items-center justify-between">
                            <h3 className="flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-200">
                                <Clock className="size-5 text-yellow-500" />
                                Order Pending
                            </h3>
                            <Link
                                href="/inventory/warehouse-orders?status=Pending"
                                className="text-sm text-primary hover:underline"
                            >
                                Lihat Semua
                            </Link>
                        </div>
                        <div className="space-y-3">
                            {pendingOrders.length === 0 ? (
                                <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-slate-300 py-8 dark:border-slate-700">
                                    <AlertCircle className="mb-2 size-8 text-slate-400" />
                                    <p className="text-sm text-slate-500">Tidak ada order pending</p>
                                </div>
                            ) : (
                                pendingOrders.slice(0, 5).map((order) => (
                                    <OrderCard
                                        key={order.id}
                                        order={order}
                                        actionLabel="Konfirmasi"
                                        actionHref={`/inventory/warehouse-orders/${order.id}`}
                                    />
                                ))
                            )}
                        </div>
                    </div>

                    {/* Confirmed Orders */}
                    <div>
                        <div className="mb-4 flex items-center justify-between">
                            <h3 className="flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-200">
                                <CheckCircle className="size-5 text-blue-500" />
                                Siap Dikirim
                            </h3>
                            <Link
                                href="/inventory/warehouse-orders?status=Confirmed"
                                className="text-sm text-primary hover:underline"
                            >
                                Lihat Semua
                            </Link>
                        </div>
                        <div className="space-y-3">
                            {confirmedOrders.length === 0 ? (
                                <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-slate-300 py-8 dark:border-slate-700">
                                    <AlertCircle className="mb-2 size-8 text-slate-400" />
                                    <p className="text-sm text-slate-500">Tidak ada order siap kirim</p>
                                </div>
                            ) : (
                                confirmedOrders.slice(0, 5).map((order) => (
                                    <OrderCard
                                        key={order.id}
                                        order={order}
                                        actionLabel="Kirim"
                                        actionHref={`/inventory/warehouse-orders/${order.id}/delivery`}
                                    />
                                ))
                            )}
                        </div>
                    </div>

                    {/* Delivered Orders */}
                    <div>
                        <div className="mb-4 flex items-center justify-between">
                            <h3 className="flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-200">
                                <Truck className="size-5 text-purple-500" />
                                Siap Diterima
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
                                <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-slate-300 py-8 dark:border-slate-700">
                                    <AlertCircle className="mb-2 size-8 text-slate-400" />
                                    <p className="text-sm text-slate-500">Tidak ada order untuk diterima</p>
                                </div>
                            ) : (
                                deliveredOrders.slice(0, 5).map((order) => (
                                    <OrderCard
                                        key={order.id}
                                        order={order}
                                        actionLabel="Terima"
                                        actionHref={`/inventory/warehouse-orders/${order.id}/receive`}
                                    />
                                ))
                            )}
                        </div>
                    </div>
                </div>
            </ContentCard>
        </RootLayout>
    );
}
