import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import DataTable from '@/components/tables/Datatable';
import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { Package, Edit, Plus, Trash2, FileSpreadsheet, ArrowRightLeft, LogOut } from 'lucide-react';
import { InventoryPermission } from '../../types/permissions';
import { router } from '@inertiajs/react';
import ConfirmationAlert from '@/components/alerts/ConfirmationAlert';
import FormSearch from '@/components/forms/FormSearch';
import Button from '@/components/buttons/Button';
import CheckPermissions from '@/components/utils/CheckPermissions';
import MobileSearchBar from '@/components/forms/MobileSearchBar';
import FloatingActionButton from '@/components/buttons/FloatingActionButton';
import { DivisionCardSkeleton } from '@/components/skeletons/CardSkeleton';
import Tooltip from '@/components/commons/Tooltip';

interface Item {
    id: number;
    name: string;
    category: string | null;
    unit_of_measure: string;
    stock: number;
    multiplier: number | null;
    reference_item: string | null;
    created_at?: string;
}

interface PaginationData {
    data: Item[];
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
    category?: string;
    unit_of_measure?: string;
    stock?: string;
}

export default function ItemIndex() {
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
    const [selectedItem, setSelectedItem] = useState<Item | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    async function loadDatatable() {
        setIsLoading(true);
        let url = `/inventory/items/datatable`;
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
        let url = `/inventory/items/print-excel`;
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
    const canManage = pageProps.permissions?.includes(InventoryPermission.ManageItem);
    const canIssue = pageProps.permissions?.includes(InventoryPermission.IssueItemGudang);
    const canConvert = pageProps.permissions?.includes(InventoryPermission.ConvertItemGudang);

    return (
        <RootLayout
            title="Barang Gudang Utama"
            mobileSearchBar={
                <MobileSearchBar
                    searchValue={params.search}
                    onSearchChange={onParamsChange}
                    placeholder="Cari barang..."
                    actionButton={
                        <a href={getPrintUrl()} target="_blank" className="p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200" rel="noreferrer">
                            <FileSpreadsheet className="size-4" />
                        </a>
                    }
                />
            }
        >
            {!(pageProps.permissions?.includes(InventoryPermission.ViewItem) || pageProps.permissions?.includes(InventoryPermission.ManageItem) || pageProps.permissions?.includes(InventoryPermission.IssueItemGudang) || pageProps.permissions?.includes(InventoryPermission.ConvertItemGudang)) ? (
                <div className="flex flex-col items-center justify-center py-12 text-center">
                    <div className="mb-4 rounded-full bg-red-100 p-3 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                        <Package className="size-8" />
                    </div>
                    <h3 className="text-lg font-semibold text-slate-900 dark:text-white">Akses Ditolak</h3>
                    <p className="mt-1 text-slate-500 dark:text-slate-400">Anda tidak memiliki akses untuk melihat data barang</p>
                </div>
            ) : (
                <>
                    <ConfirmationAlert
                        isOpen={openConfirm}
                        setOpenModalStatus={setOpenConfirm}
                        title="Konfirmasi Hapus"
                        message={`Hapus barang ${selectedItem?.name}? Tindakan ini tidak dapat dibatalkan.`}
                        confirmText="Ya, Hapus"
                        cancelText="Batal"
                        type="danger"
                        onConfirm={() => {
                            if (selectedItem?.id) {
                                router.delete(`/inventory/items/${selectedItem.id}/delete`, {
                                    onSuccess: () => loadDatatable(),
                                });
                            }
                        }}
                    />
                    <ContentCard
                        title="Barang Gudang Utama"
                        mobileFullWidth
                        additionalButton={
                            <CheckPermissions permissions={[InventoryPermission.ManageItem]}>
                                <Button className="hidden w-full md:flex" label="Tambah Barang" href="/inventory/items/create" icon={<Plus className="size-4" />} />
                            </CheckPermissions>
                        }
                    >
                        <p className="mb-4 text-sm text-gray-500 dark:text-slate-400">Daftar barang di gudang utama. Untuk melihat stok di seluruh divisi, gunakan menu <a href="/inventory/stock-monitoring" className="text-primary hover:underline">Monitoring Stok</a>.</p>
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
                            cardItem={(item: Item) => (
                                <div className="p-4 border-b dark:border-slate-700 last:border-0 hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
                                    <div className="flex items-center justify-between mb-2">
                                        <div className="flex items-center gap-2">
                                            <Package className="size-4 text-primary" />
                                            <span className="font-semibold text-gray-900 dark:text-white">{item.name}</span>
                                        </div>
                                        <span className={`text-sm font-bold ${item.stock <= 10 ? 'text-red-600' : 'text-green-600'}`}>
                                            {item.stock} {item.unit_of_measure}
                                        </span>
                                    </div>
                                    <div className="text-sm text-gray-500 dark:text-slate-400 mb-3">
                                        {item.category || '-'}
                                    </div>
                                    {(canManage || canIssue || canConvert) && (
                                        <div className="flex justify-end gap-2">
                                            {canConvert && item.multiplier && item.multiplier > 1 && item.stock > 0 && (
                                                <Button
                                                    href={`/inventory/items/${item.id}/convert`}
                                                    className="!px-3 !py-1 text-xs"
                                                    label="Konversi"
                                                    variant="secondary"
                                                    icon={<ArrowRightLeft className="size-3" />}
                                                />
                                            )}
                                            {canManage && (
                                                <Button
                                                    href={`/inventory/items/${item.id}/edit`}
                                                    className="!px-3 !py-1 text-xs !bg-yellow-500 hover:!bg-yellow-600 border-none text-white"
                                                    label="Edit"
                                                    icon={<Edit className="size-3" />}
                                                />
                                            )}
                                            {canIssue && (
                                                <Button
                                                    href={`/inventory/items/${item.id}/issue`}
                                                    className="!px-3 !py-1 text-xs !bg-orange-500 hover:!bg-orange-600 border-none text-white"
                                                    label="Keluar"
                                                    icon={<LogOut className="size-3" />}
                                                />
                                            )}
                                            {canManage && (
                                                <Button
                                                    onClick={() => {
                                                        setSelectedItem(item);
                                                        setOpenConfirm(true);
                                                    }}
                                                    className="!px-3 !py-1 text-xs"
                                                    label="Hapus"
                                                    variant="danger"
                                                    icon={<Trash2 className="size-3" />}
                                                />
                                            )}
                                        </div>
                                    )}
                                </div>
                            )}
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
                                    name: 'name',
                                    header: 'Nama Barang',
                                    render: (item: Item) => (
                                        <div className="flex items-center gap-2">
                                            <Package className="size-4 text-primary" />
                                            <span className="font-medium">{item.name}</span>
                                        </div>
                                    ),
                                    footer: <FormSearch name="name" onChange={onParamsChange} placeholder="Filter Nama" />,
                                },
                                {
                                    name: 'category',
                                    header: 'Kategori',
                                    render: (item: Item) => <span className="text-gray-500 dark:text-slate-400">{item.category || '-'}</span>,
                                    footer: <FormSearch name="category" onChange={onParamsChange} placeholder="Filter Kategori" />,
                                },
                                {
                                    name: 'unit_of_measure',
                                    header: 'Satuan',
                                    render: (item: Item) => (
                                        <div>
                                            <span className="text-gray-700 dark:text-slate-300">{item.unit_of_measure}</span>
                                            {item.multiplier && item.multiplier > 1 && (
                                                <span className="ml-1 text-xs text-blue-600 dark:text-blue-400">
                                                    ({item.multiplier}x → {item.reference_item || 'unit'})
                                                </span>
                                            )}
                                        </div>
                                    ),
                                    footer: <FormSearch name="unit_of_measure" onChange={onParamsChange} placeholder="Filter Satuan" />,
                                },
                                {
                                    name: 'stock',
                                    header: 'Stok',
                                    render: (item: Item) => (
                                        <span className={`font-semibold ${item.stock <= 10 ? 'text-red-600' : 'text-green-600'}`}>
                                            {item.stock}
                                        </span>
                                    ),
                                    footer: <FormSearch name="stock" onChange={onParamsChange} placeholder="Filter Stok" />,
                                },
                                ...((canManage || canIssue || canConvert)
                                    ? [
                                        {
                                            header: 'Aksi',
                                            render: (item: Item) => (
                                                <div className="flex justify-end gap-1">
                                                    {canConvert && item.multiplier && item.multiplier > 1 && item.stock > 0 && (
                                                        <Tooltip text="Konversi">
                                                            <Button
                                                                href={`/inventory/items/${item.id}/convert`}
                                                                className="!bg-transparent !p-1 text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20"
                                                                icon={<ArrowRightLeft className="size-4" />}
                                                            />
                                                        </Tooltip>
                                                    )}
                                                    {canManage && (
                                                        <Tooltip text="Edit">
                                                            <Button
                                                                href={`/inventory/items/${item.id}/edit`}
                                                                className="!bg-transparent !p-1 text-yellow-600 hover:bg-yellow-50 dark:text-yellow-400 dark:hover:bg-yellow-900/20"
                                                                icon={<Edit className="size-4" />}
                                                            />
                                                        </Tooltip>
                                                    )}
                                                    {canIssue && (
                                                        <Tooltip text="Keluarkan Stok">
                                                            <Button
                                                                href={`/inventory/items/${item.id}/issue`}
                                                                className="!bg-transparent !p-1 text-orange-600 hover:bg-orange-50 dark:text-orange-400 dark:hover:bg-orange-900/20"
                                                                icon={<LogOut className="size-4" />}
                                                            />
                                                        </Tooltip>
                                                    )}
                                                    {canManage && (
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
                                                    )}
                                                </div>
                                            ),
                                        },
                                    ]
                                    : []),
                            ]}
                        />
                    </ContentCard>

                    <CheckPermissions permissions={[InventoryPermission.ManageItem]}>
                        <FloatingActionButton href="/inventory/items/create" label="Tambah Barang" />
                    </CheckPermissions>
                </>
            )}
        </RootLayout>

    );
}

