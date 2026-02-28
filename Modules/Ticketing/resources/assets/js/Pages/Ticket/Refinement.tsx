import RootLayout from '@/components/layouts/RootLayout';
import ContentCard from '@/components/layouts/ContentCard';
import DataTable from '@/components/tables/Datatable';
import Button from '@/components/buttons/Button';
import FormSearch from '@/components/forms/FormSearch';
import MobileSearchBar from '@/components/forms/MobileSearchBar';
import ConfirmationAlert from '@/components/alerts/ConfirmationAlert';
import FloatingActionButton from '@/components/buttons/FloatingActionButton';
import { useEffect, useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import Modal from '@/components/modals/Modal';
import {
    History as HistoryIcon, Plus, Calendar, Check, Box, Wrench,
    MessageSquare, Activity, ArrowRight, ShieldCheck
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

interface PageProps {
    ticket: TicketData;
    permissions?: string[];
    [key: string]: unknown;
}

interface Params {
    search: string;
    limit: number;
    page: number;
    sort_by: string;
    sort_direction: 'asc' | 'desc';
    description?: string;
    result?: string;
    note?: string;
    date?: string;
}

export default function TicketRefinement() {
    const { ticket } = usePage<PageProps>().props;
    const [dataTable, setDataTable] = useState<PaginationData>({
        data: [], current_page: 1, last_page: 1, per_page: 10, from: 0, to: 0, total: 0,
    });
    const [params, setParams] = useState<Params>({
        search: '', limit: 10, page: 1, sort_by: 'date', sort_direction: 'desc',
    });
    const [isLoading, setIsLoading] = useState(true);
    const [openFinish, setOpenFinish] = useState(false);
    const [openDelete, setOpenDelete] = useState(false);
    const [selectedLogId, setSelectedLogId] = useState<number | null>(null);
    const [selectedPhoto, setSelectedPhoto] = useState<string | null>(null);

    const baseUrl = `/ticketing/tickets/${ticket.id}/refinement`;

    async function loadDatatable() {
        setIsLoading(true);
        let url = `${baseUrl}/datatable`;
        const queryParams: string[] = [];
        Object.keys(params).forEach((key) => {
            const value = params[key as keyof Params];
            if (value !== undefined && value !== null && value !== '') {
                queryParams.push(`${key}=${value}`);
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
                />
            }
        >
            <>
                {ticket.status.value === 'refinement' && (
                    <FloatingActionButton href={`${baseUrl}/create`} label="Tambah Perbaikan" />
                )}
                <ConfirmationAlert
                    isOpen={openFinish}
                    setOpenModalStatus={setOpenFinish}
                    title="Selesaikan Perbaikan"
                    message="Apakah Anda yakin ingin menyelesaikan perbaikan? Status tiket akan berubah menjadi Selesai."
                    confirmText="Ya, Selesaikan"
                    cancelText="Batal"
                    type="success"
                    onConfirm={() => {
                        router.post(`/ticketing/tickets/${ticket.id}/refinement/finish`, {}, {
                            onSuccess: () => setOpenFinish(false),
                        });
                    }}
                />

                <ContentCard
                    title="Informasi & Riwayat Perbaikan"
                    subtitle="Kelola dan pantau riwayat perbaikan lanjutan untuk aset terkait laporan ini"
                    backPath="/ticketing/tickets"
                    mobileFullWidth
                    bodyClassName="px-0 pt-2 pb-5 md:p-6"
                >
                    <div className="space-y-5 mb-8 pb-6 border-b border-slate-100 dark:border-slate-800/60 px-4 md:px-0">
                        {/* Ticket Context Container */}
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
                                {/* Column 1: Submission Details */}
                                <div className="space-y-0">
                                    <InfoRow label="Subject Masalah" value={ticket.subject} icon={<MessageSquare className="size-4" />} />
                                    <InfoRow label="Deskripsi Masalah" value={ticket.description} icon={<MessageSquare className="size-4" />} />
                                    {ticket.note && <InfoRow label="Catatan" value={ticket.note} icon={<MessageSquare className="size-4" />} />}
                                    {ticket.confirm_note && <InfoRow label="Catatan Konfirmasi" value={ticket.confirm_note} icon={<ShieldCheck className="size-4" />} />}
                                </div>

                                {/* Column 2: Handling Details */}
                                <div className="space-y-0">
                                    {ticket.diagnose && <InfoRow label="Diagnosa Masalah" value={ticket.diagnose} icon={<Activity className="size-4" />} />}
                                    {ticket.follow_up && <InfoRow label="Follow Up / Tindakan" value={ticket.follow_up} icon={<ArrowRight className="size-4" />} />}
                                    {ticket.process_note && <InfoRow label="Catatan Penanganan" value={ticket.process_note} icon={<MessageSquare className="size-4" />} />}
                                </div>
                            </div>

                            <div className="flex flex-col sm:flex-row gap-6 pt-4 border-t border-slate-200 dark:border-slate-700/50">
                                {/* Photos Bukti Kendala */}
                                {ticket.attachments && ticket.attachments.length > 0 && (
                                    <div className="flex-1">
                                        <span className="text-sm font-medium text-slate-500 dark:text-slate-400 block mb-2.5">Foto Bukti Masalah</span>
                                        <div className="flex flex-wrap gap-2.5">
                                            {ticket.attachments.map((file: any, idx: number) => (
                                                <button
                                                    key={idx}
                                                    type="button"
                                                    onClick={() => setSelectedPhoto(file.url)}
                                                    className="size-20 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden hover:opacity-80 transition-all hover:scale-105 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/50"
                                                >
                                                    <img src={file.url} alt={file.name} className="size-full object-cover" />
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                {/* Photos Bukti Penanganan */}
                                {ticket.process_attachments && ticket.process_attachments.length > 0 && (
                                    <div className="flex-1">
                                        <span className="text-sm font-medium text-slate-500 dark:text-slate-400 block mb-2.5">Foto Bukti Penanganan</span>
                                        <div className="flex flex-wrap gap-2.5">
                                            {ticket.process_attachments.map((file: any, idx: number) => (
                                                <button
                                                    key={idx}
                                                    type="button"
                                                    onClick={() => setSelectedPhoto(file.url)}
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

                    <div className="px-4 md:px-0 mb-4 flex flex-wrap items-center justify-between gap-4">
                        <div className="flex items-center gap-2.5">
                            <div className="p-2 bg-slate-100 dark:bg-slate-800 rounded-lg">
                                <HistoryIcon className="size-4 text-slate-600 dark:text-slate-300" />
                            </div>
                            <h3 className="text-base font-bold text-slate-800 dark:text-white">Riwayat Perbaikan</h3>
                        </div>
                        <div className="flex gap-2">
                            {ticket.status.value === 'refinement' && (
                                <Button
                                    href={`${baseUrl}/create`}
                                    variant="primary"
                                    icon={<Plus className="size-4" />}
                                    label="Tambah"
                                />
                            )}
                        </div>
                    </div>

                    <DataTable
                        onChangePage={onChangePage}
                        onParamsChange={onParamsChange}
                        limit={params.limit}
                        searchValue={params.search}
                        dataTable={dataTable}
                        isLoading={isLoading}
                        columns={columns}
                        cardItem={(item: RefinementLog) => (
                            <div className="px-4 py-4 hover:bg-slate-50/80 dark:hover:bg-slate-700/20 transition-colors">
                                <div className="flex items-start gap-3">
                                    <div className="p-2 rounded-xl bg-primary/10">
                                        <Wrench className="size-4 text-primary" />
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

                    {ticket.status.value === 'refinement' && (
                        <div className="md:pb-2 pt-6 mt-4 border-t border-slate-100 dark:border-slate-800/60">
                            <Button
                                onClick={() => setOpenFinish(true)}
                                variant="primary"
                                icon={<Check className="size-4" />}
                                label="Selesaikan Perbaikan Aset"
                                className="w-full sm:w-full !px-8 !py-3"
                            />
                        </div>
                    )}
                </ContentCard>
            </>

            <Modal
                show={selectedPhoto !== null}
                onClose={() => setSelectedPhoto(null)}
                title="Foto Kondisi Aset"
                maxWidth="2xl"
            >
                {selectedPhoto && (
                    <div className="flex items-center justify-center p-2">
                        <img
                            src={selectedPhoto}
                            alt="Foto"
                            className="max-h-[70vh] w-auto max-w-full rounded-lg object-contain shadow-sm"
                        />
                    </div>
                )}
            </Modal>
        </RootLayout>
    );
}
