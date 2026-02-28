import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import DataTable from '@/components/tables/Datatable';
import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { Box, Edit, Plus, Trash2, FileSpreadsheet, User, Hash, Shield } from 'lucide-react';
import { TicketingPermission } from '../../types/permissions';
import { router } from '@inertiajs/react';
import ConfirmationAlert from '@/components/alerts/ConfirmationAlert';
import FormSearch from '@/components/forms/FormSearch';
import Button from '@/components/buttons/Button';
import CheckPermissions from '@/components/utils/CheckPermissions';
import MobileSearchBar from '@/components/forms/MobileSearchBar';
import Tooltip from '@/components/commons/Tooltip';
import FloatingActionButton from '@/components/buttons/FloatingActionButton';
import Badge from '@/components/badges/Badge';
import AssetItemCardItem from './AssetItemCardItem';


interface AssetItem {
    id: number;
    asset_category: string;
    merk: string | null;
    model: string | null;
    serial_number: string | null;
    division: string;
    user: string;
    status: {
        value: string;
        label: string;
        color: 'primary' | 'secondary' | 'success' | 'danger' | 'warning' | 'info' | 'dark';
    };
    created_at: string;

}

interface PaginationData {
    data: AssetItem[];
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
    [key: string]: unknown;
}

interface Params {
    search: string;
    limit: number;
    page: number;
    sort_by: string;
    sort_direction: 'asc' | 'desc';
    merk?: string;
    model?: string;
    serial_number?: string;
}

export default function AssetItemIndex() {
    const { permissions } = usePage<PageProps>().props;
    const canManage = permissions?.includes(TicketingPermission.ManageAsset);
    const canDelete = permissions?.includes(TicketingPermission.DeleteAsset);

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
        sort_by: 'created_at',
        sort_direction: 'desc',
    });

    const [openConfirm, setOpenConfirm] = useState(false);
    const [selectedItem, setSelectedItem] = useState<AssetItem | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    const baseUrl = '/ticketing/assets';

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

    function onParamsChange(e: { target: { name: string; value: string } }) {
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

    return (
        <RootLayout
            title="Daftar Asset"
            mobileSearchBar={
                <MobileSearchBar
                    searchValue={params.search}
                    onSearchChange={onParamsChange}
                    placeholder="Cari asset..."
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
            <>
                <ConfirmationAlert
                    isOpen={openConfirm}
                    setOpenModalStatus={setOpenConfirm}
                    title="Konfirmasi Hapus"
                    message={`Hapus asset "${selectedItem?.asset_category} - ${selectedItem?.serial_number || selectedItem?.merk}"? Tindakan ini tidak dapat dibatalkan.`}
                    confirmText="Ya, Hapus"
                    cancelText="Batal"
                    type="danger"
                    onConfirm={() => {
                        if (selectedItem?.id) {
                            router.delete(`${baseUrl}/${selectedItem.id}/delete`, {
                                onSuccess: () => loadDatatable(),
                            });
                        }
                    }}
                />

                <ContentCard
                    title="Daftar Asset"
                    subtitle="Kelola item asset inventaris perusahaan"
                    mobileFullWidth
                    bodyClassName="px-0 pb-24 pt-2 md:p-6"
                    additionalButton={
                        canManage ? (
                            <Button className="hidden w-full md:flex" label="Tambah Asset" href={`${baseUrl}/create`} icon={<Plus className="size-4" />} />
                        ) : undefined
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
                        cardItem={(item: AssetItem) => (
                            <AssetItemCardItem
                                item={item}
                                canManage={canManage}
                                canDelete={canDelete}
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
                                header: 'Kategori Asset',
                                render: (item: AssetItem) => (
                                    <div className="flex items-center gap-2">
                                        <Box className="size-4 text-primary" />
                                        <span className="font-medium">{item.asset_category}</span>
                                    </div>
                                ),
                            },
                            {
                                name: 'merk',
                                header: 'Merk',
                                render: (item: AssetItem) => <span>{item.merk || '-'}</span>,
                                footer: <FormSearch name="merk" onChange={onParamsChange} placeholder="Filter Merk" />,
                            },
                            {
                                name: 'model',
                                header: 'Model',
                                render: (item: AssetItem) => <span>{item.model || '-'}</span>,
                                footer: <FormSearch name="model" onChange={onParamsChange} placeholder="Filter Model" />,
                            },
                            {
                                name: 'serial_number',
                                header: 'Serial Number',
                                render: (item: AssetItem) => <span>{item.serial_number || '-'}</span>,
                                footer: <FormSearch name="serial_number" onChange={onParamsChange} placeholder="Filter S/N" />,
                            },
                            {
                                header: 'Divisi',
                                render: (item: AssetItem) => (
                                    <div className="flex items-center gap-2">
                                        <Shield className="size-3.5 text-slate-400" />
                                        <span>{item.division}</span>
                                    </div>
                                ),
                            },
                            {
                                header: 'User',
                                render: (item: AssetItem) => (
                                    <div className="flex items-center gap-2">
                                        <User className="size-3.5 text-slate-400" />
                                        <span>{item.user || '-'}</span>
                                    </div>
                                ),
                            },
                            {
                                header: 'Status',
                                render: (item: AssetItem) => (
                                    <Badge color={item.status.color}>
                                        {item.status.label}
                                    </Badge>
                                ),
                            },
                            ...((canManage || canDelete)

                                ? [
                                    {
                                        header: 'Aksi',
                                        render: (item: AssetItem) => (
                                            <div className="flex justify-end gap-1">
                                                {canManage && (
                                                    <Tooltip text="Edit">
                                                        <Button
                                                            variant="ghost"
                                                            href={`${baseUrl}/${item.id}/edit`}
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

                <CheckPermissions permissions={[TicketingPermission.ManageAsset]}>
                    <FloatingActionButton href={`${baseUrl}/create`} label="Tambah Asset" />
                </CheckPermissions>
            </>
        </RootLayout>
    );
}
