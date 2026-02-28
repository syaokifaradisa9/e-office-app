import RootLayout from '@/components/layouts/RootLayout';
import ContentCard from '@/components/layouts/ContentCard';
import {
    CheckSquare, Check, XCircle, Clock, History as HistoryIcon,
    FileText, Image as ImageIcon, Wrench, Box, Calendar, Tag, Hash, X, User
} from 'lucide-react';
import { useState, useEffect } from 'react';

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
        merk: string;
        model: string;
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
    user: string | null;
    has_refinement: boolean;
}

interface RefinementLog {
    id: number;
    date: string;
    description: string;
    note: string | null;
    result: string;
    attachments: Attachment[] | null;
    [key: string]: unknown;
}

interface Props {
    maintenance: Maintenance;
    refinements: RefinementLog[];
    refinementCount: number;
}

export default function MaintenanceDetail({ maintenance, refinements, refinementCount }: Props) {
    const hasRefinement = refinementCount > 0;
    const [activeTab, setActiveTab] = useState<'checklist' | 'refinement'>('checklist');
    const [selectedImage, setSelectedImage] = useState<string | null>(null);

    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'pending': return <Clock className="size-3.5" />;
            case 'finish': return <Check className="size-3.5" />;
            case 'confirmed': return <Check className="size-3.5" />;
            case 'refinement': return <HistoryIcon className="size-3.5" />;
            case 'cancelled': return <XCircle className="size-3.5" />;
            default: return null;
        }
    };



    const InfoItem = ({ icon, label, value }: { icon: React.ReactNode, label: string, value: React.ReactNode }) => (
        <div className="flex items-start gap-3">
            <div className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary dark:bg-primary/15">
                {icon}
            </div>
            <div className="min-w-0">
                <p className="text-[11px] font-medium uppercase tracking-wider text-slate-400 dark:text-slate-500">{label}</p>
                <div className="mt-0.5 text-sm font-semibold text-slate-800 dark:text-white">{value}</div>
            </div>
        </div>
    );

    return (
        <RootLayout title="Detail Maintenance" backPath="/ticketing/maintenances">
            <ContentCard
                title="Detail Maintenance"
                subtitle={`${maintenance.asset_item.merk} ${maintenance.asset_item.model}`}
                backPath="/ticketing/maintenances"
                mobileFullWidth
                bodyClassName="p-0"
            >
                {/* ── Asset Info Section ── */}
                <div className="space-y-6 px-3 py-4 md:p-6">
                    {/* Asset Details Grid */}
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <InfoItem
                            icon={<Box className="size-4" />}
                            label="Asset"
                            value={
                                <span>
                                    {maintenance.asset_item.category_name} Merek {maintenance.asset_item.merk} Model {maintenance.asset_item.model} SN : <span className="font-mono text-primary">{maintenance.asset_item.serial_number}</span>
                                </span>
                            }
                        />
                        <InfoItem
                            icon={<Calendar className="size-4" />}
                            label="Tanggal Estimasi"
                            value={new Date(maintenance.estimation_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}
                        />
                        <InfoItem
                            icon={<Calendar className="size-4" />}
                            label="Tanggal Maintenance"
                            value={maintenance.actual_date
                                ? new Date(maintenance.actual_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
                                : <span className="text-slate-400 italic">Belum dilaksanakan</span>
                            }
                        />
                        {maintenance.user && (
                            <InfoItem icon={<User className="size-4" />} label="Petugas" value={maintenance.user} />
                        )}
                        {maintenance.note && (
                            <InfoItem icon={<FileText className="size-4" />} label="Catatan" value={maintenance.note} />
                        )}
                    </div>

                    {/* Attachments */}
                    {maintenance.attachments && maintenance.attachments.length > 0 && (
                        <div className="space-y-3">
                            <div className="flex items-center gap-2">
                                <ImageIcon className="size-4 text-slate-400" />
                                <p className="text-[11px] font-medium uppercase tracking-wider text-slate-400">Bukti Foto / Lampiran</p>
                            </div>
                            <div className="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6">
                                {maintenance.attachments.map((file, idx) => (
                                    <div
                                        key={idx}
                                        onClick={() => setSelectedImage(file.url)}
                                        className="group relative cursor-pointer aspect-square overflow-hidden rounded-xl border border-slate-200 bg-slate-100 transition-all hover:ring-2 hover:ring-primary dark:border-slate-700 dark:bg-slate-800"
                                    >
                                        <img src={file.url} alt={file.name} className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110" />
                                        <div className="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 to-transparent p-2 opacity-0 transition-opacity group-hover:opacity-100">
                                            <p className="truncate text-[10px] text-white font-medium">{file.name}</p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>

                {/* ── Divider ── */}
                <div className="border-t border-slate-200 dark:border-slate-700" />

                {/* ── Tab Headers (only when has refinement) ── */}
                {hasRefinement && (
                    <div className="flex border-b border-slate-200 dark:border-slate-700">
                        <button
                            onClick={() => setActiveTab('checklist')}
                            className={`flex justify-center items-center gap-2 px-2 py-3.5 sm:px-5 text-[13px] sm:text-sm font-bold transition-all relative ${activeTab === 'checklist'
                                ? 'text-primary'
                                : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'
                                }`}
                        >
                            <CheckSquare className="size-4" />
                            Checklist Maintenance
                            {activeTab === 'checklist' && (
                                <div className="absolute bottom-0 left-0 right-0 h-0.5 bg-primary rounded-full" />
                            )}
                        </button>
                        <button
                            onClick={() => setActiveTab('refinement')}
                            className={`flex justify-center items-center gap-2 px-2 py-3.5 sm:px-5 text-[13px] sm:text-sm font-bold transition-all relative ${activeTab === 'refinement'
                                ? 'text-primary'
                                : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'
                                }`}
                        >
                            <Wrench className="size-4" />
                            Perbaikan
                            <span className={`ml-1 inline-flex items-center justify-center rounded-full px-2 py-0.5 text-[10px] font-bold ${activeTab === 'refinement'
                                ? 'bg-primary/10 text-primary'
                                : 'bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400'
                                }`}>
                                {refinementCount}
                            </span>
                            {activeTab === 'refinement' && (
                                <div className="absolute bottom-0 left-0 right-0 h-0.5 bg-primary rounded-full" />
                            )}
                        </button>
                    </div>
                )}

                {/* ── Tab / Content Body ── */}
                <div className="px-2 py-4 md:p-6">
                    {/* Checklist Tab */}
                    {activeTab === 'checklist' && (
                        <div className="space-y-6">
                            {!hasRefinement && (
                                <div className="flex items-center gap-2 border-l-4 border-primary pl-3">
                                    <CheckSquare className="size-5 text-primary" />
                                    <h3 className="text-base font-bold text-slate-800 dark:text-white">Hasil Item Pemeriksaan</h3>
                                </div>
                            )}

                            <div className="grid grid-cols-1 gap-4">
                                {maintenance.checklist_results && maintenance.checklist_results.length > 0 ? (
                                    maintenance.checklist_results.map((item, index) => (
                                        <div key={index} className="group rounded-xl border border-slate-100 bg-white p-3 sm:p-5 transition-all hover:border-primary/20 dark:border-slate-800 dark:bg-slate-900/40">
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
                                                    {item.value === 'Baik' ? <Check className="size-3" /> : <XCircle className="size-3" />}
                                                    {item.value}
                                                </span>
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <div className="py-10 text-center text-sm text-slate-400">Belum ada data checklist.</div>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Refinement Tab */}
                    {activeTab === 'refinement' && hasRefinement && (
                        <div className="space-y-3">
                            <div className="flex items-center gap-2 border-l-4 border-primary pl-3 mb-4">
                                <Wrench className="size-5 text-primary" />
                                <h3 className="text-base font-bold text-slate-800 dark:text-white">Riwayat Perbaikan</h3>
                            </div>
                            {refinements.map((item) => (
                                <div key={item.id} className="group overflow-hidden transition-colors duration-150 rounded-xl border border-slate-100 bg-white hover:border-primary/20 hover:bg-slate-50/80 dark:border-slate-800 dark:bg-slate-900/40 dark:hover:bg-slate-800/60 p-4">
                                    <div className="flex items-start gap-4">
                                        <div className="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 dark:bg-primary/15">
                                            <Calendar className="size-5 text-primary" />
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                                <h3 className="truncate text-sm font-bold text-slate-800 dark:text-white">
                                                    {new Date(item.date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}
                                                </h3>
                                                <span className="self-start inline-flex items-center rounded-lg bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                                    {item.result}
                                                </span>
                                            </div>

                                            <p className="mt-2 text-sm text-slate-600 dark:text-slate-400">
                                                {item.description}
                                            </p>

                                            {item.note && (
                                                <div className="mt-3">
                                                    <span className="inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 dark:bg-amber-900/20 dark:text-amber-400 border border-amber-100 dark:border-amber-900/30">
                                                        "{item.note}"
                                                    </span>
                                                </div>
                                            )}

                                            {item.attachments && item.attachments.length > 0 && (
                                                <div className="flex gap-2 mt-4 overflow-x-auto pb-1 no-scrollbar">
                                                    {item.attachments.map((file, idx) => (
                                                        <div key={idx} onClick={() => setSelectedImage(file.url)} className="shrink-0 cursor-pointer group/img">
                                                            <div className="size-16 sm:size-20 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm transition-transform group-hover/img:scale-105 group-hover/img:ring-2 group-hover/img:ring-primary/50 relative">
                                                                <img src={file.url} alt="" className="size-full object-cover" />
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </ContentCard>

            {/* Image Modal */}
            {selectedImage && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 px-4 pb-4 pt-10 backdrop-blur-sm transition-opacity"
                    onClick={() => setSelectedImage(null)}
                >
                    <button
                        className="absolute right-4 top-4 rounded-full bg-white/10 p-2 text-white hover:bg-white/20"
                        onClick={() => setSelectedImage(null)}
                    >
                        <X className="size-6" />
                    </button>
                    <img
                        src={selectedImage}
                        alt="Preview"
                        className="max-h-full max-w-full rounded-lg object-contain"
                        onClick={(e) => e.stopPropagation()}
                    />
                </div>
            )}
        </RootLayout>
    );
}
