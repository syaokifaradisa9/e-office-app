import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import DataTable from '@/components/tables/Datatable';
import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { Box, Edit, Plus, Trash2, FileSpreadsheet } from 'lucide-react';
import { TicketingPermission } from '../../types/permissions';
import { router } from '@inertiajs/react';
import ConfirmationAlert from '@/components/alerts/ConfirmationAlert';
import FormSearch from '@/components/forms/FormSearch';
import Button from '@/components/buttons/Button';
import CheckPermissions from '@/components/utils/CheckPermissions';
import MobileSearchBar from '@/components/forms/MobileSearchBar';
import FloatingActionButton from '@/components/buttons/FloatingActionButton';
import Tooltip from '@/components/commons/Tooltip';
import AssetModelCardItem from './AssetModelCardItem';

interface AssetModel {
    id: number;
    name: string;
    type: string;
    division: string | null;
    created_at: string;
}

interface PaginationData {
    data: AssetModel[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number;
    to: number;
    total: number;
    [key: string]: any;
}

interface PageProps {
    permissions?: string[];
    [key: string]: unknown;
}

interface Params {
    search: string;
    limit: number;
    page: number;
    sort_by: string;
    sort_direction: 'asc' | 'desc';
    name?: string;
    type?: string;
    division?: string;
}

export default function AssetModelIndex() {
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
        sort_by: 'name',
        sort_direction: 'asc',
    });

    const [openConfirm, setOpenConfirm] = useState(false);
    const [selectedItem, setSelectedItem] = useState<AssetModel | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    async function loadDatatable() {
        setIsLoading(true);
        let url = `/ticketing/asset-models/datatable`;
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

    function onParamsChange(e: { target: { name: string; value: string } }) {
        setParams({ ...params, [e.target.name]: e.target.value, page: 1 });
    }

    function getPrintUrl() {
        let url = `/ticketing/asset-models/print/excel`;
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

    const pageProps = usePage<PageProps>().props;
    const canManage = pageProps.permissions?.includes(TicketingPermission.ManageAssetModel);
    const canDelete = pageProps.permissions?.includes(TicketingPermission.DeleteAssetModel);

    const viewPermissions = [
        TicketingPermission.ViewAssetModelDivisi,
        TicketingPermission.ViewAllAssetModel,
        TicketingPermission.ManageAssetModel,
    ];

    return (
        <RootLayout
            title="Asset Model"
            mobileSearchBar={
                <MobileSearchBar
                    searchValue={params.search}
                    onSearchChange={onParamsChange}
                    placeholder="Cari asset model..."
                    actionButton={
                        <div className="flex items-center gap-1">
                            <a
                                href={getPrintUrl()}
                                target="_blank"
                                className="p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                                rel="noreferrer"
                            >
                                <FileSpreadsheet className="size-4" />
                            </a>
                        </div>
                    }
                />
            }
        >
            {!(pageProps.permissions?.includes(TicketingPermission.ViewAssetModelDivisi) || pageProps.permissions?.includes(TicketingPermission.ViewAllAssetModel) || pageProps.permissions?.includes(TicketingPermission.ManageAssetModel)) ? (
                <div className="flex flex-col items-center justify-center py-12 text-center">
                    <div className="mb-4 rounded-full bg-red-100 p-3 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                        <Box className="size-8" />
                    </div>
                    <h3 className="text-lg font-semibold text-slate-900 dark:text-white">Akses Ditolak</h3>
                    <p className="mt-1 text-slate-500 dark:text-slate-400">Anda tidak memiliki akses untuk melihat data asset model</p>
                </div>
            ) : (
                <>
                    <ConfirmationAlert
                        isOpen={openConfirm}
                        setOpenModalStatus={setOpenConfirm}
                        title="Konfirmasi Hapus"
                        message={`Hapus data asset model ${selectedItem?.name}? Tindakan ini tidak dapat dibatalkan.`}
                        confirmText="Ya, Hapus"
                        cancelText="Batal"
                        type="danger"
                        onConfirm={() => {
                            if (selectedItem?.id) {
                                router.delete(`/ticketing/asset-models/${selectedItem.id}/delete`, {
                                    onSuccess: () => loadDatatable(),
                                });
                            }
                        }}
                    />
                    <ContentCard
                        title="Asset Model"
                        subtitle="Kelola aset fisik maupun digital pada setiap divisi untuk kebutuhan ticketing"
                        mobileFullWidth
                        bodyClassName="px-0 pb-24 pt-2 md:p-6"
                        additionalButton={
                            <CheckPermissions permissions={[TicketingPermission.ManageAssetModel]}>
                                <Button className="hidden w-full md:flex" label="Tambah Asset Model" href="/ticketing/asset-models/create" icon={<Plus className="size-4" />} />
                            </CheckPermissions>
                        }
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
                            cardItem={(item: AssetModel) => (
                                <AssetModelCardItem
                                    item={item}
                                    onDelete={(item) => {
                                        setSelectedItem(item);
                                        setOpenConfirm(true);
                                    }}
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
                                    name: 'name',
                                    header: 'Nama Asset Model',
                                    render: (item: AssetModel) => (
                                        <div className="flex items-center gap-2">
                                            <Box className="size-4 text-primary" />
                                            <span className="font-medium">{item.name}</span>
                                        </div>
                                    ),
                                    footer: <FormSearch name="name" onChange={onParamsChange} placeholder="Filter Nama" />,
                                },
                                {
                                    name: 'type',
                                    header: 'Tipe',
                                    render: (item: AssetModel) => (
                                        <span className={`rounded-full px-2 py-0.5 text-xs font-semibold ${item.type === 'Physic'
                                            ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                                            : 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400'
                                            }`}>
                                            {item.type === 'Physic' ? 'Fisik' : 'Digital'}
                                        </span>
                                    ),
                                    footer: <FormSearch name="type" onChange={onParamsChange} placeholder="Filter Tipe" />,
                                },
                                {
                                    name: 'division',
                                    header: 'Divisi',
                                    render: (item: AssetModel) => <span className="text-gray-500 dark:text-slate-400">{item.division || '-'}</span>,
                                    footer: <FormSearch name="division" onChange={onParamsChange} placeholder="Filter Divisi" />,
                                },
                                {
                                    name: 'created_at',
                                    header: 'Dibuat',
                                    render: (item: AssetModel) => <span className="text-slate-500 dark:text-slate-500">{item.created_at}</span>,
                                },
                                ...((canManage || canDelete)
                                    ? [
                                        {
                                            header: 'Aksi',
                                            render: (item: AssetModel) => (
                                                <div className="flex justify-end gap-1">
                                                    {canManage && (
                                                        <Tooltip text="Edit">
                                                            <Button
                                                                variant="ghost"
                                                                href={`/ticketing/asset-models/${item.id}/edit`}
                                                                className="!p-1.5 !text-amber-500 hover:bg-amber-50 dark:!text-amber-400 dark:hover:bg-amber-900/20"
                                                                icon={<Edit className="size-4" />}
                                                            />
                                                        </Tooltip>
                                                    )}
                                                    {canDelete && (
                                                        <Tooltip text="Hapus">
                                                            <Button
                                                                variant="ghost"
                                                                onClick={() => {
                                                                    setSelectedItem(item);
                                                                    setOpenConfirm(true);
                                                                }}
                                                                className="!p-1.5 !text-red-500 hover:bg-red-50 dark:!text-red-400 dark:hover:bg-red-900/20"
                                                                icon={<Trash2 className="size-4" />}
                                                            />
                                                        </Tooltip>
                                                    )}
                                                </div>
                                            ),
                                        },
                                    ]
                                    : []),
                            ]}
                        />
                    </ContentCard>

                    <CheckPermissions permissions={[TicketingPermission.ManageAssetModel]}>
                        <FloatingActionButton href="/ticketing/asset-models/create" label="Tambah Asset Model" />
                    </CheckPermissions>
                </>
            )}
        </RootLayout>
    );
}
