import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import DataTable from '@/components/tables/Datatable';
import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { ClipboardCheck, Eye, Plus, Trash2, FileSpreadsheet, Check, Edit } from 'lucide-react';
import { router } from '@inertiajs/react';
import ConfirmationAlert from '@/components/alerts/ConfirmationAlert';
import Button from '@/components/buttons/Button';
import MobileSearchBar from '@/components/forms/MobileSearchBar';
import FloatingActionButton from '@/components/buttons/FloatingActionButton';
import { DivisionCardSkeleton } from '@/components/skeletons/CardSkeleton';
import Tooltip from '@/components/commons/Tooltip';

interface StockOpname {
    id: number;
    opname_date: string;
    user: string;
    division: string | null;
    status: string;
    created_at?: string;
}

interface PaginationData {
    data: StockOpname[];
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
    type: 'warehouse' | 'division' | 'all';
    [key: string]: unknown;
}

interface Params {
    search: string;
    limit: number;
    page: number;
    sort_by: string;
    sort_direction: 'asc' | 'desc';
}

const statusColors: Record<string, string> = {
    Pending: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    Proses: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    Confirmed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    Selesai: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
};

const statusLabels: Record<string, string> = {
    Pending: 'Pending',
    Proses: 'Dalam Proses',
    Confirmed: 'Dikonfirmasi',
    Selesai: 'Selesai',
};

export default function StockOpnameIndex() {
    const { props } = usePage<PageProps>();
    const { type, permissions } = props;

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
        sort_by: 'created_at',
        sort_direction: 'desc',
    });

    const [openConfirm, setOpenConfirm] = useState(false);
    const [openConfirmOpname, setOpenConfirmOpname] = useState(false);
    const [selectedOpname, setSelectedOpname] = useState<StockOpname | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    async function loadDatatable() {
        setIsLoading(true);
        let url = `/inventory/stock-opname/datatable/${type}`;
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
    }, [params, type]); // Reload when type changes

    function onChangePage(e: React.MouseEvent<HTMLAnchorElement>) {
        e.preventDefault();
        const href = e.currentTarget.href;
        let page = href.split('page=')[1];
        page = page.split('&')[0];
        setParams({ ...params, page: parseInt(page) });
    }

    function onParamsChange(e: { target: { name: string; value: string } }) {
        setParams({ ...params, [e.target.name]: e.target.value });
    }

    function getPrintUrl() {
        let url = `/inventory/stock-opname/print-excel/${type}`;
        const queryParams: string[] = [];
        Object.keys(params).forEach((key) => {
            queryParams.push(`${key}=${params[key as keyof Params]}`);
        });
        if (queryParams.length > 0) {
            url += `?${queryParams.join('&')}`;
        }
        return url;
    }

    // Global Permission Checks
    const hasCreate = permissions?.includes('tambah_stock_opname');
    const hasProcess = permissions?.includes('proses_stock_opname');
    const hasFinalize = permissions?.includes('finalisasi_stock_opname');
    const hasAllView = permissions?.includes('lihat_semua_stock_opname');

    // For the "Create" button:
    const showCreate = type !== 'all' && hasCreate;

    const titleMap = {
        warehouse: 'Stock Opname Gudang',
        division: 'Stock Opname Divisi',
        all: 'Semua Stock Opname',
    };

    return (
        <RootLayout
            title={titleMap[type]}
            mobileSearchBar={
                <MobileSearchBar
                    searchValue={params.search}
                    onSearchChange={onParamsChange}
                    placeholder="Cari stock opname..."
                    actionButton={
                        <a href={getPrintUrl()} target="_blank" className="p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200" rel="noreferrer">
                            <FileSpreadsheet className="size-4" />
                        </a>
                    }
                />
            }
        >
            <ConfirmationAlert
                isOpen={openConfirm}
                setOpenModalStatus={setOpenConfirm}
                title="Konfirmasi Hapus"
                message={`Hapus stock opname ini? Tindakan ini tidak dapat dibatalkan.`}
                confirmText="Ya, Hapus"
                cancelText="Batal"
                type="danger"
                onConfirm={() => {
                    if (selectedOpname?.id) {
                        router.delete(`/inventory/stock-opname/${type}/${selectedOpname.id}/delete`, {
                            onSuccess: () => loadDatatable(),
                        });
                    }
                }}
            />
            <ConfirmationAlert
                isOpen={openConfirmOpname}
                setOpenModalStatus={setOpenConfirmOpname}
                title="Konfirmasi Stock Opname"
                message={`Apakah Anda yakin ingin mengkonfirmasi stock opname ini? Tindakan ini tidak dapat dibatalkan.`}
                confirmText="Ya, Konfirmasi"
                cancelText="Batal"
                type="success"
                onConfirm={() => {
                    if (selectedOpname?.id) {
                        router.post(`/inventory/stock-opname/${selectedOpname.id}/confirm`, {}, {
                            onSuccess: () => loadDatatable(),
                        });
                    }
                }}
            />
            <ContentCard
                title={titleMap[type]}
                mobileFullWidth
                additionalButton={
                    showCreate && (
                        <Button className="hidden w-full md:flex" label="Buat Stock Opname" href={`/inventory/stock-opname/${type}/create`} icon={<Plus className="size-4" />} />
                    )
                }
            >
                <DataTable
                    onChangePage={onChangePage}
                    onParamsChange={onParamsChange}
                    limit={params.limit}
                    searchValue={params.search}
                    dataTable={dataTable}
                    isLoading={isLoading}
                    SkeletonComponent={DivisionCardSkeleton}
                    sortBy={params.sort_by}
                    sortDirection={params.sort_direction}
                    additionalHeaderElements={
                        <div className="flex gap-2">
                            <Button href={getPrintUrl()} className="!bg-transparent !p-2 !text-black hover:opacity-75 dark:!text-white" icon={<FileSpreadsheet className="size-4" />} target="_blank" />
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
                            name: 'opname_date',
                            header: 'Tanggal Opname',
                            render: (opname: StockOpname) => (
                                <div className="flex items-center gap-2">
                                    <ClipboardCheck className="size-4 text-primary" />
                                    <span className="font-medium">{opname.opname_date}</span>
                                </div>
                            ),
                        },
                        {
                            name: 'user',
                            header: 'Petugas',
                            render: (opname: StockOpname) => <span>{opname.user}</span>,
                        },
                        type === 'all' && {
                            name: 'division',
                            header: 'Divisi/Gudang',
                            render: (opname: StockOpname) => <span className="text-gray-500 dark:text-slate-400">{opname.division || 'Gudang Utama'}</span>,
                        },
                        {
                            name: 'status',
                            header: 'Status',
                            render: (opname: StockOpname) => (
                                <span className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${statusColors[opname.status] || 'bg-gray-100 text-gray-800'}`}>
                                    {statusLabels[opname.status] || opname.status}
                                </span>
                            ),
                        },
                        {
                            header: 'Aksi',
                            render: (opname: StockOpname) => {
                                const rowType = opname.division ? 'division' : 'warehouse';
                                const canManageRow = rowType === 'warehouse' ? hasWarehouseManage : hasDivisionManage;

                                return (
                                    <div className="flex justify-end gap-1">
                                        <Tooltip text="Lihat Detail">
                                            <Button
                                                href={`/inventory/stock-opname/${rowType}/${opname.id}/detail`}
                                                className="!bg-transparent !p-1 text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20"
                                                icon={<Eye className="size-4" />}
                                            />
                                        </Tooltip>
                                        {type !== 'all' && hasCreate && opname.status === 'Pending' && (
                                            <>
                                                <Tooltip text="Edit">
                                                    <Button
                                                        href={`/inventory/stock-opname/${rowType}/${opname.id}/edit`}
                                                        className="!bg-transparent !p-1 text-yellow-600 hover:bg-yellow-50 dark:text-yellow-400 dark:hover:bg-yellow-900/20"
                                                        icon={<Edit className="size-4" />}
                                                    />
                                                </Tooltip>
                                                <Tooltip text="Hapus">
                                                    <Button
                                                        onClick={() => {
                                                            setSelectedOpname(opname);
                                                            setOpenConfirm(true);
                                                        }}
                                                        className="!bg-transparent !p-1 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                                                        icon={<Trash2 className="size-4" />}
                                                    />
                                                </Tooltip>
                                            </>
                                        )}
                                        {type !== 'all' && hasProcess && ['Pending', 'Proses'].includes(opname.status) && (
                                            <Tooltip text="Proses Stock Opname">
                                                <Button
                                                    href={`/inventory/stock-opname/${rowType}/${opname.id}/process`}
                                                    className="!bg-transparent !p-1 text-orange-600 hover:bg-orange-50 dark:text-orange-400 dark:hover:bg-orange-900/20"
                                                    icon={<ClipboardCheck className="size-4" />}
                                                />
                                            </Tooltip>
                                        )}
                                        {type !== 'all' && hasFinalize && opname.status === 'Confirmed' && (
                                            <Tooltip text="Finalisasi">
                                                <Button
                                                    href={`/inventory/stock-opname/${rowType}/${opname.id}/finalize`}
                                                    className="!bg-transparent !p-1 text-purple-600 hover:bg-purple-50 dark:text-purple-400 dark:hover:bg-purple-900/20"
                                                    icon={<Check className="size-4 text-purple-600" />}
                                                />
                                            </Tooltip>
                                        )}
                                    </div>
                                );
                            },
                        },
                    ].filter(Boolean) as any}
                />
            </ContentCard>

            {showCreate && (
                <FloatingActionButton href={`/inventory/stock-opname/${type}/create`} label="Buat Stock Opname" />
            )}
        </RootLayout>
    );
}
