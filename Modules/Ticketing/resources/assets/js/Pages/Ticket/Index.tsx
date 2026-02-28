import RootLayout from '@/components/layouts/RootLayout';
import ContentCard from '@/components/layouts/ContentCard';
import DataTable from '@/components/tables/Datatable';
import { useEffect, useState } from 'react';
import { usePage, router, useForm } from '@inertiajs/react';
import { AlertCircle, Calendar, Check, Clock, XCircle, CheckCircle2, History as HistoryIcon, Wrench, Ban, Star, Plus, Filter, FileSpreadsheet, X, Info, FileEdit } from 'lucide-react';
import Button from '@/components/buttons/Button';
import FormSearch from '@/components/forms/FormSearch';
import FormSearchSelect from '@/components/forms/FormSearchSelect';
import FormTextArea from '@/components/forms/FormTextArea';
import MobileSearchBar from '@/components/forms/MobileSearchBar';
import Tooltip from '@/components/commons/Tooltip';
import Modal from '@/components/modals/Modal';
import FloatingActionButton from '@/components/buttons/FloatingActionButton';
import TicketCardItem from './TicketCardItem';
import ConfirmationAlert from '@/components/alerts/ConfirmationAlert';

interface Ticket {
    id: number;
    subject: string;
    description: string;
    status: { value: string; label: string };
    priority: { value: string; label: string } | null;
    real_priority: { value: string; label: string } | null;
    asset_item: {
        id: number;
        category_name: string;
        merk: string;
        model: string;
        serial_number: string;
    };
    user: string | null;
    user_id: number;
    created_at: string;
    rating: number | null;
}

interface PaginationData {
    data: Ticket[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number;
    to: number;
    total: number;
    [key: string]: unknown;
}

interface PageProps {
    permissions?: string[];
    priorities: { value: string; label: string }[];
    [key: string]: unknown;
}

interface Params {
    search: string;
    limit: number;
    page: number;
    sort_by: string;
    sort_direction: 'asc' | 'desc';
    status?: string;
    priority?: string;
    subject?: string;
}

export default function TicketIndex() {
    const { permissions, priorities, auth } = usePage<SharedData & PageProps>().props;
    const canConfirm = permissions?.includes('Konfirmasi Ticketing');
    const canProcess = permissions?.includes('Proses Ticketing');
    const canRepair = permissions?.includes('Perbaikan Ticketing');
    const canFinish = permissions?.includes('Penyelesaian Ticketing');
    const canFeedback = permissions?.includes('Pemberian Feedback Ticketing');

    const [dataTable, setDataTable] = useState<PaginationData>({
        data: [], current_page: 1, last_page: 1, per_page: 10, from: 0, to: 0, total: 0,
    });
    const [params, setParams] = useState<Params>({
        search: '', limit: 10, page: 1, sort_by: 'created_at', sort_direction: 'desc', status: '', priority: '', subject: '',
    });
    const [isFilterModalOpen, setIsFilterModalOpen] = useState(false);
    const [isLoading, setIsLoading] = useState(true);
    const [openCloseId, setOpenCloseId] = useState<number | null>(null);

    const baseUrl = '/ticketing/tickets';

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
        const href = e.currentTarget.href;
        let page = href.split('page=')[1];
        if (page) {
            page = page.split('&')[0];
            setParams({ ...params, page: parseInt(page) });
        }
    }

    function onParamsChange(e: { target: { name: string; value: string | number } }) {
        setParams({ ...params, [e.target.name]: e.target.value, page: 1 });
    }

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

    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'process': return <Wrench className="size-3" />;
            case 'finish': return <Check className="size-3" />;
            case 'refinement': return <HistoryIcon className="size-3" />;
            case 'damaged': return <Ban className="size-3" />;
            case 'closed': return <Check className="size-3" />;
            default: return <Clock className="size-3" />;
        }
    };

    const getPriorityStyles = (priority: string) => {
        switch (priority) {
            case 'high': return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400';
            case 'medium': return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
            default: return 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400';
        }
    };

    const columns = [
        {
            header: 'Pelapor',
            name: 'user',
            width: '160px',
            render: (item: Ticket) => (
                <div>
                    <span className="text-sm text-slate-700 dark:text-white font-medium block truncate max-w-[130px]" title={item.user ?? undefined}>{item.user}</span>
                    <div className="flex items-center gap-1.5 mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                        <Calendar className="size-3" />
                        {new Date(item.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}
                    </div>
                </div>
            ),
            footer: (
                <div className="flex flex-col gap-2 pb-1">
                    <FormSearch name="user" onChange={onParamsChange} placeholder="Filter Pelapor" />
                </div>
            )
        },
        {
            header: 'Asset',
            name: 'asset_item',
            render: (item: Ticket) => (
                <div className="text-sm">
                    <span className="font-medium text-slate-700 dark:text-white">{item.asset_item.category_name}</span>
                    <p className="text-xs text-slate-500 dark:text-slate-400">{item.asset_item.merk} {item.asset_item.model}</p>
                </div>
            ),
            footer: (
                <div className="flex flex-col gap-2 pb-1">
                    <FormSearch name="asset_item" onChange={onParamsChange} placeholder="Filter Asset" />
                </div>
            )
        },
        {
            header: 'Subject',
            name: 'subject',
            render: (item: Ticket) => (
                <div>
                    <span className="font-semibold text-slate-800 dark:text-white">{item.subject}</span>
                    <p className="text-xs text-slate-500 dark:text-slate-400 truncate max-w-[250px]">{item.description}</p>
                </div>
            ),
            footer: (
                <div className="flex flex-col gap-2 pb-1">
                    <FormSearch name="subject" onChange={onParamsChange} placeholder="Filter Subject" />
                </div>
            )
        },
        {
            header: 'Prioritas',
            name: 'priority',
            width: '120px',
            render: (item: Ticket) => {
                const p = item.real_priority || item.priority;
                return p ? (
                    <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider ${getPriorityStyles(p.value)}`}>
                        {p.label}
                    </span>
                ) : (
                    <span className="text-xs text-slate-400 italic">Belum ditentukan</span>
                );
            },
            footer: (
                <div className="flex flex-col gap-2 pb-1">
                    <FormSearchSelect
                        name="priority"
                        value={params.priority || ''}
                        options={[
                            { value: '', label: 'Semua' },
                            { value: 'low', label: 'Rendah' },
                            { value: 'medium', label: 'Sedang' },
                            { value: 'high', label: 'Tinggi' }
                        ]}
                        onChange={onParamsChange}
                    />
                </div>
            )
        },
        {
            header: 'Status',
            name: 'status',
            width: '150px',
            render: (item: Ticket) => (
                <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider ${getStatusStyles(item.status.value)}`}>
                    {getStatusIcon(item.status.value)}
                    {item.status.value === 'closed' ? 'Closed' : item.status.label}
                </span>
            ),
            footer: (
                <div className="flex flex-col gap-2 pb-1">
                    <FormSearchSelect
                        name="status"
                        value={params.status || ''}
                        options={[
                            { value: '', label: 'Semua' },
                            { value: 'pending', label: 'Pending' },
                            { value: 'process', label: 'Proses' },
                            { value: 'refinement', label: 'Perbaikan' },
                            { value: 'finish', label: 'Selesai' },
                            { value: 'damaged', label: 'Rusak' },
                            { value: 'closed', label: 'Closed' },
                        ]}
                        onChange={onParamsChange}
                    />
                </div>
            )
        },

        {
            header: 'Aksi',
            name: 'actions',
            width: '120px',
            render: (item: Ticket) => (
                <div className="flex items-center gap-1">
                    {item.status.value === 'pending' && canConfirm && (
                        <>
                            <Tooltip text="Terima & Konfirmasi">
                                <Button
                                    href={`/ticketing/tickets/${item.id}/confirm/accept`}
                                    variant="ghost"
                                    className="!p-1.5 !text-emerald-500 hover:!bg-emerald-50 dark:hover:!bg-emerald-500/10"
                                    icon={<Check className="size-4" />}
                                />
                            </Tooltip>
                            <Tooltip text="Tolak">
                                <Button
                                    href={`/ticketing/tickets/${item.id}/confirm/reject`}
                                    variant="ghost"
                                    className="!p-1.5 !text-rose-500 hover:!bg-rose-50 dark:hover:!bg-rose-500/10"
                                    icon={<X className="size-4" />}
                                />
                            </Tooltip>
                        </>
                    )}
                    <Tooltip text="Detail">
                        <Button
                            variant="ghost"
                            className="!p-1.5 !text-primary hover:!bg-primary/10"
                            icon={<Info className="size-4" />}
                            href={`/ticketing/tickets/${item.id}/show`}
                        />
                    </Tooltip>
                    {['process', 'finish', 'refinement'].includes(item.status.value) && canProcess && (
                        <Tooltip text="Proses">
                            <Button
                                href={`/ticketing/tickets/${item.id}/process`}
                                variant="ghost"
                                className="!p-1.5 !text-blue-500 hover:!bg-blue-50 dark:hover:!bg-blue-500/10"
                                icon={<FileEdit className="size-4" />}
                            />
                        </Tooltip>
                    )}
                    {item.status.value === 'refinement' && canRepair && (
                        <Tooltip text="Perbaikan Aset">
                            <Button
                                href={`/ticketing/tickets/${item.id}/refinement`}
                                variant="ghost"
                                className="!p-1.5 !text-purple-500 hover:!bg-purple-50 dark:hover:!bg-purple-500/10"
                                icon={<Wrench className="size-4" />}
                            />
                        </Tooltip>
                    )}
                    {item.status.value === 'finish' && canFinish && (
                        <Tooltip text="Penyelesaian Tiket">
                            <Button
                                onClick={() => setOpenCloseId(item.id)}
                                variant="ghost"
                                className="!p-1.5 !text-emerald-500 hover:!bg-emerald-50 dark:hover:!bg-emerald-500/10"
                                icon={<Check className="size-4" />}
                            />
                        </Tooltip>
                    )}
                    {item.status.value === 'closed' && canFeedback && item.user_id === auth.user.id && !item.rating && (
                        <Tooltip text="Beri Feedback">
                            <Button
                                href={`/ticketing/tickets/${item.id}/feedback`}
                                variant="ghost"
                                className="!p-1.5 !text-amber-500 hover:!bg-amber-50 dark:hover:!bg-amber-500/10"
                                icon={<Star className="size-4" />}
                            />
                        </Tooltip>
                    )}
                </div>
            )
        },
    ];

    return (
        <RootLayout
            title="Lapor Masalah"
            mobileSearchBar={
                <MobileSearchBar
                    searchValue={params.search}
                    onSearchChange={onParamsChange}
                    placeholder="Cari laporan..."
                    actionButton={
                        <div className="flex items-center gap-1">
                            <button
                                onClick={() => setIsFilterModalOpen(true)}
                                className="p-2 text-slate-500 hover:text-primary dark:text-slate-400 dark:hover:text-primary transition-colors"
                            >
                                <Filter className="size-4" />
                            </button>
                        </div>
                    }
                />
            }
        >
            <>
                <ConfirmationAlert
                    isOpen={openCloseId !== null}
                    setOpenModalStatus={(isOpen: boolean) => !isOpen && setOpenCloseId(null)}
                    title="Penyelesaian Tiket"
                    message="Apakah Anda yakin ingin menyelesaikan dan menutup tiket ini? Status akan otomatis menjadi Ditutup dan user dapat memberikan feedback."
                    confirmText="Ya, Selesaikan"
                    cancelText="Batal"
                    type="success"
                    onConfirm={() => {
                        if (openCloseId) {
                            router.post(`/ticketing/tickets/${openCloseId}/close`, {}, {
                                onSuccess: () => {
                                    setOpenCloseId(null);
                                    loadDatatable();
                                },
                            });
                        }
                    }}
                />
                <FloatingActionButton href={`${baseUrl}/create`} label="Lapor Masalah" />
                <Modal show={isFilterModalOpen} onClose={() => setIsFilterModalOpen(false)} title="Filter Laporan Masalah" maxWidth="md">
                    <div className="space-y-4">
                        <div className="space-y-2">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-300">Status</label>
                            <FormSearchSelect
                                name="status"
                                value={params.status || ''}
                                options={[
                                    { value: '', label: 'Semua Status' },
                                    { value: 'pending', label: 'Pending' },
                                    { value: 'process', label: 'Proses' },
                                    { value: 'refinement', label: 'Perbaikan' },
                                    { value: 'finish', label: 'Selesai' },
                                    { value: 'damaged', label: 'Rusak Total' },
                                    { value: 'closed', label: 'Closed' },
                                ]}
                                onChange={onParamsChange}
                            />
                        </div>
                        <div className="space-y-2">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-300">Prioritas</label>
                            <FormSearchSelect
                                name="priority"
                                value={params.priority || ''}
                                options={[
                                    { value: '', label: 'Semua Prioritas' },
                                    { value: 'low', label: 'Rendah' },
                                    { value: 'medium', label: 'Sedang' },
                                    { value: 'high', label: 'Tinggi' },
                                ]}
                                onChange={onParamsChange}
                            />
                        </div>
                        <div className="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-800 mt-4">
                            <Button onClick={() => setIsFilterModalOpen(false)} label="Terapkan Filter" />
                        </div>
                    </div>
                </Modal>
                <ContentCard
                    title="Lapor Masalah"
                    subtitle="Daftar laporan masalah dan permintaan perbaikan asset"
                    mobileFullWidth
                    bodyClassName="px-0 pt-2 pb-5 md:p-6"
                    additionalButton={
                        <Button
                            href={`${baseUrl}/create`}
                            label="Tambah Laporan"
                            icon={<Plus className="size-4" />}
                            className="hidden md:flex"
                        />
                    }
                >
                    <DataTable
                        onChangePage={onChangePage}
                        onParamsChange={onParamsChange}
                        limit={params.limit}
                        searchValue={params.search}
                        dataTable={dataTable}
                        isLoading={isLoading}
                        columns={columns}
                        cardItem={(item: Ticket) => (
                            <TicketCardItem
                                item={item}
                                canConfirm={canConfirm}
                                canProcess={canProcess}
                                canRepair={canRepair}
                                canFinish={canFinish}
                                onFinish={(id: number) => setOpenCloseId(id)}
                            />
                        )}
                    />
                </ContentCard>
            </>
        </RootLayout>
    );
}
