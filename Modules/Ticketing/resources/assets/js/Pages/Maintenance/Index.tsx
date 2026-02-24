import RootLayout from '@/components/layouts/RootLayout';
import ContentCard from '@/components/layouts/ContentCard';
import DataTable from '@/components/tables/Datatable';
import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { Box, Calendar, Check, History as HistoryIcon, XCircle, FileSpreadsheet, Plus, AlertCircle, Clock, CheckSquare, Search, Wrench, Info, Filter } from 'lucide-react';
import { router } from '@inertiajs/react';
import ConfirmationAlert from '@/components/alerts/ConfirmationAlert';
import Button from '@/components/buttons/Button';
import FormSearch from '@/components/forms/FormSearch';
import FormSelect from '@/components/forms/FormSelect';
import FormSearchSelect from '@/components/forms/FormSearchSelect';
import MobileSearchBar from '@/components/forms/MobileSearchBar';
import Tooltip from '@/components/commons/Tooltip';
import CheckPermissions from '@/components/utils/CheckPermissions';
import MaintenanceCardItem from './MaintenanceCardItem';
import Modal from '@/components/modals/Modal';

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
    status: {
        value: string;
        label: string;
    };
    note: string | null;
    user: string | null;
    is_actionable: boolean;
}

interface PaginationData {
    data: Maintenance[];
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
    years?: number[];
    [key: string]: unknown;
}

interface Params {
    search: string;
    limit: number;
    page: number;
    sort_by: string;
    sort_direction: 'asc' | 'desc';
    year: number | string;
    status?: string;
    category_name?: string;
    serial_number?: string;
    estimation_date?: string;
    actual_date?: string;
}

export default function MaintenanceIndex() {
    const { permissions, years = [] } = usePage<PageProps>().props;
    const canManage = permissions?.includes('Kelola Data Asset');
    const canProcess = permissions?.includes('Proses Maintenance');
    const canConfirm = permissions?.includes('Konfirmasi Proses Maintenance');
    const [dataTable, setDataTable] = useState<PaginationData>({
        data: [],
        current_page: 1,
        last_page: 1,
        per_page: 10,
        from: 0,
        to: 0,
        total: 0,
    });
    const [params, setParams] = useState<Params>({
        search: '',
        limit: 10,
        page: 1,
        sort_by: 'estimation_date',
        sort_direction: 'asc',
        year: years.length > 0 ? years[0] : new Date().getFullYear(),
        status: '',
        category_name: '',
        serial_number: '',
        estimation_date: '',
        actual_date: '',
    });

    const [openConfirm, setOpenConfirm] = useState(false);
    const [selectedId, setSelectedId] = useState<number | null>(null);
    const [isFilterModalOpen, setIsFilterModalOpen] = useState(false);

    const [isLoading, setIsLoading] = useState(true);

    const baseUrl = '/ticketing/maintenances';

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

        if (queryParams.length > 0) {
            url += `?${queryParams.join('&')}`;
        }

        const response = await fetch(url);
        const data = await response.json();

        setDataTable(data);
        setIsLoading(false);
    }

    useEffect(() => {
        loadDatatable();
    }, [params]);

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

    function getPrintUrl() {
        let url = `${baseUrl}/print/excel`;
        const queryParams: string[] = [];
        Object.keys(params).forEach((key) => {
            const value = params[key as keyof Params];
            if (value !== undefined && value !== null && value !== '') {
                queryParams.push(`${key}=${value}`);
            }
        });
        if (queryParams.length > 0) {
            url += `?${queryParams.join('&')}`;
        }
        return url;
    }

    function handleConfirm(id: number) {
        setSelectedId(id);
        setOpenConfirm(true);
    }

    const getStatusStyles = (status: string) => {
        switch (status) {
            case 'finish':
                return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
            case 'confirmed':
                return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
            case 'refinement':
                return 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400';
            case 'cancelled':
                return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400';
            default:
                return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
        }
    };

    return (
        <RootLayout
            title="Jadwal Maintenance"
            mobileSearchBar={
                <MobileSearchBar
                    searchValue={params.search}
                    onSearchChange={onParamsChange}
                    placeholder="Cari maintenance..."
                    actionButton={
                        <div className="flex items-center gap-1.5">
                            <div className="hidden sm:block">
                                <FormSearchSelect
                                    name="year"
                                    value={String(params.year)}
                                    onChange={onParamsChange}
                                    options={years.map(y => ({ value: String(y), label: String(y) }))}
                                    className="w-[90px]"
                                />
                            </div>
                            <div className="flex items-center gap-1">
                                <button
                                    onClick={() => setIsFilterModalOpen(true)}
                                    className="p-2 text-slate-500 hover:text-primary dark:text-slate-400 dark:hover:text-primary transition-colors"
                                >
                                    <Filter className="size-4" />
                                </button>
                                <a href={getPrintUrl()} target="_blank" className="p-2 text-slate-500" rel="noreferrer">
                                    <FileSpreadsheet className="size-4" />
                                </a>
                            </div>
                        </div>

                    }
                />
            }
        >
            <>
                <ConfirmationAlert
                    isOpen={openConfirm}
                    setOpenModalStatus={setOpenConfirm}
                    title="Konfirmasi Maintenance"
                    message="Apakah Anda yakin ingin mengonfirmasi hasil maintenance ini? Status akan berubah menjadi Terkonfirmasi (Confirmed) dan data pengerjaan tidak dapat diubah lagi."
                    confirmText="Ya, Konfirmasi"
                    cancelText="Batal"
                    type="success"
                    onConfirm={() => {
                        if (selectedId) {
                            router.post(`/ticketing/maintenances/${selectedId}/confirm`, {}, {
                                onSuccess: () => {
                                    setOpenConfirm(false);
                                    loadDatatable();
                                }
                            });
                        }
                    }}
                />
                <Modal show={isFilterModalOpen} onClose={() => setIsFilterModalOpen(false)} title="Filter Data Maintenance" maxWidth="md">
                    <div className="space-y-4">
                        <div className="space-y-2">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-300">Tahun</label>
                            <FormSelect
                                name="year"
                                value={params.year}
                                onChange={onParamsChange}
                                options={years.map(y => ({ value: y, label: String(y) }))}
                            />
                        </div>
                        <div className="space-y-2">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-300">Kategori Asset</label>
                            <FormSearch name="category_name" value={params.category_name} onChange={onParamsChange} placeholder="Tuliskan nama kategori" />
                        </div>
                        <div className="space-y-2">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-300">Serial Number</label>
                            <FormSearch name="serial_number" value={params.serial_number} onChange={onParamsChange} placeholder="Tuliskan S/N" />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <label className="text-sm font-medium text-slate-700 dark:text-slate-300">Estimasi Tanggal</label>
                                <FormSearch name="estimation_date" type="month" value={params.estimation_date} onChange={onParamsChange} placeholder="Bulan-Tahun" />
                            </div>
                            <div className="space-y-2">
                                <label className="text-sm font-medium text-slate-700 dark:text-slate-300">Tanggal Aktual</label>
                                <FormSearch name="actual_date" type="month" value={params.actual_date} onChange={onParamsChange} placeholder="Bulan-Tahun" />
                            </div>
                        </div>
                        <div className="space-y-2">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-300">Status</label>
                            <FormSearchSelect
                                name="status"
                                value={params.status || ''}
                                options={[
                                    { value: '', label: 'Semua Status' },
                                    { value: 'pending', label: 'Sedang Berjalan' },
                                    { value: 'refinement', label: 'Perlu Perbaikan' },
                                    { value: 'finish', label: 'Selesai' },
                                    { value: 'confirmed', label: 'Terkonfirmasi' },
                                    { value: 'cancelled', label: 'Dibatalkan' }
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
                    title="Jadwal Maintenance"
                    subtitle="Monitoring jadwal pemeliharaan asset tahunan"
                    mobileFullWidth
                    bodyClassName="px-0 pt-2 pb-5 md:p-6"
                >
                    <DataTable
                        onChangePage={onChangePage}
                        onParamsChange={onParamsChange}
                        limit={params.limit}
                        searchValue={params.search}
                        dataTable={dataTable}
                        isLoading={isLoading}
                        sortBy={params.sort_by}
                        sortDirection={params.sort_direction}
                        cardItem={(item: Maintenance) => (
                            <MaintenanceCardItem
                                item={item}
                                canProcess={canProcess}
                                canConfirm={canConfirm}
                                onConfirm={handleConfirm}
                            />
                        )}
                        additionalHeaderElements={
                            <div className="flex gap-2">
                                <Tooltip text="Export Excel">
                                    <Button href={getPrintUrl()} className="!bg-transparent !p-2 !text-black hover:opacity-75 dark:!text-white" icon={<FileSpreadsheet className="size-4" />} target="_blank" />
                                </Tooltip>
                            </div>
                        }
                        onHeaderClick={(columnName: string) => {
                            const newSortDirection = params.sort_by === columnName && params.sort_direction === 'asc' ? 'desc' : 'asc';
                            setParams((prevParams) => ({
                                ...prevParams,
                                sort_by: columnName,
                                sort_direction: newSortDirection,
                            }));
                        }}
                        columns={[
                            {
                                header: 'Asset',
                                render: (item: Maintenance) => (
                                    <div className="flex flex-col">
                                        <div className="flex items-center gap-2">
                                            <Box className="size-4 text-primary" />
                                            <span className="font-medium">{item.asset_item.category_name}</span>
                                        </div>
                                        <span className="text-xs text-slate-500">{item.asset_item.merk} / {item.asset_item.model}</span>
                                    </div>
                                ),
                                footer: (
                                    <div className="flex flex-col gap-2 pb-1">
                                        <FormSearch name="category_name" onChange={onParamsChange} placeholder="Filter Asset" />
                                    </div>
                                )
                            },
                            {
                                header: 'Serial Number',
                                render: (item: Maintenance) => <span className="text-sm font-mono text-slate-600 dark:text-slate-400">{item.asset_item.serial_number}</span>,
                                footer: (
                                    <div className="flex flex-col gap-2 pb-1">
                                        <FormSearch name="serial_number" onChange={onParamsChange} placeholder="Filter S/N" />
                                    </div>
                                )
                            },
                            {
                                name: 'estimation_date',
                                header: 'Estimasi Tanggal',
                                render: (item: Maintenance) => (
                                    <div className="flex items-center gap-2 text-sm">
                                        <Calendar className="size-3.5 text-slate-400" />
                                        {new Date(item.estimation_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}
                                    </div>
                                ),
                                footer: (
                                    <div className="flex flex-col gap-2 pb-1">
                                        <FormSearch name="estimation_date" type="month" onChange={onParamsChange} />
                                    </div>
                                )
                            },
                            {
                                name: 'actual_date',
                                header: 'Tanggal Aktual',
                                render: (item: Maintenance) => (
                                    item.actual_date ? (
                                        <div className="flex items-center gap-2 text-sm">
                                            <Calendar className="size-3.5 text-slate-400" />
                                            {new Date(item.actual_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}
                                        </div>
                                    ) : (
                                        <div className="flex items-center gap-2 text-sm text-slate-400 opacity-60">
                                            <Calendar className="size-3.5" />
                                            <span>Belum dikerjakan</span>
                                        </div>
                                    )
                                ),
                                footer: (
                                    <div className="flex flex-col gap-2 pb-1">
                                        <FormSearch name="actual_date" type="month" onChange={onParamsChange} />
                                    </div>
                                )
                            },
                            {
                                name: 'status',
                                header: 'Status',
                                render: (item: Maintenance) => (
                                    <span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ${getStatusStyles(item.status.value)}`}>
                                        {item.status.value === 'pending' && <Clock className="size-3" />}
                                        {item.status.value === 'finish' && <Check className="size-3" />}
                                        {item.status.value === 'confirmed' && <Check className="size-3" />}
                                        {item.status.value === 'refinement' && <HistoryIcon className="size-3" />}
                                        {item.status.value === 'cancelled' && <XCircle className="size-3" />}
                                        {item.status.label}
                                    </span>
                                ),
                                footer: (
                                    <div className="flex flex-col gap-2 pb-1">
                                        <FormSearchSelect
                                            name="status"
                                            value={params.status || ''}
                                            options={[
                                                { value: '', label: 'Semua Status' },
                                                { value: 'pending', label: 'Sedang Berjalan' },
                                                { value: 'refinement', label: 'Perlu Perbaikan' },
                                                { value: 'finish', label: 'Selesai' },
                                                { value: 'confirmed', label: 'Terkonfirmasi' },
                                                { value: 'cancelled', label: 'Dibatalkan' }
                                            ]}
                                            onChange={onParamsChange}
                                        />
                                    </div>
                                )
                            },
                            {
                                header: 'Aksi',
                                render: (item: Maintenance) => (
                                    <div className="flex justify-end gap-1">
                                        {canProcess && item.status.value === 'refinement' && item.is_actionable && (
                                            <Tooltip text="Proses Perbaikan">
                                                <Button
                                                    href={`/ticketing/maintenances/${item.id}/refinement`}
                                                    variant="ghost"
                                                    className="!p-1.5 !text-purple-600 dark:!text-purple-400 hover:!bg-transparent"
                                                    icon={<Wrench className="size-4" />}
                                                />
                                            </Tooltip>
                                        )}
                                        {canProcess && (item.status.value === 'pending' || item.status.value === 'refinement' || item.status.value === 'finish') && item.is_actionable && (
                                            <Tooltip text="Maintenance Sekarang">
                                                <Button
                                                    href={`/ticketing/maintenances/${item.id}/process`}
                                                    variant="ghost"
                                                    className="!p-1.5 !text-primary dark:!text-primary hover:!bg-transparent"
                                                    icon={<Wrench className="size-4" />}
                                                />
                                            </Tooltip>
                                        )}
                                        {canConfirm && item.status.value === 'finish' && (
                                            <Tooltip text="Konfirmasi Maintenance">
                                                <Button
                                                    onClick={() => handleConfirm(item.id)}
                                                    variant="ghost"
                                                    className="!p-1.5 !text-emerald-600 dark:!text-emerald-400 hover:!bg-transparent"
                                                    icon={<Check className="size-4" />}
                                                />
                                            </Tooltip>
                                        )}
                                        <Tooltip text="Detail Maintenance">
                                            <Button
                                                href={`/ticketing/maintenances/${item.id}/detail`}
                                                variant="ghost"
                                                className="!p-1.5 !text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800"
                                                icon={<Info className="size-4" />}
                                            />
                                        </Tooltip>
                                    </div>
                                ),
                            },
                        ]}
                    />
                </ContentCard>
            </>
        </RootLayout>
    );
}
