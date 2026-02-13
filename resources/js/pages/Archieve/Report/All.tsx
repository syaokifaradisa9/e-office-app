import RootLayout from '@/components/layouts/RootLayout';
import ContentCard from '@/components/layouts/ContentCard';
import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    FileText,
    HardDrive,
    Building2,
    TrendingUp,
    TrendingDown,
    Users,
    Clock,
    AlertTriangle,
    BarChart3,
    Globe,
    ChevronDown,
    ChevronUp,
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

interface GlobalReport {
    overview_stats: {
        total_documents: number;
        total_size: number;
        total_size_label: string;
        this_month_documents: number;
        growth_rate: number;
    };
    upload_trend: Array<{
        month: string;
        total_documents: number;
        total_size: number;
    }>;
    category_rankings: {
        most_documents: Array<{ id: number; name: string; count: number }>;
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
        file_size_label: string;
    }>;
    division_comparison: Array<{
        division_id: number;
        division_name: string;
        document_count: number;
        total_size_label: string;
    }>;
}

interface DivisionReport {
    division_id: number;
    division_name: string;
    overview_stats: {
        total_documents: number;
        total_size_label: string;
    };
    upload_trend: Array<{ month: string; total_documents: number }>;
    category_rankings: {
        most_documents: Array<{ name: string; count: number }>;
    };
    file_type_distribution: Array<{ label: string; count: number }>;
}

interface ReportData {
    global: GlobalReport;
    per_division: DivisionReport[];
}

interface PageProps {
    reportData: ReportData;
    permissions: string[];
    [key: string]: unknown;
}

export default function ArchieveReportAll() {
    const { reportData } = usePage<PageProps>().props;
    const globalData = reportData.global;
    const [expandedDivision, setExpandedDivision] = useState<number | null>(null);

    const trendChartData = {
        labels: globalData.upload_trend?.map((t) => t.month) || [],
        datasets: [
            {
                label: 'Dokumen Diunggah',
                data: globalData.upload_trend?.map((t) => t.total_documents) || [],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.3,
                fill: true,
            },
        ],
    };

    const divisionComparisonData = {
        labels: globalData.division_comparison?.map((d) => d.division_name) || [],
        datasets: [
            {
                label: 'Jumlah Dokumen',
                data: globalData.division_comparison?.map((d) => d.document_count) || [],
                backgroundColor: '#3b82f6',
                borderRadius: 8,
            },
        ],
    };

    const classificationChartData = {
        labels: globalData.classification_rankings.most_documents?.slice(0, 5).map((c) => c.name) || [],
        datasets: [
            {
                data: globalData.classification_rankings.most_documents?.slice(0, 5).map((c) => c.count) || [],
                backgroundColor: [
                    '#3b82f6',
                    '#8b5cf6',
                    '#ec4899',
                    '#f97316',
                    '#10b981',
                ],
            },
        ],
    };

    const fileTypeChartData = {
        labels: globalData.file_type_distribution?.map((f) => f.label) || [],
        datasets: [
            {
                data: globalData.file_type_distribution?.map((f) => f.count) || [],
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
        <RootLayout title="Laporan Arsip Keseluruhan">
            <ContentCard
                title="Laporan Arsip Keseluruhan"
                subtitle="Analisis komprehensif seluruh arsip digital organisasi"
            >
                <div className="space-y-8">
                    {/* Global Overview Stats */}
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <StatCard
                            icon={FileText}
                            label="Total Dokumen"
                            value={globalData.overview_stats.total_documents.toString()}
                            color="primary"
                        />
                        <StatCard
                            icon={HardDrive}
                            label="Total Penyimpanan"
                            value={globalData.overview_stats.total_size_label}
                            color="info"
                        />
                        <StatCard
                            icon={Building2}
                            label="Total Divisi"
                            value={reportData.per_division?.length.toString() || '0'}
                            color="purple"
                        />
                        <StatCard
                            icon={globalData.overview_stats.growth_rate >= 0 ? TrendingUp : TrendingDown}
                            label="Pertumbuhan"
                            value={`${globalData.overview_stats.growth_rate >= 0 ? '+' : ''}${globalData.overview_stats.growth_rate}%`}
                            sublabel="vs bulan lalu"
                            color={globalData.overview_stats.growth_rate >= 0 ? 'success' : 'danger'}
                        />
                    </div>

                    {/* Upload Trend */}
                    {globalData.upload_trend && globalData.upload_trend.length > 0 && (
                        <div className="rounded-2xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-700 dark:bg-slate-800/50">
                            <h3 className="mb-4 text-base font-bold text-slate-900 dark:text-white">
                                Tren Upload Organisasi (12 Bulan Terakhir)
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

                    {/* Division Comparison */}
                    {globalData.division_comparison && globalData.division_comparison.length > 0 && (
                        <div className="rounded-2xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-700 dark:bg-slate-800/50">
                            <h3 className="mb-4 text-base font-bold text-slate-900 dark:text-white">
                                Perbandingan Arsip per Divisi
                            </h3>
                            <div className="h-[280px]">
                                <Bar
                                    data={divisionComparisonData}
                                    options={{
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        indexAxis: 'y',
                                        plugins: { legend: { display: false } },
                                    }}
                                />
                            </div>
                        </div>
                    )}

                    <div className="grid gap-6 lg:grid-cols-2">
                        {/* Classification Distribution */}
                        <div className="rounded-2xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-700 dark:bg-slate-800/50">
                            <h3 className="mb-4 text-base font-bold text-slate-900 dark:text-white">
                                Top Klasifikasi Dokumen
                            </h3>
                            <div className="mx-auto max-w-[220px]">
                                <Doughnut
                                    data={classificationChartData}
                                    options={{
                                        responsive: true,
                                        plugins: { legend: { position: 'bottom' } },
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
                                Top Uploader Organisasi
                            </h3>
                            <div className="space-y-2">
                                {globalData.top_uploaders?.map((user, index) => (
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
                            </div>
                        </div>

                        {/* Largest Documents */}
                        <div className="rounded-2xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-700 dark:bg-slate-800/50">
                            <h3 className="mb-4 flex items-center gap-2 text-base font-bold text-slate-900 dark:text-white">
                                <HardDrive className="size-4" />
                                Dokumen Terbesar
                            </h3>
                            <div className="space-y-2">
                                {globalData.largest_documents?.slice(0, 5).map((doc, index) => (
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

                    {/* Stagnant Documents Alert */}
                    {globalData.stagnant_documents && globalData.stagnant_documents.length > 0 && (
                        <div className="rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-900/30 dark:bg-amber-900/10">
                            <h3 className="mb-4 flex items-center gap-2 text-base font-bold text-amber-800 dark:text-amber-400">
                                <AlertTriangle className="size-4" />
                                Dokumen Tidak Aktif ({'>'}6 Bulan)
                            </h3>
                            <div className="grid gap-2 md:grid-cols-2">
                                {globalData.stagnant_documents.slice(0, 6).map((doc) => (
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

                    {/* Per Division Details */}
                    <div className="rounded-2xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-700 dark:bg-slate-800/50">
                        <h3 className="mb-4 text-base font-bold text-slate-900 dark:text-white">
                            Detail per Divisi
                        </h3>
                        <div className="space-y-2">
                            {reportData.per_division?.map((division) => (
                                <div
                                    key={division.division_id}
                                    className="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900/50"
                                >
                                    <button
                                        onClick={() =>
                                            setExpandedDivision(
                                                expandedDivision === division.division_id
                                                    ? null
                                                    : division.division_id
                                            )
                                        }
                                        className="flex w-full items-center justify-between p-4 text-left transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50"
                                    >
                                        <div className="flex items-center gap-3">
                                            <Building2 className="size-4 text-slate-400" />
                                            <div>
                                                <p className="font-bold text-slate-900 dark:text-white">
                                                    {division.division_name}
                                                </p>
                                                <p className="text-xs text-slate-500">
                                                    {division.overview_stats.total_documents} dokumen •{' '}
                                                    {division.overview_stats.total_size_label}
                                                </p>
                                            </div>
                                        </div>
                                        {expandedDivision === division.division_id ? (
                                            <ChevronUp className="size-4 text-slate-400" />
                                        ) : (
                                            <ChevronDown className="size-4 text-slate-400" />
                                        )}
                                    </button>
                                    {expandedDivision === division.division_id && (
                                        <div className="border-t border-slate-200 p-4 dark:border-slate-700">
                                            <div className="grid gap-4 md:grid-cols-2">
                                                <div>
                                                    <p className="mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                                                        Top Kategori
                                                    </p>
                                                    <div className="space-y-1">
                                                        {division.category_rankings.most_documents
                                                            ?.slice(0, 3)
                                                            .map((cat, i) => (
                                                                <div
                                                                    key={i}
                                                                    className="flex items-center justify-between text-sm"
                                                                >
                                                                    <span className="text-slate-600 dark:text-slate-300">
                                                                        {cat.name}
                                                                    </span>
                                                                    <span className="font-bold text-primary">
                                                                        {cat.count}
                                                                    </span>
                                                                </div>
                                                            ))}
                                                    </div>
                                                </div>
                                                <div>
                                                    <p className="mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                                                        Tipe File
                                                    </p>
                                                    <div className="space-y-1">
                                                        {division.file_type_distribution
                                                            ?.slice(0, 3)
                                                            .map((ft, i) => (
                                                                <div
                                                                    key={i}
                                                                    className="flex items-center justify-between text-sm"
                                                                >
                                                                    <span className="text-slate-600 dark:text-slate-300">
                                                                        {ft.label}
                                                                    </span>
                                                                    <span className="font-bold text-slate-500">
                                                                        {ft.count}
                                                                    </span>
                                                                </div>
                                                            ))}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
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
    color?: 'primary' | 'success' | 'warning' | 'danger' | 'info' | 'purple';
}) {
    const colorClasses = {
        primary: 'bg-primary/10 text-primary',
        success: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400',
        warning: 'bg-amber-100 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400',
        danger: 'bg-rose-100 text-rose-600 dark:bg-rose-900/20 dark:text-rose-400',
        info: 'bg-sky-100 text-sky-600 dark:bg-sky-900/20 dark:text-sky-400',
        purple: 'bg-violet-100 text-violet-600 dark:bg-violet-900/20 dark:text-violet-400',
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
