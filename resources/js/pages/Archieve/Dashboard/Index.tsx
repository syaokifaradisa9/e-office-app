import RootLayout from '@/components/layouts/RootLayout';
import { useState } from 'react';
import {
    HardDrive,
    FileText,
    Building2,
    Globe,
    TrendingUp,
    Clock,
    PieChart,
    Activity,
    Users,
    ChevronRight,
    Search,
    AlertCircle
} from 'lucide-react';

interface DashboardTab {
    id: string;
    label: string;
    icon: string;
    type: string;
    data: any;
}

interface PageProps {
    tabs: DashboardTab[];
    permissions: string[];
}

export default function DashboardIndex({ tabs, permissions }: PageProps) {
    const [activeTab, setActiveTab] = useState(tabs[0]?.id || '');

    const currentTab = tabs.find(t => t.id === activeTab);

    if (tabs.length === 0) {
        return (
            <RootLayout title="Dashboard Arsip">
                <div className="flex min-h-[400px] flex-col items-center justify-center rounded-[2rem] border border-dashed border-slate-200 bg-white p-12 text-center dark:border-slate-800 dark:bg-slate-900/50">
                    <div className="mb-4 rounded-full bg-slate-50 p-4 text-slate-400 dark:bg-slate-800">
                        <AlertCircle className="size-10" />
                    </div>
                    <h3 className="text-xl font-bold text-slate-900 dark:text-white">Akses Terbatas</h3>
                    <p className="mt-2 text-slate-500">Anda tidak memiliki izin untuk melihat dashboard arsip.</p>
                </div>
            </RootLayout>
        );
    }

    const getIcon = (iconName: string) => {
        switch (iconName) {
            case 'building': return <Building2 className="size-5" />;
            case 'globe': return <Globe className="size-5" />;
            default: return <PieChart className="size-5" />;
        }
    };

    return (
        <RootLayout title="Dashboard Arsip">
            <div className="space-y-8">
                {/* Header Section */}
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-3xl font-black tracking-tight text-slate-900 dark:text-white">Pusat Informasi Arsip</h1>
                        <p className="text-slate-500 dark:text-slate-400">Analisis data dan statistik pengelolaan dokumen digital.</p>
                    </div>

                    {/* Tab Switcher */}
                    <div className="flex items-center gap-1 overflow-x-auto rounded-2xl bg-slate-100 p-1 dark:bg-slate-800">
                        {tabs.map((tab) => (
                            <button
                                key={tab.id}
                                onClick={() => setActiveTab(tab.id)}
                                className={`flex items-center gap-2 whitespace-nowrap rounded-xl px-4 py-2 text-sm font-bold transition-all ${activeTab === tab.id
                                    ? 'bg-white text-primary shadow-sm dark:bg-slate-700 dark:text-white'
                                    : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
                                    }`}
                            >
                                {getIcon(tab.icon)}
                                {tab.label}
                            </button>
                        ))}
                    </div>
                </div>

                <div className="animate-in fade-in slide-in-from-bottom-2 duration-500">
                    {currentTab?.type === 'division' ? (
                        <DivisionDashboard data={currentTab.data} />
                    ) : (
                        <OverviewDashboard data={currentTab?.data} />
                    )}
                </div>
            </div>
        </RootLayout>
    );
}

function DivisionDashboard({ data }: { data: any }) {
    return (
        <div className="space-y-6">
            <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                {/* Total Documents */}
                <StatCard
                    label="Himpunan Dokumen"
                    value={data.document_count}
                    icon={<FileText className="text-blue-500" />}
                    color="blue"
                />

                {/* Storage Card */}
                <div className="col-span-1 rounded-[2rem] bg-white p-6 shadow-sm dark:bg-slate-900/50 md:col-span-2">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <div className="rounded-2xl bg-primary/10 p-3 text-primary">
                                <HardDrive className="size-6" />
                            </div>
                            <div>
                                <h4 className="text-xs font-black uppercase tracking-widest text-slate-400">Penyimpanan Divisi</h4>
                                <div className="flex items-baseline gap-2">
                                    <span className="text-2xl font-black text-slate-900 dark:text-white">{data.storage.used_label}</span>
                                    <span className="text-sm font-bold text-slate-400">/ {data.storage.max > 0 ? data.storage.max_label : 'Unlimited'}</span>
                                </div>
                            </div>
                        </div>
                        <div className="text-right">
                            <span className={`text-xl font-black ${data.storage.percentage >= 90 ? 'text-rose-500' : 'text-primary'}`}>
                                {data.storage.percentage}%
                            </span>
                        </div>
                    </div>
                    <div className="mt-6 h-3 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                        <div
                            className={`h-full rounded-full transition-all duration-1000 ${data.storage.percentage >= 90 ? 'bg-rose-500' : 'bg-primary'
                                }`}
                            style={{ width: `${data.storage.percentage}%` }}
                        />
                    </div>
                </div>

                <StatCard
                    label="Klasifikasi Aktif"
                    value={data.category_distribution.length}
                    icon={<TrendingUp className="text-emerald-500" />}
                    color="emerald"
                />
            </div>

            <div className="grid gap-6 lg:grid-cols-3">
                {/* Recent Documents */}
                <div className="col-span-2 rounded-[2rem] bg-white p-6 shadow-sm dark:bg-slate-900/50">
                    <div className="mb-6 flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <Clock className="size-5 text-slate-400" />
                            <h3 className="text-lg font-black tracking-tight text-slate-900 dark:text-white">Unggahan Terkini</h3>
                        </div>
                    </div>
                    <div className="space-y-4">
                        {data.recent_documents?.map((doc: any) => (
                            <div key={doc.id} className="group flex items-center justify-between rounded-2xl border border-slate-50 p-4 transition-all hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/50">
                                <div className="flex items-center gap-4">
                                    <div className="flex size-10 items-center justify-center rounded-xl bg-blue-50 text-blue-500 dark:bg-blue-900/20">
                                        <FileText className="size-5" />
                                    </div>
                                    <div>
                                        <h5 className="text-sm font-bold text-slate-900 dark:text-white">{doc.title}</h5>
                                        <p className="text-xs text-slate-500">{new Date(doc.created_at).toLocaleDateString()}</p>
                                    </div>
                                </div>
                                <ChevronRight className="size-4 text-slate-300 opacity-0 transition-all group-hover:opacity-100" />
                            </div>
                        ))}
                    </div>
                </div>

                {/* Categories */}
                <div className="rounded-[2rem] bg-white p-6 shadow-sm dark:bg-slate-900/50 border border-slate-50 dark:border-slate-800">
                    <div className="mb-6 flex items-center gap-2">
                        <PieChart className="size-5 text-slate-400" />
                        <h3 className="text-lg font-black tracking-tight text-slate-900 dark:text-white">Distribusi Kategori</h3>
                    </div>
                    <div className="space-y-4">
                        {data.category_distribution?.map((cat: any, i: number) => (
                            <div key={i} className="space-y-1.5">
                                <div className="flex justify-between text-xs font-bold">
                                    <span className="text-slate-700 dark:text-slate-300">{cat.name}</span>
                                    <span className="text-slate-400">{cat.count}</span>
                                </div>
                                <div className="h-1.5 w-full rounded-full bg-slate-100 dark:bg-slate-800">
                                    <div
                                        className="h-full rounded-full bg-primary/60"
                                        style={{ width: `${(cat.count / data.document_count) * 100}%` }}
                                    />
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
}

function OverviewDashboard({ data }: { data: any }) {
    if (!data) return null;

    return (
        <div className="space-y-8">
            {/* Global Stats */}
            <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <StatCard
                    label="Total Seluruh Arsip"
                    value={data.total_documents}
                    icon={<Globe className="text-primary" />}
                    color="primary"
                />
                <StatCard
                    label="Volume Data Global"
                    value={data.total_size_label}
                    icon={<Activity className="text-amber-500" />}
                    color="amber"
                />
                <StatCard
                    label="Kontributor Aktif"
                    value={data.top_uploaders?.length || 0}
                    icon={<Users className="text-emerald-500" />}
                    color="emerald"
                />
            </div>

            {/* Storage Status per Division */}
            <div className="rounded-[2rem] bg-white p-8 shadow-sm dark:bg-slate-900/50">
                <div className="mb-8">
                    <h3 className="text-xl font-black tracking-tight text-slate-900 dark:text-white">Status Penyimpanan Unit Kerja</h3>
                    <p className="text-sm text-slate-500">Pemantauan kapasitas server untuk setiap divisi.</p>
                </div>
                <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    {data.division_storage_status?.map((div: any, i: number) => (
                        <div key={i} className="group rounded-2xl border border-slate-50 p-5 transition-all hover:border-slate-100 hover:shadow-lg hover:shadow-slate-100/50 dark:border-slate-800 dark:hover:border-slate-700">
                            <div className="mb-4 flex items-center justify-between">
                                <h5 className="text-sm font-black text-slate-900 dark:text-white">{div.division_name}</h5>
                                <span className={`text-[10px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full ${div.status === 'critical' ? 'bg-rose-100 text-rose-600' : div.status === 'warning' ? 'bg-amber-100 text-amber-600' : 'bg-emerald-100 text-emerald-600'
                                    }`}>
                                    {div.status}
                                </span>
                            </div>
                            <div className="flex justify-between items-baseline mb-2">
                                <span className="text-xs font-bold text-slate-500">{div.used_size_label} digunakan</span>
                                <span className="text-sm font-black text-slate-900 dark:text-white">{div.percentage}%</span>
                            </div>
                            <div className="h-1.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                <div
                                    className={`h-full transition-all duration-1000 ${div.status === 'critical' ? 'bg-rose-500' : div.status === 'warning' ? 'bg-amber-500' : 'bg-primary'
                                        }`}
                                    style={{ width: `${div.percentage}%` }}
                                />
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}

function StatCard({ label, value, icon, color }: { label: string, value: any, icon: any, color: string }) {
    return (
        <div className="rounded-[2rem] bg-white p-6 shadow-sm dark:bg-slate-900/50 transition-all hover:shadow-md">
            <div className="flex items-center gap-4">
                <div className={`rounded-2xl bg-${color}-50 p-3 dark:bg-${color}-900/20`}>
                    {icon}
                </div>
                <div>
                    <h4 className="text-xs font-black uppercase tracking-widest text-slate-400">{label}</h4>
                    <span className="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tighter">{value}</span>
                </div>
            </div>
        </div>
    );
}
