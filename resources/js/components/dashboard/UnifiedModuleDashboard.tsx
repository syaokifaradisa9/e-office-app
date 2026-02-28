import { useState } from 'react';
import { usePage, Link } from '@inertiajs/react';
import {
    Warehouse,
    TrendingUp,
    TrendingDown,
    ClipboardCheck,
    ShoppingCart,
    History,
    ArrowRight,
    CheckCircle,
    Building2,
    Globe,
    XCircle,
    FileText,
    HardDrive,
    FolderOpen,
    Clock,
    AlertTriangle,
    Users,
    FileArchive,
    UserCheck,
    UserX,
    Star,
    Wrench,
    Calendar,
    ChevronRight,
} from 'lucide-react';

// ============================================
// INVENTORY TYPES
// ============================================
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

interface InventoryTabData {
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
    data: InventoryTabData;
}

// ============================================
// ARCHIEVE TYPES
// ============================================
interface Document {
    id: number;
    title: string;
    classification?: { name: string };
    uploader?: { name: string };
    created_at: string;
}

interface CategoryDistribution {
    name: string;
    count: number;
}

interface DivisionStorageStatus {
    division_id: number;
    division_name: string;
    used_size_label: string;
    max_size_label: string;
    percentage: number;
    status: string;
}

interface TopUploader {
    user_id: number;
    user_name: string;
    total: number;
}

interface ArchieveTabData {
    storage?: {
        used: number;
        used_label: string;
        max: number;
        max_label: string;
        percentage: number;
    };
    document_count?: number;
    recent_documents?: Document[];
    category_distribution?: CategoryDistribution[];
    division_name?: string;
    total_documents?: number;
    total_size?: number;
    total_size_label?: string;
    division_storage_status?: DivisionStorageStatus[];
    top_uploaders?: TopUploader[];
}

interface ArchieveTab {
    id: string;
    label: string;
    icon: string;
    type: string;
    data: ArchieveTabData;
}

// ============================================
// VISITOR TYPES
// ============================================
interface VisitorStatistics {
    today_visitors: number;
    active_visitors: number;
    rejected_visits: number;
    average_rating: number;
}

interface RecentVisitor {
    id: number;
    name: string;
    organization: string;
    division: string;
    purpose: string;
    status: string;
    check_in_at: string;
}

interface PendingVisitor {
    id: number;
    name: string;
    organization: string;
    division: string;
    purpose: string;
    check_in_at: string;
}

interface MonthlyTrend {
    month: string;
    count: number;
}

interface PurposeDistribution {
    name: string;
    count: number;
}

interface VisitorTabData {
    statistics?: VisitorStatistics;
    recent_visitors?: RecentVisitor[];
    pending_visitors?: PendingVisitor[];
    monthly_trend?: MonthlyTrend[];
    purpose_distribution?: PurposeDistribution[];
}

interface VisitorTab {
    id: string;
    label: string;
    icon: string;
    type: string;
    data: VisitorTabData;
}

// ============================================
// TICKETING TYPES
// ============================================
interface TicketSummary {
    total: number;
    pending?: number;
    process?: number;
    resolved?: number;
    active?: number;
    finished?: number;
}

interface RecentTicket {
    id: number;
    subject: string;
    status: { value: string; label: string };
    user?: { name: string };
    asset_item?: { asset_category?: { name: string } };
    created_at: string;
}

interface CategoryReport {
    name: string;
    total: number;
}

interface TicketingTabData {
    // New structure
    stats?: {
        total_assets: number;
        remaining_maintenance: number;
        tickets_this_year: number;
        assets_under_repair: number;
    };
    nearest_maintenances?: any[];

    // Original fields for Division/All
    tickets?: TicketSummary;
    assets_count?: number;
    upcoming_maintenance?: number;
    division_name?: string;
    recent_active_tickets?: RecentTicket[];
    maintenance_this_month?: number;
    ticket_distribution?: Record<string, number>;
    most_reported_categories?: CategoryReport[];
    overdue_maintenance_count?: number;
    total_open_tickets?: number;
    average_rating?: number;
}

interface TicketingTab {
    id: string;
    label: string;
    icon: string;
    type: string;
    data: TicketingTabData;
}

// ============================================
// UNIFIED TAB TYPE
// ============================================
interface UnifiedTab {
    id: string;
    label: string;
    icon: string;
    type: string;
    module: 'inventory' | 'archieve' | 'visitor' | 'ticketing';
    originalData: InventoryTabData | ArchieveTabData | VisitorTabData | TicketingTabData;
    stock_opname_link?: string;
}

interface DashboardData {
    inventory?: InventoryTab[];
    archieve?: ArchieveTab[];
    visitor?: VisitorTab[];
    ticketing?: TicketingTab[];
    [key: string]: unknown;
}

interface PageProps {
    dashboardData?: DashboardData;
    [key: string]: unknown;
}

export default function UnifiedModuleDashboard() {
    const { dashboardData } = usePage<PageProps>().props;

    // Combine tabs from both modules with proper labels
    const unifiedTabs: UnifiedTab[] = [];

    // Add Inventory tabs with prefix
    const inventoryTabs = dashboardData?.inventory || [];
    inventoryTabs.forEach((tab) => {
        unifiedTabs.push({
            id: `inventory-${tab.id}`,
            label: tab.label.includes('Gudang') ? tab.label : `Gudang ${tab.label}`,
            icon: tab.icon,
            type: tab.type,
            module: 'inventory',
            originalData: tab.data,
            stock_opname_link: tab.stock_opname_link,
        });
    });

    // Add Archieve tabs with prefix
    const archieveTabs = dashboardData?.archieve || [];
    archieveTabs.forEach((tab) => {
        unifiedTabs.push({
            id: `archieve-${tab.id}`,
            label: tab.label.includes('Arsip') ? tab.label : `Arsip ${tab.label}`,
            icon: tab.icon,
            type: tab.type,
            module: 'archieve',
            originalData: tab.data,
        });
    });

    // Add Visitor tabs
    const visitorTabs = dashboardData?.visitor || [];
    visitorTabs.forEach((tab) => {
        unifiedTabs.push({
            id: `visitor-${tab.id}`,
            label: tab.label,
            icon: tab.icon,
            type: tab.type,
            module: 'visitor',
            originalData: tab.data,
        });
    });

    // Add Ticketing tabs
    const ticketingTabs = dashboardData?.ticketing || [];
    ticketingTabs.forEach((tab) => {
        unifiedTabs.push({
            id: `ticketing-${tab.id}`,
            label: tab.label,
            icon: tab.icon,
            type: tab.type,
            module: 'ticketing',
            originalData: tab.data,
        });
    });

    const [activeTabIndex, setActiveTabIndex] = useState(0);

    if (unifiedTabs.length === 0) {
        return null;
    }

    const activeTab = unifiedTabs[activeTabIndex];

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

    const getTabIcon = (tab: UnifiedTab) => {
        if (tab.module === 'archieve') {
            return tab.icon === 'globe' ? Globe : FileArchive;
        }
        if (tab.module === 'visitor' || tab.module === 'ticketing') {
            if (tab.icon === 'user') return Users;
            if (tab.icon === 'building') return Building2;
            if (tab.icon === 'globe') return Globe;
            if (tab.icon === 'wrench') return Wrench;
            return Users;
        }
        switch (tab.icon) {
            case 'building':
                return Building2;
            case 'globe':
                return Globe;
            case 'warehouse':
            default:
                return Warehouse;
        }
    };

    // ============================================
    // INVENTORY RENDER FUNCTIONS
    // ============================================
    const renderInventoryWarehouseContent = (tab: UnifiedTab) => {
        const data = tab.originalData as InventoryTabData;
        const {
            most_stock_items = [],
            least_stock_items = [],
            has_stock_opname_this_month = false,
            active_orders = [],
            recent_transactions = [],
        } = data;

        const isMainWarehouse = tab.id === 'inventory-main';

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

    const renderInventoryOverviewContent = (tab: UnifiedTab) => {
        const data = tab.originalData as InventoryTabData;
        const {
            recent_transactions = [],
            recent_orders = [],
            stock_opname_status = [],
        } = data;

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

    // ============================================
    // ARCHIEVE RENDER FUNCTIONS
    // ============================================
    const renderArchieveDivisionContent = (tab: UnifiedTab) => {
        const data = tab.originalData as ArchieveTabData;
        const {
            storage,
            document_count = 0,
            recent_documents = [],
            category_distribution = [],
        } = data;

        const storageStatus = storage
            ? storage.percentage >= 90
                ? 'critical'
                : storage.percentage >= 70
                    ? 'warning'
                    : 'stable'
            : 'stable';

        return (
            <div className="space-y-5 animate-in fade-in duration-300">
                {/* Stats */}
                <div className="grid gap-4 md:grid-cols-3">
                    <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <FileText className="size-5" />
                            </div>
                            <div>
                                <p className="text-xs font-medium text-slate-500">Total Dokumen</p>
                                <p className="text-xl font-bold text-slate-900 dark:text-white">{document_count}</p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                        <div className="flex items-center gap-3">
                            <div className={`flex size-10 items-center justify-center rounded-lg ${storageStatus === 'critical' ? 'bg-rose-100 text-rose-600' :
                                storageStatus === 'warning' ? 'bg-amber-100 text-amber-600' :
                                    'bg-emerald-100 text-emerald-600'
                                }`}>
                                <HardDrive className="size-5" />
                            </div>
                            <div>
                                <p className="text-xs font-medium text-slate-500">Penyimpanan</p>
                                <p className="text-xl font-bold text-slate-900 dark:text-white">{storage?.used_label || '0 B'}</p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-lg bg-violet-100 text-violet-600">
                                <FolderOpen className="size-5" />
                            </div>
                            <div>
                                <p className="text-xs font-medium text-slate-500">Kategori</p>
                                <p className="text-xl font-bold text-slate-900 dark:text-white">{category_distribution.length}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Storage Alert */}
                {storage && storage.max > 0 && storage.percentage >= 70 && (
                    <div className={`flex items-center gap-4 rounded-xl border-l-4 p-4 ${storageStatus === 'critical'
                        ? 'border-l-rose-500 bg-rose-50 dark:bg-rose-900/20'
                        : 'border-l-amber-500 bg-amber-50 dark:bg-amber-900/20'
                        }`}>
                        <AlertTriangle className={`size-5 ${storageStatus === 'critical' ? 'text-rose-600' : 'text-amber-600'}`} />
                        <div>
                            <p className={`text-sm font-medium ${storageStatus === 'critical' ? 'text-rose-800 dark:text-rose-300' : 'text-amber-800 dark:text-amber-300'}`}>
                                {storageStatus === 'critical' ? 'Penyimpanan Hampir Penuh!' : 'Penyimpanan Mendekati Batas'}
                            </p>
                            <p className={`text-xs ${storageStatus === 'critical' ? 'text-rose-700 dark:text-rose-400' : 'text-amber-700 dark:text-amber-400'}`}>
                                {storage.used_label} dari {storage.max_label} ({storage.percentage}%)
                            </p>
                        </div>
                    </div>
                )}

                {/* Recent Documents & Category Distribution */}
                <div className="grid gap-5 lg:grid-cols-2">
                    <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                        <div className="mb-4 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Clock className="size-5 text-primary" />
                                <h3 className="font-semibold text-slate-800 dark:text-white">Dokumen Terbaru</h3>
                            </div>
                            <Link href="/archieve/documents" className="text-xs text-primary hover:underline">Lihat Semua</Link>
                        </div>
                        <div className="space-y-3">
                            {recent_documents.length > 0 ? recent_documents.slice(0, 5).map((doc) => (
                                <div key={doc.id} className="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-700/50">
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-medium text-slate-700 dark:text-slate-200">{doc.title}</p>
                                        <p className="text-xs text-slate-400">{doc.classification?.name}</p>
                                    </div>
                                </div>
                            )) : <p className="py-4 text-center text-sm text-slate-400">Belum ada dokumen</p>}
                        </div>
                    </div>

                    <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                        <div className="mb-4 flex items-center gap-2">
                            <FolderOpen className="size-5 text-violet-600" />
                            <h3 className="font-semibold text-slate-800 dark:text-white">Top Kategori</h3>
                        </div>
                        <div className="space-y-3">
                            {category_distribution.length > 0 ? category_distribution.slice(0, 5).map((cat, idx) => (
                                <div key={idx} className="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-700/50">
                                    <div className="flex items-center gap-3">
                                        <span className="flex size-7 items-center justify-center rounded-full bg-violet-100 text-xs font-bold text-violet-700 dark:bg-violet-900/30 dark:text-violet-400">{idx + 1}</span>
                                        <span className="text-sm font-medium text-slate-700 dark:text-slate-200">{cat.name}</span>
                                    </div>
                                    <span className="text-sm font-bold text-violet-600">{cat.count}</span>
                                </div>
                            )) : <p className="py-4 text-center text-sm text-slate-400">Tidak ada data</p>}
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    const renderArchieveOverviewContent = (tab: UnifiedTab) => {
        const data = tab.originalData as ArchieveTabData;
        const {
            total_documents = 0,
            total_size_label = '0 B',
            division_storage_status = [],
            top_uploaders = [],
            recent_documents = [],
        } = data;

        const criticalCount = division_storage_status.filter(d => d.status === 'critical').length;

        return (
            <div className="space-y-5 animate-in fade-in duration-300">
                {/* Stats */}
                <div className="grid gap-4 md:grid-cols-4">
                    <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <FileText className="size-5" />
                            </div>
                            <div>
                                <p className="text-xs font-medium text-slate-500">Total Dokumen</p>
                                <p className="text-xl font-bold text-slate-900 dark:text-white">{total_documents}</p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-lg bg-sky-100 text-sky-600">
                                <HardDrive className="size-5" />
                            </div>
                            <div>
                                <p className="text-xs font-medium text-slate-500">Total Penyimpanan</p>
                                <p className="text-xl font-bold text-slate-900 dark:text-white">{total_size_label}</p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-lg bg-violet-100 text-violet-600">
                                <Building2 className="size-5" />
                            </div>
                            <div>
                                <p className="text-xs font-medium text-slate-500">Total Divisi</p>
                                <p className="text-xl font-bold text-slate-900 dark:text-white">{division_storage_status.length}</p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                        <div className="flex items-center gap-3">
                            <div className={`flex size-10 items-center justify-center rounded-lg ${criticalCount > 0 ? 'bg-rose-100 text-rose-600' : 'bg-emerald-100 text-emerald-600'}`}>
                                {criticalCount > 0 ? <AlertTriangle className="size-5" /> : <CheckCircle className="size-5" />}
                            </div>
                            <div>
                                <p className="text-xs font-medium text-slate-500">Divisi Kritis</p>
                                <p className="text-xl font-bold text-slate-900 dark:text-white">{criticalCount}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Division Storage Status */}
                <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                    <div className="mb-4 flex items-center gap-2">
                        <HardDrive className="size-5 text-primary" />
                        <h3 className="font-semibold text-slate-800 dark:text-white">Status Penyimpanan Divisi</h3>
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        {division_storage_status.map((div) => (
                            <div key={div.division_id} className={`flex items-center justify-between rounded-lg px-4 py-3 ${div.status === 'critical' ? 'bg-rose-50 dark:bg-rose-900/20' :
                                div.status === 'warning' ? 'bg-amber-50 dark:bg-amber-900/20' :
                                    'bg-emerald-50 dark:bg-emerald-900/20'
                                }`}>
                                <div>
                                    <span className="text-sm font-medium text-slate-700 dark:text-slate-200">{div.division_name}</span>
                                    <p className="text-xs text-slate-500">{div.used_size_label} / {div.max_size_label}</p>
                                </div>
                                {div.status === 'critical' ? (
                                    <XCircle className="size-5 text-rose-600" />
                                ) : div.status === 'warning' ? (
                                    <AlertTriangle className="size-5 text-amber-600" />
                                ) : (
                                    <CheckCircle className="size-5 text-emerald-600" />
                                )}
                            </div>
                        ))}
                    </div>
                </div>

                {/* Top Uploaders & Recent Documents */}
                <div className="grid gap-5 lg:grid-cols-2">
                    <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                        <div className="mb-4 flex items-center gap-2">
                            <Users className="size-5 text-emerald-600" />
                            <h3 className="font-semibold text-slate-800 dark:text-white">Top Uploader</h3>
                        </div>
                        <div className="space-y-3">
                            {top_uploaders && top_uploaders.length > 0 ? top_uploaders.slice(0, 5).map((user, idx) => (
                                <div key={idx} className="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-700/50">
                                    <div className="flex items-center gap-3">
                                        <span className="flex size-7 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">{idx + 1}</span>
                                        <span className="text-sm font-medium text-slate-700 dark:text-slate-200">{user.user_name}</span>
                                    </div>
                                    <span className="text-sm font-bold text-emerald-600">{user.total}</span>
                                </div>
                            )) : <p className="py-4 text-center text-sm text-slate-400">Tidak ada data</p>}
                        </div>
                    </div>

                    <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                        <div className="mb-4 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Clock className="size-5 text-primary" />
                                <h3 className="font-semibold text-slate-800 dark:text-white">Dokumen Terbaru</h3>
                            </div>
                            <Link href="/archieve/documents" className="text-xs text-primary hover:underline">Lihat Semua</Link>
                        </div>
                        <div className="space-y-3">
                            {recent_documents && recent_documents.length > 0 ? recent_documents.slice(0, 5).map((doc) => (
                                <div key={doc.id} className="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-700/50">
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-medium text-slate-700 dark:text-slate-200">{doc.title}</p>
                                        <p className="text-xs text-slate-400">{doc.classification?.name}</p>
                                    </div>
                                </div>
                            )) : <p className="py-4 text-center text-sm text-slate-400">Belum ada dokumen</p>}
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    // ============================================
    // RENDER TAB CONTENT
    // ============================================
    // ============================================
    // VISITOR RENDER FUNCTIONS
    // ============================================
    const renderVisitorOverviewContent = (tab: UnifiedTab) => {
        const data = tab.originalData as VisitorTabData;
        const {
            statistics,
            recent_visitors = [],
            pending_visitors = [],
            monthly_trend = [],
            purpose_distribution = [],
        } = data;

        const getStatusBadgeColor = (status: string) => {
            switch (status) {
                case 'pending': return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400';
                case 'approved': return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
                case 'rejected': return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
                case 'completed': return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
                default: return 'bg-slate-100 text-slate-700';
            }
        };

        const getStatusLabel = (status: string) => {
            switch (status) {
                case 'pending': return 'Menunggu';
                case 'approved': return 'Disetujui';
                case 'rejected': return 'Ditolak';
                case 'completed': return 'Selesai';
                default: return status;
            }
        };

        return (
            <div className="space-y-5 animate-in fade-in duration-300">
                {/* Statistics */}
                <div className="grid gap-4 md:grid-cols-4">
                    <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <Users className="size-5" />
                            </div>
                            <div>
                                <p className="text-xs font-medium text-slate-500">Pengunjung Hari Ini</p>
                                <p className="text-xl font-bold text-slate-900 dark:text-white">{statistics?.today_visitors || 0}</p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                                <UserCheck className="size-5" />
                            </div>
                            <div>
                                <p className="text-xs font-medium text-slate-500">Sedang Berkunjung</p>
                                <p className="text-xl font-bold text-slate-900 dark:text-white">{statistics?.active_visitors || 0}</p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-lg bg-red-100 text-red-600">
                                <UserX className="size-5" />
                            </div>
                            <div>
                                <p className="text-xs font-medium text-slate-500">Ditolak Hari Ini</p>
                                <p className="text-xl font-bold text-slate-900 dark:text-white">{statistics?.rejected_visits || 0}</p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                                <Star className="size-5" />
                            </div>
                            <div>
                                <p className="text-xs font-medium text-slate-500">Rata-rata Rating</p>
                                <p className="text-xl font-bold text-slate-900 dark:text-white">{statistics?.average_rating || 0}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Pending & Recent Visitors */}
                <div className="grid gap-5 lg:grid-cols-2">
                    {/* Pending Visitors */}
                    <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                        <div className="mb-4 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Clock className="size-5 text-amber-600" />
                                <h3 className="font-semibold text-slate-800 dark:text-white">Menunggu Konfirmasi</h3>
                            </div>
                            <Link href="/visitor" className="text-xs text-primary hover:underline">Lihat Semua</Link>
                        </div>
                        <div className="space-y-3">
                            {pending_visitors.length > 0 ? pending_visitors.map((visitor) => (
                                <Link key={visitor.id} href={`/visitor/${visitor.id}`} className="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 transition-colors hover:bg-slate-100 dark:bg-slate-700/50 dark:hover:bg-slate-700">
                                    <div>
                                        <p className="text-sm font-medium text-slate-700 dark:text-slate-200">{visitor.name}</p>
                                        <p className="text-xs text-slate-400">{visitor.organization} • {visitor.division}</p>
                                    </div>
                                    <span className="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Menunggu</span>
                                </Link>
                            )) : <p className="py-4 text-center text-sm text-slate-400">Tidak ada pengunjung menunggu</p>}
                        </div>
                    </div>

                    {/* Recent Visitors */}
                    <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                        <div className="mb-4 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <History className="size-5 text-violet-600" />
                                <h3 className="font-semibold text-slate-800 dark:text-white">Pengunjung Terbaru</h3>
                            </div>
                            <Link href="/visitor" className="text-xs text-primary hover:underline">Lihat Semua</Link>
                        </div>
                        <div className="space-y-3">
                            {recent_visitors.length > 0 ? recent_visitors.map((visitor) => (
                                <div key={visitor.id} className="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-700/50">
                                    <div>
                                        <p className="text-sm font-medium text-slate-700 dark:text-slate-200">{visitor.name}</p>
                                        <p className="text-xs text-slate-400">{visitor.check_in_at}</p>
                                    </div>
                                    <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${getStatusBadgeColor(visitor.status)}`}>
                                        {getStatusLabel(visitor.status)}
                                    </span>
                                </div>
                            )) : <p className="py-4 text-center text-sm text-slate-400">Tidak ada pengunjung</p>}
                        </div>
                    </div>
                </div>

                {/* Monthly Trend & Purpose Distribution */}
                <div className="grid gap-5 lg:grid-cols-2">
                    {/* Monthly Trend */}
                    <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                        <div className="mb-4 flex items-center gap-2">
                            <TrendingUp className="size-5 text-primary" />
                            <h3 className="font-semibold text-slate-800 dark:text-white">Tren Bulanan</h3>
                        </div>
                        <div className="flex items-end justify-between gap-1 h-32 overflow-x-auto">
                            {monthly_trend.map((item, idx) => {
                                const maxCount = Math.max(...monthly_trend.map(d => d.count), 1);
                                const height = (item.count / maxCount) * 100;
                                return (
                                    <div key={idx} className="flex flex-1 flex-col items-center gap-1 min-w-6">
                                        <div className="w-full flex items-end justify-center" style={{ height: '80px' }}>
                                            <div
                                                className="w-full max-w-6 rounded-t bg-primary/80 transition-all hover:bg-primary"
                                                style={{ height: `${Math.max(height, 5)}%` }}
                                            />
                                        </div>
                                        <span className="text-[10px] text-slate-500 whitespace-nowrap">{item.month.split(' ')[0]}</span>
                                        <span className="text-[10px] font-bold text-slate-700 dark:text-slate-300">{item.count}</span>
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    {/* Purpose Distribution */}
                    <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                        <div className="mb-4 flex items-center gap-2">
                            <ClipboardCheck className="size-5 text-emerald-600" />
                            <h3 className="font-semibold text-slate-800 dark:text-white">Keperluan Kunjungan</h3>
                        </div>
                        <div className="space-y-3">
                            {purpose_distribution.length > 0 ? purpose_distribution.slice(0, 5).map((purpose, idx) => (
                                <div key={idx} className="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-700/50">
                                    <div className="flex items-center gap-3">
                                        <span className="flex size-7 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">{idx + 1}</span>
                                        <span className="text-sm font-medium text-slate-700 dark:text-slate-200">{purpose.name}</span>
                                    </div>
                                    <span className="text-sm font-bold text-emerald-600">{purpose.count}</span>
                                </div>
                            )) : <p className="py-4 text-center text-sm text-slate-400">Tidak ada data</p>}
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    // ============================================
    const renderTicketingContent = (tab: UnifiedTab) => {
        const data = tab.originalData as TicketingTabData;
        const isPersonal = tab.id.includes('personal');

        const {
            stats,
            nearest_maintenances = [],
            tickets,
            assets_count,
            upcoming_maintenance,
            division_name,
            recent_active_tickets = [],
            maintenance_this_month,
            ticket_distribution,
            most_reported_categories = [],
            overdue_maintenance_count,
            total_open_tickets,
            average_rating
        } = data;

        const getTicketStatusColor = (status: string) => {
            switch (status) {
                case 'pending': return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
                case 'process': return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
                case 'refinement': return 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400';
                case 'finish':
                case 'closed': return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
                case 'damaged': return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400';
                default: return 'bg-slate-100 text-slate-700';
            }
        };

        const getMaintenanceStatusColor = (status: string) => {
            switch (status) {
                case 'pending': return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
                case 'refinement': return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
                case 'finish':
                case 'confirmed': return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
                default: return 'bg-slate-100 text-slate-700';
            }
        };

        const getMaintenanceStatusLabel = (status: string) => {
            switch (status) {
                case 'pending': return 'Pending';
                case 'refinement': return 'Perbaikan';
                case 'finish': return 'Selesai';
                case 'confirmed': return 'Terkonfirmasi';
                case 'cancelled': return 'Dibatalkan';
                default: return status.charAt(0).toUpperCase() + status.slice(1);
            }
        };

        return (
            <div className="space-y-5 animate-in fade-in duration-300">
                {/* 4 Columns Stats */}
                <div className="grid gap-4 grid-cols-2 md:grid-cols-4">
                    {stats ? (
                        <>
                            <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                                <p className="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">
                                    Jumlah Aset {division_name ? `(${division_name})` : tab.id.includes('all') ? '(Seluruh)' : ''}
                                </p>
                                <div className="flex items-center gap-3">
                                    <div className="flex size-10 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30">
                                        <HardDrive className="size-5" />
                                    </div>
                                    <p className="text-2xl font-bold text-slate-900 dark:text-white">{stats.total_assets}</p>
                                </div>
                            </div>
                            <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                                <p className="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Sisa Maintenance <span className="text-[9px] lowercase opacity-60">({new Date().getFullYear()})</span></p>
                                <div className="flex items-center gap-3">
                                    <div className="flex size-10 items-center justify-center rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-900/30">
                                        <Wrench className="size-5" />
                                    </div>
                                    <p className="text-2xl font-bold text-slate-900 dark:text-white">{stats.remaining_maintenance}</p>
                                </div>
                            </div>
                            <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                                <p className="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">{tab.id.includes('all') ? 'Tiket Masuk' : 'Tiket Laporan'} <span className="text-[9px] lowercase opacity-60">({new Date().getFullYear()})</span></p>
                                <div className="flex items-center gap-3">
                                    <div className="flex size-10 items-center justify-center rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-900/30">
                                        <FileText className="size-5" />
                                    </div>
                                    <p className="text-2xl font-bold text-slate-900 dark:text-white">{stats.tickets_this_year}</p>
                                </div>
                            </div>
                            <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                                <p className="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Aset Perbaikan</p>
                                <div className="flex items-center gap-3">
                                    <div className="flex size-10 items-center justify-center rounded-lg bg-rose-100 text-rose-600 dark:bg-rose-900/30">
                                        <AlertTriangle className="size-5" />
                                    </div>
                                    <p className="text-2xl font-bold text-slate-900 dark:text-white">{stats.assets_under_repair}</p>
                                </div>
                            </div>
                        </>
                    ) : (
                        <>
                            {tickets && (
                                <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                                    <p className="text-xs font-medium text-slate-500 mb-2">Total Tiket {division_name ? `(${division_name})` : ''}</p>
                                    <div className="flex items-end justify-between">
                                        <p className="text-2xl font-bold text-slate-900 dark:text-white">{tickets.total}</p>
                                        <div className="text-right">
                                            <p className="text-[10px] text-amber-600 font-bold uppercase tracking-wider">{tickets.pending || tickets.active || 0} Aktif</p>
                                            <p className="text-[10px] text-emerald-600 font-bold uppercase tracking-wider">{tickets.resolved || tickets.finished || 0} Selesai</p>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {assets_count !== undefined && (
                                <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                                    <p className="text-xs font-medium text-slate-500 mb-2">Aset Anda</p>
                                    <div className="flex items-center gap-3">
                                        <div className="flex size-10 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30">
                                            <HardDrive className="size-5" />
                                        </div>
                                        <p className="text-2xl font-bold text-slate-900 dark:text-white">{assets_count}</p>
                                    </div>
                                </div>
                            )}

                            {(upcoming_maintenance !== undefined || maintenance_this_month !== undefined) && (
                                <div className={`rounded-xl border p-4 ${(upcoming_maintenance || maintenance_this_month) ? 'border-amber-200 bg-amber-50 dark:border-amber-900/30 dark:bg-amber-900/10' : 'border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800'}`}>
                                    <p className="text-xs font-medium text-slate-500 mb-2">Jadwal Maint.</p>
                                    <div className="flex items-center gap-3">
                                        <div className={`flex size-10 items-center justify-center rounded-lg ${(upcoming_maintenance || maintenance_this_month) ? 'bg-amber-100 text-amber-600' : 'bg-slate-100 text-slate-500'}`}>
                                            <Wrench className="size-5" />
                                        </div>
                                        <p className="text-2xl font-bold text-slate-900 dark:text-white">{upcoming_maintenance ?? maintenance_this_month}</p>
                                        <span className="text-[10px] text-slate-400 lowercase">Bulan ini</span>
                                    </div>
                                </div>
                            )}

                            {total_open_tickets !== undefined && (
                                <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                                    <p className="text-xs font-medium text-slate-500 mb-2">Open Issues</p>
                                    <div className="flex items-center gap-3">
                                        <div className="flex size-10 items-center justify-center rounded-lg bg-rose-100 text-rose-600 dark:bg-rose-900/30">
                                            <AlertTriangle className="size-5" />
                                        </div>
                                        <p className="text-2xl font-bold text-slate-900 dark:text-white">{total_open_tickets}</p>
                                    </div>
                                </div>
                            )}

                            {average_rating !== undefined && (
                                <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                                    <p className="text-xs font-medium text-slate-500 mb-2">Kepuasan Layanan</p>
                                    <div className="flex items-center gap-3">
                                        <div className="flex size-10 items-center justify-center rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-900/30">
                                            <Star className="size-5 fill-amber-600" />
                                        </div>
                                        <p className="text-2xl font-bold text-slate-900 dark:text-white">{average_rating}</p>
                                    </div>
                                </div>
                            )}
                        </>
                    )}
                </div>

                {/* Main Content Area */}
                <div className="grid gap-5 lg:grid-cols-2">
                    {/* List 1 (Maintenance / Tickets) */}
                    <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                        {(isPersonal || tab.id.includes('division') || tab.id.includes('all')) ? (
                            <>
                                <div className="mb-4 flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <Calendar className="size-5 text-amber-600" />
                                        <h3 className="font-semibold text-slate-800 dark:text-white">Maintenance Terdekat</h3>
                                    </div>
                                    <Link href="/ticketing/maintenances" className="text-xs text-primary hover:underline">Lihat Semua</Link>
                                </div>
                                <div className="space-y-3">
                                    {nearest_maintenances.length > 0 ? nearest_maintenances.map((m) => (
                                        <div key={m.id} className="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-700/50">
                                            <div className="min-w-0 flex-1 pr-4">
                                                <p className="truncate text-sm font-medium text-slate-700 dark:text-slate-200">{m.asset_item?.merk} {m.asset_item?.model}</p>
                                                <p className="text-[10px] text-slate-400">
                                                    {((tab.id.includes('division') || tab.id.includes('all')) && m.asset_item?.users?.length > 0) && `${m.asset_item.users.map((u: any) => u.name).join(', ')} • `}
                                                    {m.asset_item?.asset_category?.name} • Est. {formatDate(m.estimation_date)}
                                                </p>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <span className={`shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold ${getMaintenanceStatusColor(m.status)}`}>
                                                    {getMaintenanceStatusLabel(m.status)}
                                                </span>
                                                <ChevronRight className="size-4 text-slate-400" />
                                            </div>
                                        </div>
                                    )) : <p className="py-4 text-center text-sm text-slate-400">Tidak ada jadwal terdekat</p>}
                                </div>
                            </>
                        ) : (
                            <>
                                {recent_active_tickets.length > 0 ? (
                                    <>
                                        <div className="mb-4 flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <Clock className="size-5 text-blue-600" />
                                                <h3 className="font-semibold text-slate-800 dark:text-white">Tiket Aktif Terbaru</h3>
                                            </div>
                                            <Link href="/ticketing/tickets" className="text-xs text-primary hover:underline">Lihat Semua</Link>
                                        </div>
                                        <div className="space-y-3">
                                            {recent_active_tickets.map((ticket) => (
                                                <Link key={ticket.id} href={`/ticketing/tickets/${ticket.id}/show`} className="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 transition-colors hover:bg-slate-100 dark:bg-slate-700/50 dark:hover:bg-slate-700">
                                                    <div className="min-w-0 flex-1 pr-4">
                                                        <p className="truncate text-sm font-medium text-slate-700 dark:text-slate-200">{ticket.subject}</p>
                                                        <p className="text-[10px] text-slate-400">
                                                            {ticket.user?.name} • {formatDate(ticket.created_at)}
                                                        </p>
                                                    </div>
                                                    <span className={`shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase ${getTicketStatusColor(ticket.status.value)}`}>
                                                        {ticket.status.label}
                                                    </span>
                                                </Link>
                                            ))}
                                        </div>
                                    </>
                                ) : ticket_distribution ? (
                                    <>
                                        <div className="mb-4 flex items-center gap-2">
                                            <TrendingUp className="size-5 text-primary" />
                                            <h3 className="font-semibold text-slate-800 dark:text-white">Sebaran Status Tiket</h3>
                                        </div>
                                        <div className="flex h-48 items-center justify-center gap-8">
                                            <div className="grid grid-cols-2 gap-x-8 gap-y-4 w-full">
                                                {Object.entries(ticket_distribution).map(([status, count]) => (
                                                    <div key={status} className="flex items-center gap-3">
                                                        <div className={`size-3 rounded-full ${getTicketStatusColor(status).split(' ')[0]}`} />
                                                        <div className="flex-1">
                                                            <div className="flex justify-between text-[11px] mb-1">
                                                                <span className="capitalize text-slate-500">{status}</span>
                                                                <span className="font-bold">{count}</span>
                                                            </div>
                                                            <div className="h-1.5 w-full bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                                                <div
                                                                    className={`h-full ${getTicketStatusColor(status).split(' ')[0]}`}
                                                                    style={{ width: `${Math.min((count / (total_open_tickets || 100)) * 100, 100)}%` }}
                                                                />
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    </>
                                ) : <p className="py-4 text-center text-sm text-slate-400">Tidak ada data tiket</p>}
                            </>
                        )}
                    </div>

                    {/* List 2 (Active Tickets / Categories) */}
                    <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                        {(isPersonal || tab.id.includes('division') || tab.id.includes('all')) ? (
                            <>
                                <div className="mb-4 flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <Clock className="size-5 text-blue-600" />
                                        <h3 className="font-semibold text-slate-800 dark:text-white">Tiket Aktif Terbaru</h3>
                                    </div>
                                    <Link href="/ticketing/tickets" className="text-xs text-primary hover:underline">Lihat Semua</Link>
                                </div>
                                <div className="space-y-3">
                                    {recent_active_tickets.length > 0 ? recent_active_tickets.map((ticket) => (
                                        <Link key={ticket.id} href={`/ticketing/tickets/${ticket.id}/show`} className="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 transition-colors hover:bg-slate-100 dark:bg-slate-700/50 dark:hover:bg-slate-700">
                                            <div className="min-w-0 flex-1 pr-4">
                                                <p className="truncate text-sm font-medium text-slate-700 dark:text-slate-200">{ticket.subject}</p>
                                                <p className="text-[10px] text-slate-400">
                                                    {((tab.id.includes('division') || tab.id.includes('all')) && ticket.user?.name) && `${ticket.user.name} • `}
                                                    {formatDate(ticket.created_at)}
                                                </p>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <span className={`shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase ${getTicketStatusColor(ticket.status.value)}`}>
                                                    {ticket.status.label}
                                                </span>
                                                <ChevronRight className="size-4 text-slate-400" />
                                            </div>
                                        </Link>
                                    )) : <p className="py-4 text-center text-sm text-slate-400">Tidak ada tiket aktif</p>}
                                </div>
                            </>
                        ) : (
                            <>
                                {most_reported_categories.length > 0 ? (
                                    <>
                                        <div className="mb-4 flex items-center gap-2">
                                            <AlertTriangle className="size-5 text-rose-500" />
                                            <h3 className="font-semibold text-slate-800 dark:text-white">Aset Paling Sering Bermasalah</h3>
                                        </div>
                                        <div className="space-y-3">
                                            {most_reported_categories.map((cat, idx) => (
                                                <div key={idx} className="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-700/50">
                                                    <div className="flex items-center gap-3">
                                                        <span className="flex size-7 items-center justify-center rounded-full bg-rose-100 text-xs font-bold text-rose-700 dark:bg-rose-900/30 dark:text-rose-400">{idx + 1}</span>
                                                        <span className="text-sm font-medium text-slate-700 dark:text-slate-200">{cat.name}</span>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <span className="text-sm font-bold text-rose-600">{cat.total}</span>
                                                        <span className="text-[10px] text-slate-400">Kasus</span>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </>
                                ) : overdue_maintenance_count !== undefined ? (
                                    <div className="flex h-full flex-col justify-center items-center text-center py-6">
                                        <div className={`mb-4 flex size-20 items-center justify-center rounded-full ${overdue_maintenance_count > 0 ? 'bg-rose-50 text-rose-500' : 'bg-emerald-50 text-emerald-500'}`}>
                                            {overdue_maintenance_count > 0 ? <AlertTriangle className="size-10" /> : <CheckCircle className="size-10" />}
                                        </div>
                                        <h4 className="text-lg font-bold text-slate-800 dark:text-white">
                                            {overdue_maintenance_count > 0 ? `${overdue_maintenance_count} Maintenance Overdue!` : 'Semua Terjadwal'}
                                        </h4>
                                        <p className="text-sm text-slate-500 max-w-[250px] mt-1">
                                            {overdue_maintenance_count > 0
                                                ? 'Terdapat aset yang melewati tanggal estimasi pemeliharaan.'
                                                : 'Seluruh aset telah dipelihara sesuai jadwal atau belum masuk waktu jatuh tempo.'}
                                        </p>
                                        <Link
                                            href="/ticketing/maintenances"
                                            className="mt-6 px-6 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary/90 transition-colors"
                                        >
                                            Buka Jadwal
                                        </Link>
                                    </div>
                                ) : <p className="py-4 text-center text-sm text-slate-400">Tidak ada data penunjang</p>}
                            </>
                        )}
                    </div>
                </div>
            </div>
        );
    };

    // RENDER TAB CONTENT

    // ============================================
    const renderTabContent = (tab: UnifiedTab) => {
        if (tab.module === 'inventory') {
            if (tab.type === 'overview') {
                return renderInventoryOverviewContent(tab);
            }
            return renderInventoryWarehouseContent(tab);
        } else if (tab.module === 'archieve') {
            if (tab.type === 'overview') {
                return renderArchieveOverviewContent(tab);
            }
            return renderArchieveDivisionContent(tab);
        } else if (tab.module === 'visitor') {
            return renderVisitorOverviewContent(tab);
        } else if (tab.module === 'ticketing') {
            return renderTicketingContent(tab);
        }
        return null;
    };

    return (
        <div className="space-y-5">
            {unifiedTabs.length > 1 && (
                <div className="flex gap-6 overflow-x-auto border-b border-slate-200 dark:border-slate-700">
                    {unifiedTabs.map((tab, index) => {
                        const isActive = activeTabIndex === index;
                        const Icon = getTabIcon(tab);
                        return (
                            <button
                                key={tab.id}
                                onClick={() => setActiveTabIndex(index)}
                                className={`relative flex items-center gap-2 whitespace-nowrap pb-3 text-sm font-medium transition-colors ${isActive
                                    ? 'text-primary'
                                    : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'
                                    }`}
                            >
                                <Icon className="size-4" />
                                {tab.label}
                                {isActive && (
                                    <span className="absolute bottom-0 left-0 h-0.5 w-full bg-primary" />
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
