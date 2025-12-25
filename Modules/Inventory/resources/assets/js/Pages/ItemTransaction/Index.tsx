import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import DataTable from '@/components/tables/Datatable';
import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { ArrowUpCircle, ArrowDownCircle, RefreshCw, FileSpreadsheet } from 'lucide-react';
import Button from '@/components/buttons/Button';
import MobileSearchBar from '@/components/forms/MobileSearchBar';
import { DivisionCardSkeleton } from '@/components/skeletons/CardSkeleton';
import FormSearch from '@/components/forms/FormSearch';
import FormSearchSelect from '@/components/forms/FormSearchSelect';
import FormInput from '@/components/forms/FormInput';

interface ItemTransaction {
    id: number;
    date: string;
    type: string;
    item: string;
    quantity: number;
    user: string | null;
    description: string | null;
    division?: string | null;
}

interface PaginationData {
    data: ItemTransaction[];
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
    date: string;
    type: string;
    item_name: string;
    quantity: string;
    user_name: string;
    description: string;
    sort_by: string;
    sort_direction: 'asc' | 'desc';
}

const typeIcons: Record<string, React.ReactNode> = {
    'Barang Masuk': <ArrowDownCircle className="size-4 text-green-500" />,
    'Barang Keluar': <ArrowUpCircle className="size-4 text-red-500" />,
    'Konversi': <RefreshCw className="size-4 text-blue-500" />,
    'Konversi Masuk': <ArrowDownCircle className="size-4 text-green-500" />,
    'Konversi Keluar': <ArrowUpCircle className="size-4 text-red-500" />,
    'Stock Opname': <RefreshCw className="size-4 text-purple-500" />,
    'Stock Opname (Kurang)': <ArrowUpCircle className="size-4 text-orange-500" />,
    'Stock Opname (Lebih)': <ArrowDownCircle className="size-4 text-teal-500" />,
};

const typeColors: Record<string, string> = {
    'Barang Masuk': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    'Barang Keluar': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    'Konversi': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    'Konversi Masuk': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    'Konversi Keluar': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    'Stock Opname': 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
    'Stock Opname (Kurang)': 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
    'Stock Opname (Lebih)': 'bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-400',
};

export default function ItemTransactionIndex() {
    const { permissions } = usePage<PageProps>().props;
    const canMonitorAll = permissions?.includes('monitor_semua_transaksi_barang');

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
        date: '',
        type: '',
        item_name: '',
        quantity: '',
        user_name: '',
        description: '',
        sort_by: 'date',
        sort_direction: 'desc',
    });

    const [isLoading, setIsLoading] = useState(true);

    async function loadDatatable() {
        setIsLoading(true);
        let url = `/inventory/transactions/datatable`;
        const queryParams: string[] = [];

        Object.keys(params).forEach((key) => {
            const value = params[key as keyof Params];
            if (value) {
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
        page = page.split('&')[0];
        setParams({ ...params, page: parseInt(page) });
    }

    function onParamsChange(e: { target: { name: string; value: string } }) {
        setParams({ ...params, [e.target.name]: e.target.value });
    }

    function getPrintUrl() {
        let url = `/inventory/transactions/print-excel`;
        const queryParams: string[] = [];
        Object.keys(params).forEach((key) => {
            const value = params[key as keyof Params];
            if (value) {
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
            title="Transaksi Barang"
            mobileSearchBar={
                <MobileSearchBar
                    searchValue={params.search}
                    onSearchChange={onParamsChange}
                    placeholder="Cari transaksi..."
                    actionButton={
                        <a href={getPrintUrl()} target="_blank" className="p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200" rel="noreferrer">
                            <FileSpreadsheet className="size-4" />
                        </a>
                    }
                />
            }
        >
            <ContentCard title="Transaksi Barang" mobileFullWidth>
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
                            name: 'date',
                            header: 'Tanggal',
                            render: (tx: ItemTransaction) => <span>{tx.date}</span>,
                            footer: (
                                <FormInput
                                    name="date"
                                    type="month"
                                    value={params.date}
                                    onChange={onParamsChange}
                                    className="w-full"
                                />
                            ),
                        },
                        // Only show Division column if user has monitor_semua_transaksi_barang permission
                        ...(canMonitorAll ? [{
                            name: 'division',
                            header: 'Divisi',
                            render: (tx: ItemTransaction) => (
                                <span className="text-gray-600 dark:text-slate-300">{tx.division || 'Gudang Utama'}</span>
                            ),
                        }] : []),
                        {
                            name: 'type',
                            header: 'Tipe',
                            render: (tx: ItemTransaction) => (
                                <div className="flex items-center gap-2">
                                    {typeIcons[tx.type]}
                                    <span className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${typeColors[tx.type] || 'bg-gray-100 text-gray-800'}`}>
                                        {tx.type}
                                    </span>
                                </div>
                            ),
                            footer: (
                                <FormSearchSelect
                                    name="type"
                                    value={params.type}
                                    onChange={onParamsChange}
                                    options={[
                                        { value: '', label: 'Semua' },
                                        { value: 'In', label: 'Masuk' },
                                        { value: 'Out', label: 'Keluar' },
                                        { value: 'Conversion', label: 'Konversi' },
                                        { value: 'Stock Opname', label: 'Stock Opname' },
                                    ]}
                                />
                            ),
                        },
                        {
                            name: 'item',
                            header: 'Barang',
                            render: (tx: ItemTransaction) => <span className="font-medium">{tx.item}</span>,
                            footer: <FormSearch name="item_name" onChange={onParamsChange} placeholder="Filter Barang" value={params.item_name} />,
                        },
                        {
                            name: 'quantity',
                            header: 'Jumlah',
                            render: (tx: ItemTransaction) => {
                                const isPositive = ['Barang Masuk', 'Stock Opname (Lebih)', 'Konversi Masuk'].includes(tx.type);
                                return (
                                    <span className={`font-semibold ${isPositive ? 'text-green-600' : 'text-red-600'}`}>
                                        {isPositive ? '+' : '-'}{tx.quantity}
                                    </span>
                                );
                            },
                            footer: <FormSearch name="quantity" type="number" onChange={onParamsChange} placeholder="Filter" value={params.quantity} />,
                        },
                        {
                            name: 'user',
                            header: 'User',
                            render: (tx: ItemTransaction) => <span className="text-gray-500 dark:text-slate-400">{tx.user || '-'}</span>,
                            footer: <FormSearch name="user_name" onChange={onParamsChange} placeholder="Filter User" value={params.user_name} />,
                        },
                        {
                            name: 'description',
                            header: 'Keterangan',
                            render: (tx: ItemTransaction) => <span className="text-gray-500 dark:text-slate-400">{tx.description || '-'}</span>,
                            footer: <FormSearch name="description" onChange={onParamsChange} placeholder="Filter" value={params.description} />,
                        },
                    ]}
                />
            </ContentCard>
        </RootLayout>
    );
}
