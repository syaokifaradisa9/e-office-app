import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import DataTable from '@/components/tables/Datatable';
import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { Package, FileSpreadsheet, RefreshCw, LogOut } from 'lucide-react';
import { InventoryPermission } from '../../types/permissions';
import Button from '@/components/buttons/Button';
import MobileSearchBar from '@/components/forms/MobileSearchBar';
import { CategoryItemCardSkeleton } from '@/components/skeletons/CardSkeleton';
import StockMonitoringCardItem from './StockMonitoringCardItem';
import Tooltip from '@/components/commons/Tooltip';
import FormSearch from '@/components/forms/FormSearch';
import FormSearchSelect from '@/components/forms/FormSearchSelect';

interface Item {
    id: number;
    name: string;
    category?: { id: number; name: string } | null;
    division?: { id: number; name: string } | null;
    reference_item?: { unit_of_measure: string } | null;
    unit_of_measure: string;
    stock: number;
    multiplier: number;
    division_id: number | null;
}

interface Category {
    id: number;
    name: string;
}

interface Division {
    id: number;
    name: string;
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
    categories: Category[];
    divisions: Division[];
    loggeduser?: { division_id: number | null };
    permissions?: string[];
    [key: string]: unknown;
}

interface Params {
    search: string;
    limit: number;
    page: number;
    name: string;
    category_id: string;
    division_id: string;
    stock: string;
    stock_max: string;
    unit_of_measure: string;
    sort_by: string;
    sort_direction: 'asc' | 'desc';
}

export default function StockMonitoringIndex() {
    const pageProps = usePage<PageProps>().props;
    const { categories = [], divisions = [], loggeduser } = pageProps;
    const canIssue = pageProps.permissions?.includes(InventoryPermission.IssueStock);
    const canMonitorAll = pageProps.permissions?.includes(InventoryPermission.MonitorAllStock);
    const canConvertPermission = pageProps.permissions?.includes(InventoryPermission.ConvertStock);

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
        category_id: 'ALL',
        division_id: 'ALL',
        stock: '',
        stock_max: '',
        unit_of_measure: '',
        sort_by: 'created_at',
        sort_direction: 'desc',
    });

    const [isLoading, setIsLoading] = useState(true);

    async function loadDatatable() {
        setIsLoading(true);
        let url = `/inventory/stock-monitoring/datatable`;
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
        let url = `/inventory/stock-monitoring/print-excel`;
        const queryParams: string[] = [];
        Object.keys(params).forEach((key) => {
            queryParams.push(`${key}=${params[key as keyof Params]}`);
        });
        if (queryParams.length > 0) {
            url += `?${queryParams.join('&')}`;
        }
        return url;
    }

    const divisionOptions = [
        { value: 'ALL', label: 'Semua Divisi' },
        ...divisions.map((div) => ({ value: String(div.id), label: div.name })),
    ];

    const categoryOptions = [
        { value: 'ALL', label: 'Semua' },
        ...categories.map((cat) => ({ value: String(cat.id), label: cat.name })),
    ];

    return (
        <RootLayout
            title="Monitoring Stok"
            mobileSearchBar={
                <MobileSearchBar
                    searchValue={params.search}
                    onSearchChange={onParamsChange}
                    placeholder="Cari stok..."
                    actionButton={
                        <div className="flex items-center gap-1">
                            <a
                                href={getPrintUrl()}
                                target="_blank"
                                rel="noreferrer"
                                className="p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                            >
                                <FileSpreadsheet className="size-4" />
                            </a>
                        </div>

                    }
                />
            }
        >
            <ContentCard title="Monitoring Stok" subtitle="Monitoring stok secara real-time di tiap divisi" mobileFullWidth bodyClassName="px-0 pt-2 pb-8 md:p-6">
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
                    additionalHeaderElements={
                        <Tooltip text="Export Excel">
                            <Button
                                href={getPrintUrl()}
                                className="!bg-transparent !p-2 !text-black hover:opacity-75 dark:!text-white"
                                icon={<FileSpreadsheet className="size-4" />}
                                target="_blank"
                            />
                        </Tooltip>
                    }
                    onHeaderClick={(columnName: string) => {
                        const newSortDirection = params.sort_by === columnName && params.sort_direction === 'asc' ? 'desc' : 'asc';
                        setParams((prevParams) => ({
                            ...prevParams,
                            sort_by: columnName,
                            sort_direction: newSortDirection,
                        }));
                    }}
                    cardItem={(item: Item) => (
                        <StockMonitoringCardItem
                            item={item}
                            canConvert={!!(canConvertPermission && item.multiplier > 1 && item.division_id === loggeduser?.division_id && item.stock > 0)}
                            canIssue={!!(canIssue && item.stock > 0)}
                        />
                    )}
                    columns={[
                        // Only show Division column if user has lihat_semua_stok permission
                        ...(canMonitorAll ? [{
                            name: 'division_id',
                            header: 'Divisi',
                            render: (item: Item) => (
                                <span className="text-gray-600 dark:text-slate-300">{item.division?.name || 'Gudang Utama'}</span>
                            ),
                            footer: (
                                <FormSearchSelect
                                    name="division_id"
                                    value={params.division_id}
                                    onChange={onParamsChange}
                                    options={divisionOptions}
                                />
                            ),
                        }] : []),
                        {
                            name: 'name',
                            header: 'Nama Barang',
                            render: (item: Item) => (
                                <div className="flex items-center gap-2">
                                    <Package className="size-4 text-primary" />
                                    <span className="font-medium">{item.name}</span>
                                </div>
                            ),
                            footer: <FormSearch name="name" onChange={onParamsChange} placeholder="Filter Nama" value={params.name} />,
                        },
                        {
                            name: 'category_id',
                            header: 'Kategori',
                            render: (item: Item) => <span className="text-gray-500 dark:text-slate-400">{item.category?.name || '-'}</span>,
                            footer: (
                                <FormSearchSelect
                                    name="category_id"
                                    value={params.category_id}
                                    onChange={onParamsChange}
                                    options={categoryOptions}
                                />
                            ),
                        },
                        {
                            name: 'stock',
                            header: 'Stok',
                            render: (item: Item) => (
                                <span className={`font-semibold ${item.stock <= 0 ? 'text-red-600' : item.stock <= 10 ? 'text-yellow-600' : 'text-green-600'}`}>
                                    {item.stock}
                                </span>
                            ),
                            footer: (
                                <div className="flex gap-1">
                                    <FormSearch name="stock" type="number" onChange={onParamsChange} placeholder="Min" value={params.stock} />
                                    <FormSearch name="stock_max" type="number" onChange={onParamsChange} placeholder="Max" value={params.stock_max} />
                                </div>
                            ),
                        },
                        {
                            name: 'unit_of_measure',
                            header: 'Satuan',
                            render: (item: Item) => {
                                if (item.multiplier > 1 && item.reference_item) {
                                    return `${item.unit_of_measure} (${item.multiplier} ${item.reference_item.unit_of_measure})`;
                                }
                                return item.unit_of_measure;
                            },
                            footer: <FormSearch name="unit_of_measure" onChange={onParamsChange} placeholder="Filter Satuan" value={params.unit_of_measure} />,
                        },
                        {
                            header: 'Aksi',
                            render: (item: Item) => {
                                const canConvert = canConvertPermission && item.multiplier > 1 && item.division_id === loggeduser?.division_id && item.stock > 0;
                                const canIssueItem = canIssue && item.stock > 0;

                                if (!canConvert && !canIssueItem) return null;

                                return (
                                    <div className="flex justify-end gap-1">
                                        {canConvert && (
                                            <Tooltip text="Konversi Stok">
                                                <Button
                                                    href={`/inventory/stock-monitoring/${item.id}/convert`}
                                                    className="!bg-transparent !p-1 text-green-600 hover:bg-green-50 dark:text-green-400 dark:hover:bg-green-900/20"
                                                    icon={<RefreshCw className="size-4" />}
                                                />
                                            </Tooltip>
                                        )}
                                        {canIssueItem && (
                                            <Tooltip text="Keluar Barang">
                                                <Button
                                                    href={`/inventory/stock-monitoring/${item.id}/issue`}
                                                    className="!bg-transparent !p-1 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                                                    icon={<LogOut className="size-4" />}
                                                />
                                            </Tooltip>
                                        )}
                                    </div>
                                );
                            },
                        },
                    ]}
                />
            </ContentCard>
        </RootLayout>
    );
}
