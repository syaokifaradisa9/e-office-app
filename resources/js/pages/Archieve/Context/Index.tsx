import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import DataTable from '@/components/tables/Datatable';
import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { Edit, Plus, Trash2, FileSpreadsheet, Shield, Layers } from 'lucide-react';
import { router } from '@inertiajs/react';
import ConfirmationAlert from '@/components/alerts/ConfirmationAlert';
import FormSearch from '@/components/forms/FormSearch';
import Button from '@/components/buttons/Button';
import CheckPermissions from '@/components/utils/CheckPermissions';
import MobileSearchBar from '@/components/forms/MobileSearchBar';
import FloatingActionButton from '@/components/buttons/FloatingActionButton';
import { CategoryItemCardSkeleton } from '@/components/skeletons/CardSkeleton';
import Tooltip from '@/components/commons/Tooltip';
import { ArchievePermission } from '@/enums/ArchievePermission';
import ContextCardItem from './ContextCardItem';

interface Context {
    id: number;
    name: string;
    description: string | null;
    created_at?: string;
}

interface PaginationData {
    data: Context[];
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
    name: string;
    description: string;
    sort_by: string;
    sort_direction: 'asc' | 'desc';
}

export default function ContextIndex() {
    const { permissions } = usePage<PageProps>().props;
    const hasViewPermission = permissions?.includes(ArchievePermission.VIEW_CATEGORY);
    const hasManagePermission = permissions?.includes(ArchievePermission.MANAGE_CATEGORY);

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
        limit: 10,
        page: 1,
        name: '',
        description: '',
        sort_by: 'name',
        sort_direction: 'asc',
    });

    const [openConfirm, setOpenConfirm] = useState(false);
    const [selectedItem, setSelectedItem] = useState<Context | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    async function loadDatatable() {
        if (!hasViewPermission) {
            setIsLoading(false);
            return;
        }
        setIsLoading(true);
        let url = `/archieve/contexts/datatable`;
        const queryParams: string[] = [];

        Object.keys(params).forEach((key) => {
            if (params[key as keyof Params]) {
                queryParams.push(`${key}=${params[key as keyof Params]}`);
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
        page = page.split('&')[0];
        setParams({ ...params, page: parseInt(page) });
    }

    function onParamsChange(e: { target: { name: string; value: string } }) {
        setParams({ ...params, [e.target.name]: e.target.value });
    }

    function getPrintUrl() {
        let url = `/archieve/contexts/print-excel`;
        const queryParams: string[] = [];
        Object.keys(params).forEach((key) => {
            if (params[key as keyof Params]) {
                queryParams.push(`${key}=${params[key as keyof Params]}`);
            }
        });
        if (queryParams.length > 0) {
            url += `?${queryParams.join('&')}`;
        }
        return url;
    }

    return (
        <RootLayout
            title="Konteks Arsip"
            mobileSearchBar={
                hasViewPermission ? (
                    <MobileSearchBar
                        searchValue={params.search}
                        onSearchChange={onParamsChange}
                        placeholder="Cari konteks..."
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
                ) : undefined
            }
        >
            <ConfirmationAlert
                isOpen={openConfirm}
                setOpenModalStatus={setOpenConfirm}
                title="Konfirmasi Hapus"
                message={`Hapus konteks ${selectedItem?.name}? Tindakan ini tidak dapat dibatalkan.`}
                confirmText="Ya, Hapus"
                cancelText="Batal"
                type="danger"
                onConfirm={() => {
                    if (selectedItem?.id) {
                        router.delete(`/archieve/contexts/${selectedItem.id}`, {
                            onSuccess: () => loadDatatable(),
                        });
                    }
                }}
            />
            <ContentCard
                title="Konteks Arsip"
                subtitle="Kelola pengelompokan konteks arsip dokumen Anda"
                mobileFullWidth
                bodyClassName="px-0 pb-24 pt-2 md:p-6"
                additionalButton={
                    <CheckPermissions permissions={[ArchievePermission.MANAGE_CATEGORY]}>
                        <Button className="hidden w-full md:flex" label="Tambah Konteks" href="/archieve/contexts/create" icon={<Plus className="size-4" />} />
                    </CheckPermissions>
                }
            >
                {!hasViewPermission ? (
                    <div className="flex flex-col items-center justify-center py-12 text-center">
                        <div className="mb-4 rounded-full bg-red-100 p-3 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                            <Shield className="size-8" />
                        </div>
                        <h3 className="text-lg font-semibold text-slate-900 dark:text-white">Akses Ditolak</h3>
                        <p className="mt-1 text-slate-500 dark:text-slate-400">Anda tidak memiliki akses untuk melihat data konteks</p>
                    </div>
                ) : (
                    <DataTable
                        onChangePage={onChangePage}
                        onParamsChange={onParamsChange}
                        limit={params.limit}
                        searchValue={params.search}
                        dataTable={dataTable}
                        isLoading={isLoading}
                        SkeletonComponent={CategoryItemCardSkeleton}
                        sortBy={params.sort_by}
                        sortDirection={params.sort_direction}
                        cardItem={(item: Context) => (
                            <ContextCardItem
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
                        onHeaderClick={(columnName) => {
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
                                header: 'Nama Konteks',
                                render: (item: Context) => (
                                    <div className="flex items-center gap-2">
                                        <Layers className="size-4 text-primary" />
                                        <span className="font-medium">{item.name}</span>
                                    </div>
                                ),
                                footer: <FormSearch name="name" onChange={onParamsChange} placeholder="Filter Nama" />,
                            },
                            {
                                name: 'description',
                                header: 'Deskripsi',
                                render: (item: Context) => <span className="text-gray-500 dark:text-slate-400">{item.description || '-'}</span>,
                                footer: <FormSearch name="description" onChange={onParamsChange} placeholder="Filter Deskripsi" />,
                            },
                            ...(hasManagePermission
                                ? [
                                    {
                                        header: 'Aksi',
                                        render: (item: Context) => (
                                            <div className="flex justify-end gap-1">
                                                <Tooltip text="Edit">
                                                    <Button
                                                        href={`/archieve/contexts/${item.id}/edit`}
                                                        className="!bg-transparent !p-1 text-yellow-600 hover:bg-yellow-50 dark:text-yellow-400 dark:hover:bg-yellow-900/20"
                                                        icon={<Edit className="size-4" />}
                                                    />
                                                </Tooltip>
                                                <Tooltip text="Hapus">
                                                    <Button
                                                        onClick={() => {
                                                            setSelectedItem(item);
                                                            setOpenConfirm(true);
                                                        }}
                                                        className="!bg-transparent !p-1 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                                                        icon={<Trash2 className="size-4" />}
                                                    />
                                                </Tooltip>
                                            </div>
                                        ),
                                    },
                                ]
                                : []),
                        ]}
                    />
                )}
            </ContentCard>

            <CheckPermissions permissions={[ArchievePermission.MANAGE_CATEGORY]}>
                <FloatingActionButton href="/archieve/contexts/create" label="Tambah Konteks" />
            </CheckPermissions>
        </RootLayout>
    );
}
