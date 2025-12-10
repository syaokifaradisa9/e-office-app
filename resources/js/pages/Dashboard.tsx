import { Head, router, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import toast, { Toaster } from 'react-hot-toast';
import {
    LayoutDashboard,
    LogOut,
    User,
    Calendar,
    FileText,
    Users,
    Bell,
    Settings,
    ChevronRight,
} from 'lucide-react';

interface AuthUser {
    id: number;
    name: string;
    email: string;
    initials?: string;
}

interface FlashMessage {
    type?: 'success' | 'error';
    message?: string;
}

interface PageProps {
    auth?: {
        user: AuthUser;
    };
    flash?: FlashMessage;
    [key: string]: unknown;
}

export default function Dashboard() {
    const { auth, flash } = usePage<PageProps>().props;

    useEffect(() => {
        if (flash?.type === 'success' && flash.message) {
            toast.success(flash.message);
        }
        if (flash?.type === 'error' && flash.message) {
            toast.error(flash.message);
        }
    }, [flash]);

    const handleLogout = () => {
        router.post('/auth/logout');
    };

    // Generate initials from name
    const getInitials = (name: string): string => {
        return name
            .split(' ')
            .map((word) => word[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const quickActions = [
        { icon: FileText, label: 'Dokumen Baru', color: 'from-blue-500 to-cyan-500' },
        { icon: Users, label: 'Daftar Pengguna', color: 'from-purple-500 to-pink-500' },
        { icon: Calendar, label: 'Agenda', color: 'from-orange-500 to-amber-500' },
        { icon: Bell, label: 'Notifikasi', color: 'from-green-500 to-emerald-500' },
    ];

    const recentActivities = [
        { title: 'Dokumen baru diupload', time: '5 menit lalu', type: 'document' },
        { title: 'Pengguna baru terdaftar', time: '1 jam lalu', type: 'user' },
        { title: 'Rapat dijadwalkan', time: '2 jam lalu', type: 'calendar' },
        { title: 'Update sistem selesai', time: '1 hari lalu', type: 'system' },
    ];

    return (
        <div className="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
            <Head title="Dashboard | E-Office" />
            <Toaster position="bottom-right" />

            {/* Background Decorations */}
            <div className="pointer-events-none fixed inset-0 overflow-hidden">
                <div className="absolute -top-40 -left-40 h-80 w-80 rounded-full bg-gradient-to-r from-blue-500/20 to-cyan-500/20 blur-3xl"></div>
                <div className="absolute -right-40 -bottom-40 h-80 w-80 rounded-full bg-gradient-to-r from-purple-500/20 to-pink-500/20 blur-3xl"></div>
                <div className="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.02)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.02)_1px,transparent_1px)] bg-[size:64px_64px]"></div>
            </div>

            {/* Header */}
            <header className="relative z-20 border-b border-white/10 bg-white/5 backdrop-blur-xl">
                <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                    {/* Logo */}
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-cyan-500">
                            <LayoutDashboard className="h-5 w-5 text-white" />
                        </div>
                        <span className="text-xl font-bold text-white">E-Office</span>
                    </div>

                    {/* User Menu */}
                    <div className="flex items-center gap-4">
                        <button className="rounded-lg p-2 text-slate-400 hover:bg-white/10 hover:text-white">
                            <Bell className="h-5 w-5" />
                        </button>
                        <button className="rounded-lg p-2 text-slate-400 hover:bg-white/10 hover:text-white">
                            <Settings className="h-5 w-5" />
                        </button>
                        <div className="h-6 w-px bg-white/10"></div>
                        <div className="flex items-center gap-3">
                            <div className="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-cyan-500 text-sm font-semibold text-white">
                                {auth?.user ? getInitials(auth.user.name) : 'U'}
                            </div>
                            <div className="hidden sm:block">
                                <p className="text-sm font-medium text-white">{auth?.user?.name || 'User'}</p>
                                <p className="text-xs text-slate-400">{auth?.user?.email || 'user@email.com'}</p>
                            </div>
                        </div>
                        <button
                            onClick={handleLogout}
                            className="flex items-center gap-2 rounded-lg bg-red-500/10 px-3 py-2 text-sm font-medium text-red-400 transition-colors hover:bg-red-500/20"
                        >
                            <LogOut className="h-4 w-4" />
                            <span className="hidden sm:inline">Keluar</span>
                        </button>
                    </div>
                </div>
            </header>

            {/* Main Content */}
            <main className="relative z-10 mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                {/* Welcome Section */}
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-white">
                        Selamat Datang, {auth?.user?.name?.split(' ')[0] || 'User'}! 👋
                    </h1>
                    <p className="mt-2 text-slate-400">
                        Berikut adalah ringkasan aktivitas kantor Anda hari ini.
                    </p>
                </div>

                {/* Stats Grid */}
                <div className="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {[
                        { label: 'Total Dokumen', value: '1,234', icon: FileText, change: '+12%' },
                        { label: 'Pengguna Aktif', value: '56', icon: Users, change: '+4%' },
                        { label: 'Agenda Bulan Ini', value: '23', icon: Calendar, change: '+8%' },
                        { label: 'Tugas Pending', value: '7', icon: Bell, change: '-2%' },
                    ].map((stat, index) => (
                        <div
                            key={index}
                            className="group rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur-xl transition-all hover:border-white/20 hover:bg-white/10"
                        >
                            <div className="flex items-center justify-between">
                                <div className="rounded-xl bg-gradient-to-br from-blue-500/20 to-cyan-500/20 p-3">
                                    <stat.icon className="h-6 w-6 text-blue-400" />
                                </div>
                                <span
                                    className={`text-sm font-medium ${stat.change.startsWith('+') ? 'text-green-400' : 'text-red-400'
                                        }`}
                                >
                                    {stat.change}
                                </span>
                            </div>
                            <div className="mt-4">
                                <p className="text-3xl font-bold text-white">{stat.value}</p>
                                <p className="mt-1 text-sm text-slate-400">{stat.label}</p>
                            </div>
                        </div>
                    ))}
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Quick Actions */}
                    <div className="lg:col-span-2">
                        <div className="rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur-xl">
                            <h2 className="mb-4 text-lg font-semibold text-white">Aksi Cepat</h2>
                            <div className="grid gap-4 sm:grid-cols-2">
                                {quickActions.map((action, index) => (
                                    <button
                                        key={index}
                                        className="group flex items-center gap-4 rounded-xl border border-white/10 bg-white/5 p-4 text-left transition-all hover:border-white/20 hover:bg-white/10"
                                    >
                                        <div
                                            className={`rounded-xl bg-gradient-to-br ${action.color} p-3 shadow-lg`}
                                        >
                                            <action.icon className="h-6 w-6 text-white" />
                                        </div>
                                        <div className="flex-1">
                                            <p className="font-medium text-white">{action.label}</p>
                                            <p className="text-sm text-slate-400">Klik untuk memulai</p>
                                        </div>
                                        <ChevronRight className="h-5 w-5 text-slate-400 transition-transform group-hover:translate-x-1" />
                                    </button>
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* Recent Activity */}
                    <div className="rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur-xl">
                        <h2 className="mb-4 text-lg font-semibold text-white">Aktivitas Terbaru</h2>
                        <div className="space-y-4">
                            {recentActivities.map((activity, index) => (
                                <div
                                    key={index}
                                    className="flex items-center gap-3 rounded-xl border border-white/5 bg-white/5 p-3"
                                >
                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-500/20">
                                        {activity.type === 'document' && <FileText className="h-5 w-5 text-blue-400" />}
                                        {activity.type === 'user' && <User className="h-5 w-5 text-purple-400" />}
                                        {activity.type === 'calendar' && <Calendar className="h-5 w-5 text-orange-400" />}
                                        {activity.type === 'system' && <Settings className="h-5 w-5 text-green-400" />}
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-medium text-white">{activity.title}</p>
                                        <p className="text-xs text-slate-400">{activity.time}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </main>
        </div>
    );
}
