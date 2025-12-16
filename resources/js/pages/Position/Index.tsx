import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import DataTable from '@/components/tables/Datatable';
import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { Briefcase, Edit, Plus, Trash2, FileSpreadsheet, Users } from 'lucide-react';
import { router } from '@inertiajs/react';
import ConfirmationAlert from '@/components/alerts/ConfirmationAlert';
import FormSearch from '@/components/forms/FormSearch';
import Button from '@/components/buttons/Button';
import CheckPermissions from '@/components/utils/CheckPermissions';
import MobileSearchBar from '@/components/forms/MobileSearchBar';
import FloatingActionButton from '@/components/buttons/FloatingActionButton';
import { PositionCardSkeleton } from '@/components/skeletons/CardSkeleton';
import Tooltip from '@/components/commons/Tooltip';

import PositionCardItem from './PositionCardItem';

interface Position {
    id: number;
    name: string;
    description: string | null;
    is_active: boolean;
    users_count: number;
    created_at?: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginationData {
    data: Position[];
    links: PaginationLink[];
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
    sort_by: string;
    sort_direction: 'asc' | 'desc';
}

export default function PositionIndex() {
    const [dataTable, setDataTable] = useState<PaginationData>({
        data: [],
        links: [],
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
        name: '',
        sort_by: 'created_at',
        sort_direction: 'desc',
    });

    const [openConfirm, setOpenConfirm] = useState(false);
    const [selectedPosition, setSelectedPosition] = useState<Position | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    async function loadDatatable() {
        setIsLoading(true);
        let url = `/position/datatable`;
        const paramsKey = Object.keys(params) as (keyof Params)[];
        const queryParams: string[] = [];

        for (let i = 0; i < paramsKey.length; i++) {
            queryParams.push(`${paramsKey[i]}=${params[paramsKey[i]]}`);
        }

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

        setParams({
            ...params,
            page: parseInt(page),
        });
    }

    function onParamsChange(e: { preventDefault?: () => void; target: { name: string; value: string } }) {
        e.preventDefault?.();

        setParams({
            ...params,
            [e.target.name]: e.target.value,
        });
    }

    function getPrintUrl(type: string) {
        let url = `/position/print/${type}`;
        const paramsKey = Object.keys(params) as (keyof Params)[];
        const queryParams: string[] = [];

        for (let i = 0; i < paramsKey.length; i++) {
            queryParams.push(`${paramsKey[i]}=${params[paramsKey[i]]}`);
        }

        if (queryParams.length > 0) {
            url += `?${queryParams.join('&')}`;
        }

        return url;
    }

    return (
        <RootLayout
            title="Jabatan"
            mobileSearchBar={
                <MobileSearchBar
                    searchValue={params.search}
                    onSearchChange={onParamsChange}
                    placeholder="Cari jabatan..."
                    actionButton={
                        <a href={getPrintUrl('excel')} target="_blank" className="p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200" rel="noreferrer">
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
                message={`Hapus jabatan ${selectedPosition?.name}? Tindakan ini tidak dapat dibatalkan.`}
                confirmText="Ya, Hapus"
                cancelText="Batal"
                type="danger"
                onConfirm={() => {
                    if (selectedPosition?.id) {
                        router.delete(`/position/${selectedPosition.id}/delete`, {
                            onSuccess: () => loadDatatable(),
                        });
                    }
                }}
            />
            <ContentCard
                title="Jabatan"
                mobileFullWidth
                additionalButton={
                    <CheckPermissions permissions={['kelola_jabatan']}>
                        <Button className="hidden w-full md:flex" label="Tambah Jabatan" href="/position/create" icon={<Plus className="size-4" />} />
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
                    SkeletonComponent={PositionCardSkeleton}
                    sortBy={params.sort_by}
                    sortDirection={params.sort_direction}
                    additionalHeaderElements={
                        <div className="flex gap-2">
                            <Button href={getPrintUrl('excel')} className="!bg-transparent !p-2 !text-black hover:opacity-75 dark:!text-white" icon={<FileSpreadsheet className="size-4" />} target="_blank" />
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
                    cardItem={(item: Position) => (
                        <PositionCardItem
                            item={item}
                            onDelete={(item) => {
                                setSelectedPosition(item);
                                setOpenConfirm(true);
                            }}
                        />
                    )}
                    columns={[
                        {
                            name: 'name',
                            header: 'Nama Jabatan',
                            render: (position: Position) => (
                                <div className="flex items-center gap-2">
                                    <Briefcase className="size-4 text-primary" />
                                    <span className="font-medium">{position.name}</span>
                                </div>
                            ),
                            footer: <FormSearch name="name" onChange={onParamsChange} placeholder="Filter Nama" />,
                        },
                        {
                            name: 'description',
                            header: 'Deskripsi',
                            render: (position: Position) => <span className="text-gray-500 dark:text-slate-400">{position.description || '-'}</span>,
                        },
                        {
                            name: 'users_count',
                            header: 'Jumlah Pegawai',
                            render: (position: Position) => (
                                <div className="flex items-center gap-1">
                                    <Users className="size-4 text-slate-400" />
                                    <span>{position.users_count || 0}</span>
                                </div>
                            ),
                        },
                        {
                            name: 'is_active',
                            header: 'Status',
                            render: (position: Position) => (
                                <span className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${position.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'}`}>
                                    {position.is_active ? 'Aktif' : 'Tidak Aktif'}
                                </span>
                            ),
                        },
                        ...(usePage<PageProps>().props.permissions?.includes('kelola_jabatan')
                            ? [
                                {
                                    header: 'Aksi',
                                    render: (position: Position) => (
                                        <div className="flex justify-end gap-1">
                                            <Tooltip text="Edit">
                                                <Button
                                                    href={`/position/${position.id}/edit`}
                                                    className="!bg-transparent !p-1 text-yellow-600 hover:bg-yellow-50 dark:text-yellow-400 dark:hover:bg-yellow-900/20"
                                                    icon={<Edit className="size-4" />}
                                                />
                                            </Tooltip>
                                            <Tooltip text="Hapus">
                                                <Button
                                                    onClick={() => {
                                                        setSelectedPosition(position);
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
            </ContentCard>

            {/* FAB for mobile */}
            <CheckPermissions permissions={['kelola_jabatan']}>
                <FloatingActionButton href="/position/create" label="Tambah Jabatan" />
            </CheckPermissions>
        </RootLayout>
    );
}
