import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import DataTable from '@/components/tables/Datatable';
import { useEffect, useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import { ClipboardList, Edit, Plus, Trash2, Shield, ToggleLeft, ToggleRight, FileSpreadsheet } from 'lucide-react';
import ConfirmationAlert from '@/components/alerts/ConfirmationAlert';
import FormSearch from '@/components/forms/FormSearch';
import FormSearchSelect from '@/components/forms/FormSearchSelect';
import Button from '@/components/buttons/Button';
import CheckPermissions from '@/components/utils/CheckPermissions';
import MobileSearchBar from '@/components/forms/MobileSearchBar';
import FloatingActionButton from '@/components/buttons/FloatingActionButton';
import { DivisionCardSkeleton } from '@/components/skeletons/CardSkeleton';
import Tooltip from '@/components/commons/Tooltip';

interface PurposeCategory {
    id: number;
    name: string;
    description: string | null;
    is_active: boolean;
    created_at?: string;
}

interface PaginationData {
    data: PurposeCategory[];
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
    name?: string;
    description?: string;
    status?: string;
}

export default function PurposeCategoryIndex() {
    const { permissions } = usePage<PageProps>().props;
    const hasViewPermission = permissions?.includes('lihat_master_manajemen_pengunjung');
    const hasManagePermission = permissions?.includes('kelola_master_manajemen_pengunjung');

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
        sort_by: 'name',
        sort_direction: 'asc',
    });

    const [openConfirm, setOpenConfirm] = useState(false);
    const [selectedItem, setSelectedItem] = useState<PurposeCategory | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    async function loadDatatable() {
        if (!hasViewPermission && !hasManagePermission) {
            setIsLoading(false);
            return;
        }
        setIsLoading(true);
        let url = `/visitor/purposes/datatable`;
        const queryParams: string[] = [];

        Object.keys(params).forEach((key) => {
            queryParams.push(`${key}=${params[key as keyof Params]}`);
        });

        if (queryParams.length > 0) {
            url += `?${queryParams.join('&')}`;
        }

        try {
            const response = await fetch(url);
            const data = await response.json();
            setDataTable(data);
        } catch (error) {
            console.error('Failed to load data', error);
        }
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
        let url = `/visitor/purposes/print/excel`;
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

    function handleToggleStatus(item: PurposeCategory) {
        router.post(`/visitor/purposes/${item.id}/toggle`, {}, {
            preserveState: true,
            onSuccess: () => loadDatatable(),
        });
    }

    return (
        <RootLayout
            title="Keperluan Kunjungan"
            mobileSearchBar={
                hasViewPermission || hasManagePermission ? (
                    <MobileSearchBar
                        searchValue={params.search}
                        onSearchChange={onParamsChange}
                        placeholder="Cari keperluan..."
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
                message={`Hapus keperluan "${selectedItem?.name}"? Tindakan ini tidak dapat dibatalkan.`}
                confirmText="Ya, Hapus"
                cancelText="Batal"
                type="danger"
                onConfirm={() => {
                    if (selectedItem?.id) {
                        router.delete(`/visitor/purposes/${selectedItem.id}/delete`, {
                            onSuccess: () => loadDatatable(),
                        });
                    }
                }}
            />

            <ContentCard
                title="Keperluan Kunjungan"
                subtitle="Kelola kategori keperluan yang tersedia untuk pengunjung saat check-in"
                mobileFullWidth
                additionalButton={
                    <CheckPermissions permissions={['kelola_master_manajemen_pengunjung']}>
                        <Button
                            className="hidden w-full md:flex"
                            label="Tambah Keperluan"
                            href="/visitor/purposes/create"
                            icon={<Plus className="size-4" />}
                        />
                    </CheckPermissions>
                }
            >
                {!hasViewPermission && !hasManagePermission ? (
                    <div className="flex flex-col items-center justify-center py-12 text-center" data-testid="no-access-message">
                        <div className="mb-4 rounded-full bg-red-100 p-3 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                            <Shield className="size-8" />
                        </div>
                        <h3 className="text-lg font-semibold text-slate-900 dark:text-white">Akses Ditolak</h3>
                        <p className="mt-1 text-slate-500 dark:text-slate-400">Anda tidak memiliki akses untuk melihat data keperluan kunjungan</p>
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
                                <CheckPermissions permissions={['lihat_master_manajemen_pengunjung']}>
                                    <Button
                                        href={getPrintUrl()}
                                        variant="ghost"
                                        className="hidden h-9 w-9 items-center justify-center p-0 hover:bg-slate-50 dark:hover:bg-slate-800 md:flex"
                                        icon={<FileSpreadsheet className="size-4" />}
                                        target="_blank"
                                        title="Export Excel"
                                    />
                                </CheckPermissions>
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
                                header: 'Nama Keperluan',
                                render: (item: PurposeCategory) => (
                                    <div className="flex items-center gap-2">
                                        <ClipboardList className="size-4 text-primary" />
                                        <span className="font-medium">{item.name}</span>
                                    </div>
                                ),
                                footer: <FormSearch name="name" value={params.name} onChange={onParamsChange} placeholder="Filter Nama" />,
                            },
                            {
                                name: 'description',
                                header: 'Deskripsi',
                                render: (item: PurposeCategory) => <span className="text-gray-500 dark:text-slate-400">{item.description || '-'}</span>,
                                footer: <FormSearch name="description" value={params.description} onChange={onParamsChange} placeholder="Filter Deskripsi" />,
                            },
                            {
                                name: 'is_active',
                                header: 'Status',
                                render: (item: PurposeCategory) => (
                                    hasManagePermission ? (
                                        <button
                                            onClick={() => handleToggleStatus(item)}
                                            className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition-colors ${item.is_active
                                                ? 'bg-green-100 text-green-800 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400'
                                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-400'
                                                }`}
                                        >
                                            {item.is_active ? (
                                                <>
                                                    <ToggleRight className="size-3.5" />
                                                    Aktif
                                                </>
                                            ) : (
                                                <>
                                                    <ToggleLeft className="size-3.5" />
                                                    Nonaktif
                                                </>
                                            )}
                                        </button>
                                    ) : (
                                        <span className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${item.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'}`}>
                                            {item.is_active ? 'Aktif' : 'Nonaktif'}
                                        </span>
                                    )
                                ),
                                footer: (
                                    <FormSearchSelect
                                        name="status"
                                        value={params.status || ''}
                                        onChange={onParamsChange}
                                        options={[
                                            { value: '', label: 'Semua Status' },
                                            { value: 'active', label: 'Aktif' },
                                            { value: 'inactive', label: 'Nonaktif' },
                                        ]}
                                    />
                                ),
                            },
                            ...(hasManagePermission
                                ? [
                                    {
                                        header: 'Aksi',
                                        render: (item: PurposeCategory) => (
                                            <div className="flex justify-end gap-1">
                                                <Tooltip text="Edit">
                                                    <Button
                                                        href={`/visitor/purposes/${item.id}/edit`}
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

            <CheckPermissions permissions={['kelola_master_manajemen_pengunjung']}>
                <FloatingActionButton href="/visitor/purposes/create" label="Tambah Keperluan" />
            </CheckPermissions>
        </RootLayout>
    );
}
