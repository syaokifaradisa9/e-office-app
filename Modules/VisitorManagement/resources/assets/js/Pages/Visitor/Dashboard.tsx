import RootLayout from '@/components/layouts/RootLayout';
import ContentCard from '@/components/layouts/ContentCard';
import { Users, Clock, XCircle, Star, TrendingUp, PieChart as PieChartIcon } from 'lucide-react';
import { Chart as ChartJS, ArcElement, Tooltip, Legend, CategoryScale, LinearScale, PointElement, LineElement, Title, Filler } from 'chart.js';
import { Pie as PieChart, Line as LineChart } from 'react-chartjs-2';

ChartJS.register(ArcElement, Tooltip, Legend, CategoryScale, LinearScale, PointElement, LineElement, Title, Filler);

interface DashboardProps {
    stats: {
        today_visitors: number;
        active_visitors: number;
        rejected_visits: number;
        average_rating: number;
    };
    purposeDistribution: Array<{ name: string; count: number }>;
    weeklyTrend: Array<{ date: string; count: number }>;
}

export default function VisitorDashboard({ stats, purposeDistribution, weeklyTrend }: DashboardProps) {

    const pieData = {
        labels: purposeDistribution.map(p => p.name),
        datasets: [{
            data: purposeDistribution.map(p => p.count),
            backgroundColor: [
                '#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'
            ],
            borderWidth: 0,
        }]
    };

    const lineData = {
        labels: weeklyTrend.map(w => w.date),
        datasets: [{
            label: 'Jumlah Pengunjung',
            data: weeklyTrend.map(w => w.count),
            fill: true,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.4,
        }]
    };

    return (
        <RootLayout title="Dashboard Pengunjung">
            {/* Stats Overview */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <StatCard
                    title="Tamu Hari Ini"
                    value={stats.today_visitors}
                    icon={<Users className="size-6 text-emerald-500" />}
                    color="emerald"
                />
                <StatCard
                    title="Tamu Aktif"
                    value={stats.active_visitors}
                    icon={<Clock className="size-6 text-blue-500" />}
                    color="blue"
                />
                <StatCard
                    title="Tolak Hari Ini"
                    value={stats.rejected_visits}
                    icon={<XCircle className="size-6 text-red-500" />}
                    color="red"
                />
                <StatCard
                    title="Rata-rata Rating"
                    value={stats.average_rating}
                    icon={<Star className="size-6 text-amber-500" />}
                    color="amber"
                    suffix="/ 5.0"
                />
            </div>

            {/* Charts Section */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <ContentCard title="Tren Kunjungan (7 Hari Terakhir)">
                    <div className="h-[300px]">
                        <LineChart data={lineData} options={{ maintainAspectRatio: false, responsive: true }} />
                    </div>
                </ContentCard>

                <ContentCard title="Distribusi Berdasarkan Keperluan">
                    <div className="h-[300px] flex items-center justify-center">
                        <PieChart data={pieData} options={{ maintainAspectRatio: false, responsive: true }} />
                    </div>
                </ContentCard>
            </div>
        </RootLayout>
    );
}

function StatCard({ title, value, icon, color, suffix = '' }: { title: string, value: number | string, icon: React.ReactNode, color: string, suffix?: string }) {
    const colorClasses: Record<string, string> = {
        emerald: 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600',
        blue: 'bg-blue-50 dark:bg-blue-900/20 text-blue-600',
        red: 'bg-red-50 dark:bg-red-900/20 text-red-600',
        amber: 'bg-amber-50 dark:bg-amber-900/20 text-amber-600',
    };

    return (
        <div className="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 flex items-center gap-5">
            <div className={`size-14 rounded-xl flex items-center justify-center ${colorClasses[color]}`}>
                {icon}
            </div>
            <div>
                <p className="text-sm font-medium text-slate-500 dark:text-slate-400">{title}</p>
                <div className="flex items-baseline gap-1">
                    <h3 className="text-2xl font-bold text-slate-900 dark:text-white uppercase">{value}</h3>
                    {suffix && <span className="text-xs text-slate-400 font-medium">{suffix}</span>}
                </div>
            </div>
        </div>
    );
}
