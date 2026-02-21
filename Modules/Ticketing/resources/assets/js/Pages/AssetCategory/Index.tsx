import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import DataTable from '@/components/tables/Datatable';
import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { Box, Edit, Plus, Trash2, FileSpreadsheet, ListChecks } from 'lucide-react';
import { TicketingPermission } from '../../types/permissions';
import { router } from '@inertiajs/react';
import ConfirmationAlert from '@/components/alerts/ConfirmationAlert';
import FormSearch from '@/components/forms/FormSearch';
import Button from '@/components/buttons/Button';
import CheckPermissions from '@/components/utils/CheckPermissions';
import MobileSearchBar from '@/components/forms/MobileSearchBar';
import FloatingActionButton from '@/components/buttons/FloatingActionButton';
import Tooltip from '@/components/commons/Tooltip';
import AssetCategoryCardItem from './AssetCategoryCardItem';

interface AssetCategory {
    id: number;
    name: string;
    type: string;
    division: string | null;
    checklists_count?: number;
    maintenance_count?: number;
}

interface PaginationData {
    data: AssetCategory[];
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

export default function AssetCategoryIndex() {
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
    const [selectedItem, setSelectedItem] = useState<AssetCategory | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    async function loadDatatable() {
        setIsLoading(true);
        let url = `/ticketing/asset-categories/datatable`;
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
        let url = `/ticketing/asset-categories/print/excel`;
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
    const canManage = pageProps.permissions?.includes(TicketingPermission.ManageAssetCategory);
    const canDelete = pageProps.permissions?.includes(TicketingPermission.DeleteAssetCategory);
    const canViewChecklist = pageProps.permissions?.includes(TicketingPermission.ViewChecklist) || pageProps.permissions?.includes(TicketingPermission.ManageChecklist);

    const viewPermissions = [
        TicketingPermission.ViewAssetCategoryDivisi,
        TicketingPermission.ViewAllAssetCategory,
        TicketingPermission.ManageAssetCategory,
    ];

    return (
        <RootLayout
            title="Kategori Asset"
            mobileSearchBar={
                <MobileSearchBar
                    searchValue={params.search}
                    onSearchChange={onParamsChange}
                    placeholder="Cari kategori aset..."
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
            {!(pageProps.permissions?.includes(TicketingPermission.ViewAssetCategoryDivisi) || pageProps.permissions?.includes(TicketingPermission.ViewAllAssetCategory) || pageProps.permissions?.includes(TicketingPermission.ManageAssetCategory)) ? (
                <div className="flex flex-col items-center justify-center py-12 text-center">
                    <div className="mb-4 rounded-full bg-red-100 p-3 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                        <Box className="size-8" />
                    </div>
                    <h3 className="text-lg font-semibold text-slate-900 dark:text-white">Akses Ditolak</h3>
                    <p className="mt-1 text-slate-500 dark:text-slate-400">Anda tidak memiliki akses untuk melihat data kategori aset</p>
                </div>
            ) : (
                <>
                    <ConfirmationAlert
                        isOpen={openConfirm}
                        setOpenModalStatus={setOpenConfirm}
                        title="Konfirmasi Hapus"
                        message={`Hapus data kategori aset ${selectedItem?.name}? Tindakan ini tidak dapat dibatalkan.`}
                        confirmText="Ya, Hapus"
                        cancelText="Batal"
                        type="danger"
                        onConfirm={() => {
                            if (selectedItem?.id) {
                                router.delete(`/ticketing/asset-categories/${selectedItem.id}/delete`, {
                                    onSuccess: () => loadDatatable(),
                                });
                            }
                        }}
                    />
                    <ContentCard
                        title="Kategori Asset"
                        subtitle="Kelola aset fisik maupun digital pada setiap divisi untuk kebutuhan ticketing"
                        mobileFullWidth
                        bodyClassName="px-0 pb-24 pt-2 md:p-6"
                        additionalButton={
                            <CheckPermissions permissions={[TicketingPermission.ManageAssetCategory]}>
                                <Button className="hidden w-full md:flex" label="Tambah Kategori Asset" href="/ticketing/asset-categories/create" icon={<Plus className="size-4" />} />
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
                            cardItem={(item: AssetCategory) => (
                                <AssetCategoryCardItem
                                    item={item}
                                    canManage={canManage}
                                    canDelete={canDelete}
                                    canViewChecklist={canViewChecklist}
                                    onDelete={(item: AssetCategory) => {
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
                                    header: 'Nama Kategori Asset',
                                    render: (item: AssetCategory) => (
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
                                    render: (item: AssetCategory) => (
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
                                    render: (item: AssetCategory) => <span className="text-gray-500 dark:text-slate-400">{item.division || '-'}</span>,
                                    footer: <FormSearch name="division" onChange={onParamsChange} placeholder="Filter Divisi" />,
                                },
                                {
                                    header: 'Maintenance (Tahun)',
                                    render: (item: AssetCategory) => (
                                        <div className="flex items-center gap-1.5 font-medium text-slate-700 dark:text-slate-200">
                                            <span>
                                                {item.maintenance_count || 0} Kali
                                                {(item.maintenance_count || 0) > 0 && ` (${item.checklists_count || 0} Checklist)`}
                                            </span>
                                        </div>
                                    ),
                                },

                                ...((canManage || canDelete || canViewChecklist)
                                    ? [
                                        {
                                            header: 'Aksi',
                                            render: (item: AssetCategory) => (
                                                <div className="flex justify-end gap-1">
                                                    {canViewChecklist && (item.maintenance_count || 0) > 0 && (
                                                        <Tooltip text="Checklist">
                                                            <Button
                                                                variant="ghost"
                                                                href={`/ticketing/asset-categories/${item.id}/checklists`}
                                                                className="!p-1.5 !text-primary hover:bg-primary/10 dark:!text-primary dark:hover:bg-primary/10"
                                                                icon={<ListChecks className="size-4" />}
                                                            />
                                                        </Tooltip>
                                                    )}
                                                    {canManage && (
                                                        <Tooltip text="Edit">
                                                            <Button
                                                                variant="ghost"
                                                                href={`/ticketing/asset-categories/${item.id}/edit`}
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

                    <CheckPermissions permissions={[TicketingPermission.ManageAssetCategory]}>
                        <FloatingActionButton href="/ticketing/asset-categories/create" label="Tambah Kategori Asset" />
                    </CheckPermissions>
                </>
            )}
        </RootLayout>
    );
}
