import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import { usePage, router } from '@inertiajs/react';
import { useState } from 'react';
import {
    Wrench,
    TrendingUp,
    FileText,
    AlertTriangle,
    Clock,
    CheckCircle,
    Star,
    Calendar,
    ChevronRight,
    PieChart,
    BarChart3,
    History,
    FileSpreadsheet,
    Users,
    Activity,
    Box,
} from 'lucide-react';
import { Bar, Doughnut, Line } from 'react-chartjs-2';
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

interface ReportPageProps {
    reportData: any;
    type: 'division' | 'all';
    [key: string]: any;
}

export default function ReportIndex() {
    const { reportData, type } = usePage<ReportPageProps>().props;
    const [activeTab, setActiveTab] = useState('overview');
    const [viewMode, setViewMode] = useState<'global' | 'division'>('global');

    if (!reportData) return null;

    const tabs = [
        { id: 'overview', label: 'Ringkasan', icon: <Activity className="size-4" /> },
        { id: 'brands', label: 'Distribusi Merek', icon: <Box className="size-4" /> },
        { id: 'models', label: 'Distribusi Model', icon: <PieChart className="size-4" /> },
    ];

    if (type === 'all') {
        tabs.push({ id: 'maintenance', label: 'Maintenance', icon: <Wrench className="size-4" /> });
        tabs.push({ id: 'problems', label: 'Pelaporan Masalah', icon: <AlertTriangle className="size-4" /> });
    }

    const StatCard = ({ label, value, icon, color, subtitle }: { label: string; value: string | number; icon: React.ReactNode; color: string; subtitle?: string }) => (
        <div className="rounded-xl border border-slate-200 bg-white p-4 transition-all hover:shadow-md dark:border-slate-700 dark:bg-slate-800">
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">{label}</p>
                    <p className={`text-2xl font-black ${color}`}>{value}</p>
                    {subtitle && <p className="text-[10px] text-slate-400 mt-1">{subtitle}</p>}
                </div>
                <div className={`rounded-xl p-3 ${color.replace('text-', 'bg-')}/10`}>{icon}</div>
            </div>
        </div>
    );

    const renderDivisionReport = () => {
        const { division_name, metrics, top_problem_assets, top_users, monthly_trend, priority_stats, merk_stats, model_stats } = reportData;
        const maxMonthlyTotal = monthly_trend?.length > 0 ? Math.max(...monthly_trend.map((m: any) => m.total)) + 2 : 5;
        const maxPriorityTotal = priority_stats?.length > 0 ? Math.max(...priority_stats.map((p: any) => p.total)) + 2 : 5;

        return (
            <div className="space-y-6 animate-in fade-in duration-500">
                <div className="grid gap-4 grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                    <StatCard
                        label="Progress Maintenance"
                        value={`${metrics.compliance_rate}%`}
                        icon={<CheckCircle className="size-6 text-emerald-600" />}
                        color="text-emerald-600"
                    />
                    <StatCard
                        label="Aset Bermasalah"
                        value={`${metrics.problem_assets}/${metrics.total_assets}`}
                        icon={<AlertTriangle className="size-6 text-rose-600" />}
                        color="text-rose-600"
                    />
                    <StatCard
                        label="Total Aset"
                        value={metrics.total_assets}
                        icon={<Box className="size-6 text-blue-600" />}
                        color="text-blue-600"
                    />
                    <StatCard
                        label="Rating Maintenance"
                        value={metrics.avg_maintenance_rating}
                        icon={<Star className="size-6 text-amber-500 fill-amber-500" />}
                        color="text-amber-500"
                    />
                    <StatCard
                        label="Rating Pelaporan"
                        value={metrics.avg_ticket_rating}
                        icon={<Star className="size-6 text-amber-500 fill-amber-500" />}
                        color="text-amber-500"
                    />
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <div className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                        <h3 className="mb-6 font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                            <BarChart3 className="size-5 text-primary" />
                            Tren Jumlah Pelaporan Masalah
                        </h3>
                        <div className="h-72">
                            <Bar
                                data={{
                                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                                    datasets: [{
                                        label: 'Jumlah Laporan',
                                        data: Array.from({ length: 12 }, (_, i) => {
                                            const found = monthly_trend.find((m: any) => Number(m.month) === i + 1);
                                            return found ? found.total : 0;
                                        }),
                                        backgroundColor: '#3b82f6',
                                        borderRadius: 6,
                                    }]
                                }}
                                options={{
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: { display: false },
                                        datalabels: {
                                            display: true,
                                            align: 'end',
                                            anchor: 'end',
                                            color: '#64748b',
                                            font: { weight: 'bold' }
                                        }
                                    },
                                    scales: {
                                        y: { max: maxMonthlyTotal, beginAtZero: true, border: { display: false }, grid: { color: '#f1f5f9' }, ticks: { stepSize: 1 } },
                                        x: { grid: { display: false } }
                                    },
                                    layout: {
                                        padding: { top: 20 }
                                    }
                                }}
                            />
                        </div>
                    </div>

                    <div className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                        <h3 className="mb-4 font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                            <Activity className="size-5 text-amber-500" />
                            Distribusi Pelaporan Berdasarkan Prioritas
                        </h3>
                        <div className="h-72 mt-8">
                            <Bar
                                data={{
                                    labels: priority_stats.map((c: any) => c.priority),
                                    datasets: [{
                                        label: 'Total Pelaporan',
                                        data: priority_stats.map((c: any) => c.total),
                                        backgroundColor: ['#ef4444', '#f59e0b', '#3b82f6', '#10b981', '#8b5cf6'],
                                        borderRadius: 6,
                                    }]
                                }}
                                options={{
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: { display: false },
                                        datalabels: {
                                            display: true,
                                            align: 'end',
                                            anchor: 'end',
                                            color: '#64748b',
                                            font: { weight: 'bold' }
                                        }
                                    },
                                    scales: {
                                        y: { max: maxPriorityTotal, beginAtZero: true, border: { display: false }, grid: { color: '#f1f5f9' }, ticks: { stepSize: 1 } },
                                        x: { grid: { display: false } }
                                    },
                                    layout: {
                                        padding: { top: 20 }
                                    }
                                }}
                            />
                        </div>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <div className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                        <h3 className="mb-4 font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                            <AlertTriangle className="size-5 text-rose-500" />
                            5 Aset Paling Sering Bermasalah
                        </h3>
                        <div className="space-y-4">
                            {top_problem_assets.map((asset: any, idx: number) => (
                                <div key={idx} className="flex items-center gap-4">
                                    <div className="size-8 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center font-bold text-slate-500">{idx + 1}</div>
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm font-medium truncate">{asset.merk} {asset.model}</p>
                                        <p className="text-[10px] text-slate-400">{asset.serial_number}</p>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-sm font-bold text-rose-600">
                                            {asset.tickets_count + asset.refinement_maintenances_count} Kendala
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                        <h3 className="mb-4 font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                            <Users className="size-5 text-indigo-500" />
                            5 Pegawai dengan Kendala Terbanyak
                        </h3>
                        <div className="space-y-4">
                            {top_users.map((user: any, idx: number) => (
                                <div key={idx} className="flex items-center gap-4">
                                    <div className="size-8 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center font-bold text-slate-500">{idx + 1}</div>
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm font-medium truncate">{user.name}</p>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-sm font-bold text-indigo-600">{user.tickets_count} Tiket</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

            </div>
        );
    };

    const renderAllReport = () => {
        const { metrics, monthly_trend, maintenance_monthly_trend, top_problem_assets } = reportData;
        const maxTicketTrend = monthly_trend?.length > 0 ? Math.max(...monthly_trend.map((m: any) => m.total)) + 2 : 5;
        const maxMaintenanceTrend = maintenance_monthly_trend?.length > 0 ? Math.max(...maintenance_monthly_trend.map((m: any) => m.total)) + 2 : 5;

        const avgOverallRating = ((Number(metrics.avg_ticket_rating) + Number(metrics.avg_maintenance_rating)) / 2).toFixed(1);

        return (
            <div className="space-y-6 animate-in fade-in duration-500">
                <div className="grid gap-4 grid-cols-2 lg:grid-cols-3">
                    <StatCard
                        label="Total Asset"
                        value={metrics.total_assets}
                        icon={<Box className="size-6 text-blue-600" />}
                        color="text-blue-600"
                    />
                    <StatCard
                        label="Total Maintenance"
                        value={metrics.total_maintenances}
                        icon={<Wrench className="size-6 text-emerald-600" />}
                        color="text-emerald-600"
                    />
                    <StatCard
                        label="Total Ticket"
                        value={metrics.total_tickets}
                        icon={<Activity className="size-6 text-indigo-600" />}
                        color="text-indigo-600"
                    />
                    <StatCard
                        label="Rating Maintenance"
                        value={metrics.avg_maintenance_rating}
                        icon={<Star className="size-6 text-amber-500 fill-amber-500" />}
                        color="text-amber-500"
                    />
                    <StatCard
                        label="Rating Ticket"
                        value={metrics.avg_ticket_rating}
                        icon={<Star className="size-6 text-amber-500 fill-amber-500" />}
                        color="text-amber-500"
                    />
                    <StatCard
                        label="Rating Rata-rata"
                        value={avgOverallRating}
                        icon={<Star className="size-6 text-yellow-500 fill-yellow-500" />}
                        color="text-yellow-500"
                    />
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <div className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                        <h3 className="mb-6 font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                            <Wrench className="size-5 text-emerald-500" />
                            Tren Jumlah Maintenance Bulanan
                        </h3>
                        <div className="h-72">
                            <Bar
                                data={{
                                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                                    datasets: [{
                                        label: 'Jumlah Maintenance',
                                        data: Array.from({ length: 12 }, (_, i) => {
                                            const found = (maintenance_monthly_trend || []).find((m: any) => Number(m.month) === i + 1);
                                            return found ? found.total : 0;
                                        }),
                                        backgroundColor: '#10b981',
                                        borderRadius: 6,
                                    }]
                                }}
                                options={{
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: { display: false },
                                        datalabels: {
                                            display: true,
                                            align: 'end',
                                            anchor: 'end',
                                            color: '#64748b',
                                            font: { weight: 'bold' }
                                        }
                                    },
                                    scales: {
                                        y: { max: maxMaintenanceTrend, beginAtZero: true, border: { display: false }, grid: { color: '#f1f5f9' }, ticks: { stepSize: 1 } },
                                        x: { grid: { display: false } }
                                    },
                                    layout: { padding: { top: 20 } }
                                }}
                            />
                        </div>
                    </div>

                    <div className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                        <h3 className="mb-6 font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                            <Activity className="size-5 text-indigo-500" />
                            Tren Jumlah Ticketing Bulanan
                        </h3>
                        <div className="h-72">
                            <Bar
                                data={{
                                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                                    datasets: [{
                                        label: 'Jumlah Laporan',
                                        data: Array.from({ length: 12 }, (_, i) => {
                                            const found = monthly_trend.find((m: any) => Number(m.month) === i + 1);
                                            return found ? found.total : 0;
                                        }),
                                        backgroundColor: '#4f46e5',
                                        borderRadius: 6,
                                    }]
                                }}
                                options={{
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: { display: false },
                                        datalabels: {
                                            display: true,
                                            align: 'end',
                                            anchor: 'end',
                                            color: '#64748b',
                                            font: { weight: 'bold' }
                                        }
                                    },
                                    scales: {
                                        y: { max: maxTicketTrend, beginAtZero: true, border: { display: false }, grid: { color: '#f1f5f9' }, ticks: { stepSize: 1 } },
                                        x: { grid: { display: false } }
                                    },
                                    layout: { padding: { top: 20 } }
                                }}
                            />
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    return (
        <RootLayout title="Laporan Ticketing">
            <ContentCard
                title={`Laporan Ticketing ${type === 'division' ? 'Divisi' : 'Keseluruhan'}`}
                subtitle={`Analisis kepatuhan maintenance, statistik laporan kendala, dan performa layanan ${type === 'all' ? 'seluruh departemen' : ''}.`}
                mobileFullWidth={false}
                bodyClassName="p-6 md:p-8"
                additionalButton={
                    <button className="flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium hover:bg-emerald-700 transition-colors">
                        <FileSpreadsheet className="size-4" />
                        Export Excel
                    </button>
                }
            >
                {/* Tabs */}
                <div className="mb-8 flex gap-6 border-b border-slate-200 dark:border-slate-700">
                    {tabs.map((tab) => (
                        <button
                            key={tab.id}
                            onClick={() => setActiveTab(tab.id)}
                            className={`relative pb-3 text-sm font-medium transition-colors ${activeTab === tab.id
                                ? 'text-slate-900 dark:text-white'
                                : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'
                                }`}
                        >
                            <div className="flex items-center gap-2">
                                {tab.icon}
                                {tab.label}
                            </div>
                            {activeTab === tab.id && (
                                <span className="absolute bottom-0 left-0 h-0.5 w-full bg-primary dark:bg-white" />
                            )}
                        </button>
                    ))}
                </div>

                {activeTab === 'overview' && (
                    <>
                        {type === 'division' && renderDivisionReport()}
                        {type === 'all' && renderAllReport()}
                    </>
                )}

                {activeTab === 'brands' && (
                    <div className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800 animate-in fade-in duration-500">
                        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
                            <h3 className="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                                <Box className="size-5 text-primary" />
                                Distribusi Merek Aset {type === 'all' && (viewMode === 'global' ? 'Keseluruhan' : 'Berdasarkan Divisi')}
                            </h3>
                            {type === 'all' && (
                                <select
                                    className="text-sm rounded-lg border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-primary focus:border-primary px-3 py-2 w-full sm:w-auto"
                                    value={viewMode}
                                    onChange={(e) => setViewMode(e.target.value as 'global' | 'division')}
                                >
                                    <option value="global">Tampilkan Berdasarkan Keseluruhan</option>
                                    <option value="division">Tampilkan Berdasarkan Divisi</option>
                                </select>
                            )}
                        </div>

                        {(type === 'division' || viewMode === 'global') ? (
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                {reportData?.merk_stats?.length > 0 ? reportData.merk_stats.map((item: any, idx: number) => (
                                    <div key={idx} className="flex items-center justify-between p-4 rounded-xl border border-slate-100 bg-slate-50 hover:border-slate-200 transition-colors dark:bg-slate-700/50 dark:border-slate-600/50">
                                        <div className="flex-1 min-w-0 pr-4">
                                            <p className="text-sm font-semibold text-slate-700 dark:text-slate-200 truncate">{item.merk || 'Tanpa Merek'}</p>
                                        </div>
                                        <div className="flex-shrink-0">
                                            <span className="inline-flex items-center justify-center px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-bold">
                                                {item.total}
                                            </span>
                                        </div>
                                    </div>
                                )) : (
                                    <div className="col-span-full py-12 text-center text-slate-400">
                                        Tidak ada data merek aset
                                    </div>
                                )}
                            </div>
                        ) : (
                            <div className="space-y-8">
                                {Object.entries(reportData?.merk_stats_by_division || {}).map(([divisionName, stats]: [string, any], divIdx) => (
                                    <div key={divIdx} className="space-y-4">
                                        <h4 className="font-bold text-slate-700 dark:text-slate-300 border-b pb-2 dark:border-slate-700">{divisionName}</h4>
                                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                            {stats.length > 0 ? stats.map((item: any, idx: number) => (
                                                <div key={idx} className="flex items-center justify-between p-4 rounded-xl border border-slate-100 bg-slate-50 hover:border-slate-200 transition-colors dark:bg-slate-700/50 dark:border-slate-600/50">
                                                    <div className="flex-1 min-w-0 pr-4">
                                                        <p className="text-sm font-semibold text-slate-700 dark:text-slate-200 truncate">{item.merk || 'Tanpa Merek'}</p>
                                                    </div>
                                                    <div className="flex-shrink-0">
                                                        <span className="inline-flex items-center justify-center px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-bold">
                                                            {item.total}
                                                        </span>
                                                    </div>
                                                </div>
                                            )) : (
                                                <div className="col-span-full py-4 text-center text-slate-400 text-sm">
                                                    Tidak ada data merek aset
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}

                {activeTab === 'models' && (
                    <div className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800 animate-in fade-in duration-500">
                        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
                            <h3 className="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                                <PieChart className="size-5 text-primary" />
                                Distribusi Model Aset {type === 'all' && (viewMode === 'global' ? 'Keseluruhan' : 'Berdasarkan Divisi')}
                            </h3>
                            {type === 'all' && (
                                <select
                                    className="text-sm rounded-lg border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-primary focus:border-primary px-3 py-2 w-full sm:w-auto"
                                    value={viewMode}
                                    onChange={(e) => setViewMode(e.target.value as 'global' | 'division')}
                                >
                                    <option value="global">Tampilkan Berdasarkan Keseluruhan</option>
                                    <option value="division">Tampilkan Berdasarkan Divisi</option>
                                </select>
                            )}
                        </div>

                        {(type === 'division' || viewMode === 'global') ? (
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                {reportData?.model_stats?.length > 0 ? reportData.model_stats.map((item: any, idx: number) => (
                                    <div key={idx} className="flex items-center justify-between p-4 rounded-xl border border-slate-100 bg-slate-50 hover:border-slate-200 transition-colors dark:bg-slate-700/50 dark:border-slate-600/50">
                                        <div className="flex-1 min-w-0 pr-4">
                                            <p className="text-sm font-semibold text-slate-700 dark:text-slate-200 truncate" title={item.model}>{item.model || 'Tanpa Model'}</p>
                                        </div>
                                        <div className="flex-shrink-0">
                                            <span className="inline-flex items-center justify-center px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-600 text-xs font-bold">
                                                {item.total}
                                            </span>
                                        </div>
                                    </div>
                                )) : (
                                    <div className="col-span-full py-12 text-center text-slate-400">
                                        Tidak ada data model aset
                                    </div>
                                )}
                            </div>
                        ) : (
                            <div className="space-y-8">
                                {Object.entries(reportData?.model_stats_by_division || {}).map(([divisionName, stats]: [string, any], divIdx) => (
                                    <div key={divIdx} className="space-y-4">
                                        <h4 className="font-bold text-slate-700 dark:text-slate-300 border-b pb-2 dark:border-slate-700">{divisionName}</h4>
                                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                            {stats.length > 0 ? stats.map((item: any, idx: number) => (
                                                <div key={idx} className="flex items-center justify-between p-4 rounded-xl border border-slate-100 bg-slate-50 hover:border-slate-200 transition-colors dark:bg-slate-700/50 dark:border-slate-600/50">
                                                    <div className="flex-1 min-w-0 pr-4">
                                                        <p className="text-sm font-semibold text-slate-700 dark:text-slate-200 truncate" title={item.model}>{item.model || 'Tanpa Model'}</p>
                                                    </div>
                                                    <div className="flex-shrink-0">
                                                        <span className="inline-flex items-center justify-center px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-600 text-xs font-bold">
                                                            {item.total}
                                                        </span>
                                                    </div>
                                                </div>
                                            )) : (
                                                <div className="col-span-full py-4 text-center text-slate-400 text-sm">
                                                    Tidak ada data model aset
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}

                {activeTab === 'maintenance' && type === 'all' && (
                    <div className="space-y-6 animate-in fade-in duration-500">
                        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <h3 className="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                                <Wrench className="size-5 text-primary" />
                                Laporan Maintenance {viewMode === 'global' ? 'Keseluruhan' : 'Berdasarkan Divisi'}
                            </h3>
                            <select
                                className="text-sm rounded-lg border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-primary focus:border-primary px-3 py-2 w-full sm:w-auto"
                                value={viewMode}
                                onChange={(e) => setViewMode(e.target.value as 'global' | 'division')}
                            >
                                <option value="global">Tampilkan Berdasarkan Keseluruhan</option>
                                <option value="division">Tampilkan Berdasarkan Divisi</option>
                            </select>
                        </div>

                        {viewMode === 'global' ? (
                            <>
                                <div className="grid gap-4 grid-cols-1 md:grid-cols-3">
                                    <StatCard
                                        label="Progress Maintenance"
                                        value={`${reportData.metrics.compliance_rate}%`}
                                        icon={<CheckCircle className="size-6 text-emerald-600" />}
                                        color="text-emerald-600"
                                        subtitle={`${reportData.metrics.confirmed_maintenances} / ${reportData.metrics.total_maintenances} Selesai`}
                                    />
                                    <StatCard
                                        label="Total Asset Harus Dimaintenance"
                                        value={reportData.metrics.total_maintenances}
                                        icon={<Wrench className="size-6 text-blue-600" />}
                                        color="text-blue-600"
                                    />
                                    <StatCard
                                        label="Rating Maintenance"
                                        value={reportData.metrics.avg_maintenance_rating}
                                        icon={<Star className="size-6 text-amber-500 fill-amber-500" />}
                                        color="text-amber-500"
                                    />
                                </div>

                                <div className="grid gap-6 lg:grid-cols-2">
                                    <div className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                                        <h3 className="mb-6 font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                                            <BarChart3 className="size-5 text-primary" />
                                            Tren Estimasi Maintenance Bulanan
                                        </h3>
                                        <div className="h-72">
                                            <Bar
                                                data={{
                                                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                                                    datasets: [{
                                                        label: 'Jumlah Maintenance',
                                                        data: Array.from({ length: 12 }, (_, i) => {
                                                            const found = (reportData.maintenance_monthly_trend || []).find((m: any) => Number(m.month) === i + 1);
                                                            return found ? found.total : 0;
                                                        }),
                                                        backgroundColor: '#3b82f6',
                                                        borderRadius: 6,
                                                    }]
                                                }}
                                                options={{
                                                    maintainAspectRatio: false,
                                                    plugins: {
                                                        legend: { display: false },
                                                        datalabels: {
                                                            display: true,
                                                            align: 'end',
                                                            anchor: 'end',
                                                            color: '#64748b',
                                                            font: { weight: 'bold' }
                                                        }
                                                    },
                                                    scales: {
                                                        y: {
                                                            max: reportData.maintenance_monthly_trend?.length > 0 ? Math.max(...reportData.maintenance_monthly_trend.map((m: any) => m.total)) + 2 : 5,
                                                            beginAtZero: true, border: { display: false }, grid: { color: '#f1f5f9' }, ticks: { stepSize: 1 }
                                                        },
                                                        x: { grid: { display: false } }
                                                    },
                                                    layout: {
                                                        padding: { top: 20 }
                                                    }
                                                }}
                                            />
                                        </div>
                                    </div>

                                    <div className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                                        <h3 className="mb-4 font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                                            <AlertTriangle className="size-5 text-rose-500" />
                                            Aset Maintenance Bermasalah
                                        </h3>
                                        <div className="space-y-4 max-h-72 overflow-y-auto pr-2 custom-scrollbar">
                                            {reportData.maintenance_problem_assets?.length > 0 ? reportData.maintenance_problem_assets.map((asset: any, idx: number) => (
                                                <div key={idx} className="flex gap-4 p-4 rounded-xl border border-rose-100 bg-rose-50/50 dark:bg-rose-900/10 dark:border-rose-900/30">
                                                    <div className="size-8 rounded-lg bg-rose-100 dark:bg-rose-900/50 flex-shrink-0 flex items-center justify-center font-bold text-rose-600">{idx + 1}</div>
                                                    <div className="flex-1 min-w-0">
                                                        <p className="text-sm font-bold text-slate-800 dark:text-slate-200 truncate">{asset.merk} {asset.model}</p>
                                                        <div className="mb-2 text-[10px] text-slate-50 flex flex-wrap gap-2 items-center dark:text-slate-400">
                                                            <span className="font-medium text-slate-400">SN: {asset.serial_number}</span>
                                                            <span className="size-1 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                                            <span className="text-slate-600 dark:text-slate-300 flex items-center gap-1"><Users className="size-3" /> {asset.user}</span>
                                                            <span className="size-1 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                                            <span className="text-indigo-600 dark:text-indigo-400 text-[10px] bg-indigo-50 dark:bg-indigo-900/40 px-1.5 py-0.5 rounded-md font-medium">{asset.division}</span>
                                                        </div>

                                                        <div className="space-y-1.5">
                                                            {asset.problems.slice(0, 3).map((problem: string, pidx: number) => (
                                                                <div key={pidx} className="text-xs text-rose-600 dark:text-rose-400 flex items-start gap-1.5 line-clamp-2">
                                                                    <div className="mt-1 size-1.5 rounded-full bg-rose-400 flex-shrink-0" />
                                                                    {problem}
                                                                </div>
                                                            ))}
                                                            {asset.problems.length > 3 && (
                                                                <p className="text-xs text-slate-500 italic">+ {asset.problems.length - 3} kendala lainnya</p>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                            )) : (
                                                <div className="py-12 flex flex-col items-center justify-center text-slate-400">
                                                    <CheckCircle className="size-8 mb-2 text-emerald-500/50" />
                                                    <p className="text-sm">Tidak ada catatan aset bermasalah saat maintenance</p>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </>
                        ) : (
                            <div className="space-y-12">
                                {Object.entries(reportData?.maintenance_stats_by_division || {}).map(([divisionName, stats]: [string, any], divIdx) => (
                                    <div key={divIdx} className="space-y-6 bg-slate-50/50 dark:bg-slate-900/10 p-6 rounded-2xl border border-slate-100 dark:border-slate-800">
                                        <h4 className="font-black text-lg text-slate-800 dark:text-slate-100 border-b pb-3 dark:border-slate-700 flex items-center gap-2">
                                            <div className="size-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                                <Users className="size-4" />
                                            </div>
                                            {divisionName}
                                        </h4>
                                        <div className="grid gap-4 grid-cols-1 md:grid-cols-3">
                                            <StatCard
                                                label="Progress Maintenance"
                                                value={`${stats.metrics.compliance_rate}%`}
                                                icon={<CheckCircle className="size-6 text-emerald-600" />}
                                                color="text-emerald-600"
                                                subtitle={`${stats.metrics.confirmed_maintenances} / ${stats.metrics.total_maintenances} Selesai`}
                                            />
                                            <StatCard
                                                label="Total Asset"
                                                value={stats.metrics.total_maintenances}
                                                icon={<Wrench className="size-6 text-blue-600" />}
                                                color="text-blue-600"
                                            />
                                            <StatCard
                                                label="Rating"
                                                value={stats.metrics.avg_maintenance_rating}
                                                icon={<Star className="size-6 text-amber-500 fill-amber-500" />}
                                                color="text-amber-500"
                                            />
                                        </div>
                                        <div className="grid gap-6 lg:grid-cols-2">
                                            <div className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                                                <h3 className="mb-6 font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2 text-sm">
                                                    <BarChart3 className="size-4 text-primary" />
                                                    Tren Estimasi Bulanan ({divisionName})
                                                </h3>
                                                <div className="h-64">
                                                    <Bar
                                                        data={{
                                                            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                                                            datasets: [{
                                                                label: 'Jumlah Maintenance',
                                                                data: Array.from({ length: 12 }, (_, i) => {
                                                                    const found = (stats.monthly_trend || []).find((m: any) => Number(m.month) === i + 1);
                                                                    return found ? found.total : 0;
                                                                }),
                                                                backgroundColor: '#3b82f6',
                                                                borderRadius: 6,
                                                            }]
                                                        }}
                                                        options={{
                                                            maintainAspectRatio: false,
                                                            plugins: {
                                                                legend: { display: false },
                                                                datalabels: {
                                                                    display: true,
                                                                    align: 'end',
                                                                    anchor: 'end',
                                                                    color: '#64748b',
                                                                    font: { weight: 'bold' }
                                                                }
                                                            },
                                                            scales: {
                                                                y: {
                                                                    max: stats.monthly_trend?.length > 0 ? Math.max(...stats.monthly_trend.map((m: any) => m.total)) + 2 : 5,
                                                                    beginAtZero: true, border: { display: false }, grid: { color: '#f1f5f9' }, ticks: { stepSize: 1 }
                                                                },
                                                                x: { grid: { display: false } }
                                                            },
                                                            layout: { padding: { top: 20 } }
                                                        }}
                                                    />
                                                </div>
                                            </div>
                                            <div className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                                                <h3 className="mb-4 font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2 text-sm">
                                                    <AlertTriangle className="size-4 text-rose-500" />
                                                    Aset Bermasalah ({divisionName})
                                                </h3>
                                                <div className="space-y-4 max-h-64 overflow-y-auto pr-2 custom-scrollbar">
                                                    {stats.problem_assets?.length > 0 ? stats.problem_assets.map((asset: any, idx: number) => (
                                                        <div key={idx} className="flex gap-4 p-4 rounded-xl border border-rose-100 bg-rose-50/50 dark:bg-rose-900/10 dark:border-rose-900/30">
                                                            <div className="size-8 rounded-lg bg-rose-100 dark:bg-rose-900/50 flex-shrink-0 flex items-center justify-center font-bold text-rose-600">{idx + 1}</div>
                                                            <div className="flex-1 min-w-0">
                                                                <p className="text-sm font-bold text-slate-800 dark:text-slate-200 truncate">{asset.merk} {asset.model}</p>
                                                                <div className="mb-2 text-[10px] text-slate-50 flex flex-wrap gap-2 items-center dark:text-slate-400">
                                                                    <span className="font-medium text-slate-400">SN: {asset.serial_number}</span>
                                                                    <span className="size-1 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                                                    <span className="text-slate-600 dark:text-slate-300 flex items-center gap-1"><Users className="size-3" /> {asset.user}</span>
                                                                    <span className="size-1 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                                                    <span className="text-indigo-600 dark:text-indigo-400 text-[10px] bg-indigo-50 dark:bg-indigo-900/40 px-1.5 py-0.5 rounded-md font-medium">{asset.division}</span>
                                                                </div>
                                                                <div className="space-y-1.5">
                                                                    {asset.problems.slice(0, 3).map((problem: string, pidx: number) => (
                                                                        <div key={pidx} className="text-xs text-rose-600 dark:text-rose-400 flex items-start gap-1.5 line-clamp-2">
                                                                            <div className="mt-1 size-1.5 rounded-full bg-rose-400 flex-shrink-0" />
                                                                            {problem}
                                                                        </div>
                                                                    ))}
                                                                    {asset.problems.length > 3 && (
                                                                        <p className="text-xs text-slate-500 italic">+ {asset.problems.length - 3} kendala lainnya</p>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    )) : (
                                                        <div className="py-12 flex flex-col items-center justify-center text-slate-400">
                                                            <CheckCircle className="size-8 mb-2 text-emerald-500/50" />
                                                            <p className="text-sm">Tidak ada aset bermasalah di divisi ini</p>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}

                {activeTab === 'problems' && type === 'all' && (
                    <div className="space-y-6 animate-in fade-in duration-500">
                        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <h3 className="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                                <AlertTriangle className="size-5 text-primary" />
                                Laporan Pelaporan Masalah {viewMode === 'global' ? 'Keseluruhan' : 'Berdasarkan Divisi'}
                            </h3>
                            <select
                                className="text-sm rounded-lg border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-primary focus:border-primary px-3 py-2 w-full sm:w-auto"
                                value={viewMode}
                                onChange={(e) => setViewMode(e.target.value as 'global' | 'division')}
                            >
                                <option value="global">Tampilkan Berdasarkan Keseluruhan</option>
                                <option value="division">Tampilkan Berdasarkan Divisi</option>
                            </select>
                        </div>

                        {viewMode === 'global' ? (
                            <>
                                <div className="grid gap-4 grid-cols-1 md:grid-cols-3">
                                    <StatCard
                                        label="Total Laporan"
                                        value={reportData.metrics.total_tickets}
                                        icon={<Activity className="size-6 text-indigo-600" />}
                                        color="text-indigo-600"
                                    />
                                    <StatCard
                                        label="Total Aset Bermasalah"
                                        value={reportData.metrics.assets_with_tickets}
                                        icon={<AlertTriangle className="size-6 text-rose-600" />}
                                        color="text-rose-600"
                                    />
                                    <StatCard
                                        label="Rating Ticketing"
                                        value={reportData.metrics.avg_ticket_rating}
                                        icon={<Star className="size-6 text-amber-500 fill-amber-500" />}
                                        color="text-amber-500"
                                    />
                                </div>

                                <div className="grid gap-6 lg:grid-cols-2">
                                    <div className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                                        <h3 className="mb-6 font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                                            <BarChart3 className="size-5 text-primary" />
                                            Tren Jumlah Pelaporan Masalah Bulanan
                                        </h3>
                                        <div className="h-72">
                                            <Bar
                                                data={{
                                                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                                                    datasets: [{
                                                        label: 'Jumlah Laporan',
                                                        data: Array.from({ length: 12 }, (_, i) => {
                                                            const found = (reportData.monthly_trend || []).find((m: any) => Number(m.month) === i + 1);
                                                            return found ? found.total : 0;
                                                        }),
                                                        backgroundColor: '#3b82f6',
                                                        borderRadius: 6,
                                                    }]
                                                }}
                                                options={{
                                                    maintainAspectRatio: false,
                                                    plugins: {
                                                        legend: { display: false },
                                                        datalabels: {
                                                            display: true,
                                                            align: 'end',
                                                            anchor: 'end',
                                                            color: '#64748b',
                                                            font: { weight: 'bold' }
                                                        }
                                                    },
                                                    scales: {
                                                        y: {
                                                            max: reportData.monthly_trend?.length > 0 ? Math.max(...reportData.monthly_trend.map((m: any) => m.total)) + 2 : 5,
                                                            beginAtZero: true, border: { display: false }, grid: { color: '#f1f5f9' }, ticks: { stepSize: 1 }
                                                        },
                                                        x: { grid: { display: false } }
                                                    },
                                                    layout: { padding: { top: 20 } }
                                                }}
                                            />
                                        </div>
                                    </div>

                                    <div className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                                        <h3 className="mb-6 font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                                            <PieChart className="size-5 text-fuchsia-500" />
                                            Distribusi Berdasarkan Prioritas
                                        </h3>
                                        <div className="h-72">
                                            <Bar
                                                data={{
                                                    labels: (reportData.priority_stats || []).map((c: any) => c.priority),
                                                    datasets: [{
                                                        label: 'Total Pelaporan',
                                                        data: (reportData.priority_stats || []).map((c: any) => c.total),
                                                        backgroundColor: ['#ef4444', '#f59e0b', '#3b82f6', '#10b981', '#8b5cf6'],
                                                        borderRadius: 6,
                                                    }]
                                                }}
                                                options={{
                                                    maintainAspectRatio: false,
                                                    plugins: {
                                                        legend: { display: false },
                                                        datalabels: {
                                                            display: true,
                                                            align: 'end',
                                                            anchor: 'end',
                                                            color: '#64748b',
                                                            font: { weight: 'bold' }
                                                        }
                                                    },
                                                    scales: {
                                                        y: {
                                                            max: reportData.priority_stats?.length > 0 ? Math.max(...reportData.priority_stats.map((p: any) => p.total)) + 2 : 5,
                                                            beginAtZero: true, border: { display: false }, grid: { color: '#f1f5f9' }, ticks: { stepSize: 1 }
                                                        },
                                                        x: { grid: { display: false } }
                                                    },
                                                    layout: { padding: { top: 20 } }
                                                }}
                                            />
                                        </div>
                                    </div>

                                    <div className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                                        <h3 className="mb-4 font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                                            <Activity className="size-5 text-indigo-500" />
                                            Distribusi Status Ticketing
                                        </h3>
                                        <div className="space-y-4">
                                            {reportData.ticket_status_stats?.map((status: any, idx: number) => (
                                                <div key={idx} className="flex items-center justify-between p-3 rounded-lg border border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50">
                                                    <span className="font-medium text-slate-700 dark:text-slate-300 capitalize">
                                                        {{ 'pending': 'Pending', 'process': 'Proses', 'refinement': 'Perbaikan', 'finish': 'Selesai', 'closed': 'Closed' }[String(status.status).toLowerCase()] || status.status}
                                                    </span>
                                                    <span className="font-bold text-indigo-600 dark:text-indigo-400">{status.total} Tiket</span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>

                                    <div className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                                        <h3 className="mb-4 font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                                            <AlertTriangle className="size-5 text-rose-500" />
                                            Aset Paling Sering Bermasalah
                                        </h3>
                                        <div className="space-y-4 mt-4">
                                            {reportData.top_problem_assets?.length > 0 ? reportData.top_problem_assets.map((asset: any, idx: number) => (
                                                <div key={idx} className="flex items-center gap-4">
                                                    <div className="size-8 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center font-bold text-slate-500">{idx + 1}</div>
                                                    <div className="flex-1 min-w-0">
                                                        <p className="text-sm font-medium truncate">{asset.merk} {asset.model}</p>
                                                        <p className="text-[10px] text-slate-400">{asset.serial_number}</p>
                                                    </div>
                                                    <div className="text-right">
                                                        <p className="text-sm font-bold text-rose-600">
                                                            {asset.tickets_count + (asset.refinement_maintenances_count || 0)} Kendala
                                                        </p>
                                                    </div>
                                                </div>
                                            )) : (
                                                <div className="py-8 text-center text-slate-400 text-sm">Tidak ada aset bermasalah</div>
                                            )}
                                        </div>
                                    </div>

                                </div>
                            </>
                        ) : (
                            <div className="space-y-12">
                                {Object.entries(reportData?.ticket_stats_by_division || {}).map(([divisionName, stats]: [string, any], divIdx) => (
                                    <div key={divIdx} className="space-y-6 bg-slate-50/50 dark:bg-slate-900/10 p-6 rounded-2xl border border-slate-100 dark:border-slate-800">
                                        <h4 className="font-black text-lg text-slate-800 dark:text-slate-100 border-b pb-3 dark:border-slate-700 flex items-center gap-2">
                                            <div className="size-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                                <Users className="size-4" />
                                            </div>
                                            {divisionName}
                                        </h4>
                                        <div className="grid gap-4 grid-cols-1 md:grid-cols-3">
                                            <StatCard
                                                label="Total Laporan"
                                                value={stats.metrics.total_tickets}
                                                icon={<Activity className="size-6 text-indigo-600" />}
                                                color="text-indigo-600"
                                            />
                                            <StatCard
                                                label="Total Aset Bermasalah"
                                                value={stats.metrics.assets_with_tickets}
                                                icon={<AlertTriangle className="size-6 text-rose-600" />}
                                                color="text-rose-600"
                                            />
                                            <StatCard
                                                label="Rating Ticketing"
                                                value={stats.metrics.avg_ticket_rating}
                                                icon={<Star className="size-6 text-amber-500 fill-amber-500" />}
                                                color="text-amber-500"
                                            />
                                        </div>
                                        <div className="grid gap-6 lg:grid-cols-2">
                                            <div className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                                                <h3 className="mb-6 font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2 text-sm">
                                                    <BarChart3 className="size-4 text-primary" />
                                                    Tren Bulanan ({divisionName})
                                                </h3>
                                                <div className="h-64">
                                                    <Bar
                                                        data={{
                                                            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                                                            datasets: [{
                                                                label: 'Jumlah Laporan',
                                                                data: Array.from({ length: 12 }, (_, i) => {
                                                                    const found = (stats.monthly_trend || []).find((m: any) => Number(m.month) === i + 1);
                                                                    return found ? found.total : 0;
                                                                }),
                                                                backgroundColor: '#3b82f6',
                                                                borderRadius: 6,
                                                            }]
                                                        }}
                                                        options={{
                                                            maintainAspectRatio: false,
                                                            plugins: {
                                                                legend: { display: false },
                                                                datalabels: {
                                                                    display: true,
                                                                    align: 'end',
                                                                    anchor: 'end',
                                                                    color: '#64748b',
                                                                    font: { weight: 'bold' }
                                                                }
                                                            },
                                                            scales: {
                                                                y: {
                                                                    max: stats.monthly_trend?.length > 0 ? Math.max(...stats.monthly_trend.map((m: any) => m.total)) + 2 : 5,
                                                                    beginAtZero: true, border: { display: false }, grid: { color: '#f1f5f9' }, ticks: { stepSize: 1 }
                                                                },
                                                                x: { grid: { display: false } }
                                                            }
                                                        }}
                                                    />
                                                </div>
                                            </div>

                                            <div className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                                                <h3 className="mb-6 font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2 text-sm">
                                                    <PieChart className="size-4 text-fuchsia-500" />
                                                    Prioritas ({divisionName})
                                                </h3>
                                                <div className="h-64">
                                                    <Bar
                                                        data={{
                                                            labels: (stats.priority_stats || []).map((c: any) => c.priority),
                                                            datasets: [{
                                                                label: 'Total Pelaporan',
                                                                data: (stats.priority_stats || []).map((c: any) => c.total),
                                                                backgroundColor: ['#ef4444', '#f59e0b', '#3b82f6', '#10b981', '#8b5cf6'],
                                                                borderRadius: 6,
                                                            }]
                                                        }}
                                                        options={{
                                                            maintainAspectRatio: false,
                                                            plugins: {
                                                                legend: { display: false },
                                                                datalabels: {
                                                                    display: true,
                                                                    align: 'end',
                                                                    anchor: 'end',
                                                                    color: '#64748b',
                                                                    font: { weight: 'bold' }
                                                                }
                                                            },
                                                            scales: {
                                                                y: {
                                                                    max: stats.priority_stats?.length > 0 ? Math.max(...stats.priority_stats.map((p: any) => p.total)) + 2 : 5,
                                                                    beginAtZero: true, border: { display: false }, grid: { color: '#f1f5f9' }, ticks: { stepSize: 1 }
                                                                },
                                                                x: { grid: { display: false } }
                                                            }
                                                        }}
                                                    />
                                                </div>
                                            </div>

                                            <div className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                                                <h3 className="mb-4 font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2 text-sm">
                                                    <Activity className="size-4 text-indigo-500" />
                                                    Status ({divisionName})
                                                </h3>
                                                <div className="space-y-4">
                                                    {stats.status_stats?.map((status: any, idx: number) => (
                                                        <div key={idx} className="flex items-center justify-between p-3 rounded-lg border border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50">
                                                            <span className="font-medium text-slate-700 dark:text-slate-300 capitalize text-sm">
                                                                {{ 'pending': 'Pending', 'process': 'Proses', 'refinement': 'Perbaikan', 'finish': 'Selesai', 'closed': 'Closed' }[String(status.status).toLowerCase()] || status.status}
                                                            </span>
                                                            <span className="font-bold text-indigo-600 dark:text-indigo-400 text-sm">{status.total} Tiket</span>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>

                                            <div className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                                                <h3 className="mb-4 font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2 text-sm">
                                                    <AlertTriangle className="size-4 text-rose-500" />
                                                    Aset Bermasalah ({divisionName})
                                                </h3>
                                                <div className="space-y-4 mt-4 max-h-64 overflow-y-auto pr-2 custom-scrollbar">
                                                    {stats.top_problem_assets?.length > 0 ? stats.top_problem_assets.map((asset: any, idx: number) => (
                                                        <div key={idx} className="flex items-center gap-4">
                                                            <div className="size-8 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center font-bold text-slate-500">{idx + 1}</div>
                                                            <div className="flex-1 min-w-0">
                                                                <p className="text-sm font-medium truncate">{asset.merk} {asset.model}</p>
                                                                <p className="text-[10px] text-slate-400">{asset.serial_number}</p>
                                                            </div>
                                                            <div className="text-right">
                                                                <p className="text-sm font-bold text-rose-600">
                                                                    {asset.tickets_count + (asset.refinement_maintenances_count || 0)} Kendala
                                                                </p>
                                                            </div>
                                                        </div>
                                                    )) : (
                                                        <div className="py-8 text-center text-slate-400 text-sm">Tidak ada aset bermasalah</div>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}

            </ContentCard>
        </RootLayout>
    );
}
