import RootLayout from '@/components/layouts/RootLayout';
import ContentCard from '@/components/layouts/ContentCard';
import { usePage } from '@inertiajs/react';
import {
    FileText,
    HardDrive,
    TrendingUp,
    TrendingDown,
    Users,
    Clock,
    AlertTriangle,
    BarChart3,
} from 'lucide-react';
import { ArchievePermission } from '@/enums/ArchievePermission';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    LineElement,
    PointElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';
import { Bar, Doughnut, Line } from 'react-chartjs-2';

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    LineElement,
    PointElement,
    ArcElement,
    Title,
    Tooltip,
    Legend
);

interface ReportData {
    division_name: string;
    overview_stats: {
        total_documents: number;
        total_size: number;
        total_size_label: string;
        this_month_documents: number;
        last_month_documents: number;
        growth_rate: number;
        storage_used: number;
        storage_max: number;
        storage_percentage: number;
    };
    upload_trend: Array<{
        month: string;
        total_documents: number;
        total_size: number;
    }>;
    category_rankings: {
        most_documents: Array<{ id: number; name: string; count: number }>;
        least_documents: Array<{ id: number; name: string; count: number }>;
    };
    classification_rankings: {
        most_documents: Array<{ id: number; code: string; name: string; count: number }>;
    };
    file_type_distribution: Array<{
        type: string;
        label: string;
        count: number;
        total_size_label: string;
    }>;
    stagnant_documents: Array<{
        id: number;
        title: string;
        file_size_label: string;
        last_activity: string;
    }>;
    top_uploaders: Array<{
        user_id: number;
        user_name: string;
        total_documents: number;
        total_size_label: string;
    }>;
    largest_documents: Array<{
        id: number;
        title: string;
        classification: string;
        uploader: string;
        file_size_label: string;
        created_at: string;
    }>;
}

interface PageProps {
    reportData: ReportData;
    permissions: string[];
    [key: string]: unknown;
}

export default function ArchieveReportDivision() {
    const { reportData } = usePage<PageProps>().props;
    const stats = reportData.overview_stats;

    const trendChartData = {
        labels: reportData.upload_trend?.map((t) => t.month) || [],
        datasets: [
            {
                label: 'Dokumen Diunggah',
                data: reportData.upload_trend?.map((t) => t.total_documents) || [],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.3,
                fill: true,
            },
        ],
    };

    const categoryChartData = {
        labels: reportData.category_rankings.most_documents?.slice(0, 5).map((c) => c.name) || [],
        datasets: [
            {
                label: 'Jumlah Dokumen',
                data: reportData.category_rankings.most_documents?.slice(0, 5).map((c) => c.count) || [],
                backgroundColor: '#3b82f6',
                borderRadius: 8,
            },
        ],
    };

    const fileTypeChartData = {
        labels: reportData.file_type_distribution?.map((f) => f.label) || [],
        datasets: [
            {
                data: reportData.file_type_distribution?.map((f) => f.count) || [],
                backgroundColor: [
                    '#ef4444',
                    '#3b82f6',
                    '#10b981',
                    '#f97316',
                    '#8b5cf6',
                    '#ec4899',
                    '#06b6d4',
                    '#eab308',
                ],
            },
        ],
    };

    return (
        <RootLayout title={`Laporan Arsip - ${reportData.division_name}`}>
            <ContentCard
                title="Laporan Arsip Divisi"
                subtitle={`Analisis lengkap arsip digital untuk ${reportData.division_name}`}
            >
                <div className="space-y-8">
                    {/* Overview Stats */}
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <StatCard
                            icon={FileText}
                            label="Total Dokumen"
                            value={stats.total_documents.toString()}
                            color="primary"
                        />
                        <StatCard
                            icon={HardDrive}
                            label="Penyimpanan"
                            value={stats.total_size_label}
                            sublabel={stats.storage_max > 0 ? `${stats.storage_percentage}% terpakai` : 'Unlimited'}
                            color={stats.storage_percentage >= 90 ? 'danger' : stats.storage_percentage >= 70 ? 'warning' : 'success'}
                        />
                        <StatCard
                            icon={BarChart3}
                            label="Bulan Ini"
                            value={stats.this_month_documents.toString()}
                            sublabel="dokumen baru"
                            color="info"
                        />
                        <StatCard
                            icon={stats.growth_rate >= 0 ? TrendingUp : TrendingDown}
                            label="Pertumbuhan"
                            value={`${stats.growth_rate >= 0 ? '+' : ''}${stats.growth_rate}%`}
                            sublabel="vs bulan lalu"
                            color={stats.growth_rate >= 0 ? 'success' : 'danger'}
                        />
                    </div>

                    {/* Upload Trend */}
                    {reportData.upload_trend && reportData.upload_trend.length > 0 && (
                        <div className="rounded-2xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-700 dark:bg-slate-800/50">
                            <h3 className="mb-4 text-base font-bold text-slate-900 dark:text-white">
                                Tren Upload Bulanan (12 Bulan Terakhir)
                            </h3>
                            <div className="h-[280px]">
                                <Line
                                    data={trendChartData}
                                    options={{
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: { legend: { display: false } },
                                        scales: { y: { beginAtZero: true } },
                                    }}
                                />
                            </div>
                        </div>
                    )}

                    <div className="grid gap-6 lg:grid-cols-2">
                        {/* Category Rankings */}
                        <div className="rounded-2xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-700 dark:bg-slate-800/50">
                            <h3 className="mb-4 text-base font-bold text-slate-900 dark:text-white">
                                Kategori Terpopuler
                            </h3>
                            <div className="h-[220px]">
                                <Bar
                                    data={categoryChartData}
                                    options={{
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        indexAxis: 'y',
                                        plugins: { legend: { display: false } },
                                    }}
                                />
                            </div>
                        </div>

                        {/* File Type Distribution */}
                        <div className="rounded-2xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-700 dark:bg-slate-800/50">
                            <h3 className="mb-4 text-base font-bold text-slate-900 dark:text-white">
                                Distribusi Tipe File
                            </h3>
                            <div className="mx-auto max-w-[220px]">
                                <Doughnut
                                    data={fileTypeChartData}
                                    options={{
                                        responsive: true,
                                        plugins: { legend: { position: 'bottom' } },
                                    }}
                                />
                            </div>
                        </div>
                    </div>

                    <div className="grid gap-6 lg:grid-cols-2">
                        {/* Top Uploaders */}
                        <div className="rounded-2xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-700 dark:bg-slate-800/50">
                            <h3 className="mb-4 flex items-center gap-2 text-base font-bold text-slate-900 dark:text-white">
                                <Users className="size-4" />
                                Top Uploader
                            </h3>
                            <div className="space-y-2">
                                {reportData.top_uploaders?.map((user, index) => (
                                    <div
                                        key={index}
                                        className="flex items-center gap-3 rounded-xl bg-white p-3 dark:bg-slate-900/50"
                                    >
                                        <div className="flex size-7 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary">
                                            {index + 1}
                                        </div>
                                        <div className="flex-1">
                                            <p className="text-sm font-bold text-slate-900 dark:text-white">
                                                {user.user_name}
                                            </p>
                                            <p className="text-xs text-slate-500">{user.total_size_label}</p>
                                        </div>
                                        <span className="text-sm font-bold text-primary">
                                            {user.total_documents}
                                        </span>
                                    </div>
                                ))}
                                {(!reportData.top_uploaders || reportData.top_uploaders.length === 0) && (
                                    <p className="py-6 text-center text-sm text-slate-500">Tidak ada data</p>
                                )}
                            </div>
                        </div>

                        {/* Largest Documents */}
                        <div className="rounded-2xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-700 dark:bg-slate-800/50">
                            <h3 className="mb-4 flex items-center gap-2 text-base font-bold text-slate-900 dark:text-white">
                                <HardDrive className="size-4" />
                                Dokumen Terbesar
                            </h3>
                            <div className="space-y-2">
                                {reportData.largest_documents?.slice(0, 5).map((doc, index) => (
                                    <div
                                        key={index}
                                        className="flex items-center gap-3 rounded-xl bg-white p-3 dark:bg-slate-900/50"
                                    >
                                        <div className="rounded-lg bg-rose-100 p-2 text-rose-600 dark:bg-rose-900/20">
                                            <FileText className="size-3.5" />
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-bold text-slate-900 dark:text-white">
                                                {doc.title}
                                            </p>
                                            <p className="text-xs text-slate-500">{doc.classification}</p>
                                        </div>
                                        <span className="text-sm font-bold text-rose-500">
                                            {doc.file_size_label}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* Stagnant Documents */}
                    {reportData.stagnant_documents && reportData.stagnant_documents.length > 0 && (
                        <div className="rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-900/30 dark:bg-amber-900/10">
                            <h3 className="mb-4 flex items-center gap-2 text-base font-bold text-amber-800 dark:text-amber-400">
                                <AlertTriangle className="size-4" />
                                Dokumen Tidak Aktif ({'>'}6 Bulan)
                            </h3>
                            <div className="space-y-2">
                                {reportData.stagnant_documents.map((doc) => (
                                    <div
                                        key={doc.id}
                                        className="flex items-center gap-3 rounded-xl bg-white p-3 dark:bg-slate-900/50"
                                    >
                                        <Clock className="size-4 text-amber-500" />
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-medium text-slate-900 dark:text-white">
                                                {doc.title}
                                            </p>
                                        </div>
                                        <span className="text-xs text-slate-500">{doc.last_activity}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </ContentCard>
        </RootLayout>
    );
}

function StatCard({
    icon: Icon,
    label,
    value,
    sublabel,
    color = 'primary',
}: {
    icon: React.ElementType;
    label: string;
    value: string;
    sublabel?: string;
    color?: 'primary' | 'success' | 'warning' | 'danger' | 'info';
}) {
    const colorClasses = {
        primary: 'bg-primary/10 text-primary',
        success: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400',
        warning: 'bg-amber-100 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400',
        danger: 'bg-rose-100 text-rose-600 dark:bg-rose-900/20 dark:text-rose-400',
        info: 'bg-sky-100 text-sky-600 dark:bg-sky-900/20 dark:text-sky-400',
    };

    return (
        <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/50">
            <div className="flex items-start gap-3">
                <div className={`rounded-xl p-2.5 ${colorClasses[color]}`}>
                    <Icon className="size-5" />
                </div>
                <div>
                    <p className="text-[10px] font-bold uppercase tracking-widest text-slate-400">{label}</p>
                    <p className="mt-0.5 text-xl font-black text-slate-900 dark:text-white">{value}</p>
                    {sublabel && <p className="text-xs text-slate-500">{sublabel}</p>}
                </div>
            </div>
        </div>
    );
}
