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
    CheckCircle,
    XCircle,
    RefreshCw,
    Users,
    FileSpreadsheet,
    BarChart,
    PieChart,
    LineChart,
    ClipboardList,
} from 'lucide-react';
import Button from '@/components/buttons/Button';
import { Bar, Line, Doughnut } from 'react-chartjs-2';
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
    category?: { name: string };
}

interface ReportData {
    overview_stats: Record<string, number>;
    request_trend: { month: string; total_orders: number; total_items: number }[];
    opname_variance_trend: { month: string; total_minus: number }[];
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
        fulfillment_rate: {
            total_orders: number;
            finished_orders: number;
            pending_orders: number;
            delivered_orders: number;
            fulfillment_rate: number;
        };
    };
}

interface PageProps {
    reportData: ReportData;
    divisions: { id: number; name: string }[];
    [key: string]: unknown;
}

export default function ReportIndex() {
    const { reportData } = usePage<PageProps>().props;
    const [activeTab, setActiveTab] = useState<'requests' | 'stock' | 'analysis' | 'opname'>('requests');

    if (!reportData) return null;

    const {
        overview_stats,
        request_trend,
        opname_variance_trend,
        item_rankings,
        category_rankings,
        stock_analysis,
        alerts
    } = reportData;

    const tabs = [
        { id: 'requests', label: 'Permintaan Barang', icon: <TrendingUp className="size-4" /> },
        { id: 'stock', label: 'Stok Barang', icon: <Package className="size-4" /> },
        { id: 'opname', label: 'Stock Opname', icon: <ClipboardList className="size-4" /> },
        { id: 'analysis', label: 'Stok Tertimbun', icon: <Clock className="size-4" /> },
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
                {title}
            </h4>
            <div className="space-y-2">
                {items?.length > 0 ? (
                    items.map((item, idx) => (
                        <div key={idx} className="flex items-center justify-between border-b border-slate-50 pb-2 last:border-0 last:pb-0 dark:border-slate-700/50">
                            <div className="flex items-center gap-2">
                                <span className="flex size-5 items-center justify-center rounded-full bg-slate-100 text-[10px] font-bold text-slate-500 dark:bg-slate-700">
                                    {idx + 1}
                                </span>
                                <span className="text-sm text-slate-600 dark:text-slate-400">
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

    return (
        <RootLayout title="Laporan Inventory">
            <ContentCard
                title="Laporan Inventory & Statistik"
                mobileFullWidth={false}
                bodyClassName="p-6 md:p-8"
                additionalButton={
                    <Button
                        href="/inventory/report/print-excel"
                        className="w-full md:w-auto"
                        label="Export Excel"
                        icon={<FileSpreadsheet className="size-4" />}
                        target="_blank"
                    />
                }
            >
                {/* Tabs */}
                <div className="mb-8 flex flex-wrap gap-2 border-b border-slate-200 pb-4 dark:border-slate-700">
                    {tabs.map((tab) => (
                        <button
                            key={tab.id}
                            onClick={() => setActiveTab(tab.id as typeof activeTab)}
                            className={`flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-bold transition-all ${activeTab === tab.id
                                ? 'bg-primary text-white shadow-lg shadow-primary/25'
                                : 'bg-slate-50 text-slate-500 hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700'
                                }`}
                        >
                            {tab.icon}
                            {tab.label}
                        </button>
                    ))}
                </div>

                {/* Requests Tab */}
                {activeTab === 'requests' && (
                    <div className="space-y-8 animate-in fade-in duration-500">
                        {/* Status Stats */}
                        <div>
                            <h3 className="mb-4 text-sm font-bold text-slate-400 uppercase tracking-widest">Status Permintaan Barang</h3>
                            <div className="grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-8">
                                {Object.entries(overview_stats).map(([status, count]) => (
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
                                Tren Permintaan Barang (Januari - Desember)
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
                                                    const data = request_trend?.find(d => d.month === monthStr);
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
                                                    const data = request_trend?.find(d => d.month === monthStr);
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
                                        plugins: {
                                            legend: { position: 'top', labels: { usePointStyle: true, font: { weight: 'bold' } } },
                                            datalabels: { display: false },
                                        },
                                        scales: {
                                            y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { display: false }, border: { display: false } },
                                            x: { grid: { display: false }, border: { display: false } }
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
                                items={item_rankings.most_requested || []}
                                valueField="total"
                                valueSuffix="qty"
                                color="text-green-600"
                            />
                            <SimpleList
                                title="10 Barang Paling Sedikit Diminta"
                                icon={<TrendingDown className="size-5 text-red-400" />}
                                items={item_rankings.least_requested || []}
                                valueField="total"
                                valueSuffix="qty"
                                color="text-red-500"
                            />
                            <SimpleList
                                title="10 Barang Paling Banyak Keluar"
                                icon={<Package className="size-5 text-blue-500" />}
                                items={item_rankings.most_outbound || []}
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
                                items={category_rankings.most_requested || []}
                                valueField="total_requests"
                                valueSuffix="order"
                                color="text-purple-600"
                            />
                            <SimpleList
                                title="5 Kategori Paling Sedikit Diminta"
                                icon={<BarChart className="size-5 text-slate-400" />}
                                items={category_rankings.least_requested || []}
                                valueField="total_requests"
                                valueSuffix="order"
                                color="text-slate-500"
                            />
                            <SimpleList
                                title="5 Kategori Paling Banyak Keluar"
                                icon={<BarChart className="size-5 text-emerald-500" />}
                                items={category_rankings.most_outbound || []}
                                valueField="total_quantity"
                                valueSuffix="qty"
                                color="text-emerald-600"
                            />
                        </div>
                    </div>
                )}

                {/* Stock Tab */}
                {activeTab === 'stock' && (
                    <div className="space-y-8 animate-in fade-in duration-500">
                        <div className="grid gap-6 md:grid-cols-2">
                            <SimpleList
                                title="10 Barang Stok Terbanyak"
                                icon={<Package className="size-5 text-primary" />}
                                items={item_rankings.most_stock || []}
                                valueField="stock"
                                valueSuffix={item_rankings.most_stock?.[0]?.unit_of_measure}
                                color="text-primary"
                            />
                            <SimpleList
                                title="10 Barang Stok Tersedikit"
                                icon={<AlertTriangle className="size-5 text-red-500" />}
                                items={item_rankings.least_stock || []}
                                valueField="stock"
                                valueSuffix={item_rankings.least_stock?.[0]?.unit_of_measure}
                                color="text-red-600"
                            />
                        </div>


                    </div>
                )}

                {/* Stock Opname Tab */}
                {activeTab === 'opname' && (
                    <div className="space-y-8 animate-in fade-in duration-500">
                        {/* Trend Chart */}
                        <div className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                            <h3 className="mb-6 flex items-center gap-2 font-bold text-slate-800 dark:text-slate-200 text-lg">
                                <LineChart className="size-5 text-orange-500" />
                                Tren Selisih Stock Opname (Januari - Desember)
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
                                                    const data = opname_variance_trend?.find(d => d.month === monthStr);
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

                        <div className="w-full">
                            <SimpleList
                                title="Laporan Selisih Barang Stock Opname (Minus)"
                                icon={<AlertTriangle className="size-5 text-orange-500" />}
                                items={item_rankings.opname_variance_minus || []}
                                valueField="total_difference"
                                color="text-orange-600"
                                emptyMessage="Tidak ada selisih opname minus di divisi ini."
                            />
                        </div>
                    </div>
                )}



                {/* Analysis Tab */}
                {activeTab === 'analysis' && (
                    <div className="space-y-8 animate-in fade-in duration-500">
                        <div className="rounded-2xl border border-dashed border-slate-300 bg-slate-50/50 p-6 dark:border-slate-600 dark:bg-slate-900/50">
                            <h3 className="mb-6 flex items-center gap-2 text-xl font-bold text-slate-800 dark:text-slate-200">
                                <Clock className="size-6 text-slate-500" />
                                Analisa Stok Barang Tertimbun
                                <span className="ml-2 rounded-full bg-slate-200 px-3 py-1 text-xs font-normal text-slate-600 dark:bg-slate-800 dark:text-slate-400">Jarang keluar {'>'} 3 Bulan</span>
                            </h3>
                            {stock_analysis.stagnant_stock?.length > 0 ? (
                                <div className="grid gap-4 md:grid-cols-3 lg:grid-cols-5">
                                    {stock_analysis.stagnant_stock.map((item: any, idx: number) => (
                                        <div key={idx} className="rounded-xl bg-white p-4 border border-slate-100 dark:bg-slate-800 dark:border-slate-700 transition-all hover:border-primary/30">
                                            <p className="text-sm font-bold text-slate-800 dark:text-slate-200 truncate" title={item.name}>{item.name}</p>
                                            <div className="mt-3 flex items-center justify-between">
                                                <p className="text-[10px] text-slate-400 uppercase font-bold">Stok Sisa</p>
                                                <p className="text-xs font-black text-primary">{item.stock} <span className="text-[10px] text-slate-400 font-normal">{item.unit_of_measure}</span></p>
                                            </div>
                                            <div className="mt-2 flex items-center justify-between border-t border-slate-100 pt-2 dark:border-slate-700">
                                                <p className="text-[10px] text-slate-400 uppercase font-bold">Terakhir Keluar</p>
                                                <p className="text-[10px] font-medium text-orange-600">
                                                    {item.last_activity_date
                                                        ? new Date(item.last_activity_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
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
                                    <p className="mt-4 text-sm font-medium text-slate-600 dark:text-slate-400">Tidak ada stok tertimbun di divisi ini</p>
                                    <p className="mt-1 text-xs text-slate-400">Semua barang aktif keluar dalam 3 bulan terakhir</p>
                                </div>
                            )}
                        </div>
                    </div>
                )}
            </ContentCard>
        </RootLayout>
    );
}
