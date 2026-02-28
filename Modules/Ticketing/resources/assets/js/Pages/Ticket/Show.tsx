import RootLayout from '@/components/layouts/RootLayout';
import ContentCard from '@/components/layouts/ContentCard';
import Button from '@/components/buttons/Button';
import DataTable from '@/components/tables/Datatable';
import FormSearch from '@/components/forms/FormSearch';
import ConfirmationAlert from '@/components/alerts/ConfirmationAlert';
import { useForm, usePage, router } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import {
    AlertCircle, Check, Clock, XCircle, History as HistoryIcon, Wrench, Ban, Star,
    Box, MessageSquare, ShieldCheck, Activity, ArrowRight, Loader2, Calendar, Plus
} from 'lucide-react';

interface RefinementLog {
    id: number;
    date: string;
    description: string;
    note: string | null;
    result: string;
    attachments: { name: string; url: string }[] | null;
}

interface TicketData {
    id: number;
    subject: string;
    description: string;
    status: { value: string; label: string };
    priority: { value: string; label: string } | null;
    real_priority: { value: string; label: string } | null;
    priority_reason: string | null;
    asset_item: {
        id: number;
        category_name: string;
        merk: string;
        model: string;
        serial_number: string;
    };
    attachments: { name: string; url: string; path: string }[] | null;
    diagnose: string | null;
    follow_up: string | null;
    note: string | null;
    confirm_note: string | null;
    process_note: string | null;
    process_attachments: { name: string; url: string; path: string }[] | null;
    rating: number | null;
    feedback_description: string | null;
    user: string | null;
    user_id: number;
    confirmed_by: string | null;
    processed_by: string | null;
    confirmed_at: string | null;
    processed_at: string | null;
    finished_at: string | null;
    closed_at: string | null;
    created_at: string;
    has_refinement: boolean;
}

interface PageProps {
    ticket: TicketData;
    refinementCount: number;
    priorities: { value: string; label: string }[];
    permissions?: string[];
    auth?: { user: { id: number } };
    [key: string]: unknown;
}

export default function TicketShow() {
    const { ticket, priorities, permissions, auth } = usePage<PageProps>().props;
    const canConfirm = permissions?.includes('Konfirmasi Ticketing');
    const canProcess = permissions?.includes('Proses Ticketing');
    const canRepair = permissions?.includes('Perbaikan Ticketing');
    const canFinish = permissions?.includes('Penyelesaian Ticketing');
    const canFeedback = permissions?.includes('Pemberian Feedback Ticketing');
    const isOwner = auth?.user?.id === ticket.user_id;

    const [openClose, setOpenClose] = useState(false);
    const [showImageModal, setShowImageModal] = useState<string | null>(null);

    // Datatable state
    const [isLoading, setIsLoading] = useState(false);
    const [dataTable, setDataTable] = useState<any>(null);
    const [params, setParams] = useState({
        page: 1,
        limit: 10,
        search: '',
        date: '',
        description: '',
        result: '',
        note: '',
    });

    async function loadDatatable() {
        if (!ticket.has_refinement) return;
        setIsLoading(true);
        let url = `/ticketing/tickets/${ticket.id}/refinement/datatable`;
        const queryParams = [];
        Object.entries(params).forEach(([key, value]) => {
            if (value) {
                queryParams.push(`${key}=${encodeURIComponent(value)}`);
            }
        });
        if (queryParams.length > 0) url += `?${queryParams.join('&')}`;
        const response = await fetch(url);
        const data = await response.json();
        setDataTable(data);
        setIsLoading(false);
    }

    useEffect(() => { loadDatatable(); }, [params]);

    function onChangePage(e: React.MouseEvent<HTMLAnchorElement>) {
        e.preventDefault();
        let page = e.currentTarget.href.split('page=')[1];
        if (page) {
            page = page.split('&')[0];
            setParams({ ...params, page: parseInt(page) });
        }
    }

    function onParamsChange(e: { target: { name: string; value: string | number } }) {
        setParams({ ...params, [e.target.name]: e.target.value, page: 1 });
    }

    const formatDate = (dateString: string | null) => {
        if (!dateString) return null;
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'short',
                year: 'numeric'
            });
        } catch (e) {
            return dateString;
        }
    };

    const refinementColumns = [
        {
            header: 'Tanggal',
            name: 'date',
            width: '120px',
            render: (item: RefinementLog) => (
                <span className="text-slate-700 dark:text-white font-medium">
                    {new Date(item.date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}
                </span>
            ),
            footer: (
                <div className="flex flex-col gap-2 pb-1">
                    <FormSearch name="date" type="month" onChange={onParamsChange} />
                </div>
            )
        },
        {
            header: 'Deskripsi Perbaikan',
            name: 'description',
            render: (item: RefinementLog) => (
                <div className="space-y-2">
                    <p className="text-slate-700 dark:text-white font-medium leading-relaxed">{item.description}</p>
                    {item.attachments && item.attachments.length > 0 && (
                        <div className="flex -space-x-2 overflow-hidden py-1">
                            {item.attachments.map((file, idx) => (
                                <a key={idx} href={file.url} target="_blank" rel="noopener" className="relative group">
                                    <div className="size-7 rounded-full border-2 border-white dark:border-white/10 bg-slate-100 overflow-hidden shadow-sm">
                                        <img src={file.url} alt="" className="size-full object-cover" title={file.name} />
                                    </div>
                                </a>
                            ))}
                        </div>
                    )}
                </div>
            ),
            footer: (
                <div className="flex flex-col gap-2 pb-1">
                    <FormSearch name="description" onChange={onParamsChange} placeholder="Filter Deskripsi" />
                </div>
            )
        },
        {
            header: 'Hasil',
            name: 'result',
            width: '150px',
            render: (item: RefinementLog) => (
                <span className="text-sm text-slate-700 dark:text-white font-medium">{item.result}</span>
            ),
            footer: (
                <div className="flex flex-col gap-2 pb-1">
                    <FormSearch name="result" onChange={onParamsChange} placeholder="Filter Hasil" />
                </div>
            )
        },
        {
            header: 'Catatan',
            name: 'note',
            width: '180px',
            render: (item: RefinementLog) => (
                <span className="text-sm text-slate-700 dark:text-white font-medium italic">
                    {item.note ? `"${item.note}"` : '-'}
                </span>
            ),
            footer: (
                <div className="flex flex-col gap-2 pb-1">
                    <FormSearch name="note" onChange={onParamsChange} placeholder="Filter Catatan" />
                </div>
            )
        },
    ];

    // Feedback form
    const feedbackForm = useForm({
        rating: 0,
        feedback_description: '',
    });

    const handleFeedback = (e: React.FormEvent) => {
        e.preventDefault();
        feedbackForm.post(`/ticketing/tickets/${ticket.id}/feedback`);
    };

    const getStatusStyles = (status: string) => {
        switch (status) {
            case 'process': return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
            case 'finish': return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
            case 'refinement': return 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400';
            case 'damaged': return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400';
            case 'closed': return 'bg-slate-100 text-slate-600 dark:bg-slate-700/40 dark:text-slate-300';
            default: return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
        }
    };

    const InfoRow = ({ label, value, icon }: { label: string; value: React.ReactNode; icon?: React.ReactNode }) => (
        <div className="flex items-start gap-3 py-3 border-b border-slate-100 dark:border-white/5 last:border-0 border-dashed">
            {icon && <div className="mt-0.5 text-slate-400 dark:text-slate-500">{icon}</div>}
            <div className="min-w-0 flex-1">
                <span className="text-sm font-medium text-slate-500 dark:text-slate-400">{label}</span>
                <div className="mt-0.5 text-sm text-slate-800 dark:text-white font-normal break-words">{value || <span className="italic text-slate-400">-</span>}</div>
            </div>
        </div>
    );

    return (
        <RootLayout title={`Detail Tiket #${ticket.id}`} backPath="/ticketing/tickets">
            <>
                {/* Confirm Close Dialog */}
                <ConfirmationAlert
                    isOpen={openClose}
                    setOpenModalStatus={setOpenClose}
                    title="Tutup Tiket"
                    message="Apakah Anda yakin ingin menutup tiket ini? Status akan berubah menjadi Ditutup dan user dapat memberikan feedback."
                    confirmText="Ya, Tutup"
                    cancelText="Batal"
                    type="success"
                    onConfirm={() => {
                        router.post(`/ticketing/tickets/${ticket.id}/close`, {}, {
                            onSuccess: () => setOpenClose(false),
                        });
                    }}
                />

                {/* Image Modal */}
                {showImageModal && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" onClick={() => setShowImageModal(null)}>
                        <img src={showImageModal} alt="" className="max-w-full max-h-[90vh] rounded-xl" />
                    </div>
                )}

                {/* Main Content */}
                <div className="space-y-6">


                    {/* Ticket Journey Tracker (Stepper Style) */}
                    <div className="relative pt-8 pb-0 px-4 overflow-hidden">
                        <div className="relative flex justify-between">
                            {/* Connecting Line Backdrop */}
                            <div className="absolute top-5 left-5 right-5 h-0.5 bg-slate-100 dark:bg-slate-800 -z-0" />

                            {/* Active Line Progress Container */}
                            <div className="absolute top-5 left-5 right-5 h-0.5 -z-0 overflow-hidden">
                                <div
                                    className="h-full bg-emerald-500 transition-all duration-1000"
                                    style={{ width: ticket.closed_at ? '100%' : (ticket.finished_at || ticket.has_refinement) ? '66.6%' : ticket.confirmed_at ? '33.3%' : '0%' }}
                                />
                            </div>

                            {/* Step 1: Laporan */}
                            <div className="relative z-10 flex flex-col items-center">
                                <div className="size-10 rounded-full bg-emerald-500 text-white flex items-center justify-center shadow-lg shadow-emerald-200 dark:shadow-none transition-transform hover:scale-110">
                                    <Check className="size-5" />
                                </div>
                                <div className="mt-3 text-center">
                                    <p className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Tahap 1</p>
                                    <p className="text-xs font-bold text-slate-800 dark:text-white mt-0.5 text-nowrap">Pengajuan</p>
                                    <p className="text-[9px] text-slate-400 italic mt-0.5 font-normal">{formatDate(ticket.created_at)}</p>
                                </div>
                            </div>

                            {/* Step 2: Konfirmasi */}
                            <div className="relative z-10 flex flex-col items-center">
                                <div className={`size-10 rounded-full flex items-center justify-center transition-all duration-500 ${ticket.confirmed_at ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-200 dark:shadow-none' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 border-2 border-slate-200 dark:border-slate-700'}`}>
                                    {ticket.confirmed_at ? <Check className="size-5" /> : <Clock className="size-5" />}
                                </div>
                                <div className="mt-3 text-center">
                                    <p className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Tahap 2</p>
                                    <p className={`text-xs font-bold mt-0.5 text-nowrap ${ticket.confirmed_at ? 'text-slate-800 dark:text-white' : 'text-slate-400'}`}>Penerimaan</p>
                                    {ticket.confirmed_at ? (
                                        <p className="text-[9px] text-slate-400 italic mt-0.5 font-normal">{formatDate(ticket.confirmed_at)}</p>
                                    ) : (
                                        <p className="text-[10px] text-slate-400 italic mt-1 font-normal">Menunggu</p>
                                    )}
                                </div>
                            </div>

                            {/* Step 3: Pengerjaan */}
                            <div className="relative z-10 flex flex-col items-center">
                                <div className={`size-10 rounded-full flex items-center justify-center transition-all duration-500 ${(ticket.finished_at || ticket.has_refinement) ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-200 dark:shadow-none' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 border-2 border-slate-200 dark:border-slate-700'}`}>
                                    {(ticket.finished_at || ticket.has_refinement) ? <Check className="size-5" /> : <Clock className="size-5" />}
                                </div>
                                <div className="mt-3 text-center">
                                    <p className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Tahap 3</p>
                                    <p className={`text-xs font-bold mt-0.5 text-nowrap ${(ticket.finished_at || ticket.has_refinement) ? 'text-slate-800 dark:text-white' : 'text-slate-400'}`}>Penanganan</p>
                                    {(ticket.finished_at || ticket.has_refinement) ? (
                                        <p className="text-[9px] text-slate-400 italic mt-0.5 font-normal">
                                            {formatDate(ticket.finished_at) || 'Perbaikan Aset'}
                                        </p>
                                    ) : (
                                        <p className="text-[10px] text-slate-400 italic mt-1 font-normal">Proses</p>
                                    )}
                                </div>
                            </div>

                            {/* Step 4: Penyelesaian */}
                            <div className="relative z-10 flex flex-col items-center">
                                <div className={`size-10 rounded-full flex items-center justify-center transition-all duration-500 ${ticket.closed_at ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-200 dark:shadow-none' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 border-2 border-slate-200 dark:border-slate-700'}`}>
                                    {ticket.closed_at ? <Check className="size-5" /> : <Clock className="size-5" />}
                                </div>
                                <div className="mt-3 text-center">
                                    <p className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Tahap 4</p>
                                    <p className={`text-xs font-bold mt-0.5 text-nowrap ${ticket.closed_at ? 'text-slate-800 dark:text-white' : 'text-slate-400'}`}>Closed</p>
                                    {ticket.closed_at ? (
                                        <p className="text-[9px] text-slate-400 italic mt-0.5 font-normal">{formatDate(ticket.closed_at)}</p>
                                    ) : (
                                        <p className="text-[10px] text-slate-400 italic mt-1 font-normal">Pending</p>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Unified Ticket Details Card */}
                    <ContentCard
                        title="Detail Informasi Tiket"
                        subtitle={ticket.subject}
                        backPath="/ticketing/tickets"
                    >
                        <div className="space-y-8">

                            {/* Asset Info Section */}
                            <div className="flex items-start gap-4 p-4 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-slate-700/50">
                                <div className="p-3 bg-indigo-100 dark:bg-indigo-900/40 rounded-xl text-indigo-600 dark:text-indigo-400">
                                    <Box className="size-6" />
                                </div>
                                <div className="flex-1">
                                    <p className="text-sm font-medium text-slate-500 mb-1">Detail Aset</p>
                                    <p className="text-base font-normal text-slate-800 dark:text-white">
                                        {ticket.asset_item.category_name} Merek {ticket.asset_item.merk} Model {ticket.asset_item.model} SN {ticket.asset_item.serial_number}
                                    </p>
                                </div>
                            </div>

                            <hr className="border-slate-100 dark:border-slate-800/60" />

                            {/* 3-Column Grid for Major Sections */}
                            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                                {/* 1. Informasi Pengajuan Masalah */}
                                <div className="space-y-4">
                                    <h3 className="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                                        <MessageSquare className="size-4 text-emerald-500" />
                                        Detail Laporan
                                    </h3>

                                    <div className="space-y-0">
                                        <InfoRow label="Prioritas" value={ticket.priority?.label} icon={<Star className="size-4" />} />
                                        <InfoRow label="Subject Masalah" value={ticket.subject} icon={<MessageSquare className="size-4" />} />
                                        <InfoRow label="Deskripsi Masalah" value={ticket.description} icon={<MessageSquare className="size-4" />} />
                                        {ticket.note && <InfoRow label="Catatan" value={ticket.note} icon={<MessageSquare className="size-4" />} />}
                                    </div>

                                    {/* Attachments */}
                                    {ticket.attachments && ticket.attachments.length > 0 && (
                                        <div className="pt-2">
                                            <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Foto Bukti Masalah</span>
                                            <div className="flex flex-wrap gap-2 mt-2">
                                                {ticket.attachments.map((file, idx) => (
                                                    <div key={idx} className="cursor-pointer" onClick={() => setShowImageModal(file.url)}>
                                                        <div className="size-16 rounded-lg border-2 border-slate-200 dark:border-slate-700 overflow-hidden">
                                                            <img src={file.url} alt={file.name} className="size-full object-cover hover:scale-110 transition-transform" />
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>

                                {/* 2. Informasi Konfirmasi */}
                                <div className="space-y-4">
                                    <h3 className="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                                        <ShieldCheck className="size-4 text-amber-500" />
                                        Konfirmasi Laporan
                                    </h3>
                                    {(ticket.confirmed_at || ticket.confirm_note) ? (
                                        <div className="space-y-0">
                                            <InfoRow label="Perubahan Prioritas" value={ticket.real_priority?.label} icon={<Star className="size-4" />} />
                                            {ticket.priority_reason && <InfoRow label="Alasan Perubahan" value={ticket.priority_reason} icon={<AlertCircle className="size-4" />} />}
                                            {ticket.confirm_note && <InfoRow label="Catatan Konfirmasi" value={ticket.confirm_note} icon={<MessageSquare className="size-4" />} />}
                                        </div>
                                    ) : (
                                        <div className="p-6 rounded-xl bg-slate-50 dark:bg-slate-800/20 border border-dashed border-slate-200 dark:border-slate-700/50 flex flex-col items-center justify-center text-center">
                                            <Clock className="size-8 text-slate-300 mb-2" />
                                            <p className="text-xs text-slate-400 italic">Menunggu Konfirmasi</p>
                                        </div>
                                    )}
                                </div>

                                {/* 3. Informasi Penanganan */}
                                <div className="space-y-4">
                                    <h3 className="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                                        <Wrench className="size-4 text-blue-500" />
                                        Penanganan (Proses)
                                    </h3>
                                    {ticket.processed_at ? (
                                        <>
                                            <div className="space-y-0">
                                                <InfoRow label="Diagnosa Masalah" value={ticket.diagnose} icon={<Activity className="size-4" />} />
                                                <InfoRow label="Follow Up / Tindakan" value={ticket.follow_up} icon={<ArrowRight className="size-4" />} />
                                                {ticket.process_note && <InfoRow label="Catatan Penanganan" value={ticket.process_note} icon={<MessageSquare className="size-4" />} />}
                                            </div>

                                            {/* Process Attachments */}
                                            {ticket.process_attachments && ticket.process_attachments.length > 0 && (
                                                <div className="pt-2">
                                                    <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Foto Bukti Proses</span>
                                                    <div className="flex flex-wrap gap-2 mt-2">
                                                        {ticket.process_attachments.map((file, idx) => (
                                                            <div key={idx} className="cursor-pointer" onClick={() => setShowImageModal(file.url)}>
                                                                <div className="size-16 rounded-lg border-2 border-slate-200 dark:border-slate-700 overflow-hidden">
                                                                    <img src={file.url} alt={file.name} className="size-full object-cover hover:scale-110 transition-transform" />
                                                                </div>
                                                            </div>
                                                        ))}
                                                    </div>
                                                </div>
                                            )}
                                        </>
                                    ) : (
                                        <div className="p-6 rounded-xl bg-slate-50 dark:bg-slate-800/20 border border-dashed border-slate-200 dark:border-slate-700/50 flex flex-col items-center justify-center text-center">
                                            <Wrench className="size-8 text-slate-300 mb-2" />
                                            <p className="text-xs text-slate-400 italic">Belum Diproses</p>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Refinement Info */}
                            {ticket.has_refinement && (
                                <div className="space-y-4 pt-4 border-t border-slate-100 dark:border-slate-800/60">
                                    <div className="flex flex-wrap items-center justify-between gap-4">
                                        <h3 className="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-2">
                                            <HistoryIcon className="size-4 text-purple-500" />
                                            Riwayat Perbaikan Aset
                                        </h3>
                                    </div>
                                    <div className="mt-4">
                                        {dataTable ? (
                                            <DataTable
                                                onChangePage={onChangePage}
                                                onParamsChange={onParamsChange}
                                                limit={params.limit}
                                                searchValue={params.search}
                                                dataTable={dataTable}
                                                isLoading={isLoading}
                                                columns={refinementColumns}
                                                cardItem={(item: RefinementLog) => (
                                                    <div className="px-4 py-4 hover:bg-slate-50/80 dark:hover:bg-slate-700/20 transition-colors">
                                                        <div className="flex items-start gap-3">
                                                            <div className="p-2 rounded-xl bg-purple-100 dark:bg-purple-900/40">
                                                                <Wrench className="size-4 text-purple-600 dark:text-purple-400" />
                                                            </div>
                                                            <div className="min-w-0 flex-1">
                                                                <div className="flex items-center gap-2 text-xs text-slate-400">
                                                                    <Calendar className="size-3" />
                                                                    {new Date(item.date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}
                                                                </div>
                                                                <p className="mt-1 text-sm font-medium text-slate-700 dark:text-white">{item.description}</p>
                                                                <div className="mt-2 space-y-1">
                                                                    <div>
                                                                        <span className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Hasil</span>
                                                                        <p className="text-[13px] text-slate-600 dark:text-slate-300">{item.result}</p>
                                                                    </div>
                                                                    {item.note && (
                                                                        <div>
                                                                            <span className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Catatan</span>
                                                                            <p className="text-[13px] text-slate-500 dark:text-slate-400 italic">"{item.note}"</p>
                                                                        </div>
                                                                    )}
                                                                </div>
                                                                {item.attachments && item.attachments.length > 0 && (
                                                                    <div className="flex -space-x-2 mt-2">
                                                                        {item.attachments.map((file, idx) => (
                                                                            <a key={idx} href={file.url} target="_blank" rel="noopener">
                                                                                <div className="size-7 rounded-full border-2 border-white dark:border-slate-800 overflow-hidden">
                                                                                    <img src={file.url} alt="" className="size-full object-cover" />
                                                                                </div>
                                                                            </a>
                                                                        ))}
                                                                    </div>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                )}
                                            />
                                        ) : (
                                            <div className="flex items-center justify-center py-12">
                                                <Loader2 className="size-8 text-slate-300 animate-spin" />
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>
                    </ContentCard>

                    {/* Action Buttons */}
                    <div className="flex flex-wrap gap-3">
                        {ticket.status.value === 'pending' && canConfirm && (
                            <>
                                <Button
                                    href={`/ticketing/tickets/${ticket.id}/confirm/accept`}
                                    label="Terima Laporan"
                                    icon={<Check className="size-4" />}
                                    className="flex-1 md:flex-none !bg-emerald-500 hover:!bg-emerald-600 !text-white !border-emerald-500 hover:!border-emerald-600"
                                />
                                <Button
                                    href={`/ticketing/tickets/${ticket.id}/confirm/reject`}
                                    label="Tolak Laporan"
                                    icon={<XCircle className="size-4" />}
                                    className="flex-1 md:flex-none !bg-rose-500 hover:!bg-rose-600 !text-white !border-rose-500 hover:!border-rose-600"
                                />
                            </>
                        )}
                        {ticket.status.value === 'refinement' && canRepair && (
                            <Button
                                href={`/ticketing/tickets/${ticket.id}/refinement`}
                                label="Kelola Perbaikan"
                                icon={<HistoryIcon className="size-4" />}
                                variant="outline"
                                className="flex-1 md:flex-none !text-purple-600 !border-purple-200 dark:!text-purple-400 dark:!border-purple-800/50"
                            />
                        )}
                    </div>

                    {/* Feedback/Rating Section */}
                    {ticket.rating && (
                        <ContentCard title="Feedback Pengguna">
                            <div className="space-y-3">
                                <div className="flex gap-1">
                                    {[1, 2, 3, 4, 5].map((star) => (
                                        <Star
                                            key={star}
                                            className={`size-6 ${star <= ticket.rating! ? 'fill-amber-400 text-amber-400' : 'text-slate-300 dark:text-slate-600'}`}
                                        />
                                    ))}
                                </div>
                                {ticket.feedback_description && (
                                    <p className="text-sm text-slate-600 dark:text-slate-300 italic">"{ticket.feedback_description}"</p>
                                )}
                            </div>
                        </ContentCard>
                    )}


                </div>
            </>
        </RootLayout >
    );
}
