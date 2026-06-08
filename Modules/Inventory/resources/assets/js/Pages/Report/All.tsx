import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    Package,
    TrendingUp,
    TrendingDown,
    AlertTriangle,
    BarChart3,
    Clock,
    FileSpreadsheet,
    BarChart,
    PieChart,
    LineChart,
    Layers,
    Warehouse,
    Activity,
    ClipboardList,
} from 'lucide-react';
import Button from '@/components/buttons/Button';
import { Bar, Line } from 'react-chartjs-2';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
    ArcElement,
    PointElement,
    LineElement,
} from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
    ArcElement,
    PointElement,
    LineElement,
    ChartDataLabels
);

interface ItemRanking {
    id?: number;
    name: string;
    total?: number;
    total_requested?: number;
    total_quantity?: number;
    total_difference?: number;
    stock?: number;
    unit_of_measure?: string;
    total_requests?: number;
}

interface ReportSection {
    overview_stats: Record<string, number>;
    request_trend: { month: string; total_orders: number; total_items: number }[];
    opname_variance_trend?: { month: string; total_minus: number }[];
    item_rankings: {
        most_requested: ItemRanking[];
        least_requested: ItemRanking[];
        most_outbound: ItemRanking[];
        opname_variance_minus: ItemRanking[];
        most_stock: ItemRanking[];
        least_stock: ItemRanking[];
    };
    category_rankings: {
        most_requested: ItemRanking[];
        least_requested: ItemRanking[];
        most_outbound: ItemRanking[];
    };
    stock_analysis: {
        stagnant_stock: ItemRanking[];
    };
    alerts: {
        critical_stock: ItemRanking[];
        fulfillment_rate: any;
    };
    division_name?: string;
}

interface ReportData {
    global: ReportSection;
    per_division: ReportSection[];
}

interface PageProps {
    reportData: ReportData;
    divisions: { id: number; name: string }[];
    [key: string]: unknown;
}

export default function ReportAll() {
    const { reportData } = usePage<PageProps>().props;
    const [activeTab, setActiveTab] = useState<'global_barang' | 'division_barang' | 'stok_barang' | 'opname' | 'stok_tertimbun'>('global_barang');

    if (!reportData) return null;

    const { global, per_division } = reportData;

    const tabs = [
        { id: 'global_barang', label: 'Permintaan Barang', icon: <Package className="size-4" /> },
        { id: 'division_barang', label: 'Permintaan Barang Divisi', icon: <Layers className="size-4" /> },
        { id: 'stok_barang', label: 'Stok Barang', icon: <Warehouse className="size-4" /> },
        { id: 'opname', label: 'Stock Opname', icon: <ClipboardList className="size-4" /> },
        { id: 'stok_tertimbun', label: 'Stok Tertimbun', icon: <Clock className="size-4" /> },
    ];

    const StatCard = ({ label, value, icon, color }: { label: string; value: string | number; icon: React.ReactNode; color: string }) => (
        <div className="rounded-xl border border-slate-200 bg-white p-4 transition-all hover:shadow-md dark:border-slate-700 dark:bg-slate-800">
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-xs font-medium text-slate-500 uppercase tracking-wider dark:text-slate-400">{label}</p>
                    <p className={`mt-1 text-2xl font-bold ${color}`}>{value}</p>
                </div>
                <div className={`rounded-lg p-2 ${color.replace('text-', 'bg-')}/10`}>{icon}</div>
            </div>
        </div>
    );

    const SimpleList = ({ title, items, valueField, valueSuffix = '', color = 'text-slate-700', icon, emptyMessage = "Data tidak tersedia" }: { title: string; items: ItemRanking[]; valueField: keyof ItemRanking; valueSuffix?: string; color?: string; icon?: React.ReactNode; emptyMessage?: string }) => (
        <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
            <h4 className="mb-4 flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-200">
                {icon}
                <span className="truncate">{title}</span>
            </h4>
            <div className="space-y-2">
                {items?.length > 0 ? (
                    items.map((item, idx) => (
                        <div key={idx} className="flex items-center justify-between border-b border-slate-50 pb-2 last:border-0 last:pb-0 dark:border-slate-700/50">
                            <div className="flex items-center gap-2">
                                <span className="flex size-5 items-center justify-center rounded-full bg-slate-100 text-[10px] font-bold text-slate-500 dark:bg-slate-700">
                                    {idx + 1}
                                </span>
                                <span className="text-sm text-slate-600 dark:text-slate-400 truncate max-w-[140px]" title={item.name}>
                                    {item.name}
                                </span>
                            </div>
                            <span className={`text-xs font-bold ${color}`}>
                                {String(item[valueField] ?? 0)} {valueSuffix}
                            </span>
                        </div>
                    ))
                ) : (
                    <p className="py-4 text-center text-xs text-slate-400 italic">{emptyMessage}</p>
                )}
            </div>
        </div>
    );

    const statusLabels: Record<string, string> = {
        'Pending': 'Menunggu',
        'Confirmed': 'Dikonfirmasi',
        'Accepted': 'Diproses',
        'Delivery': 'Dikirim',
        'Delivered': 'Sampai',
        'Finished': 'Selesai',
        'Rejected': 'Ditolak',
        'Revision': 'Revisi',
    };

    const statusColors: Record<string, string> = {
        'Pending': 'text-yellow-600',
        'Confirmed': 'text-blue-600',
        'Accepted': 'text-purple-600',
        'Delivery': 'text-indigo-600',
        'Delivered': 'text-teal-600',
        'Finished': 'text-green-600',
        'Rejected': 'text-red-600',
        'Revision': 'text-orange-600',
    };

    return (
        <RootLayout title="Laporan Inventory Global">
            <ContentCard
                title="Dashboard Laporan Lintas Divisi"
                subtitle="Pantau statistik inventaris menyeluruh, perbandingan antar divisi, dan performa stok global."
                mobileFullWidth={true}
                bodyClassName="p-1 md:p-6"
                additionalButton={
                    <Button
                        href="/inventory/reports/print-excel"
                        className="w-full md:w-auto"
                        label="Export Laporan Lengkap"
                        icon={<FileSpreadsheet className="size-4" />}
                        target="_blank"
                    />
                }
            >
                {/* Tabs */}
                <div className="mb-8 flex overflow-x-auto hide-scrollbar gap-6 border-b border-slate-200 dark:border-slate-700 pb-1">
                    {tabs.map((tab) => (
                        <button
                            key={tab.id}
                            onClick={() => setActiveTab(tab.id as typeof activeTab)}
                            className={`relative flex items-center gap-2 whitespace-nowrap pb-3 text-sm font-medium transition-colors ${activeTab === tab.id
                                ? 'text-slate-900 dark:text-white'
                                : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'
                                }`}
                        >
                            {tab.icon}
                            {tab.label}
                            {activeTab === tab.id && (
                                <span className="absolute bottom-0 left-0 h-0.5 w-full bg-primary dark:bg-white" />
                            )}
                        </button>
                    ))}
                </div>

                {/* Tab: Laporan Barang (Global) */}
                {activeTab === 'global_barang' && (
                    <div className="space-y-8 animate-in fade-in duration-500">
                        <div>
                            <h3 className="mb-4 text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
                                <Activity className="size-4" />
                                Status Permintaan Barang (Seluruh Sistem)
                            </h3>
                            <div className="grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-8">
                                {Object.entries(global.overview_stats).map(([status, count]) => (
                                    <div key={status} className="rounded-xl border border-slate-100 bg-white p-3 text-center dark:border-slate-700 dark:bg-slate-800">
                                        <p className="text-[10px] font-bold text-slate-400 uppercase tracking-tighter truncate">{statusLabels[status] || status}</p>
                                        <p className={`text-xl font-black ${statusColors[status] || 'text-slate-800'}`}>{count}</p>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Trend Chart - Full Width */}
                        <div className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                            <h3 className="mb-6 flex items-center gap-2 font-bold text-slate-800 dark:text-slate-200 text-lg">
                                <LineChart className="size-5 text-primary" />
                                Tren Permintaan Barang Global (Januari - Desember)
                            </h3>
                            <div className="h-[300px]">
                                <Line
                                    data={{
                                        labels: Array.from({ length: 12 }, (_, i) =>
                                            new Date(0, i).toLocaleDateString('id-ID', { month: 'long' })
                                        ),
                                        datasets: [
                                            {
                                                label: 'Total Order',
                                                data: Array.from({ length: 12 }, (_, i) => {
                                                    const monthStr = `${new Date().getFullYear()}-${String(i + 1).padStart(2, '0')}`;
                                                    const data = global.request_trend.find(d => d.month === monthStr);
                                                    return data ? data.total_orders : 0;
                                                }),
                                                borderColor: '#3b82f6',
                                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                                fill: true,
                                                tension: 0.4,
                                                pointRadius: 4,
                                            },
                                            {
                                                label: 'Total Barang',
                                                data: Array.from({ length: 12 }, (_, i) => {
                                                    const monthStr = `${new Date().getFullYear()}-${String(i + 1).padStart(2, '0')}`;
                                                    const data = global.request_trend.find(d => d.month === monthStr);
                                                    return data ? data.total_items : 0;
                                                }),
                                                borderColor: '#10b981',
                                                backgroundColor: 'transparent',
                                                tension: 0.4,
                                                pointRadius: 4,
                                            },
                                        ],
                                    }}
                                    options={{
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        scales: {
                                            y: { beginAtZero: true, ticks: { stepSize: 1 } }
                                        }
                                    }}
                                />
                            </div>
                        </div>

                        {/* Item Rankings - 3 Columns Grid */}
                        <div className="grid gap-6 md:grid-cols-3">
                            <SimpleList
                                title="10 Barang Paling Banyak Diminta"
                                icon={<TrendingUp className="size-5 text-green-500" />}
                                items={global.item_rankings.most_requested || []}
                                valueField="total"
                                valueSuffix="qty"
                                color="text-green-600"
                            />
                            <SimpleList
                                title="10 Barang Paling Sedikit Diminta"
                                icon={<TrendingDown className="size-5 text-red-400" />}
                                items={global.item_rankings.least_requested || []}
                                valueField="total"
                                valueSuffix="qty"
                                color="text-red-500"
                            />
                            <SimpleList
                                title="10 Barang Paling Banyak Keluar"
                                icon={<Package className="size-5 text-blue-500" />}
                                items={global.item_rankings.most_outbound || []}
                                valueField="total"
                                valueSuffix="qty"
                                color="text-blue-600"
                            />
                        </div>

                        {/* Category Rankings - 3 Columns Grid */}
                        <div className="grid gap-6 md:grid-cols-3">
                            <SimpleList
                                title="5 Kategori Paling Banyak Diminta"
                                icon={<BarChart className="size-5 text-purple-500" />}
                                items={global.category_rankings.most_requested || []}
                                valueField="total_requests"
                                valueSuffix="order"
                                color="text-purple-600"
                            />
                            <SimpleList
                                title="5 Kategori Paling Sedikit Diminta"
                                icon={<BarChart className="size-5 text-slate-400" />}
                                items={global.category_rankings.least_requested || []}
                                valueField="total_requests"
                                valueSuffix="order"
                                color="text-slate-500"
                            />
                            <SimpleList
                                title="5 Kategori Paling Banyak Keluar"
                                icon={<BarChart className="size-5 text-emerald-500" />}
                                items={global.category_rankings.most_outbound || []}
                                valueField="total_quantity"
                                valueSuffix="qty"
                                color="text-emerald-600"
                            />
                        </div>

                    </div>
                )}

                {/* Tab: Permintaan Barang Divisi (Perbandingan) */}
                {activeTab === 'division_barang' && (
                    <div className="space-y-12 animate-in fade-in duration-500">
                        <div className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                            <h3 className="mb-8 flex items-center gap-2 font-bold text-slate-800 dark:text-slate-200 text-lg">
                                <Layers className="size-5 text-primary" />
                                Perbandingan Tren Permintaan Antar Divisi (Tahunan)
                            </h3>
                            <div className="h-[500px]">
                                <Line
                                    data={{
                                        labels: Array.from({ length: 12 }, (_, i) =>
                                            new Date(0, i).toLocaleDateString('id-ID', { month: 'long' })
                                        ),
                                        datasets: per_division.flatMap((div, idx) => {
                                            const colors = [
                                                '#3b82f6', '#10b981', '#f59e0b', '#ef4444',
                                                '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16'
                                            ];
                                            const baseColor = colors[idx % colors.length];
                                            return [
                                                {
                                                    label: `${div.division_name} (Order)`,
                                                    data: Array.from({ length: 12 }, (_, i) => {
                                                        const monthStr = `${new Date().getFullYear()}-${String(i + 1).padStart(2, '0')}`;
                                                        const data = div.request_trend.find(d => d.month === monthStr);
                                                        return data ? data.total_orders : 0;
                                                    }),
                                                    borderColor: baseColor,
                                                    backgroundColor: 'transparent',
                                                    tension: 0.4,
                                                    pointRadius: 4,
                                                    borderWidth: 2,
                                                },
                                                {
                                                    label: `${div.division_name} (Barang)`,
                                                    data: Array.from({ length: 12 }, (_, i) => {
                                                        const monthStr = `${new Date().getFullYear()}-${String(i + 1).padStart(2, '0')}`;
                                                        const data = div.request_trend.find(d => d.month === monthStr);
                                                        return data ? data.total_items : 0;
                                                    }),
                                                    borderColor: baseColor,
                                                    backgroundColor: 'transparent',
                                                    tension: 0.4,
                                                    pointRadius: 3,
                                                    borderWidth: 1.5,
                                                    borderDash: [5, 5],
                                                }
                                            ];
                                        })
                                    }}
                                    options={{
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: {
                                            legend: {
                                                display: true,
                                                position: 'top',
                                                labels: {
                                                    usePointStyle: true,
                                                    boxWidth: 6,
                                                    font: { weight: 'bold', size: 10 },
                                                    padding: 15
                                                }
                                            },
                                            datalabels: { display: false }
                                        },
                                        scales: {
                                            y: {
                                                beginAtZero: true,
                                                ticks: { stepSize: 5 },
                                                grid: { color: 'rgba(0,0,0,0.05)' }
                                            },
                                            x: { grid: { display: false } }
                                        }
                                    }}
                                />
                            </div>
                        </div>

                        <div className="grid gap-8">
                            <h3 className="text-sm font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
                                <TrendingUp className="size-4" />
                                10 Barang Paling Banyak & Sedikit Diminta Tiap Divisi
                            </h3>
                            <div className="grid gap-8 md:grid-cols-2">
                                {per_division.map((div, idx) => (
                                    <div key={idx} className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800 shadow-sm">
                                        <div className="mb-6 flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-700">
                                            <h4 className="text-lg font-black text-primary uppercase tracking-tight">{div.division_name}</h4>
                                            <div className="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-bold text-slate-500 dark:bg-slate-700">
                                                Total Order Selesai: {div.overview_stats?.['Finished'] || 0}
                                            </div>
                                        </div>

                                        <div className="grid gap-6 sm:grid-cols-2">
                                            <SimpleList
                                                title="10 Paling Banyak"
                                                icon={<TrendingUp className="size-4 text-green-500" />}
                                                items={div.item_rankings.most_requested || []}
                                                valueField="total"
                                                valueSuffix="qty"
                                                color="text-green-600"
                                            />
                                            <SimpleList
                                                title="10 Paling Sedikit"
                                                icon={<TrendingDown className="size-4 text-red-400" />}
                                                items={div.item_rankings.least_requested || []}
                                                valueField="total"
                                                valueSuffix="qty"
                                                color="text-red-500"
                                            />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                )}

                {/* Tab: Stok Barang */}
                {activeTab === 'stok_barang' && (
                    <div className="space-y-8 animate-in fade-in duration-500">
                        {/* Gudang Utama */}
                        <div>
                            <h3 className="mb-4 text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
                                <Warehouse className="size-4" />
                                Stok Gudang Utama
                            </h3>
                            <div className="grid gap-6 md:grid-cols-2">
                                <SimpleList
                                    title="10 Stok Terbanyak"
                                    icon={<TrendingUp className="size-5 text-blue-500" />}
                                    items={global.item_rankings.most_stock || []}
                                    valueField="stock"
                                    color="text-blue-600"
                                />
                                <SimpleList
                                    title="10 Stok Tersedikit"
                                    icon={<TrendingDown className="size-5 text-orange-500" />}
                                    items={global.item_rankings.least_stock || []}
                                    valueField="stock"
                                    color="text-orange-600"
                                />
                            </div>
                        </div>

                        {/* Per Divisi */}
                        <div>
                            <h3 className="mb-4 text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
                                <Layers className="size-4" />
                                Stok Per Divisi
                            </h3>
                            <div className="grid gap-8 md:grid-cols-2">
                                {per_division.map((div, idx) => (
                                    <div key={idx} className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800 shadow-sm">
                                        <div className="mb-6 flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-700">
                                            <h4 className="text-lg font-black text-primary uppercase tracking-tight">{div.division_name}</h4>
                                        </div>

                                        <div className="grid gap-6 sm:grid-cols-2">
                                            <SimpleList
                                                title="10 Stok Terbanyak"
                                                icon={<TrendingUp className="size-4 text-blue-500" />}
                                                items={div.item_rankings.most_stock || []}
                                                valueField="stock"
                                                color="text-blue-600"
                                            />
                                            <SimpleList
                                                title="10 Stok Tersedikit"
                                                icon={<TrendingDown className="size-4 text-orange-400" />}
                                                items={div.item_rankings.least_stock || []}
                                                valueField="stock"
                                                color="text-orange-500"
                                            />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                )}

                {/* Tab: Stock Opname */}
                {activeTab === 'opname' && (
                    <div className="space-y-8 animate-in fade-in duration-500">
                        {/* Trend Chart - Gudang Utama */}
                        <div className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                            <h3 className="mb-6 flex items-center gap-2 font-bold text-slate-800 dark:text-slate-200 text-lg">
                                <LineChart className="size-5 text-orange-500" />
                                Tren Selisih Stock Opname - Gudang Utama (Januari - Desember)
                            </h3>
                            <div className="h-[300px]">
                                <Line
                                    data={{
                                        labels: Array.from({ length: 12 }, (_, i) =>
                                            new Date(0, i).toLocaleDateString('id-ID', { month: 'long' })
                                        ),
                                        datasets: [
                                            {
                                                label: 'Total Selisih (Minus)',
                                                data: Array.from({ length: 12 }, (_, i) => {
                                                    const monthStr = `${new Date().getFullYear()}-${String(i + 1).padStart(2, '0')}`;
                                                    const data = global.opname_variance_trend?.find(d => d.month === monthStr);
                                                    return data ? data.total_minus : 0;
                                                }),
                                                borderColor: '#f97316',
                                                backgroundColor: 'rgba(249, 115, 22, 0.1)',
                                                fill: true,
                                                tension: 0.4,
                                                pointRadius: 4,
                                            },
                                        ],
                                    }}
                                    options={{
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        scales: {
                                            y: { beginAtZero: true }
                                        }
                                    }}
                                />
                            </div>
                        </div>

                        {/* Trend Chart - Per Divisi */}
                        <div className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                            <h3 className="mb-6 flex items-center gap-2 font-bold text-slate-800 dark:text-slate-200 text-lg">
                                <Layers className="size-5 text-purple-500" />
                                Perbandingan Tren Selisih Stock Opname Per Divisi (Januari - Desember)
                            </h3>
                            <div className="h-[400px]">
                                <Line
                                    data={{
                                        labels: Array.from({ length: 12 }, (_, i) =>
                                            new Date(0, i).toLocaleDateString('id-ID', { month: 'long' })
                                        ),
                                        datasets: per_division.map((div, idx) => {
                                            const colors = [
                                                '#3b82f6', '#10b981', '#f59e0b', '#ef4444',
                                                '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16'
                                            ];
                                            return {
                                                label: div.division_name,
                                                data: Array.from({ length: 12 }, (_, i) => {
                                                    const monthStr = `${new Date().getFullYear()}-${String(i + 1).padStart(2, '0')}`;
                                                    const data = div.opname_variance_trend?.find(d => d.month === monthStr);
                                                    return data ? data.total_minus : 0;
                                                }),
                                                borderColor: colors[idx % colors.length],
                                                backgroundColor: 'transparent',
                                                tension: 0.4,
                                                pointRadius: 4,
                                            };
                                        })
                                    }}
                                    options={{
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: {
                                            legend: {
                                                display: true,
                                                position: 'top',
                                                labels: { usePointStyle: true, boxWidth: 6, font: { weight: 'bold', size: 10 } }
                                            }
                                        },
                                        scales: {
                                            y: { beginAtZero: true }
                                        }
                                    }}
                                />
                            </div>
                        </div>

                        {/* Lists */}
                        <div className="grid gap-6 md:grid-cols-2">
                            <SimpleList
                                title="Selisih Opname Gudang Utama"
                                icon={<Warehouse className="size-5 text-slate-600" />}
                                items={global.item_rankings.opname_variance_minus || []}
                                valueField="total_difference"
                                color="text-red-600"
                                emptyMessage="Tidak ada selisih minus di Gudang Utama"
                            />
                            <div className="space-y-6">
                                {per_division.map((div, idx) => (
                                    div.item_rankings.opname_variance_minus?.length > 0 && (
                                        <SimpleList
                                            key={idx}
                                            title={`Selisih Opname: ${div.division_name}`}
                                            icon={<Layers className="size-5 text-slate-400" />}
                                            items={div.item_rankings.opname_variance_minus}
                                            valueField="total_difference"
                                            color="text-red-500"
                                        />
                                    )
                                ))}
                            </div>
                        </div>
                    </div>
                )}

                {/* Tab: Stok Tertimbun */}
                {activeTab === 'stok_tertimbun' && (() => {
                    // Calculate summary stats
                    const globalStagnant = global.stock_analysis.stagnant_stock || [];
                    const totalGlobalItems = globalStagnant.length;
                    const totalGlobalStock = globalStagnant.reduce((sum, item) => sum + (item.stock || 0), 0);
                    const divisionsWithStagnant = per_division.filter(d => (d.stock_analysis.stagnant_stock?.length || 0) > 0).length;
                    const totalDivisionItems = per_division.reduce((sum, d) => sum + (d.stock_analysis.stagnant_stock?.length || 0), 0);
                    const totalAllItems = totalGlobalItems + totalDivisionItems;

                    const getSeverityColor = (dateStr: string | null | undefined) => {
                        if (!dateStr) return { bg: 'bg-red-50 dark:bg-red-900/20', text: 'text-red-600 dark:text-red-400', border: 'border-red-200 dark:border-red-800', label: 'Belum pernah', dot: 'bg-red-500' };
                        const months = Math.floor((Date.now() - new Date(dateStr).getTime()) / (1000 * 60 * 60 * 24 * 30));
                        if (months >= 12) return { bg: 'bg-red-50 dark:bg-red-900/20', text: 'text-red-600 dark:text-red-400', border: 'border-red-200 dark:border-red-800', label: `${months} bln lalu`, dot: 'bg-red-500' };
                        if (months >= 6) return { bg: 'bg-amber-50 dark:bg-amber-900/20', text: 'text-amber-600 dark:text-amber-400', border: 'border-amber-200 dark:border-amber-800', label: `${months} bln lalu`, dot: 'bg-amber-500' };
                        return { bg: 'bg-orange-50 dark:bg-orange-900/20', text: 'text-orange-600 dark:text-orange-400', border: 'border-orange-200 dark:border-orange-800', label: `${months} bln lalu`, dot: 'bg-orange-400' };
                    };

                    return (
                        <div className="space-y-8 animate-in fade-in duration-500">

                            {/* Summary Statistics */}
                            <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                                <div className="rounded-lg border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-800 transition-all hover:shadow-sm">
                                    <div className="flex items-center gap-2.5">
                                        <div className="flex size-8 items-center justify-center rounded-md bg-orange-50 dark:bg-orange-900/20">
                                            <Package className="size-4 text-orange-500" />
                                        </div>
                                        <div>
                                            <p className="text-[11px] font-medium text-slate-400">Total Barang</p>
                                            <p className="text-lg font-bold text-slate-800 dark:text-slate-100">{totalAllItems}</p>
                                        </div>
                                    </div>
                                </div>
                                <div className="rounded-lg border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-800 transition-all hover:shadow-sm">
                                    <div className="flex items-center gap-2.5">
                                        <div className="flex size-8 items-center justify-center rounded-md bg-blue-50 dark:bg-blue-900/20">
                                            <Warehouse className="size-4 text-blue-500" />
                                        </div>
                                        <div>
                                            <p className="text-[11px] font-medium text-slate-400">Gudang Utama</p>
                                            <p className="text-lg font-bold text-slate-800 dark:text-slate-100">{totalGlobalItems} <span className="text-xs font-normal text-slate-400">item</span></p>
                                        </div>
                                    </div>
                                </div>
                                <div className="rounded-lg border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-800 transition-all hover:shadow-sm">
                                    <div className="flex items-center gap-2.5">
                                        <div className="flex size-8 items-center justify-center rounded-md bg-purple-50 dark:bg-purple-900/20">
                                            <Layers className="size-4 text-purple-500" />
                                        </div>
                                        <div>
                                            <p className="text-[11px] font-medium text-slate-400">Divisi Terdampak</p>
                                            <p className="text-lg font-bold text-slate-800 dark:text-slate-100">{divisionsWithStagnant} <span className="text-xs font-normal text-slate-400">/ {per_division.length}</span></p>
                                        </div>
                                    </div>
                                </div>
                                <div className="rounded-lg border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-800 transition-all hover:shadow-sm">
                                    <div className="flex items-center gap-2.5">
                                        <div className="flex size-8 items-center justify-center rounded-md bg-rose-50 dark:bg-rose-900/20">
                                            <AlertTriangle className="size-4 text-rose-500" />
                                        </div>
                                        <div>
                                            <p className="text-[11px] font-medium text-slate-400">Total Stok Idle</p>
                                            <p className="text-lg font-bold text-slate-800 dark:text-slate-100">{totalGlobalStock} <span className="text-xs font-normal text-slate-400">unit</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Gudang Utama - Detail Table */}
                            <div className="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800 overflow-hidden">
                                <div className="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-slate-700">
                                    <div className="flex items-center gap-2.5">
                                        <div className="flex size-7 items-center justify-center rounded-md bg-slate-100 dark:bg-slate-700">
                                            <Warehouse className="size-3.5 text-slate-500 dark:text-slate-300" />
                                        </div>
                                        <div>
                                            <h3 className="text-sm font-semibold text-slate-700 dark:text-slate-200">Gudang Utama</h3>
                                            <p className="text-[11px] text-slate-400">Barang tidak diminta lebih dari 3 bulan</p>
                                        </div>
                                    </div>
                                    <span className="rounded-full bg-orange-50 px-2.5 py-0.5 text-[11px] font-semibold text-orange-600 dark:bg-orange-900/20 dark:text-orange-400">
                                        {totalGlobalItems} item
                                    </span>
                                </div>

                                {globalStagnant.length > 0 ? (
                                    <>
                                        {/* Mobile: Card Layout */}
                                        <div className="divide-y divide-slate-100 dark:divide-slate-700/50 md:hidden">
                                            {globalStagnant.map((item: any, idx: number) => {
                                                const severity = getSeverityColor(item.last_activity_date);
                                                return (
                                                    <div key={idx} className="flex items-start justify-between gap-3 px-4 py-3">
                                                        <div className="flex items-start gap-2.5 min-w-0">
                                                            <span className="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[10px] font-medium text-slate-500 dark:bg-slate-700 dark:text-slate-400">
                                                                {idx + 1}
                                                            </span>
                                                            <div className="min-w-0">
                                                                <p className="text-[13px] font-medium text-slate-700 dark:text-slate-200">{item.name}</p>
                                                                <div className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
                                                                    <span className="text-xs text-slate-500 dark:text-slate-400">
                                                                        Stok: <span className="font-bold text-slate-700 dark:text-slate-100">{item.stock}</span> {item.unit_of_measure || ''}
                                                                    </span>
                                                                    <span className="text-[11px] text-slate-400">
                                                                        {item.last_activity_date
                                                                            ? new Date(item.last_activity_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
                                                                            : '-'
                                                                        }
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <span className={`mt-0.5 shrink-0 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold ${severity.bg} ${severity.text}`}>
                                                            <span className={`size-1.5 rounded-full ${severity.dot}`} />
                                                            {severity.label}
                                                        </span>
                                                    </div>
                                                );
                                            })}
                                        </div>

                                        {/* Desktop: Table Layout */}
                                        <div className="hidden md:block overflow-x-auto">
                                            <table className="w-full">
                                                <thead>
                                                    <tr className="border-b border-slate-100 dark:border-slate-700">
                                                        <th className="px-5 py-2.5 text-left text-[11px] font-medium text-slate-400">No</th>
                                                        <th className="px-5 py-2.5 text-left text-[11px] font-medium text-slate-400">Nama Barang</th>
                                                        <th className="px-5 py-2.5 text-center text-[11px] font-medium text-slate-400">Stok</th>
                                                        <th className="px-5 py-2.5 text-center text-[11px] font-medium text-slate-400">Satuan</th>
                                                        <th className="px-5 py-2.5 text-center text-[11px] font-medium text-slate-400">Terakhir Diminta</th>
                                                        <th className="px-5 py-2.5 text-center text-[11px] font-medium text-slate-400">Durasi</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-slate-50 dark:divide-slate-700/50">
                                                    {globalStagnant.map((item: any, idx: number) => {
                                                        const severity = getSeverityColor(item.last_activity_date);
                                                        return (
                                                            <tr key={idx} className="transition-colors hover:bg-slate-50/70 dark:hover:bg-slate-700/30">
                                                                <td className="px-5 py-2.5">
                                                                    <span className="flex size-5 items-center justify-center rounded-full bg-slate-100 text-[10px] font-medium text-slate-500 dark:bg-slate-700 dark:text-slate-400">
                                                                        {idx + 1}
                                                                    </span>
                                                                </td>
                                                                <td className="px-5 py-2.5">
                                                                    <p className="text-[13px] font-medium text-slate-700 dark:text-slate-200">{item.name}</p>
                                                                </td>
                                                                <td className="px-5 py-2.5 text-center">
                                                                    <span className="text-[13px] font-bold text-slate-700 dark:text-slate-100">{item.stock}</span>
                                                                </td>
                                                                <td className="px-5 py-2.5 text-center">
                                                                    <span className="text-xs text-slate-500 dark:text-slate-400">{item.unit_of_measure || '-'}</span>
                                                                </td>
                                                                <td className="px-5 py-2.5 text-center">
                                                                    <span className="text-xs text-slate-500 dark:text-slate-300">
                                                                        {item.last_activity_date
                                                                            ? new Date(item.last_activity_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
                                                                            : '-'
                                                                        }
                                                                    </span>
                                                                </td>
                                                                <td className="px-5 py-2.5 text-center">
                                                                    <span className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold ${severity.bg} ${severity.text}`}>
                                                                        <span className={`size-1.5 rounded-full ${severity.dot}`} />
                                                                        {severity.label}
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        );
                                                    })}
                                                </tbody>
                                            </table>
                                        </div>
                                    </>
                                ) : (
                                    <div className="flex flex-col items-center justify-center py-14 text-center">
                                        <div className="rounded-full bg-emerald-100 p-4 dark:bg-emerald-900/20">
                                            <Package className="size-8 text-emerald-500" />
                                        </div>
                                        <p className="mt-4 text-sm font-semibold text-slate-600 dark:text-slate-300">Tidak ada stok tertimbun</p>
                                        <p className="mt-1 text-xs text-slate-400">Semua barang aktif diminta dalam 3 bulan terakhir</p>
                                    </div>
                                )}
                            </div>

                            {/* Per Divisi - Expandable Cards */}
                            <div>
                                <div className="mb-4 flex items-center gap-2.5">
                                    <div className="flex size-7 items-center justify-center rounded-md bg-purple-50 dark:bg-purple-900/20">
                                        <Layers className="size-3.5 text-purple-500" />
                                    </div>
                                    <div>
                                        <h3 className="text-sm font-semibold text-slate-700 dark:text-slate-200">Stok Tertimbun Per Divisi</h3>
                                        <p className="text-[11px] text-slate-400">Barang tidak keluar lebih dari 3 bulan di masing-masing divisi</p>
                                    </div>
                                </div>

                                <div className="space-y-4">
                                    {per_division.map((div, idx) => {
                                        const divStagnant = div.stock_analysis.stagnant_stock || [];
                                        const divTotalStock = divStagnant.reduce((sum: number, item: any) => sum + (item.stock || 0), 0);
                                        const hasStagnant = divStagnant.length > 0;

                                        return (
                                            <div key={idx} className={`rounded-xl border overflow-hidden transition-all ${hasStagnant ? 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800' : 'border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/50'}`}>
                                                <div className={`flex items-center justify-between px-4 py-3 ${hasStagnant ? 'border-b border-slate-100 dark:border-slate-700' : ''}`}>
                                                    <div className="flex items-center gap-2.5">
                                                        <h4 className="text-[13px] font-semibold text-primary">{div.division_name}</h4>
                                                        {!hasStagnant && (
                                                            <span className="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-medium text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400">
                                                                <svg className="size-2.5" fill="currentColor" viewBox="0 0 20 20"><path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" /></svg>
                                                                Aman
                                                            </span>
                                                        )}
                                                    </div>
                                                    <div className="flex items-center gap-2.5">
                                                        {hasStagnant && (
                                                            <span className="text-[11px] text-slate-400">
                                                                {divTotalStock} unit tersimpan
                                                            </span>
                                                        )}
                                                        <span className={`rounded-full px-2 py-0.5 text-[10px] font-medium ${hasStagnant ? 'bg-orange-50 text-orange-600 dark:bg-orange-900/20 dark:text-orange-400' : 'bg-slate-50 text-slate-400 dark:bg-slate-700'}`}>
                                                            {divStagnant.length} item
                                                        </span>
                                                    </div>
                                                </div>

                                                {hasStagnant && (
                                                    <>
                                                        {/* Mobile: Card Layout */}
                                                        <div className="divide-y divide-slate-100 dark:divide-slate-700/50 md:hidden">
                                                            {divStagnant.map((item: any, i: number) => {
                                                                const severity = getSeverityColor(item.last_activity_date);
                                                                return (
                                                                    <div key={i} className="flex items-start justify-between gap-3 px-4 py-2.5">
                                                                        <div className="flex items-start gap-2 min-w-0">
                                                                            <span className="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[10px] font-medium text-slate-500 dark:bg-slate-700 dark:text-slate-400">
                                                                                {i + 1}
                                                                            </span>
                                                                            <div className="min-w-0">
                                                                                <p className="text-[13px] font-medium text-slate-700 dark:text-slate-300">{item.name}</p>
                                                                                <div className="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-0.5">
                                                                                    <span className="text-xs text-slate-500 dark:text-slate-400">
                                                                                        Stok: <span className="font-semibold text-slate-700 dark:text-slate-100">{item.stock}</span> {item.unit_of_measure || ''}
                                                                                    </span>
                                                                                    <span className="text-[11px] text-slate-400">
                                                                                        {item.last_activity_date
                                                                                            ? new Date(item.last_activity_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
                                                                                            : '-'
                                                                                        }
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <span className={`mt-0.5 shrink-0 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold ${severity.bg} ${severity.text}`}>
                                                                            <span className={`size-1.5 rounded-full ${severity.dot}`} />
                                                                            {severity.label}
                                                                        </span>
                                                                    </div>
                                                                );
                                                            })}
                                                        </div>

                                                        {/* Desktop: Table Layout */}
                                                        <div className="hidden md:block overflow-x-auto">
                                                            <table className="w-full">
                                                                <thead>
                                                                    <tr className="border-b border-slate-50 dark:border-slate-700/50">
                                                                        <th className="px-4 py-2 text-left text-[10px] font-medium text-slate-400">No</th>
                                                                        <th className="px-4 py-2 text-left text-[10px] font-medium text-slate-400">Nama Barang</th>
                                                                        <th className="px-4 py-2 text-center text-[10px] font-medium text-slate-400">Stok</th>
                                                                        <th className="px-4 py-2 text-center text-[10px] font-medium text-slate-400">Terakhir Keluar</th>
                                                                        <th className="px-4 py-2 text-center text-[10px] font-medium text-slate-400">Status</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody className="divide-y divide-slate-50 dark:divide-slate-700/50">
                                                                    {divStagnant.map((item: any, i: number) => {
                                                                        const severity = getSeverityColor(item.last_activity_date);
                                                                        return (
                                                                            <tr key={i} className="transition-colors hover:bg-slate-50/50 dark:hover:bg-slate-700/20">
                                                                                <td className="px-4 py-2">
                                                                                    <span className="flex size-5 items-center justify-center rounded-full bg-slate-100 text-[10px] font-medium text-slate-500 dark:bg-slate-700 dark:text-slate-400">
                                                                                        {i + 1}
                                                                                    </span>
                                                                                </td>
                                                                                <td className="px-4 py-2">
                                                                                    <p className="text-[13px] font-medium text-slate-700 dark:text-slate-300">{item.name}</p>
                                                                                </td>
                                                                                <td className="px-4 py-2 text-center">
                                                                                    <span className="text-[13px] font-semibold text-slate-700 dark:text-slate-100">{item.stock}</span>
                                                                                    <span className="ml-1 text-[10px] text-slate-400">{item.unit_of_measure || ''}</span>
                                                                                </td>
                                                                                <td className="px-4 py-2 text-center">
                                                                                    <span className="text-xs text-slate-500 dark:text-slate-400">
                                                                                        {item.last_activity_date
                                                                                            ? new Date(item.last_activity_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
                                                                                            : '-'
                                                                                        }
                                                                                    </span>
                                                                                </td>
                                                                                <td className="px-4 py-2 text-center">
                                                                                    <span className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold ${severity.bg} ${severity.text}`}>
                                                                                        <span className={`size-1.5 rounded-full ${severity.dot}`} />
                                                                                        {severity.label}
                                                                                    </span>
                                                                                </td>
                                                                            </tr>
                                                                        );
                                                                    })}
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </>
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>

                            {/* Legend */}
                            <div className="flex flex-wrap items-center justify-center gap-6 rounded-xl bg-slate-50 px-6 py-3 dark:bg-slate-800/50">
                                <span className="text-[11px] font-medium text-slate-500">Keterangan Durasi:</span>
                                <div className="flex items-center gap-1.5">
                                    <span className="size-2.5 rounded-full bg-orange-400" />
                                    <span className="text-[11px] text-slate-500">3 - 5 Bulan</span>
                                </div>
                                <div className="flex items-center gap-1.5">
                                    <span className="size-2.5 rounded-full bg-amber-500" />
                                    <span className="text-[11px] text-slate-500">6 - 11 Bulan</span>
                                </div>
                                <div className="flex items-center gap-1.5">
                                    <span className="size-2.5 rounded-full bg-red-500" />
                                    <span className="text-[11px] text-slate-500">≥ 12 Bulan / Belum Pernah</span>
                                </div>
                            </div>
                        </div>
                    );
                })()}


            </ContentCard>
        </RootLayout>
    );
}
