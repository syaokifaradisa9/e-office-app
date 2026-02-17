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
                <div className="mb-8 flex gap-6 border-b border-slate-200 dark:border-slate-700">
                    {tabs.map((tab) => (
                        <button
                            key={tab.id}
                            onClick={() => setActiveTab(tab.id as typeof activeTab)}
                            className={`relative pb-3 text-sm font-medium transition-colors ${activeTab === tab.id
                                ? 'text-slate-900 dark:text-white'
                                : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'
                                }`}
                        >
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
                {activeTab === 'stok_tertimbun' && (
                    <div className="space-y-8 animate-in fade-in duration-500">
                        {/* Gudang Utama */}
                        <div className="rounded-2xl border border-dashed border-slate-300 bg-slate-50/50 p-6 dark:border-slate-600 dark:bg-slate-900/50">
                            <h3 className="mb-6 flex items-center gap-2 text-xl font-bold text-slate-800 dark:text-slate-200">
                                <Warehouse className="size-6 text-slate-500" />
                                Stok Tertimbun - Gudang Utama
                                <span className="ml-2 rounded-full bg-slate-200 px-3 py-1 text-xs font-normal text-slate-600 dark:bg-slate-800 dark:text-slate-400">Tidak diminta {'>'} 3 Bulan</span>
                            </h3>
                            {global.stock_analysis.stagnant_stock?.length > 0 ? (
                                <div className="grid gap-4 md:grid-cols-3 lg:grid-cols-5">
                                    {global.stock_analysis.stagnant_stock.map((item, idx) => (
                                        <div key={idx} className="rounded-xl bg-white p-4 border border-slate-100 dark:bg-slate-800 dark:border-slate-700 transition-all hover:border-primary/30">
                                            <p className="text-sm font-bold text-slate-800 dark:text-slate-200 truncate" title={item.name}>{item.name}</p>
                                            <div className="mt-3 flex items-center justify-between">
                                                <p className="text-[10px] text-slate-400 uppercase font-bold">Stok Sisa</p>
                                                <p className="text-xs font-black text-primary">{item.stock}</p>
                                            </div>
                                            <div className="mt-2 flex items-center justify-between border-t border-slate-100 pt-2 dark:border-slate-700">
                                                <p className="text-[10px] text-slate-400 uppercase font-bold">Terakhir Diminta</p>
                                                <p className="text-[10px] font-medium text-orange-600">
                                                    {(item as any).last_activity_date
                                                        ? new Date((item as any).last_activity_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
                                                        : 'Belum pernah'
                                                    }
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="flex flex-col items-center justify-center py-12 text-center">
                                    <div className="rounded-full bg-green-100 p-4 dark:bg-green-900/20">
                                        <Package className="size-8 text-green-500" />
                                    </div>
                                    <p className="mt-4 text-sm font-medium text-slate-600 dark:text-slate-400">Tidak ada stok tertimbun di Gudang Utama</p>
                                    <p className="mt-1 text-xs text-slate-400">Semua barang aktif diminta dalam 3 bulan terakhir</p>
                                </div>
                            )}
                        </div>

                        {/* Per Divisi */}
                        <div>
                            <h3 className="mb-4 text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
                                <Layers className="size-4" />
                                Stok Tertimbun Per Divisi (Tidak Keluar {'>'} 3 Bulan)
                            </h3>
                            <div className="grid gap-6 md:grid-cols-2">
                                {per_division.map((div, idx) => (
                                    <div key={idx} className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800 shadow-sm">
                                        <div className="mb-4 flex items-center justify-between border-b border-slate-100 pb-3 dark:border-slate-700">
                                            <h4 className="text-lg font-black text-primary uppercase tracking-tight">{div.division_name}</h4>
                                            <span className="text-xs text-slate-400">{div.stock_analysis.stagnant_stock?.length || 0} item</span>
                                        </div>
                                        {div.stock_analysis.stagnant_stock?.length > 0 ? (
                                            <div className="grid gap-3 sm:grid-cols-2">
                                                {div.stock_analysis.stagnant_stock.slice(0, 6).map((item, i) => (
                                                    <div key={i} className="rounded-lg bg-slate-50 p-3 dark:bg-slate-700/50">
                                                        <p className="text-xs font-bold truncate" title={item.name}>{item.name}</p>
                                                        <div className="mt-2 flex items-center justify-between">
                                                            <span className="text-[10px] text-slate-400">Stok: {item.stock}</span>
                                                            <span className="text-[10px] text-orange-500">
                                                                {(item as any).last_activity_date
                                                                    ? new Date((item as any).last_activity_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
                                                                    : '-'
                                                                }
                                                            </span>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        ) : (
                                            <div className="flex items-center justify-center py-6 text-center">
                                                <p className="text-xs text-green-600 dark:text-green-400">✓ Tidak ada stok tertimbun</p>
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                )}


            </ContentCard>
        </RootLayout>
    );
}
