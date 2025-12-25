import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import DataTable from '@/components/tables/Datatable';
import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { Edit, Plus, Trash2, FileSpreadsheet } from 'lucide-react';
import { router } from '@inertiajs/react';
import ConfirmationAlert from '@/components/alerts/ConfirmationAlert';
import FormSearch from '@/components/forms/FormSearch';
import Button from '@/components/buttons/Button';
import CheckPermissions from '@/components/utils/CheckPermissions';
import MobileSearchBar from '@/components/forms/MobileSearchBar';
import FloatingActionButton from '@/components/buttons/FloatingActionButton';
import { RoleCardSkeleton } from '@/components/skeletons/CardSkeleton';
import Tooltip from '@/components/commons/Tooltip';

import RoleCardItem from './RoleCardItem';

interface Permission {
    id: number;
    name: string;
}

interface Role {
    id: number;
    name: string;
    permissions_count: number;
    permissions: Permission[];
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginationData {
    data: Role[];
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

export default function RoleIndex() {
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
    const [selectedRole, setSelectedRole] = useState<Role | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    async function loadDatatable() {
        setIsLoading(true);
        let url = `/role/datatable`;
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
        let url = `/role/print/${type}`;
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
            title="Role & Permission"
            mobileSearchBar={
                <MobileSearchBar
                    searchValue={params.search}
                    onSearchChange={onParamsChange}
                    placeholder="Cari role..."
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
                message={`Hapus role ${selectedRole?.name}? Tindakan ini tidak dapat dibatalkan.`}
                confirmText="Ya, Hapus"
                cancelText="Batal"
                type="danger"
                onConfirm={() => {
                    if (selectedRole?.id) {
                        router.delete(`/role/${selectedRole.id}/delete`, {
                            onSuccess: () => loadDatatable(),
                        });
                    }
                }}
            />
            <ContentCard
                title="Role & Permission"
                mobileFullWidth
                additionalButton={
                    <CheckPermissions permissions={['kelola_role']}>
                        <Button className="hidden w-full md:flex" label="Tambah Role" href="/role/create" icon={<Plus className="size-4" />} />
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
                    SkeletonComponent={RoleCardSkeleton}
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
                    cardItem={(item: Role) => (
                        <RoleCardItem
                            item={item}
                            onDelete={(item) => {
                                setSelectedRole(item);
                                setOpenConfirm(true);
                            }}
                        />
                    )}
                    columns={[
                        {
                            name: 'name',
                            header: 'Nama Role',
                            render: (role: Role) => <div className="font-medium">{role.name}</div>,
                            footer: <FormSearch name="name" onChange={onParamsChange} placeholder="Filter Nama" />,
                        },
                        {
                            name: 'permissions_count',
                            header: 'Jumlah Permission',
                            render: (role: Role) => (
                                <span className="rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {role.permissions_count} Permissions
                                </span>
                            ),
                        },
                        ...(usePage<PageProps>().props.permissions?.includes('kelola_role')
                            ? [
                                {
                                    header: 'Aksi',
                                    render: (role: Role) => (
                                        <div className="flex justify-end gap-1">
                                            <Tooltip text="Edit">
                                                <Button
                                                    href={`/role/${role.id}/edit`}
                                                    className="!bg-transparent !p-1 text-yellow-600 hover:bg-yellow-50 dark:text-yellow-400 dark:hover:bg-yellow-900/20"
                                                    icon={<Edit className="size-4" />}
                                                />
                                            </Tooltip>
                                            {role.name !== 'Superadmin' && (
                                                <Tooltip text="Hapus">
                                                    <Button
                                                        onClick={() => {
                                                            setSelectedRole(role);
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

            {/* FAB for mobile */}
            <CheckPermissions permissions={['kelola_role']}>
                <FloatingActionButton href="/role/create" label="Tambah Role" />
            </CheckPermissions>
        </RootLayout>
    );
}
