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

interface Item {
    id?: number;
    name: string;
    stock?: number;
    unit_of_measure?: string;
    total_requested?: number;
    turnover_ratio?: number;
    division?: { name: string };
    category?: { name: string };
}

interface Division {
    id: number;
    name: string;
}

interface ReportData {
    overall: {
        stock_extremes?: { most: Item[]; least: Item[] };
        out_of_stock?: Item[];
        request_extremes?: { most: { name: string; total: number }[]; least: { name: string; total: number }[] };
        monthly_requests?: { month: string; total_orders: number; total_items: number }[];
        stock_by_category?: { name: string; total_stock: number }[];
        dead_stock?: Item[];
        stock_turnover?: Item[];
        order_status_stats?: Record<string, number>;
        efficiency_stats?: { avg_total_lead_time: number; avg_approval_time: number; avg_delivery_time: number };
        reorder_recommendations?: Item[];
    };
    division: {
        stock_extremes?: { division_name: string; most: Item[]; least: Item[]; out_of_stock: Item[] }[];
        lead_time_analysis?: { division_name: string; avg_total_lead_time: number; avg_approval_time: number; avg_delivery_time: number }[];
        top_requesters?: { division_name: string; top_users: { name: string; total_requests: number; finished_requests: number }[] }[];
    };
    alerts: {
        critical_stock?: Item[];
        fulfillment_rate?: {
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
    divisions: Division[];
    [key: string]: unknown;
}

export default function ReportIndex() {
    const { reportData, divisions = [] } = usePage<PageProps>().props;
    const [activeTab, setActiveTab] = useState<'overview' | 'stock' | 'orders' | 'alerts'>('overview');

    const { overall = {}, division = {}, alerts = {} } = reportData || {};

    const tabs = [
        { id: 'overview', label: 'Overview', icon: <BarChart3 className="size-4" /> },
        { id: 'stock', label: 'Stok', icon: <Package className="size-4" /> },
        { id: 'orders', label: 'Order', icon: <TrendingUp className="size-4" /> },
        { id: 'alerts', label: 'Alerts', icon: <AlertTriangle className="size-4" /> },
    ];

    const StatCard = ({ label, value, icon, color }: { label: string; value: string | number; icon: React.ReactNode; color: string }) => (
        <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-sm text-slate-500 dark:text-slate-400">{label}</p>
                    <p className={`text-2xl font-bold ${color}`}>{value}</p>
                </div>
                <div className={`rounded-lg p-2 ${color.replace('text-', 'bg-')}/10`}>{icon}</div>
            </div>
        </div>
    );

    const ItemList = ({ title, items, showStock = true }: { title: string; items: Item[]; showStock?: boolean }) => (
        <div className="rounded-lg border border-slate-200 p-4 dark:border-slate-700">
            <h4 className="mb-3 font-medium text-slate-700 dark:text-slate-300">{title}</h4>
            {items.length === 0 ? (
                <p className="text-sm text-slate-500">Tidak ada data</p>
            ) : (
                <div className="space-y-2">
                    {items.slice(0, 5).map((item, idx) => (
                        <div key={idx} className="flex items-center justify-between text-sm">
                            <span className="text-slate-600 dark:text-slate-400">{item.name}</span>
                            {showStock && (
                                <span className="font-medium text-slate-800 dark:text-slate-200">
                                    {item.stock} {item.unit_of_measure}
                                </span>
                            )}
                        </div>
                    ))}
                </div>
            )}
        </div>
    );

    return (
        <RootLayout title="Laporan Inventory">
            <ContentCard
                title="Laporan Inventory"
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
                <div className="mb-6 flex flex-wrap gap-2 border-b border-slate-200 pb-4 dark:border-slate-700">
                    {tabs.map((tab) => (
                        <button
                            key={tab.id}
                            onClick={() => setActiveTab(tab.id as typeof activeTab)}
                            className={`flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors ${activeTab === tab.id
                                ? 'bg-primary text-white'
                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600'
                                }`}
                        >
                            {tab.icon}
                            {tab.label}
                        </button>
                    ))}
                </div>

                {/* Overview Tab */}
                {activeTab === 'overview' && (
                    <div className="space-y-6">
                        {/* Quick Stats */}
                        <div className="grid gap-4 md:grid-cols-4">
                            <StatCard
                                label="Total Order"
                                value={alerts.fulfillment_rate?.total_orders || 0}
                                icon={<Package className="size-5" />}
                                color="text-primary"
                            />
                            <StatCard
                                label="Order Selesai"
                                value={alerts.fulfillment_rate?.finished_orders || 0}
                                icon={<CheckCircle className="size-5" />}
                                color="text-green-600"
                            />
                            <StatCard
                                label="Order Pending"
                                value={alerts.fulfillment_rate?.pending_orders || 0}
                                icon={<Clock className="size-5" />}
                                color="text-yellow-600"
                            />
                            <StatCard
                                label="Order Dikirim"
                                value={alerts.fulfillment_rate?.delivered_orders || 0}
                                icon={<TrendingUp className="size-5" />}
                                color="text-blue-600"
                            />
                        </div>

                        {/* Charts Row 1 */}
                        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            {/* Monthly Requests Chart (Line) */}
                            <div className="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                                <h3 className="mb-4 flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-200">
                                    <BarChart className="size-5 text-primary" />
                                    Tren Permintaan Bulanan
                                </h3>
                                <div className="h-64">
                                    <Line
                                        data={{
                                            labels: overall.monthly_requests?.map((d) => {
                                                const [year, month] = d.month.split('-');
                                                return new Date(parseInt(year), parseInt(month) - 1).toLocaleDateString('id-ID', { month: 'short' });
                                            }) || [],
                                            datasets: [
                                                {
                                                    label: 'Total Order',
                                                    data: overall.monthly_requests?.map((d) => d.total_orders) || [],
                                                    borderColor: 'rgb(59, 130, 246)',
                                                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                                                    tension: 0.3,
                                                },
                                                {
                                                    label: 'Total Barang',
                                                    data: overall.monthly_requests?.map((d) => d.total_items) || [],
                                                    borderColor: 'rgb(16, 185, 129)',
                                                    backgroundColor: 'rgba(16, 185, 129, 0.5)',
                                                    tension: 0.3,
                                                },
                                            ],
                                        }}
                                        options={{
                                            responsive: true,
                                            maintainAspectRatio: false,
                                            plugins: {
                                                legend: { position: 'top' },
                                                datalabels: { display: false },
                                            },
                                            scales: {
                                                y: { beginAtZero: true },
                                            },
                                        }}
                                    />
                                </div>
                            </div>

                            {/* Status Distribution Chart (Doughnut) */}
                            <div className="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                                <h3 className="mb-4 flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-200">
                                    <PieChart className="size-5 text-purple-500" />
                                    Distribusi Status Order
                                </h3>
                                <div className="flex h-64 items-center justify-center">
                                    <Doughnut
                                        data={{
                                            labels: Object.keys(overall.order_status_stats || {}),
                                            datasets: [
                                                {
                                                    data: Object.values(overall.order_status_stats || {}),
                                                    backgroundColor: [
                                                        'rgb(234, 179, 8)', // pending - yellow
                                                        'rgb(59, 130, 246)', // confirmed - blue
                                                        'rgb(168, 85, 247)', // process/delivery - purple
                                                        'rgb(16, 185, 129)', // delivered/finished - green
                                                        'rgb(239, 68, 68)', // rejected - red
                                                    ],
                                                    borderWidth: 0,
                                                },
                                            ],
                                        }}
                                        options={{
                                            responsive: true,
                                            maintainAspectRatio: false,
                                            plugins: {
                                                legend: { position: 'right' },
                                                datalabels: {
                                                    color: '#fff',
                                                    font: { weight: 'bold' },
                                                    formatter: (value) => value > 0 ? value : '',
                                                },
                                            },
                                        }}
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Charts Row 2: Most Requested Items (Bar) */}
                        <div className="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                            <h3 className="mb-4 flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-200">
                                <TrendingUp className="size-5 text-green-500" />
                                10 Barang Paling Banyak Diminta
                            </h3>
                            <div className="h-72">
                                <Bar
                                    data={{
                                        labels: overall.request_extremes?.most?.map((i) => i.name.substring(0, 15) + (i.name.length > 15 ? '...' : '')) || [],
                                        datasets: [
                                            {
                                                label: 'Total Quantity',
                                                data: overall.request_extremes?.most?.map((i) => i.total) || [],
                                                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                                                borderRadius: 4,
                                            },
                                        ],
                                    }}
                                    options={{
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: {
                                            legend: { display: false },
                                            datalabels: {
                                                anchor: 'end',
                                                align: 'top',
                                                font: { weight: 'bold' },
                                            },
                                        },
                                        scales: {
                                            y: { beginAtZero: true },
                                            x: { ticks: { maxRotation: 45, minRotation: 45 } },
                                        },
                                    }}
                                />
                            </div>
                        </div>

                        {/* Efficiency Stats */}
                        {overall.efficiency_stats && (
                            <div className="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                                <h3 className="mb-4 flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-200">
                                    <Clock className="size-5 text-orange-500" />
                                    Efisiensi Waktu Rata-rata
                                </h3>
                                <div className="grid gap-4 md:grid-cols-3">
                                    <div className="text-center">
                                        <div className="text-3xl font-bold text-slate-800 dark:text-slate-200">
                                            {overall.efficiency_stats.avg_approval_time}
                                        </div>
                                        <div className="text-sm text-slate-500">Jam Approval</div>
                                    </div>
                                    <div className="text-center">
                                        <div className="text-3xl font-bold text-slate-800 dark:text-slate-200">
                                            {overall.efficiency_stats.avg_delivery_time}
                                        </div>
                                        <div className="text-sm text-slate-500">Jam Pengiriman</div>
                                    </div>
                                    <div className="text-center">
                                        <div className="text-3xl font-bold text-primary">
                                            {overall.efficiency_stats.avg_total_lead_time}
                                        </div>
                                        <div className="text-sm text-slate-500">Total Lead Time (Jam)</div>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                )}

                {/* Stock Tab */}
                {activeTab === 'stock' && (
                    <div className="space-y-6">
                        <div className="grid gap-4 md:grid-cols-2">
                            <ItemList title="Stok Terbanyak" items={overall.stock_extremes?.most || []} />
                            <ItemList title="Stok Terendah" items={overall.stock_extremes?.least || []} />
                        </div>

                        {overall.out_of_stock && overall.out_of_stock.length > 0 && (
                            <div className="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-900/50 dark:bg-red-900/20">
                                <h4 className="mb-3 flex items-center gap-2 font-medium text-red-700 dark:text-red-400">
                                    <XCircle className="size-5" />
                                    Barang Habis ({overall.out_of_stock.length})
                                </h4>
                                <div className="flex flex-wrap gap-2">
                                    {overall.out_of_stock.map((item, idx) => (
                                        <span
                                            key={idx}
                                            className="rounded-full bg-red-100 px-3 py-1 text-sm text-red-700 dark:bg-red-900/50 dark:text-red-300"
                                        >
                                            {item.name}
                                        </span>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Stock Turnover */}
                        {overall.stock_turnover && overall.stock_turnover.length > 0 && (
                            <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                                <h4 className="mb-3 flex items-center gap-2 font-medium text-slate-700 dark:text-slate-300">
                                    <RefreshCw className="size-5 text-primary" />
                                    Stock Turnover (3 Bulan Terakhir)
                                </h4>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b border-slate-200 dark:border-slate-700">
                                                <th className="py-2 text-left text-slate-500">Barang</th>
                                                <th className="py-2 text-right text-slate-500">Stok</th>
                                                <th className="py-2 text-right text-slate-500">Diminta</th>
                                                <th className="py-2 text-right text-slate-500">Rasio</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {overall.stock_turnover.map((item, idx) => (
                                                <tr key={idx} className="border-b border-slate-100 dark:border-slate-800">
                                                    <td className="py-2 text-slate-700 dark:text-slate-300">{item.name}</td>
                                                    <td className="py-2 text-right text-slate-600 dark:text-slate-400">
                                                        {item.stock} {item.unit_of_measure}
                                                    </td>
                                                    <td className="py-2 text-right text-slate-600 dark:text-slate-400">{item.total_requested}</td>
                                                    <td className="py-2 text-right font-medium text-primary">{item.turnover_ratio}x</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        )}

                        {/* Dead Stock */}
                        {overall.dead_stock && overall.dead_stock.length > 0 && (
                            <div className="rounded-xl border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-900/50 dark:bg-yellow-900/20">
                                <h4 className="mb-3 flex items-center gap-2 font-medium text-yellow-700 dark:text-yellow-400">
                                    <AlertTriangle className="size-5" />
                                    Dead Stock (Tidak diminta 3 bulan)
                                </h4>
                                <div className="flex flex-wrap gap-2">
                                    {overall.dead_stock.map((item, idx) => (
                                        <span
                                            key={idx}
                                            className="rounded-full bg-yellow-100 px-3 py-1 text-sm text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300"
                                        >
                                            {item.name}
                                        </span>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                )}

                {/* Orders Tab */}
                {activeTab === 'orders' && (
                    <div className="space-y-6">
                        {/* Request Extremes */}
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="rounded-lg border border-slate-200 p-4 dark:border-slate-700">
                                <h4 className="mb-3 flex items-center gap-2 font-medium text-slate-700 dark:text-slate-300">
                                    <TrendingUp className="size-5 text-green-500" />
                                    Barang Paling Banyak Diminta
                                </h4>
                                {overall.request_extremes?.most?.length ? (
                                    <div className="space-y-2">
                                        {overall.request_extremes.most.map((item, idx) => (
                                            <div key={idx} className="flex items-center justify-between text-sm">
                                                <span className="text-slate-600 dark:text-slate-400">{item.name}</span>
                                                <span className="font-medium text-green-600">{item.total} qty</span>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-sm text-slate-500">Tidak ada data</p>
                                )}
                            </div>
                            <div className="rounded-lg border border-slate-200 p-4 dark:border-slate-700">
                                <h4 className="mb-3 flex items-center gap-2 font-medium text-slate-700 dark:text-slate-300">
                                    <TrendingDown className="size-5 text-red-500" />
                                    Barang Paling Sedikit Diminta
                                </h4>
                                {overall.request_extremes?.least?.length ? (
                                    <div className="space-y-2">
                                        {overall.request_extremes.least.map((item, idx) => (
                                            <div key={idx} className="flex items-center justify-between text-sm">
                                                <span className="text-slate-600 dark:text-slate-400">{item.name}</span>
                                                <span className="font-medium text-red-600">{item.total} qty</span>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-sm text-slate-500">Tidak ada data</p>
                                )}
                            </div>
                        </div>

                        {/* Top Requesters */}
                        {division.top_requesters && division.top_requesters.length > 0 && (
                            <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                                <h4 className="mb-4 flex items-center gap-2 font-medium text-slate-700 dark:text-slate-300">
                                    <Users className="size-5 text-primary" />
                                    Pengguna Paling Aktif per Divisi
                                </h4>
                                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                    {division.top_requesters.map((div, idx) => (
                                        <div key={idx} className="rounded-lg border border-slate-100 p-3 dark:border-slate-700">
                                            <h5 className="mb-2 font-medium text-slate-700 dark:text-slate-300">{div.division_name}</h5>
                                            <div className="space-y-1">
                                                {div.top_users.slice(0, 3).map((user, uidx) => (
                                                    <div key={uidx} className="flex items-center justify-between text-sm">
                                                        <span className="text-slate-600 dark:text-slate-400">{user.name}</span>
                                                        <span className="text-slate-500">{user.total_requests} order</span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                )}

                {/* Alerts Tab */}
                {activeTab === 'alerts' && (
                    <div className="space-y-6">
                        {/* Critical Stock */}
                        {alerts.critical_stock && alerts.critical_stock.length > 0 && (
                            <div className="rounded-xl border border-orange-200 bg-orange-50 p-4 dark:border-orange-900/50 dark:bg-orange-900/20">
                                <h4 className="mb-3 flex items-center gap-2 font-medium text-orange-700 dark:text-orange-400">
                                    <AlertTriangle className="size-5" />
                                    Stok Kritis (≤10 unit)
                                </h4>
                                <div className="grid gap-2 md:grid-cols-2 lg:grid-cols-3">
                                    {alerts.critical_stock.map((item, idx) => (
                                        <div
                                            key={idx}
                                            className="flex items-center justify-between rounded-lg bg-white p-3 dark:bg-slate-800"
                                        >
                                            <div>
                                                <div className="font-medium text-slate-700 dark:text-slate-300">{item.name}</div>
                                                <div className="text-xs text-slate-500">{item.category?.name}</div>
                                            </div>
                                            <span className="font-bold text-orange-600">
                                                {item.stock} {item.unit_of_measure}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Reorder Recommendations */}
                        {overall.reorder_recommendations && overall.reorder_recommendations.length > 0 && (
                            <div className="rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-900/50 dark:bg-blue-900/20">
                                <h4 className="mb-3 flex items-center gap-2 font-medium text-blue-700 dark:text-blue-400">
                                    <Package className="size-5" />
                                    Rekomendasi Restock (Stok Rendah + Permintaan Tinggi)
                                </h4>
                                <div className="grid gap-2 md:grid-cols-2">
                                    {overall.reorder_recommendations.map((item, idx) => (
                                        <div
                                            key={idx}
                                            className="flex items-center justify-between rounded-lg bg-white p-3 dark:bg-slate-800"
                                        >
                                            <div>
                                                <div className="font-medium text-slate-700 dark:text-slate-300">{item.name}</div>
                                                <div className="text-xs text-slate-500">Diminta: {item.total_requested} dalam 3 bulan</div>
                                            </div>
                                            <span className="font-bold text-blue-600">
                                                {item.stock} {item.unit_of_measure}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {(!alerts.critical_stock || alerts.critical_stock.length === 0) &&
                            (!overall.reorder_recommendations || overall.reorder_recommendations.length === 0) && (
                                <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-green-300 bg-green-50 py-12 dark:border-green-900/50 dark:bg-green-900/20">
                                    <CheckCircle className="mb-4 size-12 text-green-500" />
                                    <h3 className="text-lg font-medium text-green-700 dark:text-green-400">Semua Baik!</h3>
                                    <p className="text-sm text-green-600 dark:text-green-500">Tidak ada alert yang perlu diperhatikan</p>
                                </div>
                            )}
                    </div>
                )}
            </ContentCard>
        </RootLayout>
    );
}
