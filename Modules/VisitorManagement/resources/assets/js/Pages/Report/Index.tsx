import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import UiTooltip from '@/components/commons/Tooltip';
import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    Users,
    TrendingUp,
    TrendingDown,
    CheckCircle,
    XCircle,
    Clock,
    Star,
    Building2,
    ClipboardCheck,
    FileSpreadsheet,
    BarChart,
    PieChart,
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

interface MonthlyTrend {
    month: string;
    total_visitors: number;
    completed: number;
    rejected: number;
}

interface Ranking {
    name: string;
    total: number;
}

interface QuestionStat {
    question: string;
    avg_rating: number;
    total: number;
}

interface ReportData {
    overview_stats: {
        total_visitors_year: number;
        total_visitors_month: number;
        total_visitors_today: number;
        approved_count: number;
        rejected_count: number;
        completed_count: number;
        pending_count: number;
        average_rating: number;
    };
    monthly_trend: MonthlyTrend[];
    status_distribution: Record<string, number>;
    purpose_rankings: {
        most_visited: Ranking[];
        least_visited: Ranking[];
    };
    division_rankings: {
        most_visited: Ranking[];
        least_visited: Ranking[];
    };
    feedback_stats: {
        rating_distribution: Record<number, number>;
        question_stats: QuestionStat[];
        average_rating: number;
        total_feedbacks: number;
    };
    peak_hours: Record<number, number>;
    busiest_days: { day: string; count: number }[];
    top_organizations: { organization: string; total: number }[];
    average_duration: { minutes: number; formatted: string };
    month_comparison: { this_month: number; last_month: number; change_percent: number; trend: string };
    repeat_visitors: { name: string; phone: string; visit_count: number }[];
    active_visitors: number;
}

interface PageProps {
    reportData: ReportData;
    [key: string]: unknown;
}

export default function ReportIndex() {
    const { reportData } = usePage<PageProps>().props;
    const [activeTab, setActiveTab] = useState<'overview' | 'visitors' | 'feedback'>('overview');

    if (!reportData) return null;

    const {
        overview_stats,
        monthly_trend,
        status_distribution,
        purpose_rankings,
        division_rankings,
        feedback_stats,
        peak_hours,
        busiest_days,
        top_organizations,
        average_duration,
        month_comparison,
        repeat_visitors,
        active_visitors,
    } = reportData;

    const tabs = [
        { id: 'overview', label: 'Ringkasan', icon: <BarChart className="size-4" /> },
        { id: 'visitors', label: 'Data Pengunjung', icon: <Users className="size-4" /> },
        { id: 'feedback', label: 'Feedback', icon: <Star className="size-4" /> },
    ];

    const statusLabels: Record<string, string> = {
        'pending': 'Menunggu',
        'approved': 'Disetujui',
        'rejected': 'Ditolak',
        'completed': 'Selesai',
        'invited': 'Diundang',
        'cancelled': 'Dibatalkan',
    };

    const statusColors: Record<string, string> = {
        'pending': '#f59e0b',
        'approved': '#10b981',
        'rejected': '#ef4444',
        'completed': '#3b82f6',
        'invited': '#8b5cf6',
        'cancelled': '#6b7280',
    };

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

    const SimpleList = ({ title, items, icon, color = 'text-slate-700', emptyMessage = "Data tidak tersedia" }: { title: string; items: Ranking[]; icon?: React.ReactNode; color?: string; emptyMessage?: string }) => (
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
                                {item.total} kunjungan
                            </span>
                        </div>
                    ))
                ) : (
                    <p className="py-4 text-center text-xs text-slate-400 italic">{emptyMessage}</p>
                )}
            </div>
        </div>
    );

    return (
        <RootLayout title="Laporan Pengunjung">
            <ContentCard
                title="Laporan & Statistik Pengunjung"
                subtitle="Analisis data kunjungan, tren bulanan, dan feedback pengunjung"
                mobileFullWidth={false}
                bodyClassName="p-6 md:p-8"
                additionalButton={
                    <UiTooltip text="Cetak Laporan">
                        <Button
                            href="/visitor/reports/export"
                            variant="primary"
                            label="Cetak Excel"
                            icon={<FileSpreadsheet className="size-4" />}
                            target="_blank"
                        />
                    </UiTooltip>
                }
            >
                {/* Tabs */}
                <div className="mb-8 flex gap-6 border-b border-slate-200 dark:border-slate-700">
                    {tabs.map((tab) => (
                        <button
                            key={tab.id}
                            onClick={() => setActiveTab(tab.id as typeof activeTab)}
                            className={`relative flex items-center gap-2 pb-3 text-sm font-medium transition-colors ${activeTab === tab.id
                                ? 'text-primary'
                                : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'
                                }`}
                        >
                            {tab.icon}
                            {tab.label}
                            {activeTab === tab.id && (
                                <span className="absolute bottom-0 left-0 h-0.5 w-full bg-primary" />
                            )}
                        </button>
                    ))}
                </div>

                {activeTab === 'overview' && (
                    <div className="space-y-8 animate-in fade-in duration-500">
                        {/* Average Duration - Top */}
                        <div className="rounded-xl border border-slate-200 bg-gradient-to-br from-teal-50 to-emerald-50 p-5 dark:border-slate-700 dark:from-teal-900/20 dark:to-emerald-900/20">
                            <div className="flex items-center gap-4">
                                <div className="flex size-14 items-center justify-center rounded-xl bg-teal-500/20 text-teal-600">
                                    <Clock className="size-7" />
                                </div>
                                <div>
                                    <p className="text-xs font-medium text-slate-500 uppercase tracking-wider dark:text-slate-400">Rata-rata Durasi Kunjungan</p>
                                    <p className="text-2xl font-bold text-teal-700 dark:text-teal-400">{average_duration?.formatted || '0 menit'}</p>
                                </div>
                            </div>
                        </div>

                        {/* Stats Grid */}
                        <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                            <StatCard
                                label="Total Pengunjung Tahun Ini"
                                value={overview_stats.total_visitors_year}
                                icon={<Users className="size-5 text-primary" />}
                                color="text-primary"
                            />
                            <StatCard
                                label="Pengunjung Bulan Ini"
                                value={overview_stats.total_visitors_month}
                                icon={<TrendingUp className="size-5 text-emerald-600" />}
                                color="text-emerald-600"
                            />
                            <StatCard
                                label="Pengunjung Hari Ini"
                                value={overview_stats.total_visitors_today}
                                icon={<Clock className="size-5 text-blue-600" />}
                                color="text-blue-600"
                            />
                            <StatCard
                                label="Rata-rata Rating"
                                value={overview_stats.average_rating}
                                icon={<Star className="size-5 text-amber-500" />}
                                color="text-amber-500"
                            />
                        </div>

                        {/* Status Stats */}
                        <div>
                            <h3 className="mb-4 text-sm font-bold text-slate-400 uppercase tracking-widest">Status Kunjungan Tahun Ini</h3>
                            <div className="grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-6">
                                {Object.entries(status_distribution).map(([status, count]) => (
                                    <div key={status} className="rounded-xl border border-slate-100 bg-white p-3 text-center dark:border-slate-700 dark:bg-slate-800">
                                        <p className="text-[10px] font-bold text-slate-400 uppercase tracking-tighter truncate">{statusLabels[status] || status}</p>
                                        <p className="text-xl font-black" style={{ color: statusColors[status] || '#64748b' }}>{count}</p>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Monthly Trend Chart */}
                        <div className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                            <h3 className="mb-6 flex items-center gap-2 font-bold text-slate-800 dark:text-slate-200 text-lg">
                                <TrendingUp className="size-5 text-primary" />
                                Tren Pengunjung Bulanan
                            </h3>
                            <div className="h-[300px]">
                                <Line
                                    data={{
                                        labels: Array.from({ length: 12 }, (_, i) =>
                                            new Date(0, i).toLocaleDateString('id-ID', { month: 'short' })
                                        ),
                                        datasets: [
                                            {
                                                label: 'Total Pengunjung',
                                                data: Array.from({ length: 12 }, (_, i) => {
                                                    const monthStr = `${new Date().getFullYear()}-${String(i + 1).padStart(2, '0')}`;
                                                    const data = monthly_trend?.find(d => d.month === monthStr);
                                                    return data ? data.total_visitors : 0;
                                                }),
                                                borderColor: '#0d9488',
                                                backgroundColor: 'rgba(13, 148, 136, 0.1)',
                                                fill: true,
                                                tension: 0.4,
                                                pointRadius: 4,
                                            },
                                            {
                                                label: 'Selesai',
                                                data: Array.from({ length: 12 }, (_, i) => {
                                                    const monthStr = `${new Date().getFullYear()}-${String(i + 1).padStart(2, '0')}`;
                                                    const data = monthly_trend?.find(d => d.month === monthStr);
                                                    return data ? data.completed : 0;
                                                }),
                                                borderColor: '#3b82f6',
                                                backgroundColor: 'transparent',
                                                tension: 0.4,
                                                pointRadius: 4,
                                            },
                                            {
                                                label: 'Ditolak',
                                                data: Array.from({ length: 12 }, (_, i) => {
                                                    const monthStr = `${new Date().getFullYear()}-${String(i + 1).padStart(2, '0')}`;
                                                    const data = monthly_trend?.find(d => d.month === monthStr);
                                                    return data ? data.rejected : 0;
                                                }),
                                                borderColor: '#ef4444',
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
                                            datalabels: {
                                                anchor: 'end',
                                                align: 'top',
                                                color: '#64748b',
                                                font: { weight: 'bold', size: 10 },
                                                formatter: (value) => value > 0 ? value : '',
                                            },
                                        },
                                        scales: {
                                            y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { display: false }, border: { display: false } },
                                            x: { grid: { display: false }, border: { display: false } }
                                        }
                                    }}
                                />
                            </div>
                        </div>

                        {/* Peak Hours Chart */}
                        <div className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                            <h3 className="mb-6 flex items-center gap-2 font-bold text-slate-800 dark:text-slate-200 text-lg">
                                <Clock className="size-5 text-violet-500" />
                                Jam Kedatangan Populer (06:00 - 18:00)
                            </h3>
                            <div className="h-[200px]">
                                {(() => {
                                    const peakData = Array.from({ length: 13 }, (_, i) => peak_hours[i + 6] || 0);
                                    const maxValue = Math.max(...peakData, 0);
                                    return (
                                        <Bar
                                            data={{
                                                labels: Array.from({ length: 13 }, (_, i) => `${String(i + 6).padStart(2, '0')}:00`),
                                                datasets: [{
                                                    label: 'Jumlah Pengunjung',
                                                    data: peakData,
                                                    backgroundColor: 'rgba(139, 92, 246, 0.7)',
                                                    borderRadius: 4,
                                                }],
                                            }}
                                            options={{
                                                responsive: true,
                                                maintainAspectRatio: false,
                                                plugins: {
                                                    legend: { display: false },
                                                    datalabels: {
                                                        anchor: 'end',
                                                        align: 'top',
                                                        color: '#64748b',
                                                        font: { weight: 'bold', size: 11 },
                                                        formatter: (value) => value > 0 ? value : '',
                                                    },
                                                },
                                                scales: {
                                                    y: { beginAtZero: true, suggestedMax: maxValue + 1, grid: { display: false }, border: { display: false } },
                                                    x: { grid: { display: false }, border: { display: false } }
                                                }
                                            }}
                                        />
                                    );
                                })()}
                            </div>
                        </div>

                        {/* Busiest Days Chart - Full Width */}
                        <div className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                            <h3 className="mb-6 flex items-center gap-2 font-bold text-slate-800 dark:text-slate-200 text-lg">
                                <BarChart className="size-5 text-orange-500" />
                                Hari Tersibuk
                            </h3>
                            <div className="h-[200px]">
                                {(() => {
                                    const dayData = busiest_days?.map(d => d.count) || [];
                                    const maxValue = Math.max(...dayData, 0);
                                    return (
                                        <Bar
                                            data={{
                                                labels: busiest_days?.map(d => d.day) || [],
                                                datasets: [{
                                                    label: 'Jumlah Pengunjung',
                                                    data: dayData,
                                                    backgroundColor: [
                                                        'rgba(239, 68, 68, 0.7)',   // Minggu - red
                                                        'rgba(59, 130, 246, 0.7)',  // Senin - blue
                                                        'rgba(16, 185, 129, 0.7)',  // Selasa - green
                                                        'rgba(245, 158, 11, 0.7)',  // Rabu - amber
                                                        'rgba(139, 92, 246, 0.7)',  // Kamis - violet
                                                        'rgba(236, 72, 153, 0.7)',  // Jumat - pink
                                                        'rgba(107, 114, 128, 0.7)', // Sabtu - gray
                                                    ],
                                                    borderRadius: 4,
                                                }],
                                            }}
                                            options={{
                                                responsive: true,
                                                maintainAspectRatio: false,
                                                plugins: {
                                                    legend: { display: false },
                                                    datalabels: {
                                                        anchor: 'end',
                                                        align: 'top',
                                                        color: '#64748b',
                                                        font: { weight: 'bold', size: 11 },
                                                        formatter: (value) => value > 0 ? value : '',
                                                    },
                                                },
                                                scales: {
                                                    y: { beginAtZero: true, suggestedMax: maxValue + 1, grid: { display: false }, border: { display: false } },
                                                    x: { grid: { display: false }, border: { display: false } }
                                                }
                                            }}
                                        />
                                    );
                                })()}
                            </div>
                        </div>
                    </div>
                )}

                {/* Visitors Tab */}
                {activeTab === 'visitors' && (
                    <div className="space-y-8 animate-in fade-in duration-500">
                        {/* Purpose Rankings - Combined */}
                        <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                            <h4 className="mb-4 flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-200">
                                <ClipboardCheck className="size-5 text-emerald-500" />
                                Top 5 Keperluan Kunjungan
                            </h4>
                            <div className="space-y-2">
                                {(purpose_rankings.most_visited || []).length > 0 ? (
                                    (purpose_rankings.most_visited || []).map((item, idx) => (
                                        <div key={idx} className="flex items-center justify-between border-b border-slate-50 pb-2 last:border-0 last:pb-0 dark:border-slate-700/50">
                                            <div className="flex items-center gap-2">
                                                <span className="flex size-5 items-center justify-center rounded-full bg-emerald-100 text-[10px] font-bold text-emerald-600 dark:bg-emerald-900/30">{idx + 1}</span>
                                                <span className="text-sm text-slate-600 dark:text-slate-400">{item.name}</span>
                                            </div>
                                            <span className="text-xs font-bold text-emerald-600">{item.total} kunjungan</span>
                                        </div>
                                    ))
                                ) : (
                                    <p className="py-2 text-xs text-slate-400 italic">Data tidak tersedia</p>
                                )}
                            </div>
                        </div>

                        {/* Division Rankings - Combined */}
                        <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                            <h4 className="mb-4 flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-200">
                                <Building2 className="size-5 text-blue-500" />
                                Top 5 Divisi Tujuan
                            </h4>
                            <div className="space-y-2">
                                {(division_rankings.most_visited || []).length > 0 ? (
                                    (division_rankings.most_visited || []).map((item, idx) => (
                                        <div key={idx} className="flex items-center justify-between border-b border-slate-50 pb-2 last:border-0 last:pb-0 dark:border-slate-700/50">
                                            <div className="flex items-center gap-2">
                                                <span className="flex size-5 items-center justify-center rounded-full bg-blue-100 text-[10px] font-bold text-blue-600 dark:bg-blue-900/30">{idx + 1}</span>
                                                <span className="text-sm text-slate-600 dark:text-slate-400">{item.name}</span>
                                            </div>
                                            <span className="text-xs font-bold text-blue-600">{item.total} kunjungan</span>
                                        </div>
                                    ))
                                ) : (
                                    <p className="py-2 text-xs text-slate-400 italic">Data tidak tersedia</p>
                                )}
                            </div>
                        </div>

                        {/* Top Organizations */}
                        <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                            <h4 className="mb-4 flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-200">
                                <Building2 className="size-5 text-indigo-500" />
                                Top 5 Organisasi
                            </h4>
                            <div className="space-y-2">
                                {(top_organizations || []).length > 0 ? (
                                    top_organizations.map((org, idx) => (
                                        <div key={idx} className="flex items-center justify-between border-b border-slate-50 pb-2 last:border-0 last:pb-0 dark:border-slate-700/50">
                                            <div className="flex items-center gap-2">
                                                <span className="flex size-5 items-center justify-center rounded-full bg-indigo-100 text-[10px] font-bold text-indigo-600 dark:bg-indigo-900/30">{idx + 1}</span>
                                                <span className="text-sm text-slate-600 dark:text-slate-400">{org.organization}</span>
                                            </div>
                                            <span className="text-xs font-bold text-indigo-600">{org.total} kunjungan</span>
                                        </div>
                                    ))
                                ) : (
                                    <p className="py-2 text-xs text-slate-400 italic">Data tidak tersedia</p>
                                )}
                            </div>
                        </div>

                        {/* Repeat Visitors */}
                        <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                            <h4 className="mb-4 flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-200">
                                <Users className="size-5 text-violet-500" />
                                Top 5 Pengunjung Berulang
                            </h4>
                            <div className="space-y-2">
                                {(repeat_visitors || []).length > 0 ? (
                                    repeat_visitors.map((v, idx) => (
                                        <div key={idx} className="flex items-center justify-between border-b border-slate-50 pb-2 last:border-0 last:pb-0 dark:border-slate-700/50">
                                            <div className="flex items-center gap-2">
                                                <span className="flex size-5 items-center justify-center rounded-full bg-violet-100 text-[10px] font-bold text-violet-600 dark:bg-violet-900/30">{idx + 1}</span>
                                                <div>
                                                    <span className="text-sm text-slate-600 dark:text-slate-400">{v.name}</span>
                                                    {v.phone && <span className="text-xs text-slate-400 ml-2">({v.phone})</span>}
                                                </div>
                                            </div>
                                            <span className="text-xs font-bold text-violet-600">{v.visit_count}x kunjungan</span>
                                        </div>
                                    ))
                                ) : (
                                    <p className="py-2 text-xs text-slate-400 italic">Belum ada pengunjung berulang</p>
                                )}
                            </div>
                        </div>

                        {/* Status Distribution Doughnut */}
                        <div className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                            <h3 className="mb-6 flex items-center gap-2 font-bold text-slate-800 dark:text-slate-200 text-lg">
                                <PieChart className="size-5 text-primary" />
                                Distribusi Status Kunjungan
                            </h3>
                            <div className="h-[300px] flex items-center justify-center">
                                <Doughnut
                                    data={{
                                        labels: Object.keys(status_distribution).map(s => statusLabels[s] || s),
                                        datasets: [{
                                            data: Object.values(status_distribution),
                                            backgroundColor: Object.keys(status_distribution).map(s => statusColors[s] || '#64748b'),
                                            borderWidth: 0,
                                        }],
                                    }}
                                    options={{
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: {
                                            legend: { position: 'right', labels: { usePointStyle: true } },
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
                )}

                {/* Feedback Tab */}
                {activeTab === 'feedback' && (
                    <div className="space-y-8 animate-in fade-in duration-500">
                        {/* Feedback Stats */}
                        <div className="grid grid-cols-2 gap-4">
                            <StatCard
                                label="Total Feedback"
                                value={feedback_stats.total_feedbacks}
                                icon={<Star className="size-5 text-primary" />}
                                color="text-primary"
                            />
                            <StatCard
                                label="Rata-rata Rating"
                                value={feedback_stats.average_rating}
                                icon={<Star className="size-5 text-amber-500" />}
                                color="text-amber-500"
                            />
                        </div>

                        {/* Rating Distribution */}
                        <div className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                            <h3 className="mb-6 flex items-center gap-2 font-bold text-slate-800 dark:text-slate-200 text-lg">
                                <Star className="size-5 text-amber-500" />
                                Distribusi Rating
                            </h3>
                            <div className="h-[200px]">
                                {(() => {
                                    const ratingData = [1, 2, 3, 4, 5].map(r => feedback_stats.rating_distribution[r] || 0);
                                    const maxValue = Math.max(...ratingData, 0);
                                    return (
                                        <Bar
                                            data={{
                                                labels: ['1 ⭐', '2 ⭐', '3 ⭐', '4 ⭐', '5 ⭐'],
                                                datasets: [{
                                                    label: 'Jumlah',
                                                    data: ratingData,
                                                    backgroundColor: ['#ef4444', '#f97316', '#eab308', '#84cc16', '#22c55e'],
                                                    borderRadius: 8,
                                                }],
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
                                                        formatter: (value) => value > 0 ? value : '',
                                                    },
                                                },
                                                scales: {
                                                    y: { beginAtZero: true, suggestedMax: maxValue + 1, grid: { display: false }, border: { display: false } },
                                                    x: { grid: { display: false }, border: { display: false } }
                                                }
                                            }}
                                        />
                                    );
                                })()}
                            </div>
                        </div>

                        {/* Question Stats */}
                        <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                            <h4 className="mb-4 flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-200">
                                <ClipboardCheck className="size-5 text-primary" />
                                Rating per Pertanyaan
                            </h4>
                            <div className="space-y-3">
                                {feedback_stats.question_stats?.length > 0 ? (
                                    feedback_stats.question_stats.map((q, idx) => (
                                        <div key={idx} className="flex items-center justify-between border-b border-slate-50 pb-3 last:border-0 last:pb-0 dark:border-slate-700/50">
                                            <span className="text-sm text-slate-600 dark:text-slate-400 flex-1">{q.question}</span>
                                            <div className="flex items-center gap-4">
                                                <span className="text-xs text-slate-400">{q.total} responden</span>
                                                <span className="flex items-center gap-1 text-sm font-bold text-amber-500">
                                                    <Star className="size-4 fill-amber-400 text-amber-400" />
                                                    {Number(q.avg_rating).toFixed(1)}
                                                </span>
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <p className="py-4 text-center text-xs text-slate-400 italic">Belum ada data feedback</p>
                                )}
                            </div>
                        </div>
                    </div>
                )}
            </ContentCard>
        </RootLayout>
    );
}
