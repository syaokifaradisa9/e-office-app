import { Building2, Briefcase, Users, Shield } from 'lucide-react';
import { usePage } from '@inertiajs/react';

interface StatItem {
    label: string;
    value: number;
    icon: React.ReactNode;
    iconBg: string;
    iconColor: string;
    borderColor: string;
    permission: string;
}

interface PageProps {
    permissions: string[];
    statistics: {
        total_divisions?: number;
        total_positions?: number;
        total_employees?: number;
        total_roles?: number;
    };
    [key: string]: unknown;
}

export default function DashboardStats() {
    const { permissions = [], statistics = {} } = usePage<PageProps>().props;

    const stats: StatItem[] = [
        {
            label: 'Divisi',
            value: statistics.total_divisions || 0,
            icon: <Building2 className="size-7" />,
            iconBg: 'bg-blue-50 dark:bg-blue-900/20',
            iconColor: 'text-blue-600 dark:text-blue-400',
            borderColor: 'border-l-blue-500',
            permission: 'lihat_divisi',
        },
        {
            label: 'Jabatan',
            value: statistics.total_positions || 0,
            icon: <Briefcase className="size-7" />,
            iconBg: 'bg-violet-50 dark:bg-violet-900/20',
            iconColor: 'text-violet-600 dark:text-violet-400',
            borderColor: 'border-l-violet-500',
            permission: 'lihat_jabatan',
        },
        {
            label: 'Pegawai',
            value: statistics.total_employees || 0,
            icon: <Users className="size-7" />,
            iconBg: 'bg-emerald-50 dark:bg-emerald-900/20',
            iconColor: 'text-emerald-600 dark:text-emerald-400',
            borderColor: 'border-l-emerald-500',
            permission: 'lihat_pengguna',
        },
        {
            label: 'Role',
            value: statistics.total_roles || 0,
            icon: <Shield className="size-7" />,
            iconBg: 'bg-amber-50 dark:bg-amber-900/20',
            iconColor: 'text-amber-600 dark:text-amber-400',
            borderColor: 'border-l-amber-500',
            permission: 'lihat_role',
        },
    ];

    const visibleStats = stats.filter(stat => permissions.includes(stat.permission));

    if (visibleStats.length === 0) return null;

    return (
        <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            {visibleStats.map((stat, index) => (
                <div
                    key={index}
                    className={`group rounded-xl border-l-4 ${stat.borderColor} bg-white p-6 shadow-sm transition-all duration-200 hover:shadow-lg dark:bg-slate-800`}
                >
                    <div className="flex items-center justify-between">
                        <div className="space-y-3">
                            <p className={`text-sm font-semibold uppercase tracking-wide ${stat.iconColor}`}>
                                {stat.label}
                            </p>
                            <p className="text-2xl font-bold tabular-nums text-slate-900 dark:text-white">
                                {stat.value.toLocaleString('id-ID')}
                            </p>
                        </div>
                        <div className={`flex size-14 items-center justify-center rounded-2xl ${stat.iconBg} ${stat.iconColor} transition-transform duration-200 group-hover:scale-105`}>
                            {stat.icon}
                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
}
