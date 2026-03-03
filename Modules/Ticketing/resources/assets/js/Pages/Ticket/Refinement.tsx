import RootLayout from '@/components/layouts/RootLayout';
import ContentCard from '@/components/layouts/ContentCard';
import Button from '@/components/buttons/Button';
import DataTable from '@/components/tables/Datatable';
import { router } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { Link } from '@inertiajs/react';
import Tooltip from '@/components/commons/Tooltip';
import ConfirmationAlert from '@/components/alerts/ConfirmationAlert';
import Modal from '@/components/modals/Modal';
import { Box, Calendar, Check, Edit, Plus, Trash2, Equal, Wrench, Loader2, History as HistoryIcon, X, MessageSquare, Activity, ArrowRight, ShieldCheck, Filter } from 'lucide-react';
import MobileSearchBar from '@/components/forms/MobileSearchBar';
import FormSearch from '@/components/forms/FormSearch';

interface Attachment {
    name: string;
    url: string;
    size: number;
}

interface TicketData {
    id: number;
    subject: string;
    description: string;
    status: { value: string; label: string };
    asset_item: {
        id: number;
        category_name: string;
        merk: string;
        model: string;
        serial_number: string;
    };
    diagnose: string | null;
    follow_up: string | null;
    note: string | null;
    confirm_note: string | null;
    process_note: string | null;
    attachments: { name: string; url: string }[] | null;
    process_attachments: { name: string; url: string }[] | null;
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

interface PaginationData {
    data: RefinementLog[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number;
    to: number;
    total: number;
    [key: string]: unknown;
}

interface Props {
    ticket: TicketData;
}

export default function TicketRefinement({ ticket }: Props) {
    const [processing, setProcessing] = useState(false);
    const [openFinish, setOpenFinish] = useState(false);
    const [openDelete, setOpenDelete] = useState(false);
    const [selectedLogId, setSelectedLogId] = useState<number | null>(null);
    const [selectedImage, setSelectedImage] = useState<string | null>(null);
    const [isFilterModalOpen, setIsFilterModalOpen] = useState(false);
    const [isOpenFAB, setIsOpenFAB] = useState(false);

    const [dataTable, setDataTable] = useState<PaginationData>({
        data: [],
        current_page: 1,
        last_page: 1,
        per_page: 5,
        from: 0,
        to: 0,
        total: 0,
    });

    const [params, setParams] = useState({
        search: '',
        description: '',
        result: '',
        date: '',
        note: '',
        limit: 5,
        page: 1,
        sort_by: 'date',
        sort_direction: 'desc' as 'asc' | 'desc',
    });

    const [isLoading, setIsLoading] = useState(true);

    const baseUrl = `/ticketing/tickets/${ticket.id}/refinement`;

    const loadDatatable = async () => {
        setIsLoading(true);
        try {
            const queryParams = new URLSearchParams({
                ...params,
                page: params.page.toString(),
                limit: params.limit.toString(),
            }).toString();
            const response = await fetch(`${baseUrl}/datatable?${queryParams}`);
            const result = await response.json();
            setDataTable(result);
        } catch (error) {
            console.error(error);
        } finally {
            setIsLoading(false);
        }
    };

    useEffect(() => {
        loadDatatable();
    }, [params]);

    function onParamsChange(e: { target: { name: string; value: string } }) {
        setParams({ ...params, [e.target.name]: e.target.value, page: 1 });
    }

    function onChangePage(e: React.MouseEvent<HTMLAnchorElement>) {
        e.preventDefault();
        const url = new URL(e.currentTarget.href);
        const pageNum = url.searchParams.get('page');
        if (pageNum) setParams(prev => ({ ...prev, page: parseInt(pageNum) }));
    }

    const handleFinishRepair = () => {
        setOpenFinish(true);
    };

    const submitFinishRepair = () => {
        setProcessing(true);
        router.post(`${baseUrl}/finish`, {}, {
            onFinish: () => {
                setProcessing(false);
                setOpenFinish(false);
            }
        });
    };

    const isRefinement = ticket.status.value === 'refinement';

    const InfoRow = ({ label, value, icon }: { label: string; value: React.ReactNode; icon?: React.ReactNode }) => (
        <div className="flex items-start gap-3 py-3 border-b border-slate-100 dark:border-white/5 last:border-0 border-dashed">
            {icon && <div className="mt-0.5 text-slate-400 dark:text-slate-500">{icon}</div>}
            <div className="min-w-0 flex-1">
                <span className="text-sm font-medium text-slate-500 dark:text-slate-400">{label}</span>
                <div className="mt-0.5 text-sm text-slate-800 dark:text-white font-normal break-words">{value || <span className="italic text-slate-400">-</span>}</div>
            </div>
        </div>
    );

    const columns = [
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
        ...(isRefinement ? [{
            header: 'Aksi',
            name: 'actions',
            width: '100px',
            render: (item: RefinementLog) => (
                <div className="flex justify-end gap-1">
                    <Tooltip text="Edit Perbaikan">
                        <Button
                            variant="ghost"
                            className="!p-1.5 !text-amber-500 hover:!bg-amber-50 dark:!text-amber-400 dark:hover:!bg-amber-900/20"
                            icon={<Edit className="size-4" />}
                            href={`/ticketing/ticket-refinement/${item.id}/edit`}
                        />
                    </Tooltip>
                    <Tooltip text="Hapus Perbaikan">
                        <Button
                            variant="ghost"
                            className="!p-1.5 !text-red-500 hover:!bg-red-50 dark:!text-red-400 dark:hover:!bg-red-900/20"
                            icon={<Trash2 className="size-4" />}
                            onClick={() => {
                                setSelectedLogId(item.id);
                                setOpenDelete(true);
                            }}
                        />
                    </Tooltip>
                </div>
            )
        }] : []),
    ];

    return (
        <RootLayout
            title={`Perbaikan: ${ticket.subject}`}
            backPath={`/ticketing/tickets/${ticket.id}/show`}
            mobileSearchBar={
                <MobileSearchBar
                    searchValue={params.search}
                    onSearchChange={onParamsChange}
                    placeholder="Cari perbaikan..."
                    actionButton={
                        <button
                            onClick={() => setIsFilterModalOpen(true)}
                            className="p-2 text-slate-500 hover:text-primary dark:text-slate-400 dark:hover:text-primary transition-colors"
                        >
                            <Filter className="size-4" />
                        </button>
                    }
                />
            }
        >
            <>
                {/* Filter Modal */}
                <Modal show={isFilterModalOpen} onClose={() => setIsFilterModalOpen(false)} title="Filter Perbaikan">
                    <div className="space-y-4 p-1">
                        <div className="space-y-2">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-300">Deskripsi</label>
                            <FormSearch
                                name="description"
                                value={params.description || ''}
                                onChange={onParamsChange}
                                placeholder="Tuliskan deskripsi tindakan"
                            />
                        </div>
                        <div className="space-y-2">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-300">Hasil</label>
                            <FormSearch
                                name="result"
                                value={params.result}
                                onChange={onParamsChange}
                                placeholder="Contoh: Selesai, Normal"
                            />
                        </div>
                        <div className="space-y-2">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-300">Catatan</label>
                            <FormSearch
                                name="note"
                                value={params.note || ''}
                                onChange={onParamsChange}
                                placeholder="Tuliskan catatan perbaikan"
                            />
                        </div>
                        <div className="space-y-2">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-300">Bulan Perbaikan</label>
                            <FormSearch
                                name="date"
                                type="month"
                                value={params.date}
                                onChange={onParamsChange}
                            />
                        </div>
                        <div className="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-800 mt-4">
                            <Button onClick={() => setIsFilterModalOpen(false)} label="Terapkan Filter" />
                        </div>
                    </div>
                </Modal>

                <ConfirmationAlert
                    isOpen={openDelete}
                    setOpenModalStatus={setOpenDelete}
                    title="Hapus Log Perbaikan"
                    message="Apakah Anda yakin ingin menghapus data riwayat perbaikan ini? Tindakan ini tidak dapat dibatalkan."
                    confirmText="Ya, Hapus"
                    cancelText="Batal"
                    type="danger"
                    onConfirm={() => {
                        if (selectedLogId) {
                            router.delete(`/ticketing/ticket-refinement/${selectedLogId}/delete`, {
                                onSuccess: () => {
                                    setOpenDelete(false);
                                    loadDatatable();
                                }
                            });
                        }
                    }}
                />
                <ConfirmationAlert
                    isOpen={openFinish}
                    setOpenModalStatus={setOpenFinish}
                    title="Selesaikan Perbaikan"
                    message="Apakah Anda yakin ingin menyelesaikan perbaikan? Status tiket akan berubah menjadi Selesai dan status aset akan dikembalikan menjadi tersedia."
                    confirmText="Ya, Selesaikan"
                    cancelText="Batal"
                    type="success"
                    onConfirm={submitFinishRepair}
                />

                <ContentCard
                    title={`Perbaikan: ${ticket.subject}`}
                    subtitle="Kelola dan pantau riwayat perbaikan lanjutan untuk aset terkait laporan ini"
                    backPath={`/ticketing/tickets/${ticket.id}/show`}
                    mobileFullWidth
                    bodyClassName="px-0 pb-24 pt-2 md:p-6"
                >
                    <div className="flex flex-col md:space-y-12">
                        {/* Info Section - Desktop only */}
                        <div className="hidden md:block">
                            <div className="p-4 sm:p-5 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700/50 rounded-xl space-y-5">
                                {/* Asset Info */}
                                <div className="flex items-start gap-3 pb-4 border-b border-slate-200 dark:border-slate-700/50">
                                    <div className="p-2 bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 rounded-lg shrink-0 mt-0.5">
                                        <Box className="size-5" />
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-slate-500 dark:text-slate-400">Aset Terkait</p>
                                        <p className="text-sm font-normal text-slate-800 dark:text-slate-200 mt-0.5">
                                            {ticket.asset_item.category_name} Merek {ticket.asset_item.merk} Model {ticket.asset_item.model} SN {ticket.asset_item.serial_number}
                                        </p>
                                    </div>
                                </div>

                                {/* Subject & Description - 2 Column Grid */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-x-8 items-start">
                                    <div className="space-y-0">
                                        <InfoRow label="Subject Masalah" value={ticket.subject} icon={<MessageSquare className="size-4" />} />
                                        <InfoRow label="Deskripsi Masalah" value={ticket.description} icon={<MessageSquare className="size-4" />} />
                                        {ticket.note && <InfoRow label="Catatan" value={ticket.note} icon={<MessageSquare className="size-4" />} />}
                                        {ticket.confirm_note && <InfoRow label="Catatan Konfirmasi" value={ticket.confirm_note} icon={<ShieldCheck className="size-4" />} />}
                                    </div>
                                    <div className="space-y-0">
                                        {ticket.diagnose && <InfoRow label="Diagnosa Masalah" value={ticket.diagnose} icon={<Activity className="size-4" />} />}
                                        {ticket.follow_up && <InfoRow label="Follow Up / Tindakan" value={ticket.follow_up} icon={<ArrowRight className="size-4" />} />}
                                        {ticket.process_note && <InfoRow label="Catatan Penanganan" value={ticket.process_note} icon={<MessageSquare className="size-4" />} />}
                                    </div>
                                </div>

                                <div className="flex flex-col sm:flex-row gap-6 pt-4 border-t border-slate-200 dark:border-slate-700/50">
                                    {ticket.attachments && ticket.attachments.length > 0 && (
                                        <div className="flex-1">
                                            <span className="text-sm font-medium text-slate-500 dark:text-slate-400 block mb-2.5">Foto Bukti Masalah</span>
                                            <div className="flex flex-wrap gap-2.5">
                                                {ticket.attachments.map((file: any, idx: number) => (
                                                    <button
                                                        key={idx}
                                                        type="button"
                                                        onClick={() => setSelectedImage(file.url)}
                                                        className="size-20 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden hover:opacity-80 transition-all hover:scale-105 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/50"
                                                    >
                                                        <img src={file.url} alt={file.name} className="size-full object-cover" />
                                                    </button>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                    {ticket.process_attachments && ticket.process_attachments.length > 0 && (
                                        <div className="flex-1">
                                            <span className="text-sm font-medium text-slate-500 dark:text-slate-400 block mb-2.5">Foto Bukti Penanganan</span>
                                            <div className="flex flex-wrap gap-2.5">
                                                {ticket.process_attachments.map((file: any, idx: number) => (
                                                    <button
                                                        key={idx}
                                                        type="button"
                                                        onClick={() => setSelectedImage(file.url)}
                                                        className="size-20 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden hover:opacity-80 transition-all hover:scale-105 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/50"
                                                    >
                                                        <img src={file.url} alt={file.name} className="size-full object-cover" />
                                                    </button>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* History Section */}
                        <div className="space-y-4 md:pt-4">
                            <div className="hidden md:flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <div className="p-2 rounded-xl bg-slate-50 dark:bg-white/5">
                                        <HistoryIcon className="size-4 text-slate-500 dark:text-white/60" />
                                    </div>
                                    <h3 className="text-base font-black text-slate-800 dark:text-white">Riwayat Perbaikan</h3>
                                </div>
                                {isRefinement && (
                                    <Button
                                        href={`${baseUrl}/create`}
                                        variant="primary"
                                        icon={<Plus className="size-4" />}
                                        label="Tambah"
                                    />
                                )}
                            </div>

                            <div className="overflow-hidden">
                                <DataTable
                                    dataTable={dataTable}
                                    columns={columns}
                                    onParamsChange={onParamsChange}
                                    onChangePage={onChangePage}
                                    limit={params.limit}
                                    isLoading={isLoading}
                                    searchValue={params.search}
                                    onSearchChange={onParamsChange}
                                    sortBy={params.sort_by}
                                    sortDirection={params.sort_direction}
                                    cardItem={(item: RefinementLog) => (
                                        <div className="group transition-colors duration-150 hover:bg-slate-50/80 dark:hover:bg-slate-700/20">
                                            <div className="flex items-start gap-3.5 px-4 py-4">
                                                {/* Icon */}
                                                <div className="mt-0.5 flex size-10 flex-shrink-0 items-center justify-center rounded-xl bg-primary/10 dark:bg-primary/15">
                                                    <Wrench className="size-5 text-primary" />
                                                </div>

                                                {/* Content */}
                                                <div className="min-w-0 flex-1">
                                                    {/* Title: Date */}
                                                    <h3 className="truncate text-[15px] font-semibold text-slate-800 dark:text-white">
                                                        {new Date(item.date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}
                                                    </h3>

                                                    {/* Deskripsi */}
                                                    <p className="mt-0 text-[13px] text-slate-500 dark:text-slate-400 leading-relaxed">
                                                        {item.description}
                                                    </p>

                                                    {/* Hasil & Catatan */}
                                                    <div className="mt-2.5 space-y-2">
                                                        <div>
                                                            <span className="text-[11px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Hasil</span>
                                                            <p className="mt-0.5 text-[13px] font-medium text-emerald-700 dark:text-emerald-400">
                                                                {item.result}
                                                            </p>
                                                        </div>

                                                        {item.note && (
                                                            <div>
                                                                <span className="text-[11px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Catatan</span>
                                                                <p className="mt-0.5 text-[13px] text-slate-500 dark:text-slate-400 italic">
                                                                    "{item.note}"
                                                                </p>
                                                            </div>
                                                        )}
                                                    </div>

                                                    {/* Attachments */}
                                                    {item.attachments && item.attachments.length > 0 && (
                                                        <div className="flex gap-2 mt-2 overflow-x-auto pb-1 no-scrollbar">
                                                            {item.attachments.map((file, idx) => (
                                                                <button key={idx} onClick={() => setSelectedImage(file.url)} className="flex-shrink-0 cursor-pointer">
                                                                    <div className="size-12 rounded-xl border border-slate-100 dark:border-white/5 bg-slate-50 dark:bg-white/5 overflow-hidden shadow-sm">
                                                                        <img src={file.url} alt="" className="size-full object-cover" />
                                                                    </div>
                                                                </button>
                                                            ))}
                                                        </div>
                                                    )}

                                                    {/* Actions */}
                                                    {isRefinement && (
                                                        <div className="mt-3 grid grid-cols-2 gap-2">
                                                            <Button
                                                                href={`/ticketing/ticket-refinement/${item.id}/edit`}
                                                                variant="outline"
                                                                className="!py-2 !bg-transparent !text-amber-600 !border-amber-200 hover:!bg-amber-50 dark:!text-amber-400 dark:!border-amber-800/50 dark:hover:!bg-amber-900/20"
                                                                icon={<Edit className="size-4" />}
                                                                label="Edit"
                                                            />
                                                            <Button
                                                                variant="outline"
                                                                className="!py-2 !bg-transparent !text-red-500 !border-red-200 hover:!bg-red-50 dark:!text-red-400 dark:!border-red-800/50 dark:hover:!bg-red-900/20"
                                                                icon={<Trash2 className="size-4" />}
                                                                label="Hapus"
                                                                onClick={() => {
                                                                    setSelectedLogId(item.id);
                                                                    setOpenDelete(true);
                                                                }}
                                                            />
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                />
                            </div>
                        </div>

                        {isRefinement && (
                            <div className="mt-8 px-4 md:px-0 hidden md:block">
                                <Button
                                    onClick={handleFinishRepair}
                                    label="Selesaikan Perbaikan Aset"
                                    icon={<Check className="size-4" />}
                                    isLoading={processing}
                                    variant="primary"
                                    className="w-full !rounded-xl py-3 shadow-lg shadow-primary/10 active:scale-95 transition-all font-bold"
                                />
                            </div>
                        )}
                    </div>
                </ContentCard>

                {/* FAB Menu - Mobile */}
                {isRefinement && (
                    <div className="fixed bottom-[88px] right-5 z-40 md:hidden flex flex-col items-end gap-3">
                        {/* Backdrop */}
                        {isOpenFAB && (
                            <div
                                className="fixed inset-0 bg-slate-900/20 dark:bg-slate-900/40 backdrop-blur-sm z-[-1]"
                                onClick={() => setIsOpenFAB(false)}
                            />
                        )}

                        {/* Action Items */}
                        <div className={`flex flex-col items-end gap-3 transition-all duration-300 origin-bottom ${isOpenFAB ? 'scale-100 opacity-100' : 'scale-0 opacity-0 pointer-events-none absolute bottom-16 right-0'}`}>
                            <div className="flex items-center gap-3">
                                <span className="bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 px-3 py-1.5 rounded-lg shadow-lg text-sm font-medium border border-slate-100 dark:border-white/5">
                                    Selesaikan Perbaikan
                                </span>
                                <button
                                    onClick={() => {
                                        setIsOpenFAB(false);
                                        handleFinishRepair();
                                    }}
                                    className="flex items-center justify-center size-12 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white shadow-[0_4px_14px_0_rgba(5,150,105,0.39)] transition-transform active:scale-95"
                                >
                                    {processing ? <Loader2 className="size-5 animate-spin" /> : <Check className="size-5" strokeWidth={3} />}
                                </button>
                            </div>

                            <div className="flex items-center gap-3">
                                <span className="bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 px-3 py-1.5 rounded-lg shadow-lg text-sm font-medium border border-slate-100 dark:border-white/5">
                                    Tambah Perbaikan
                                </span>
                                <Link
                                    href={`${baseUrl}/create`}
                                    className="flex items-center justify-center size-12 rounded-full bg-primary text-white shadow-lg shadow-primary/30 transition-transform active:scale-95"
                                >
                                    <Plus className="size-5" strokeWidth={2.5} />
                                </Link>
                            </div>
                        </div>

                        {/* Main Toggle Button */}
                        <button
                            onClick={() => setIsOpenFAB(!isOpenFAB)}
                            className={`flex items-center justify-center size-14 rounded-full shadow-lg transition-transform duration-300 active:scale-95 ${isOpenFAB ? 'bg-rose-500 text-white rotate-90 shadow-rose-500/30' : 'bg-primary text-white shadow-primary/30 hover:scale-105'}`}
                        >
                            {isOpenFAB ? <X className="size-6" strokeWidth={2.5} /> : <Equal className="size-6" strokeWidth={2} />}
                        </button>
                    </div>
                )}

                {/* Image Preview Modal */}
                <Modal show={!!selectedImage} onClose={() => setSelectedImage(null)} title="Preview Lampiran" maxWidth="2xl">
                    <div className="flex flex-col items-center">
                        {selectedImage && (
                            <img
                                src={selectedImage}
                                alt="Preview"
                                className="max-w-full max-h-[70vh] rounded-lg shadow-lg object-contain"
                            />
                        )}
                        <div className="mt-6 w-full">
                            <Button
                                variant="outline"
                                label="Tutup"
                                onClick={() => setSelectedImage(null)}
                                className="w-full"
                            />
                        </div>
                    </div>
                </Modal>
            </>
        </RootLayout>
    );
}
