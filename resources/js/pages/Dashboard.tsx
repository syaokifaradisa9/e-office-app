import { usePage } from '@inertiajs/react';
import { Bell, Calendar, ChevronRight, FileText, Settings, User, Users } from 'lucide-react';

import RootLayout from '../components/layouts/RootLayout';

interface AuthUser {
    id: number;
    name: string;
    email: string;
    initials?: string;
}

interface PageProps {
    auth?: {
        user: AuthUser;
    };
    [key: string]: unknown;
}

export default function Dashboard() {
    const { auth } = usePage<PageProps>().props;

    const quickActions = [
        { icon: FileText, label: 'Dokumen Baru', color: 'from-blue-500 to-cyan-500', href: '#' },
        { icon: Users, label: 'Daftar Pengguna', color: 'from-purple-500 to-pink-500', href: '/user' },
        { icon: Calendar, label: 'Agenda', color: 'from-orange-500 to-amber-500', href: '#' },
        { icon: Bell, label: 'Notifikasi', color: 'from-green-500 to-emerald-500', href: '#' },
    ];

    const recentActivities = [
        { title: 'Dokumen baru diupload', time: '5 menit lalu', type: 'document' },
        { title: 'Pengguna baru terdaftar', time: '1 jam lalu', type: 'user' },
        { title: 'Rapat dijadwalkan', time: '2 jam lalu', type: 'calendar' },
        { title: 'Update sistem selesai', time: '1 hari lalu', type: 'system' },
    ];

    return (
        <RootLayout title="Dashboard">
            <div className="space-y-6">
                {/* Welcome Section */}
                <div className="rounded-2xl border border-gray-200 bg-gradient-to-br from-primary/10 via-white to-cyan-50 p-6 dark:border-slate-700 dark:from-primary/20 dark:via-slate-800 dark:to-cyan-900/20">
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Selamat Datang, {auth?.user?.name?.split(' ')[0] || 'User'}! 👋</h1>
                    <p className="mt-2 text-gray-600 dark:text-slate-400">Berikut adalah ringkasan aktivitas kantor Anda hari ini.</p>
                </div>

                {/* Stats Grid */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {[
                        { label: 'Total Dokumen', value: '1,234', icon: FileText, change: '+12%', color: 'blue' },
                        { label: 'Pengguna Aktif', value: '56', icon: Users, change: '+4%', color: 'purple' },
                        { label: 'Agenda Bulan Ini', value: '23', icon: Calendar, change: '+8%', color: 'orange' },
                        { label: 'Tugas Pending', value: '7', icon: Bell, change: '-2%', color: 'green' },
                    ].map((stat, index) => (
                        <div key={index} className="group rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition-all hover:shadow-md dark:border-slate-700 dark:bg-slate-800">
                            <div className="flex items-center justify-between">
                                <div className={`rounded-xl bg-${stat.color}-100 p-3 dark:bg-${stat.color}-900/30`}>
                                    <stat.icon className={`h-6 w-6 text-${stat.color}-600 dark:text-${stat.color}-400`} />
                                </div>
                                <span className={`text-sm font-medium ${stat.change.startsWith('+') ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}`}>{stat.change}</span>
                            </div>
                            <div className="mt-4">
                                <p className="text-3xl font-bold text-gray-900 dark:text-white">{stat.value}</p>
                                <p className="mt-1 text-sm text-gray-500 dark:text-slate-400">{stat.label}</p>
                            </div>
                        </div>
                    ))}
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Quick Actions */}
                    <div className="lg:col-span-2">
                        <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                            <h2 className="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Aksi Cepat</h2>
                            <div className="grid gap-4 sm:grid-cols-2">
                                {quickActions.map((action, index) => (
                                    <a
                                        key={index}
                                        href={action.href}
                                        className="group flex items-center gap-4 rounded-xl border border-gray-200 bg-gray-50 p-4 transition-all hover:border-primary/30 hover:bg-primary/5 dark:border-slate-600 dark:bg-slate-700/50 dark:hover:border-primary/30 dark:hover:bg-primary/10"
                                    >
                                        <div className={`rounded-xl bg-gradient-to-br ${action.color} p-3 shadow-lg`}>
                                            <action.icon className="h-6 w-6 text-white" />
                                        </div>
                                        <div className="flex-1">
                                            <p className="font-medium text-gray-900 dark:text-white">{action.label}</p>
                                            <p className="text-sm text-gray-500 dark:text-slate-400">Klik untuk memulai</p>
                                        </div>
                                        <ChevronRight className="h-5 w-5 text-gray-400 transition-transform group-hover:translate-x-1 dark:text-slate-500" />
                                    </a>
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* Recent Activity */}
                    <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <h2 className="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Aktivitas Terbaru</h2>
                        <div className="space-y-4">
                            {recentActivities.map((activity, index) => (
                                <div key={index} className="flex items-center gap-3 rounded-xl border border-gray-100 bg-gray-50 p-3 dark:border-slate-600 dark:bg-slate-700/50">
                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                                        {activity.type === 'document' && <FileText className="h-5 w-5 text-blue-600 dark:text-blue-400" />}
                                        {activity.type === 'user' && <User className="h-5 w-5 text-purple-600 dark:text-purple-400" />}
                                        {activity.type === 'calendar' && <Calendar className="h-5 w-5 text-orange-600 dark:text-orange-400" />}
                                        {activity.type === 'system' && <Settings className="h-5 w-5 text-green-600 dark:text-green-400" />}
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-medium text-gray-900 dark:text-white">{activity.title}</p>
                                        <p className="text-xs text-gray-500 dark:text-slate-400">{activity.time}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </RootLayout>
    );
}
