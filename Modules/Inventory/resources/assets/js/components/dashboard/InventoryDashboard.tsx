import { useState } from 'react';
import { usePage, Link } from '@inertiajs/react';
import { Warehouse, TrendingUp, TrendingDown, ClipboardCheck, ShoppingCart, History, ArrowRight, CheckCircle, Building2, Globe, XCircle } from 'lucide-react';

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
    division?: { id: number; name: string };
    carts_count?: number;
    carts_sum_quantity?: number;
    created_at: string;
}

interface Transaction {
    id: number;
    type: string;
    quantity: number;
    created_at: string;
    item?: { id: number; name: string; division_id?: number; division?: { name: string } };
    user?: { id: number; name: string };
}

interface StockOpnameStatus {
    division_id: number | null;
    division_name: string;
    has_stock_opname: boolean;
}

interface TabData {
    most_stock_items?: Item[];
    least_stock_items?: Item[];
    has_stock_opname_this_month?: boolean;
    active_orders?: Order[];
    recent_orders?: Order[];
    recent_transactions?: Transaction[];
    stock_opname_status?: StockOpnameStatus[];
}

interface InventoryTab {
    id: string;
    label: string;
    icon: string;
    type: string;
    stock_opname_link?: string;
    data: TabData;
}

interface DashboardData {
    inventory?: InventoryTab[];
    [key: string]: unknown;
}

interface PageProps {
    dashboardData?: DashboardData;
    [key: string]: unknown;
}

export default function InventoryDashboard() {
    const { dashboardData } = usePage<PageProps>().props;
    const inventoryTabs = dashboardData?.inventory || [];

    const [activeTabIndex, setActiveTabIndex] = useState(0);

    if (inventoryTabs.length === 0) {
        return null;
    }

    const activeTab = inventoryTabs[activeTabIndex];

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
        });
    };

    const getStatusBadge = (status: string) => {
        const styles: Record<string, string> = {
            Pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
            Confirmed: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            Delivered: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
            Revision: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
            Finished: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
            Rejected: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        };
        return styles[status] || 'bg-slate-100 text-slate-700';
    };

    const getIcon = (iconName: string) => {
        switch (iconName) {
            case 'building':
                return Building2;
            case 'globe':
                return Globe;
            case 'warehouse':
            default:
                return Warehouse;
        }
    };

    const renderWarehouseContent = (tab: InventoryTab) => {
        const {
            most_stock_items = [],
            least_stock_items = [],
            has_stock_opname_this_month = false,
            active_orders = [],
            recent_transactions = [],
        } = tab.data;

        const isMainWarehouse = tab.id === 'main';

        return (
            <div className="space-y-5 animate-in fade-in duration-300">
                {/* Stock Opname Alert */}
                {!has_stock_opname_this_month ? (
                    <div className="flex items-center gap-4 rounded-xl border-l-4 border-l-amber-500 bg-amber-50 p-5 dark:bg-amber-900/20">
                        <div className="flex size-12 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/30">
                            <ClipboardCheck className="size-6 text-amber-600" />
                        </div>
                        <div className="flex-1">
                            <p className="text-base font-medium text-amber-800 dark:text-amber-300">Pengingat Stock Opname</p>
                            <p className="text-sm text-amber-700 dark:text-amber-400">{tab.label} belum melakukan stock opname bulan ini.</p>
                        </div>
                        <Link
                            href={tab.stock_opname_link || '#'}
                            className="flex items-center gap-2 rounded-lg bg-amber-600 px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-amber-700"
                        >
                            Lakukan
                            <ArrowRight className="size-4" />
                        </Link>
                    </div>
                ) : (
                    <div className="flex items-center gap-3 rounded-xl border-l-4 border-l-emerald-500 bg-emerald-50 p-4 dark:bg-emerald-900/20">
                        <CheckCircle className="size-5 text-emerald-600" />
                        <p className="text-sm font-medium text-emerald-700 dark:text-emerald-400">Stock opname {tab.label.toLowerCase()} bulan ini sudah dilakukan ✓</p>
                    </div>
                )}

                {/* Stock Info */}
                <div className="grid gap-5 lg:grid-cols-2">
                    <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                        <div className="mb-4 flex items-center gap-2">
                            <TrendingUp className="size-5 text-emerald-600" />
                            <h3 className="font-semibold text-slate-800 dark:text-white">Stok Terbanyak</h3>
                        </div>
                        <div className="space-y-3">
                            {most_stock_items.length > 0 ? most_stock_items.map((item, idx) => (
                                <div key={item.id} className="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-700/50">
                                    <div className="flex items-center gap-3">
                                        <span className="flex size-7 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">{idx + 1}</span>
                                        <span className="text-sm font-medium text-slate-700 dark:text-slate-200">{item.name}</span>
                                    </div>
                                    <span className="text-sm font-bold text-emerald-600">{item.stock} {item.unit_of_measure}</span>
                                </div>
                            )) : <p className="py-4 text-center text-sm text-slate-400">Tidak ada data</p>}
                        </div>
                    </div>

                    <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                        <div className="mb-4 flex items-center gap-2">
                            <TrendingDown className="size-5 text-red-600" />
                            <h3 className="font-semibold text-slate-800 dark:text-white">Stok Tersedikit</h3>
                        </div>
                        <div className="space-y-3">
                            {least_stock_items.length > 0 ? least_stock_items.map((item, idx) => (
                                <div key={item.id} className="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-700/50">
                                    <div className="flex items-center gap-3">
                                        <span className="flex size-7 items-center justify-center rounded-full bg-red-100 text-xs font-bold text-red-700 dark:bg-red-900/30 dark:text-red-400">{idx + 1}</span>
                                        <span className="text-sm font-medium text-slate-700 dark:text-slate-200">{item.name}</span>
                                    </div>
                                    <span className="text-sm font-bold text-red-600">{item.stock} {item.unit_of_measure}</span>
                                </div>
                            )) : <p className="py-4 text-center text-sm text-slate-400">Tidak ada data</p>}
                        </div>
                    </div>
                </div>

                {/* Active Orders & Recent Transactions */}
                <div className="grid gap-5 lg:grid-cols-2">
                    <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                        <div className="mb-4 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <ShoppingCart className="size-5 text-blue-600" />
                                <h3 className="font-semibold text-slate-800 dark:text-white">Permintaan Aktif</h3>
                            </div>
                            <Link href="/inventory/warehouse-orders" className="text-xs text-primary hover:underline">Lihat Semua</Link>
                        </div>
                        <div className="space-y-3">
                            {active_orders.length > 0 ? active_orders.map((order) => (
                                <Link key={order.id} href={`/inventory/warehouse-orders/${order.id}`} className="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 transition-colors hover:bg-slate-100 dark:bg-slate-700/50 dark:hover:bg-slate-700">
                                    <div>
                                        <p className="text-sm font-medium text-slate-700 dark:text-slate-200">
                                            Order #{order.id}
                                            {isMainWarehouse && order.division && <span className="ml-1 text-xs text-slate-400">({order.division.name})</span>}
                                        </p>
                                        <p className="text-xs text-slate-400">{order.carts_count} item • {formatDate(order.created_at)}</p>
                                    </div>
                                    <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${getStatusBadge(order.status)}`}>{order.status}</span>
                                </Link>
                            )) : <p className="py-4 text-center text-sm text-slate-400">Tidak ada permintaan aktif</p>}
                        </div>
                    </div>

                    <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                        <div className="mb-4 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <History className="size-5 text-violet-600" />
                                <h3 className="font-semibold text-slate-800 dark:text-white">Transaksi Terbaru</h3>
                            </div>
                            <Link href="/inventory/transactions" className="text-xs text-primary hover:underline">Lihat Semua</Link>
                        </div>
                        <div className="space-y-3">
                            {recent_transactions.length > 0 ? recent_transactions.map((tx) => (
                                <div key={tx.id} className="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-700/50">
                                    <div>
                                        <p className="text-sm font-medium text-slate-700 dark:text-slate-200">{tx.item?.name}</p>
                                        <p className="text-xs text-slate-400">{formatDate(tx.created_at)}</p>
                                    </div>
                                    <span className={`text-sm font-bold ${tx.type === 'in' ? 'text-emerald-600' : 'text-red-600'}`}>
                                        {tx.type === 'in' ? '+' : '-'}{tx.quantity}
                                    </span>
                                </div>
                            )) : <p className="py-4 text-center text-sm text-slate-400">Tidak ada transaksi</p>}
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    const renderOverviewContent = (tab: InventoryTab) => {
        const {
            recent_transactions = [],
            recent_orders = [],
            stock_opname_status = [],
        } = tab.data;

        const completedCount = stock_opname_status.filter(s => s.has_stock_opname).length;
        const totalCount = stock_opname_status.length;

        return (
            <div className="space-y-5 animate-in fade-in duration-300">
                {/* Stock Opname Monitoring */}
                <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                    <div className="mb-4 flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <ClipboardCheck className="size-5 text-primary" />
                            <h3 className="font-semibold text-slate-800 dark:text-white">Status Stock Opname Bulan Ini</h3>
                        </div>
                        <span className="text-sm font-medium text-slate-500">
                            {completedCount}/{totalCount} Selesai
                        </span>
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        {stock_opname_status.map((status, idx) => (
                            <div key={idx} className={`flex items-center justify-between rounded-lg px-4 py-3 ${status.has_stock_opname ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-red-50 dark:bg-red-900/20'}`}>
                                <span className="text-sm font-medium text-slate-700 dark:text-slate-200">{status.division_name}</span>
                                {status.has_stock_opname ? (
                                    <CheckCircle className="size-5 text-emerald-600" />
                                ) : (
                                    <XCircle className="size-5 text-red-500" />
                                )}
                            </div>
                        ))}
                    </div>
                </div>

                {/* Recent Orders & Transactions */}
                <div className="grid gap-5 lg:grid-cols-2">
                    <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                        <div className="mb-4 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <ShoppingCart className="size-5 text-blue-600" />
                                <h3 className="font-semibold text-slate-800 dark:text-white">5 Permintaan Terbaru</h3>
                            </div>
                            <Link href="/inventory/warehouse-orders" className="text-xs text-primary hover:underline">Lihat Semua</Link>
                        </div>
                        <div className="space-y-3">
                            {recent_orders.length > 0 ? recent_orders.map((order) => (
                                <Link key={order.id} href={`/inventory/warehouse-orders/${order.id}`} className="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 transition-colors hover:bg-slate-100 dark:bg-slate-700/50 dark:hover:bg-slate-700">
                                    <div>
                                        <p className="text-sm font-medium text-slate-700 dark:text-slate-200">
                                            Order #{order.id}
                                            {order.division && <span className="ml-1 text-xs text-slate-400">({order.division.name})</span>}
                                        </p>
                                        <p className="text-xs text-slate-400">{order.carts_count} item • {formatDate(order.created_at)}</p>
                                    </div>
                                    <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${getStatusBadge(order.status)}`}>{order.status}</span>
                                </Link>
                            )) : <p className="py-4 text-center text-sm text-slate-400">Tidak ada permintaan</p>}
                        </div>
                    </div>

                    <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                        <div className="mb-4 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <History className="size-5 text-violet-600" />
                                <h3 className="font-semibold text-slate-800 dark:text-white">Transaksi Terbaru (Global)</h3>
                            </div>
                            <Link href="/inventory/transactions" className="text-xs text-primary hover:underline">Lihat Semua</Link>
                        </div>
                        <div className="space-y-3">
                            {recent_transactions.length > 0 ? recent_transactions.map((tx) => (
                                <div key={tx.id} className="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-700/50">
                                    <div>
                                        <p className="text-sm font-medium text-slate-700 dark:text-slate-200">{tx.item?.name}</p>
                                        <p className="text-xs text-slate-400">
                                            {tx.item?.division?.name || 'Gudang Utama'} • {formatDate(tx.created_at)}
                                        </p>
                                    </div>
                                    <span className={`text-sm font-bold ${tx.type === 'in' ? 'text-emerald-600' : 'text-red-600'}`}>
                                        {tx.type === 'in' ? '+' : '-'}{tx.quantity}
                                    </span>
                                </div>
                            )) : <p className="py-4 text-center text-sm text-slate-400">Tidak ada transaksi</p>}
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    const renderTabContent = (tab: InventoryTab) => {
        if (tab.type === 'overview') {
            return renderOverviewContent(tab);
        }
        return renderWarehouseContent(tab);
    };

    return (
        <div className="space-y-5">
            {inventoryTabs.length > 1 && (
                <div className="flex gap-6 border-b border-slate-200 dark:border-slate-700">
                    {inventoryTabs.map((tab, index) => {
                        const isActive = activeTabIndex === index;
                        return (
                            <button
                                key={tab.id}
                                onClick={() => setActiveTabIndex(index)}
                                className={`relative pb-3 text-sm font-medium transition-colors ${isActive
                                        ? 'text-white'
                                        : 'text-slate-400 hover:text-slate-300'
                                    }`}
                            >
                                {tab.label}
                                {isActive && (
                                    <span className="absolute bottom-0 left-0 h-0.5 w-full bg-white" />
                                )}
                            </button>
                        );
                    })}
                </div>
            )}

            {activeTab && renderTabContent(activeTab)}
        </div>
    );
}
