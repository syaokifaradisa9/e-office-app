import { useState } from 'react';
import { usePage, Link } from '@inertiajs/react';
import {
    FileText,
    HardDrive,
    TrendingUp,
    FolderOpen,
    Building2,
    Globe,
    Clock,
    AlertTriangle,
    CheckCircle,
    XCircle,
    Users,
} from 'lucide-react';

interface Document {
    id: number;
    title: string;
    classification?: { name: string };
    uploader?: { name: string };
    created_at: string;
}

interface CategoryDistribution {
    name: string;
    count: number;
}

interface DivisionStorageStatus {
    division_id: number;
    division_name: string;
    used_size_label: string;
    max_size_label: string;
    percentage: number;
    status: string;
}

interface TopUploader {
    user_id: number;
    user_name: string;
    total: number;
}

interface TabData {
    // Division tab
    storage?: {
        used: number;
        used_label: string;
        max: number;
        max_label: string;
        percentage: number;
    };
    document_count?: number;
    recent_documents?: Document[];
    category_distribution?: CategoryDistribution[];
    division_name?: string;

    // All tab
    total_documents?: number;
    total_size?: number;
    total_size_label?: string;
    division_storage_status?: DivisionStorageStatus[];
    top_uploaders?: TopUploader[];
}

interface ArchieveTab {
    id: string;
    label: string;
    icon: string;
    type: string;
    data: TabData;
}

interface DashboardData {
    archieve?: ArchieveTab[];
    [key: string]: unknown;
}

interface PageProps {
    dashboardData?: DashboardData;
    [key: string]: unknown;
}

export default function ArchieveDashboard() {
    const { dashboardData } = usePage<PageProps>().props;
    const archieveTabs = dashboardData?.archieve || [];

    const [activeTabIndex, setActiveTabIndex] = useState(0);

    if (archieveTabs.length === 0) {
        return null;
    }

    const activeTab = archieveTabs[activeTabIndex];

    const getIcon = (iconName: string) => {
        switch (iconName) {
            case 'building':
                return Building2;
            case 'globe':
                return Globe;
            default:
                return FileText;
        }
    };

    const renderDivisionContent = (tab: ArchieveTab) => {
        const {
            storage,
            document_count = 0,
            recent_documents = [],
            category_distribution = [],
        } = tab.data;

        const storageStatus = storage
            ? storage.percentage >= 90
                ? 'critical'
                : storage.percentage >= 70
                    ? 'warning'
                    : 'stable'
            : 'stable';

        return (
            <div className="space-y-5 animate-in fade-in duration-300">
                {/* Stats */}
                <div className="grid gap-4 md:grid-cols-3">
                    <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <FileText className="size-5" />
                            </div>
                            <div>
                                <p className="text-xs font-medium text-slate-500">Total Dokumen</p>
                                <p className="text-xl font-bold text-slate-900 dark:text-white">{document_count}</p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                        <div className="flex items-center gap-3">
                            <div className={`flex size-10 items-center justify-center rounded-lg ${storageStatus === 'critical' ? 'bg-rose-100 text-rose-600' :
                                    storageStatus === 'warning' ? 'bg-amber-100 text-amber-600' :
                                        'bg-emerald-100 text-emerald-600'
                                }`}>
                                <HardDrive className="size-5" />
                            </div>
                            <div>
                                <p className="text-xs font-medium text-slate-500">Penyimpanan</p>
                                <p className="text-xl font-bold text-slate-900 dark:text-white">{storage?.used_label || '0 B'}</p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-lg bg-violet-100 text-violet-600">
                                <FolderOpen className="size-5" />
                            </div>
                            <div>
                                <p className="text-xs font-medium text-slate-500">Kategori</p>
                                <p className="text-xl font-bold text-slate-900 dark:text-white">{category_distribution.length}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Storage Alert */}
                {storage && storage.max > 0 && storage.percentage >= 70 && (
                    <div className={`flex items-center gap-4 rounded-xl border-l-4 p-4 ${storageStatus === 'critical'
                            ? 'border-l-rose-500 bg-rose-50 dark:bg-rose-900/20'
                            : 'border-l-amber-500 bg-amber-50 dark:bg-amber-900/20'
                        }`}>
                        <AlertTriangle className={`size-5 ${storageStatus === 'critical' ? 'text-rose-600' : 'text-amber-600'}`} />
                        <div>
                            <p className={`text-sm font-medium ${storageStatus === 'critical' ? 'text-rose-800 dark:text-rose-300' : 'text-amber-800 dark:text-amber-300'}`}>
                                {storageStatus === 'critical' ? 'Penyimpanan Hampir Penuh!' : 'Penyimpanan Mendekati Batas'}
                            </p>
                            <p className={`text-xs ${storageStatus === 'critical' ? 'text-rose-700 dark:text-rose-400' : 'text-amber-700 dark:text-amber-400'}`}>
                                {storage.used_label} dari {storage.max_label} ({storage.percentage}%)
                            </p>
                        </div>
                    </div>
                )}

                {/* Recent Documents & Category Distribution */}
                <div className="grid gap-5 lg:grid-cols-2">
                    <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                        <div className="mb-4 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Clock className="size-5 text-primary" />
                                <h3 className="font-semibold text-slate-800 dark:text-white">Dokumen Terbaru</h3>
                            </div>
                            <Link href="/archieve/documents" className="text-xs text-primary hover:underline">Lihat Semua</Link>
                        </div>
                        <div className="space-y-3">
                            {recent_documents.length > 0 ? recent_documents.slice(0, 5).map((doc) => (
                                <div key={doc.id} className="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-700/50">
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-medium text-slate-700 dark:text-slate-200">{doc.title}</p>
                                        <p className="text-xs text-slate-400">{doc.classification?.name}</p>
                                    </div>
                                </div>
                            )) : <p className="py-4 text-center text-sm text-slate-400">Belum ada dokumen</p>}
                        </div>
                    </div>

                    <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                        <div className="mb-4 flex items-center gap-2">
                            <FolderOpen className="size-5 text-violet-600" />
                            <h3 className="font-semibold text-slate-800 dark:text-white">Top Kategori</h3>
                        </div>
                        <div className="space-y-3">
                            {category_distribution.length > 0 ? category_distribution.slice(0, 5).map((cat, idx) => (
                                <div key={idx} className="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-700/50">
                                    <div className="flex items-center gap-3">
                                        <span className="flex size-7 items-center justify-center rounded-full bg-violet-100 text-xs font-bold text-violet-700 dark:bg-violet-900/30 dark:text-violet-400">{idx + 1}</span>
                                        <span className="text-sm font-medium text-slate-700 dark:text-slate-200">{cat.name}</span>
                                    </div>
                                    <span className="text-sm font-bold text-violet-600">{cat.count}</span>
                                </div>
                            )) : <p className="py-4 text-center text-sm text-slate-400">Tidak ada data</p>}
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    const renderOverviewContent = (tab: ArchieveTab) => {
        const {
            total_documents = 0,
            total_size_label = '0 B',
            division_storage_status = [],
            top_uploaders = [],
            recent_documents = [],
        } = tab.data;

        const criticalCount = division_storage_status.filter(d => d.status === 'critical').length;

        return (
            <div className="space-y-5 animate-in fade-in duration-300">
                {/* Stats */}
                <div className="grid gap-4 md:grid-cols-4">
                    <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <FileText className="size-5" />
                            </div>
                            <div>
                                <p className="text-xs font-medium text-slate-500">Total Dokumen</p>
                                <p className="text-xl font-bold text-slate-900 dark:text-white">{total_documents}</p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-lg bg-sky-100 text-sky-600">
                                <HardDrive className="size-5" />
                            </div>
                            <div>
                                <p className="text-xs font-medium text-slate-500">Total Penyimpanan</p>
                                <p className="text-xl font-bold text-slate-900 dark:text-white">{total_size_label}</p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-lg bg-violet-100 text-violet-600">
                                <Building2 className="size-5" />
                            </div>
                            <div>
                                <p className="text-xs font-medium text-slate-500">Total Divisi</p>
                                <p className="text-xl font-bold text-slate-900 dark:text-white">{division_storage_status.length}</p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                        <div className="flex items-center gap-3">
                            <div className={`flex size-10 items-center justify-center rounded-lg ${criticalCount > 0 ? 'bg-rose-100 text-rose-600' : 'bg-emerald-100 text-emerald-600'}`}>
                                {criticalCount > 0 ? <AlertTriangle className="size-5" /> : <CheckCircle className="size-5" />}
                            </div>
                            <div>
                                <p className="text-xs font-medium text-slate-500">Divisi Kritis</p>
                                <p className="text-xl font-bold text-slate-900 dark:text-white">{criticalCount}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Division Storage Status */}
                <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                    <div className="mb-4 flex items-center gap-2">
                        <HardDrive className="size-5 text-primary" />
                        <h3 className="font-semibold text-slate-800 dark:text-white">Status Penyimpanan Divisi</h3>
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        {division_storage_status.map((div) => (
                            <div key={div.division_id} className={`flex items-center justify-between rounded-lg px-4 py-3 ${div.status === 'critical' ? 'bg-rose-50 dark:bg-rose-900/20' :
                                    div.status === 'warning' ? 'bg-amber-50 dark:bg-amber-900/20' :
                                        'bg-emerald-50 dark:bg-emerald-900/20'
                                }`}>
                                <div>
                                    <span className="text-sm font-medium text-slate-700 dark:text-slate-200">{div.division_name}</span>
                                    <p className="text-xs text-slate-500">{div.used_size_label} / {div.max_size_label}</p>
                                </div>
                                {div.status === 'critical' ? (
                                    <XCircle className="size-5 text-rose-600" />
                                ) : div.status === 'warning' ? (
                                    <AlertTriangle className="size-5 text-amber-600" />
                                ) : (
                                    <CheckCircle className="size-5 text-emerald-600" />
                                )}
                            </div>
                        ))}
                    </div>
                </div>

                {/* Top Uploaders & Recent Documents */}
                <div className="grid gap-5 lg:grid-cols-2">
                    <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                        <div className="mb-4 flex items-center gap-2">
                            <Users className="size-5 text-emerald-600" />
                            <h3 className="font-semibold text-slate-800 dark:text-white">Top Uploader</h3>
                        </div>
                        <div className="space-y-3">
                            {top_uploaders.length > 0 ? top_uploaders.slice(0, 5).map((user, idx) => (
                                <div key={idx} className="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-700/50">
                                    <div className="flex items-center gap-3">
                                        <span className="flex size-7 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">{idx + 1}</span>
                                        <span className="text-sm font-medium text-slate-700 dark:text-slate-200">{user.user_name}</span>
                                    </div>
                                    <span className="text-sm font-bold text-emerald-600">{user.total}</span>
                                </div>
                            )) : <p className="py-4 text-center text-sm text-slate-400">Tidak ada data</p>}
                        </div>
                    </div>

                    <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                        <div className="mb-4 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Clock className="size-5 text-primary" />
                                <h3 className="font-semibold text-slate-800 dark:text-white">Dokumen Terbaru</h3>
                            </div>
                            <Link href="/archieve/documents" className="text-xs text-primary hover:underline">Lihat Semua</Link>
                        </div>
                        <div className="space-y-3">
                            {recent_documents && recent_documents.length > 0 ? recent_documents.slice(0, 5).map((doc: Document) => (
                                <div key={doc.id} className="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-700/50">
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-medium text-slate-700 dark:text-slate-200">{doc.title}</p>
                                        <p className="text-xs text-slate-400">{doc.classification?.name}</p>
                                    </div>
                                </div>
                            )) : <p className="py-4 text-center text-sm text-slate-400">Belum ada dokumen</p>}
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    const renderTabContent = (tab: ArchieveTab) => {
        if (tab.type === 'overview') {
            return renderOverviewContent(tab);
        }
        return renderDivisionContent(tab);
    };

    return (
        <div className="space-y-5">
            {archieveTabs.length > 1 && (
                <div className="flex gap-6 border-b border-slate-200 dark:border-slate-700">
                    {archieveTabs.map((tab, index) => {
                        const isActive = activeTabIndex === index;
                        return (
                            <button
                                key={tab.id}
                                onClick={() => setActiveTabIndex(index)}
                                className={`relative pb-3 text-sm font-medium transition-colors ${isActive
                                    ? 'text-white'
                                    : 'text-slate-400 hover:text-slate-300'
                                    }`}
                            >
                                {tab.label}
                                {isActive && (
                                    <span className="absolute bottom-0 left-0 h-0.5 w-full bg-white" />
                                )}
                            </button>
                        );
                    })}
                </div>
            )}

            {activeTab && renderTabContent(activeTab)}
        </div>
    );
}
