import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import DataTable from '@/components/tables/Datatable';
import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { Folder, Edit, Plus, Trash2, FileSpreadsheet, Shield } from 'lucide-react';
import { router } from '@inertiajs/react';
import ConfirmationAlert from '@/components/alerts/ConfirmationAlert';
import FormSearch from '@/components/forms/FormSearch';
import Button from '@/components/buttons/Button';
import CheckPermissions from '@/components/utils/CheckPermissions';
import MobileSearchBar from '@/components/forms/MobileSearchBar';
import FloatingActionButton from '@/components/buttons/FloatingActionButton';
import { DivisionCardSkeleton } from '@/components/skeletons/CardSkeleton';
import Tooltip from '@/components/commons/Tooltip';

interface CategoryItem {
    id: number;
    name: string;
    description: string | null;
    is_active: boolean;
    created_at?: string;
}

interface PaginationData {
    data: CategoryItem[];
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
}

export default function CategoryItemIndex() {
    const { permissions } = usePage<PageProps>().props;
    const hasViewPermission = permissions?.includes('lihat_kategori');
    const hasManagePermission = permissions?.includes('kelola_kategori');

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
    const [selectedItem, setSelectedItem] = useState<CategoryItem | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    async function loadDatatable() {
        if (!hasViewPermission) {
            setIsLoading(false);
            return;
        }
        setIsLoading(true);
        let url = `/inventory/categories/datatable`;
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
        page = page.split('&')[0];
        setParams({ ...params, page: parseInt(page) });
    }

    function onParamsChange(e: { target: { name: string; value: string } }) {
        setParams({ ...params, [e.target.name]: e.target.value });
    }

    function getPrintUrl() {
        let url = `/inventory/categories/print-excel`;
        const queryParams: string[] = [];
        Object.keys(params).forEach((key) => {
            queryParams.push(`${key}=${params[key as keyof Params]}`);
        });
        if (queryParams.length > 0) {
            url += `?${queryParams.join('&')}`;
        }
        return url;
    }

    return (
        <RootLayout
            title="Kategori Barang"
            mobileSearchBar={
                hasViewPermission ? (
                    <MobileSearchBar
                        searchValue={params.search}
                        onSearchChange={onParamsChange}
                        placeholder="Cari kategori..."
                        actionButton={
                            <a href={getPrintUrl()} target="_blank" className="p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200" rel="noreferrer">
                                <FileSpreadsheet className="size-4" />
                            </a>
                        }
                    />
                ) : undefined
            }
        >
            <ConfirmationAlert
                isOpen={openConfirm}
                setOpenModalStatus={setOpenConfirm}
                title="Konfirmasi Hapus"
                message={`Hapus kategori ${selectedItem?.name}? Tindakan ini tidak dapat dibatalkan.`}
                confirmText="Ya, Hapus"
                cancelText="Batal"
                type="danger"
                onConfirm={() => {
                    if (selectedItem?.id) {
                        router.delete(`/inventory/categories/${selectedItem.id}/delete`, {
                            onSuccess: () => loadDatatable(),
                        });
                    }
                }}
            />
            <ContentCard
                title="Kategori Barang"
                mobileFullWidth
                additionalButton={
                    <CheckPermissions permissions={['kelola_kategori']}>
                        <Button className="hidden w-full md:flex" label="Tambah Kategori" href="/inventory/categories/create" icon={<Plus className="size-4" />} />
                    </CheckPermissions>
                }
            >
                {!hasViewPermission ? (
                    <div className="flex flex-col items-center justify-center py-12 text-center" data-testid="no-access-message">
                        <div className="mb-4 rounded-full bg-red-100 p-3 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                            <Shield className="size-8" />
                        </div>
                        <h3 className="text-lg font-semibold text-slate-900 dark:text-white">Akses Ditolak</h3>
                        <p className="mt-1 text-slate-500 dark:text-slate-400">Anda tidak memiliki akses untuk melihat data kategori</p>
                    </div>
                ) : (
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
                                header: 'Nama Kategori',
                                render: (item: CategoryItem) => (
                                    <div className="flex items-center gap-2">
                                        <Folder className="size-4 text-primary" />
                                        <span className="font-medium">{item.name}</span>
                                    </div>
                                ),
                                footer: <FormSearch name="name" onChange={onParamsChange} placeholder="Filter Nama" />,
                            },
                            {
                                name: 'description',
                                header: 'Deskripsi',
                                render: (item: CategoryItem) => <span className="text-gray-500 dark:text-slate-400">{item.description || '-'}</span>,
                            },
                            {
                                name: 'is_active',
                                header: 'Status',
                                render: (item: CategoryItem) => (
                                    <span className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${item.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'}`}>
                                        {item.is_active ? 'Aktif' : 'Tidak Aktif'}
                                    </span>
                                ),
                            },
                            ...(usePage<PageProps>().props.permissions?.includes('kelola_kategori')
                                ? [
                                    {
                                        header: 'Aksi',
                                        render: (item: CategoryItem) => (
                                            <div className="flex justify-end gap-1">
                                                <Tooltip text="Edit">
                                                    <Button
                                                        href={`/inventory/categories/${item.id}/edit`}
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

            <CheckPermissions permissions={['kelola_kategori']}>
                <FloatingActionButton href="/inventory/categories/create" label="Tambah Kategori" />
            </CheckPermissions>
        </RootLayout>
    );
}
