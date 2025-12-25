import RootLayout from '@/components/layouts/RootLayout';
import ContentCard from '@/components/layouts/ContentCard';
import { usePage, Link } from '@inertiajs/react';
import { Package, TrendingUp, TrendingDown, AlertTriangle, ClipboardCheck, ShoppingCart, History, ArrowRight, CheckCircle } from 'lucide-react';

interface Item {
    id: number;
    name: string;
    stock: number;
    unit_of_measure: string;
}

interface Order {
    id: number;
    status: string;
    user?: { name: string };
    carts_count?: number;
    carts_sum_quantity?: number;
    created_at: string;
}

interface Transaction {
    id: number;
    type: string;
    quantity: number;
    created_at: string;
    item?: { id: number; name: string };
    user?: { id: number; name: string };
}

interface PageProps {
    mostStockItems: Item[];
    leastStockItems: Item[];
    hasStockOpnameThisMonth: boolean;
    activeOrders: Order[];
    recentTransactions: Transaction[];
    divisionName?: string;
    error?: string;
    [key: string]: unknown;
}

export default function DivisionWarehouse() {
    const {
        mostStockItems = [],
        leastStockItems = [],
        hasStockOpnameThisMonth = false,
        activeOrders = [],
        recentTransactions = [],
        divisionName,
        error
    } = usePage<PageProps>().props;

    if (error) {
        return (
            <RootLayout title="Dashboard Gudang Divisi">
                <ContentCard title="Dashboard Gudang Divisi">
                    <div className="flex flex-col items-center justify-center py-12">
                        <AlertTriangle className="size-12 text-red-500" />
                        <p className="mt-4 text-slate-600 dark:text-slate-400">{error}</p>
                    </div>
                </ContentCard>
            </RootLayout>
        );
    }

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        });
    };

    const getStatusBadge = (status: string) => {
        const styles: Record<string, string> = {
            Pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
            Confirmed: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            Delivered: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
            Revision: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
        };
        return styles[status] || 'bg-slate-100 text-slate-700';
    };

    return (
        <RootLayout title="Dashboard Gudang Divisi">
            <div className="space-y-6">
                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Dashboard Gudang Divisi</h1>
                    {divisionName && (
                        <p className="mt-1 text-slate-500 dark:text-slate-400">{divisionName}</p>
                    )}
                </div>

                {/* Stock Opname Alert */}
                {!hasStockOpnameThisMonth && (
                    <div className="flex items-center gap-4 rounded-xl border-l-4 border-l-amber-500 bg-amber-50 p-4 dark:bg-amber-900/20">
                        <div className="flex size-10 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/30">
                            <ClipboardCheck className="size-5 text-amber-600" />
                        </div>
                        <div className="flex-1">
                            <p className="font-medium text-amber-800 dark:text-amber-300">Pengingat Stock Opname</p>
                            <p className="text-sm text-amber-700 dark:text-amber-400">Divisi Anda belum melakukan stock opname bulan ini.</p>
                        </div>
                        <Link
                            href="/inventory/stock-opname/division"
                            className="flex items-center gap-1 rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-amber-700"
                        >
                            Lakukan Sekarang
                            <ArrowRight className="size-4" />
                        </Link>
                    </div>
                )}

                {hasStockOpnameThisMonth && (
                    <div className="flex items-center gap-4 rounded-xl border-l-4 border-l-emerald-500 bg-emerald-50 p-4 dark:bg-emerald-900/20">
                        <div className="flex size-10 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                            <CheckCircle className="size-5 text-emerald-600" />
                        </div>
                        <div>
                            <p className="font-medium text-emerald-800 dark:text-emerald-300">Stock Opname Selesai</p>
                            <p className="text-sm text-emerald-700 dark:text-emerald-400">Divisi Anda sudah melakukan stock opname bulan ini.</p>
                        </div>
                    </div>
                )}

                {/* Stock Info */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Most Stock */}
                    <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                        <div className="mb-4 flex items-center gap-2">
                            <TrendingUp className="size-5 text-emerald-600" />
                            <h3 className="font-semibold text-slate-800 dark:text-white">5 Barang Stok Terbanyak</h3>
                        </div>
                        <div className="space-y-3">
                            {mostStockItems.length > 0 ? (
                                mostStockItems.map((item, idx) => (
                                    <div key={item.id} className="flex items-center justify-between rounded-lg bg-slate-50 p-3 dark:bg-slate-700/50">
                                        <div className="flex items-center gap-3">
                                            <span className="flex size-7 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">{idx + 1}</span>
                                            <span className="text-sm font-medium text-slate-700 dark:text-slate-200">{item.name}</span>
                                        </div>
                                        <span className="text-sm font-bold text-emerald-600">{item.stock} {item.unit_of_measure}</span>
                                    </div>
                                ))
                            ) : (
                                <p className="py-4 text-center text-sm text-slate-400">Tidak ada data</p>
                            )}
                        </div>
                    </div>

                    {/* Least Stock */}
                    <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                        <div className="mb-4 flex items-center gap-2">
                            <TrendingDown className="size-5 text-red-600" />
                            <h3 className="font-semibold text-slate-800 dark:text-white">5 Barang Stok Tersedikit</h3>
                        </div>
                        <div className="space-y-3">
                            {leastStockItems.length > 0 ? (
                                leastStockItems.map((item, idx) => (
                                    <div key={item.id} className="flex items-center justify-between rounded-lg bg-slate-50 p-3 dark:bg-slate-700/50">
                                        <div className="flex items-center gap-3">
                                            <span className="flex size-7 items-center justify-center rounded-full bg-red-100 text-xs font-bold text-red-700 dark:bg-red-900/30 dark:text-red-400">{idx + 1}</span>
                                            <span className="text-sm font-medium text-slate-700 dark:text-slate-200">{item.name}</span>
                                        </div>
                                        <span className="text-sm font-bold text-red-600">{item.stock} {item.unit_of_measure}</span>
                                    </div>
                                ))
                            ) : (
                                <p className="py-4 text-center text-sm text-slate-400">Tidak ada data</p>
                            )}
                        </div>
                    </div>
                </div>

                {/* Active Orders & Recent Transactions */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Active Orders */}
                    <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                        <div className="mb-4 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <ShoppingCart className="size-5 text-blue-600" />
                                <h3 className="font-semibold text-slate-800 dark:text-white">Permintaan Aktif</h3>
                            </div>
                            <Link href="/inventory/warehouse-orders" className="text-xs text-primary hover:underline">Lihat Semua</Link>
                        </div>
                        <div className="space-y-3">
                            {activeOrders.length > 0 ? (
                                activeOrders.map((order) => (
                                    <Link
                                        key={order.id}
                                        href={`/inventory/warehouse-orders/${order.id}`}
                                        className="flex items-center justify-between rounded-lg bg-slate-50 p-3 transition-colors hover:bg-slate-100 dark:bg-slate-700/50 dark:hover:bg-slate-700"
                                    >
                                        <div>
                                            <p className="text-sm font-medium text-slate-700 dark:text-slate-200">Order #{order.id}</p>
                                            <p className="text-xs text-slate-400">{order.carts_count} item • {formatDate(order.created_at)}</p>
                                        </div>
                                        <span className={`rounded-full px-2 py-1 text-xs font-medium ${getStatusBadge(order.status)}`}>
                                            {order.status}
                                        </span>
                                    </Link>
                                ))
                            ) : (
                                <p className="py-4 text-center text-sm text-slate-400">Tidak ada permintaan aktif</p>
                            )}
                        </div>
                    </div>

                    {/* Recent Transactions */}
                    <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                        <div className="mb-4 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <History className="size-5 text-violet-600" />
                                <h3 className="font-semibold text-slate-800 dark:text-white">Transaksi Terbaru</h3>
                            </div>
                            <Link href="/inventory/transactions" className="text-xs text-primary hover:underline">Lihat Semua</Link>
                        </div>
                        <div className="space-y-3">
                            {recentTransactions.length > 0 ? (
                                recentTransactions.map((tx) => (
                                    <div key={tx.id} className="flex items-center justify-between rounded-lg bg-slate-50 p-3 dark:bg-slate-700/50">
                                        <div>
                                            <p className="text-sm font-medium text-slate-700 dark:text-slate-200">{tx.item?.name}</p>
                                            <p className="text-xs text-slate-400">{tx.user?.name} • {formatDate(tx.created_at)}</p>
                                        </div>
                                        <span className={`text-sm font-bold ${tx.type === 'in' ? 'text-emerald-600' : 'text-red-600'}`}>
                                            {tx.type === 'in' ? '+' : '-'}{tx.quantity}
                                        </span>
                                    </div>
                                ))
                            ) : (
                                <p className="py-4 text-center text-sm text-slate-400">Tidak ada transaksi</p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </RootLayout>
    );
}
