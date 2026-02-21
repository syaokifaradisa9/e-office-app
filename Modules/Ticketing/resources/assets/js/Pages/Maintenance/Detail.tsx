// Detail Page for Maintenance
import RootLayout from '@/components/layouts/RootLayout';
import ContentCard from '@/components/layouts/ContentCard';
import Button from '@/components/buttons/Button';
import { X, CheckSquare, CheckCircle2, XCircle, Clock, History as HistoryIcon, FileText, Image as ImageIcon } from 'lucide-react';

interface ChecklistResult {
    checklist_id: number;
    label: string;
    description: string | null;
    value: 'Baik' | 'Tidak Baik';
    note: string;
    follow_up: string;
}

interface Attachment {
    name: string;
    url: string;
    size: number;
}

interface Maintenance {
    id: number;
    asset_item: {
        id: number;
        category_name: string;
        serial_number: string;
    };
    estimation_date: string;
    actual_date: string | null;
    note: string | null;
    status: {
        value: string;
        label: string;
    };
    checklist_results: ChecklistResult[] | null;
    attachments: Attachment[] | null;
}

interface Props {
    maintenance: Maintenance;
}

export default function MaintenanceDetail({ maintenance }: Props) {
    const getStatusStyles = (status: string) => {
        switch (status) {
            case 'pending': return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
            case 'finish': return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
            case 'confirmed': return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
            case 'refinement': return 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400';
            case 'cancelled': return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400';
            default: return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400';
        }
    };

    return (
        <RootLayout title="Detail Maintenance" backPath="/ticketing/maintenances">
            <ContentCard
                title="Detail Pengerjaan Maintenance"
                subtitle={`Asset: ${maintenance.asset_item.category_name} (${maintenance.asset_item.serial_number})`}
                backPath="/ticketing/maintenances"
                mobileFullWidth
            >
                <div className="space-y-8 pb-10">
                    {/* Header Info */}
                    <div className="flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-slate-50 p-6 dark:bg-slate-800/40">
                        <div className="space-y-1">
                            <p className="text-xs font-medium text-slate-500">Status Saat Ini</p>
                            <span className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold ${getStatusStyles(maintenance.status.value)}`}>
                                {maintenance.status.value === 'pending' && <Clock className="size-3.5" />}
                                {maintenance.status.value === 'finish' && <CheckCircle2 className="size-3.5" />}
                                {maintenance.status.value === 'confirmed' && <CheckCircle2 className="size-3.5" />}
                                {maintenance.status.value === 'refinement' && <HistoryIcon className="size-3.5" />}
                                {maintenance.status.value === 'cancelled' && <XCircle className="size-3.5" />}
                                {maintenance.status.label}
                            </span>
                        </div>
                        <div className="space-y-1 md:text-right">
                            <p className="text-xs font-medium text-slate-500">Tanggal Pelaksanaan</p>
                            <p className="text-sm font-bold text-slate-900 dark:text-white">
                                {maintenance.actual_date ? new Date(maintenance.actual_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-'}
                            </p>
                        </div>
                    </div>

                    {/* General Note */}
                    <div className="space-y-4">
                        <div className="flex items-center gap-2 border-l-4 border-primary pl-3">
                            <FileText className="size-5 text-primary" />
                            <h3 className="text-base font-bold text-slate-800 dark:text-white">Catatan Umum</h3>
                        </div>
                        <div className="rounded-xl border border-slate-100 p-4 text-sm text-slate-600 dark:border-slate-800 dark:text-slate-400">
                            {maintenance.note || 'Tidak ada catatan tambahan.'}
                        </div>
                    </div>

                    {/* Checklist Results */}
                    <div className="space-y-6">
                        <div className="flex items-center gap-2 border-l-4 border-primary pl-3">
                            <CheckSquare className="size-5 text-primary" />
                            <h3 className="text-base font-bold text-slate-800 dark:text-white">Hasil Item Pemeriksaan</h3>
                        </div>

                        <div className="grid grid-cols-1 gap-4">
                            {maintenance.checklist_results?.map((item, index) => (
                                <div key={index} className="group rounded-xl border border-slate-100 bg-white p-5 transition-all hover:border-primary/20 dark:border-slate-800 dark:bg-slate-900/40">
                                    <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                        <div className="space-y-1">
                                            <h4 className="text-sm font-bold text-slate-900 dark:text-white">{item.label}</h4>
                                            {item.description && <p className="text-[10px] text-slate-400">{item.description}</p>}
                                            <div className="mt-2 space-y-1 text-xs text-slate-500 dark:text-slate-400">
                                                <p><span className="font-semibold text-slate-400">Catatan:</span> {item.note || '-'}</p>
                                                {item.value === 'Tidak Baik' && (
                                                    <p className="text-rose-500"><span className="font-semibold">Follow Up:</span> {item.follow_up}</p>
                                                )}
                                            </div>
                                        </div>
                                        <span className={`inline-flex shrink-0 items-center gap-1 rounded-lg px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider ${item.value === 'Baik'
                                            ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400'
                                            : 'bg-rose-50 text-rose-600 dark:bg-rose-900/20 dark:text-rose-400'
                                            }`}>
                                            {item.value === 'Baik' ? <CheckCircle2 className="size-3" /> : <XCircle className="size-3" />}
                                            {item.value}
                                        </span>
                                    </div>
                                </div>
                            )) || (
                                    <div className="py-10 text-center text-sm text-slate-400">Belum ada data checklist.</div>
                                )}
                        </div>
                    </div>

                    {/* Attachments */}
                    <div className="space-y-6">
                        <div className="flex items-center gap-2 border-l-4 border-primary pl-3">
                            <ImageIcon className="size-5 text-primary" />
                            <h3 className="text-base font-bold text-slate-800 dark:text-white">Bukti Foto / Lampiran</h3>
                        </div>

                        {maintenance.attachments && maintenance.attachments.length > 0 ? (
                            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                                {maintenance.attachments.map((file, idx) => (
                                    <a
                                        key={idx}
                                        href={file.url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="group relative aspect-square overflow-hidden rounded-xl border border-slate-200 bg-slate-100 transition-all hover:ring-2 hover:ring-primary dark:border-slate-700 dark:bg-slate-800"
                                    >
                                        <img src={file.url} alt={file.name} className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110" />
                                        <div className="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 to-transparent p-2 opacity-0 transition-opacity group-hover:opacity-100">
                                            <p className="truncate text-[10px] text-white font-medium">{file.name}</p>
                                        </div>
                                    </a>
                                ))}
                            </div>
                        ) : (
                            <div className="rounded-xl border border-dashed border-slate-200 py-10 text-center text-sm text-slate-400 dark:border-slate-800">
                                Tidak ada lampiran.
                            </div>
                        )}
                    </div>

                </div>
            </ContentCard>
        </RootLayout>
    );
}
