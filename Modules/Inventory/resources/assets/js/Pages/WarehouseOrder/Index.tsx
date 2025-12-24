import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import DataTable from '@/components/tables/Datatable';
import { useEffect, useState } from 'react';
import Button from '@/components/buttons/Button';
import { router, usePage } from '@inertiajs/react';
import { Edit, Plus, Trash2, Eye, Check, PackageCheck, X, ClipboardCheck, FileSpreadsheet, Filter, RotateCcw } from 'lucide-react';
import ConfirmationAlert from '@/components/alerts/ConfirmationAlert';
import FormSearch from '@/components/forms/FormSearch';
import FormSearchSelect from '@/components/forms/FormSearchSelect';
import Badge from '@/components/badges/Badge';
import FormInput from '@/components/forms/FormInput';
import CheckPermissions from '@/components/utils/CheckPermissions';
import WarehouseOrderCardItem from './WarehouseOrderCardItem';
import Modal from '@/components/modals/Modal';
import FormTextArea from '@/components/forms/FormTextArea';
import MobileSearchBar from '@/components/forms/MobileSearchBar';
import FloatingActionButton from '@/components/buttons/FloatingActionButton';
import { WarehouseOrderCardSkeleton } from '@/components/skeletons/CardSkeleton';
import Tooltip from '@/components/commons/Tooltip';

interface User {
    id: number;
    name: string;
}

interface Division {
    id: number;
    name: string;
}

interface WarehouseOrderItem {
    id: number;
    order_number: string;
    status: string;
    user_id: number;
    division_id: number;
    user?: { id: number; name: string } | null;
    division?: { id: number; name: string } | null;
    created_at: string;
}

interface PaginationData {
    data: WarehouseOrderItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number;
    to: number;
    total: number;
    [key: string]: unknown;
}

interface PageProps {
    users: User[];
    divisions: Division[];
    permissions?: string[];
    loggeduser?: { id: number; division_id: number | null };
    [key: string]: unknown;
}

interface Params {
    search: string;
    limit: number;
    page: number;
    order_number: string;
    status: string;
    user_id: string;
    division_id: string;
    created_at: string;
    sort_by: string;
    sort_direction: 'asc' | 'desc';
}

export default function WarehouseOrderIndex({ users = [], divisions = [] }: { users?: User[]; divisions?: Division[] }) {
    const { permissions, loggeduser: currentUser } = usePage<PageProps>().props;

    // Permission checks
    const hasCreatePermission = permissions?.includes('buat_permintaan_barang');
    const hasConfirmPermission = permissions?.includes('konfirmasi_permintaan_barang');
    const hasHandoverPermission = permissions?.includes('serah_terima_barang');
    const hasReceivePermission = permissions?.includes('terima_barang');
    const canViewList = permissions?.includes('lihat_permintaan_barang_divisi') || permissions?.includes('lihat_semua_permintaan_barang');

    // Show action column if user has any action permission
    const showActionColumn = hasConfirmPermission || hasHandoverPermission || hasReceivePermission || hasCreatePermission;

    const [dataTable, setDataTable] = useState<PaginationData>({
        data: [],
        current_page: 1,
        last_page: 1,
        per_page: 20,
        from: 0,
        to: 0,
        total: 0,
    });
    const [params, setParams] = useState<Params>({
        search: '',
        limit: 20,
        page: 1,
        order_number: '',
        status: 'ALL',
        user_id: '',
        division_id: 'ALL',
        created_at: '',
        sort_by: 'created_at',
        sort_direction: 'desc',
    });

    const [openConfirm, setOpenConfirm] = useState(false);
    const [selectedItem, setSelectedItem] = useState<WarehouseOrderItem | null>(null);
    const [confirmType, setConfirmType] = useState<'delete' | 'confirm'>('delete');

    const [openReject, setOpenReject] = useState(false);
    const [rejectionNote, setRejectionNote] = useState('');
    const [openFilter, setOpenFilter] = useState(false);
    const [filterParams, setFilterParams] = useState({
        division_id: 'ALL',
        status: 'ALL',
        created_at: '',
    });
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        if (openFilter) {
            setFilterParams({
                division_id: params.division_id,
                status: params.status,
                created_at: params.created_at,
            });
        }
    }, [openFilter]);

    function onFilterParamsChange(e: { target: { name: string; value: string } }) {
        setFilterParams({
            ...filterParams,
            [e.target.name]: e.target.value,
        });
    }

    function resetFilterParams() {
        setFilterParams({
            division_id: 'ALL',
            status: 'ALL',
            created_at: '',
        });
    }

    function applyFilter() {
        setParams({
            ...params,
            ...filterParams,
            page: 1,
        });
        setOpenFilter(false);
    }

    const divisionOptions = [
        { value: 'ALL', label: 'Semua Divisi' },
        ...divisions.map((div) => ({ value: String(div.id), label: div.name })),
    ];

    async function loadDatatable() {
        setIsLoading(true);
        let url = `/inventory/warehouse-orders/datatable`;
        const queryParams: string[] = [];

        Object.keys(params).forEach((key) => {
            queryParams.push(`${key}=${params[key as keyof Params]}`);
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
        page = page?.split('&')[0] || '1';
        setParams({ ...params, page: parseInt(page) });
    }

    function onParamsChange(e: { target: { name: string; value: string } }) {
        setParams({ ...params, [e.target.name]: e.target.value });
    }

    // Status translations (English in DB, Indonesian for display)
    const statusLabels: Record<string, string> = {
        Pending: 'Menunggu',
        Confirmed: 'Dikonfirmasi',
        Delivered: 'Diserahkan',
        Finished: 'Selesai',
        Rejected: 'Ditolak',
        Revision: 'Revisi',
    };

    const statusOptions = [
        { value: 'ALL', label: 'Semua Status' },
        { value: 'Pending', label: 'Menunggu' },
        { value: 'Confirmed', label: 'Dikonfirmasi' },
        { value: 'Delivered', label: 'Diserahkan' },
        { value: 'Finished', label: 'Selesai' },
        { value: 'Rejected', label: 'Ditolak' },
        { value: 'Revision', label: 'Revisi' },
    ];

    function getStatusColor(status: string) {
        switch (status) {
            case 'Pending':
                return 'warning';
            case 'Confirmed':
                return 'info';
            case 'Delivered':
                return 'primary';
            case 'Finished':
                return 'success';
            case 'Rejected':
                return 'danger';
            case 'Revision':
                return 'warning';
            default:
                return 'secondary';
        }
    }

    function getPrintUrl() {
        let url = `/inventory/warehouse-orders/print-excel`;
        const queryParams: string[] = [];

        Object.keys(params).forEach((key) => {
            queryParams.push(`${key}=${params[key as keyof Params]}`);
        });

        if (queryParams.length > 0) {
            url += `?${queryParams.join('&')}`;
        }

        return url;
    }

    function formatDate(dateString: string) {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    return (
        <RootLayout
            title="Permintaan Barang"
            mobileSearchBar={
                <MobileSearchBar
                    searchValue={params.search}
                    onSearchChange={onParamsChange}
                    placeholder="Cari permintaan..."
                    actionButton={
                        <div className="flex items-center gap-1">
                            {canViewList && (
                                <button
                                    onClick={() => setOpenFilter(true)}
                                    className="p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                                >
                                    <Filter className="size-4" />
                                </button>
                            )}
                            {canViewList && (
                                <a
                                    href={getPrintUrl()}
                                    target="_blank"
                                    className="p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                                    rel="noreferrer"
                                >
                                    <FileSpreadsheet className="size-4" />
                                </a>
                            )}
                        </div>
                    }
                />
            }
        >
            <ConfirmationAlert
                isOpen={openConfirm}
                setOpenModalStatus={setOpenConfirm}
                title={confirmType === 'delete' ? 'Konfirmasi Hapus' : 'Konfirmasi Permintaan'}
                message={
                    confirmType === 'delete'
                        ? `Hapus permintaan ${selectedItem?.order_number || 'ini'}? Tindakan ini tidak dapat dibatalkan.`
                        : `Konfirmasi permintaan dari ${selectedItem?.user?.name}? Status akan berubah menjadi Confirmed.`
                }
                confirmText={confirmType === 'delete' ? 'Ya, Hapus' : 'Ya, Konfirmasi'}
                cancelText="Batal"
                type={confirmType === 'delete' ? 'danger' : 'info'}
                onConfirm={() => {
                    if (selectedItem?.id) {
                        if (confirmType === 'delete') {
                            router.delete(`/inventory/warehouse-orders/${selectedItem.id}/delete`, {
                                onSuccess: () => loadDatatable(),
                            });
                        } else {
                            router.patch(
                                `/inventory/warehouse-orders/${selectedItem.id}/confirm`,
                                {},
                                {
                                    onSuccess: () => loadDatatable(),
                                },
                            );
                        }
                    }
                }}
            />

            <Modal show={openReject} onClose={() => setOpenReject(false)} title="Tolak Permintaan" maxWidth="md">
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        router.post(
                            `/inventory/warehouse-orders/${selectedItem?.id}/reject`,
                            { reason: rejectionNote },
                            {
                                onSuccess: () => {
                                    setOpenReject(false);
                                    setRejectionNote('');
                                    loadDatatable();
                                },
                            },
                        );
                    }}
                    className="space-y-4"
                >
                    <FormTextArea
                        label="Alasan Penolakan"
                        name="rejection_note"
                        value={rejectionNote}
                        onChange={(e) => setRejectionNote(e.target.value)}
                        placeholder="Masukkan alasan penolakan..."
                        required
                    />
                    <div className="flex justify-end gap-3">
                        <Button type="button" variant="secondary" onClick={() => setOpenReject(false)} label="Batal" />
                        <Button type="submit" variant="danger" label="Tolak Permintaan" />
                    </div>
                </form>
            </Modal>

            <Modal show={openFilter} onClose={() => setOpenFilter(false)} title="Filter Data" maxWidth="sm">
                <div className="space-y-4">
                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Filter Divisi</label>
                        <FormSearchSelect
                            name="division_id"
                            value={filterParams.division_id}
                            onChange={onFilterParamsChange}
                            options={divisionOptions}
                        />
                    </div>

                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Filter Status</label>
                        <FormSearchSelect name="status" value={filterParams.status} onChange={onFilterParamsChange} options={statusOptions} />
                    </div>

                    <FormInput
                        label="Filter Bulan"
                        type="month"
                        name="created_at"
                        value={filterParams.created_at}
                        onChange={onFilterParamsChange}
                    />

                    <div className="flex flex-col gap-2 pt-2">
                        <Button
                            onClick={resetFilterParams}
                            label="Reset Filter"
                            variant="secondary"
                            icon={<RotateCcw className="size-4" />}
                            className="w-full"
                        />
                        <Button onClick={applyFilter} label="Tutup & Terapkan" icon={<Check className="size-4" />} className="w-full" />
                    </div>
                </div>
            </Modal>

            <ContentCard
                title="Permintaan Barang"
                mobileFullWidth
                additionalButton={
                    hasCreatePermission ? (
                        <Button
                            className="hidden w-full md:flex"
                            label="Tambah Permintaan"
                            href="/inventory/warehouse-orders/create"
                            icon={<Plus className="size-4" />}
                        />
                    ) : null
                }
            >
                {canViewList ? (
                    <DataTable
                        additionalHeaderElements={
                            <div className="flex gap-2">
                                {canViewList && (
                                    <Button
                                        href={getPrintUrl()}
                                        className="!bg-transparent !p-2 !text-black hover:opacity-75 dark:!text-white"
                                        icon={<FileSpreadsheet className="size-4" />}
                                        target="_blank"
                                    />
                                )}
                            </div>
                        }
                        onChangePage={onChangePage}
                        onParamsChange={onParamsChange}
                        limit={params.limit}
                        searchValue={params.search}
                        dataTable={dataTable}
                        isLoading={isLoading}
                        SkeletonComponent={WarehouseOrderCardSkeleton}
                        sortBy={params.sort_by}
                        sortDirection={params.sort_direction}
                        onHeaderClick={(columnName) => {
                            const newSortDirection = params.sort_by === columnName && params.sort_direction === 'asc' ? 'desc' : 'asc';
                            setParams((prevParams) => ({
                                ...prevParams,
                                sort_by: columnName,
                                sort_direction: newSortDirection,
                            }));
                        }}
                        cardItem={(item: WarehouseOrderItem) => (
                            <WarehouseOrderCardItem
                                item={item}
                                onConfirm={(item) => {
                                    setSelectedItem(item);
                                    setConfirmType('confirm');
                                    setOpenConfirm(true);
                                }}
                                onDelete={(item) => {
                                    setSelectedItem(item);
                                    setConfirmType('delete');
                                    setOpenConfirm(true);
                                }}
                                onReject={(item) => {
                                    setSelectedItem(item);
                                    setOpenReject(true);
                                }}
                            />
                        )}
                        columns={[
                            {
                                name: 'order_number',
                                header: 'Nomor Order',
                                render: (item: WarehouseOrderItem) => (
                                    <span className="font-medium text-gray-900 dark:text-white">{item.order_number || '-'}</span>
                                ),
                                footer: (
                                    <FormSearch
                                        name="order_number"
                                        value={params.order_number}
                                        onChange={onParamsChange}
                                        placeholder="Filter No. Order"
                                    />
                                ),
                            },
                            {
                                name: 'user_id',
                                header: 'Pemohon',
                                render: (item: WarehouseOrderItem) => item.user?.name ?? '-',
                                footer: (
                                    <FormSearch name="user_id" value={params.user_id} onChange={onParamsChange} placeholder="Filter Pemohon" />
                                ),
                            },
                            {
                                name: 'division_id',
                                header: 'Divisi',
                                render: (item: WarehouseOrderItem) => item.division?.name ?? '-',
                                footer: (
                                    <FormSearchSelect
                                        name="division_id"
                                        onChange={onParamsChange}
                                        options={divisionOptions}
                                        value={params.division_id}
                                    />
                                ),
                            },
                            {
                                name: 'status',
                                header: 'Status',
                                render: (item: WarehouseOrderItem) => <Badge color={getStatusColor(item.status)}>{statusLabels[item.status] || item.status}</Badge>,
                                footer: (
                                    <FormSearchSelect name="status" value={params.status} onChange={onParamsChange} options={statusOptions} />
                                ),
                            },
                            {
                                name: 'created_at',
                                header: 'Tanggal Dibuat',
                                render: (item: WarehouseOrderItem) => formatDate(item.created_at),
                                footer: (
                                    <FormSearch
                                        name="created_at"
                                        type="month"
                                        value={params.created_at}
                                        onChange={onParamsChange}
                                        placeholder="Filter Tanggal"
                                    />
                                ),
                            },
                            {
                                header: 'Aksi',
                                render: (item: WarehouseOrderItem) => (
                                    <div className="flex justify-end gap-1">
                                        {/* Detail - always show */}
                                        <Tooltip text="Detail">
                                            <Button
                                                href={`/inventory/warehouse-orders/${item.id}`}
                                                className="!bg-transparent !p-1 !text-blue-600 hover:bg-blue-50 dark:!text-blue-400 dark:hover:bg-blue-900/20"
                                                icon={<Eye className="size-4" />}
                                            />
                                        </Tooltip>

                                        {/* Konfirmasi & Tolak */}
                                        {hasConfirmPermission && (item.status === 'Pending' || item.status === 'Revision') && (
                                            <>
                                                <Tooltip text="Konfirmasi">
                                                    <Button
                                                        onClick={() => {
                                                            setSelectedItem(item);
                                                            setConfirmType('confirm');
                                                            setOpenConfirm(true);
                                                        }}
                                                        className="!bg-transparent !p-1 !text-green-600 hover:bg-green-50 dark:!text-green-400 dark:hover:bg-green-900/20"
                                                        icon={<Check className="size-4" />}
                                                    />
                                                </Tooltip>
                                                <Tooltip text="Tolak">
                                                    <Button
                                                        onClick={() => {
                                                            setSelectedItem(item);
                                                            setOpenReject(true);
                                                        }}
                                                        className="!bg-transparent !p-1 !text-red-600 hover:bg-red-50 dark:!text-red-400 dark:hover:bg-red-900/20"
                                                        icon={<X className="size-4" />}
                                                    />
                                                </Tooltip>
                                            </>
                                        )}

                                        {/* Penyerahan */}
                                        {hasHandoverPermission && item.status === 'Confirmed' && (
                                            <Tooltip text="Serahkan Barang">
                                                <Button
                                                    href={`/inventory/warehouse-orders/${item.id}/delivery`}
                                                    className="!bg-transparent !p-1 !text-blue-600 hover:bg-blue-50 dark:!text-blue-400 dark:hover:bg-blue-900/20"
                                                    icon={<PackageCheck className="size-4" />}
                                                />
                                            </Tooltip>
                                        )}

                                        {/* Penerimaan */}
                                        {hasReceivePermission &&
                                            item.status === 'Delivered' &&
                                            (currentUser?.id === item.user_id || currentUser?.division_id === item.division_id) && (
                                                <Tooltip text="Terima Barang">
                                                    <Button
                                                        href={`/inventory/warehouse-orders/${item.id}/receive`}
                                                        className="!bg-transparent !p-1 !text-green-600 hover:bg-green-50 dark:!text-green-400 dark:hover:bg-green-900/20"
                                                        icon={<ClipboardCheck className="size-4" />}
                                                    />
                                                </Tooltip>
                                            )}

                                        {/* Edit & Delete */}
                                        {(item.status === 'Pending' || item.status === 'Revision' || item.status === 'Rejected') &&
                                            currentUser?.id === item.user_id &&
                                            hasCreatePermission && (
                                                <>
                                                    <Tooltip text="Edit">
                                                        <Button
                                                            href={`/inventory/warehouse-orders/${item.id}/edit`}
                                                            className="!bg-transparent !p-1 !text-yellow-600 hover:bg-yellow-50 dark:!text-yellow-400 dark:hover:bg-yellow-900/20"
                                                            icon={<Edit className="size-4" />}
                                                        />
                                                    </Tooltip>
                                                    <Tooltip text="Hapus">
                                                        <Button
                                                            onClick={() => {
                                                                setSelectedItem(item);
                                                                setConfirmType('delete');
                                                                setOpenConfirm(true);
                                                            }}
                                                            className="!bg-transparent !p-1 !text-red-600 hover:bg-red-50 dark:!text-red-400 dark:hover:bg-red-900/20"
                                                            icon={<Trash2 className="size-4" />}
                                                        />
                                                    </Tooltip>
                                                </>
                                            )}
                                    </div>
                                ),
                            },
                        ].filter((column) => {
                            if (column.header === 'Aksi') {
                                return showActionColumn;
                            }
                            return true;
                        })}
                    />
                ) : (
                    <div className="flex flex-col items-center justify-center py-20 text-center">
                        <div className="rounded-full bg-red-50 p-4 dark:bg-red-900/10">
                            <X className="size-10 text-red-500" />
                        </div>
                        <h3 className="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Akses Ditolak</h3>
                        <p className="mt-2 text-sm text-gray-500 dark:text-slate-400">
                            Anda tidak memiliki izin untuk melihat daftar permintaan barang.
                        </p>
                    </div>
                )}
            </ContentCard>

            {/* FAB for mobile */}
            {hasCreatePermission && <FloatingActionButton href="/inventory/warehouse-orders/create" label="Tambah Permintaan" />}
        </RootLayout>
    );
}
