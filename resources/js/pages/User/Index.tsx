import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import DataTable from '@/components/tables/Datatable';
import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { User, Edit, Plus, Trash2, FileSpreadsheet, Shield } from 'lucide-react';
import { router } from '@inertiajs/react';
import ConfirmationAlert from '@/components/alerts/ConfirmationAlert';
import FormSearch from '@/components/forms/FormSearch';
import Button from '@/components/buttons/Button';
import CheckPermissions from '@/components/utils/CheckPermissions';
import MobileSearchBar from '@/components/forms/MobileSearchBar';
import FloatingActionButton from '@/components/buttons/FloatingActionButton';
import { UserCardSkeleton } from '@/components/skeletons/CardSkeleton';
import Tooltip from '@/components/commons/Tooltip';
import FormSearchSelect from '@/components/forms/FormSearchSelect';

import UserCardItem from './UserCardItem';

interface Role {
    id: number;
    name: string;
}

interface UserData {
    id: number;
    name: string;
    email: string;
    phone?: string;
    is_active: boolean;
    division?: { id: number; name: string };
    position?: { id: number; name: string };
    roles: Role[];
    created_at?: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginationData {
    data: UserData[];
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
    email: string;
    division_id: string;
    position_id: string;
    is_active: string;
    sort_by: string;
    sort_direction: 'asc' | 'desc';
}

export default function UserIndex() {
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
        email: '',
        division_id: '',
        position_id: '',
        is_active: '',
        sort_by: 'created_at',
        sort_direction: 'desc',
    });

    const [openConfirm, setOpenConfirm] = useState(false);
    const [selectedUser, setSelectedUser] = useState<UserData | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    async function loadDatatable() {
        setIsLoading(true);
        let url = `/user/datatable`;
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
        let url = `/user/print/${type}`;
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
            title="Pengguna"
            mobileSearchBar={
                <MobileSearchBar
                    searchValue={params.search}
                    onSearchChange={onParamsChange}
                    placeholder="Cari pengguna..."
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
                message={`Hapus pengguna ${selectedUser?.name}? Tindakan ini tidak dapat dibatalkan.`}
                confirmText="Ya, Hapus"
                cancelText="Batal"
                type="danger"
                onConfirm={() => {
                    if (selectedUser?.id) {
                        router.delete(`/user/${selectedUser.id}/delete`, {
                            onSuccess: () => loadDatatable(),
                        });
                    }
                }}
            />
            <ContentCard
                title="Pengguna"
                subtitle="Kelola dan atur daftar pengguna sistem Anda"
                mobileFullWidth
                additionalButton={
                    <CheckPermissions permissions={['kelola_pengguna']}>
                        <Button className="hidden w-full md:flex" label="Tambah Pengguna" href="/user/create" icon={<Plus className="size-4" />} />
                    </CheckPermissions>
                }
            >
                {usePage<PageProps>().props.permissions?.includes('lihat_pengguna') ? (
                    <DataTable
                        onChangePage={onChangePage}
                        onParamsChange={onParamsChange}
                        limit={params.limit}
                        searchValue={params.search}
                        dataTable={dataTable}
                        isLoading={isLoading}
                        SkeletonComponent={UserCardSkeleton}
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
                        cardItem={(item: UserData) => (
                            <UserCardItem
                                item={item}
                                onDelete={(item) => {
                                    setSelectedUser(item);
                                    setOpenConfirm(true);
                                }}
                            />
                        )}
                        columns={[
                            {
                                name: 'name',
                                header: 'Nama',
                                render: (user: UserData) => (
                                    <div className="flex items-center gap-2">
                                        <User className="size-4 text-primary" />
                                        <span className="font-medium">{user.name}</span>
                                    </div>
                                ),
                                footer: <FormSearch name="name" onChange={onParamsChange} placeholder="Filter Nama" />,
                            },
                            {
                                name: 'email',
                                header: 'Email',
                                render: (user: UserData) => <span className="text-gray-500 dark:text-slate-400">{user.email}</span>,
                                footer: <FormSearch name="email" onChange={onParamsChange} placeholder="Filter Email" />,
                            },
                            {
                                header: 'Divisi',
                                render: (user: UserData) => <span className="text-gray-500 dark:text-slate-400">{user.division?.name || '-'}</span>,
                                footer: (
                                    <FormSearchSelect
                                        name="division_id"
                                        value={params.division_id}
                                        onChange={onParamsChange}
                                        options={[
                                            { value: '', label: 'Semua Divisi' },
                                            ...(usePage<any>().props.divisions || []).map((div: any) => ({
                                                value: div.id.toString(),
                                                label: div.name,
                                            })),
                                        ]}
                                    />
                                ),
                            },
                            {
                                header: 'Jabatan',
                                render: (user: UserData) => <span className="text-gray-500 dark:text-slate-400">{user.position?.name || '-'}</span>,
                                footer: (
                                    <FormSearchSelect
                                        name="position_id"
                                        value={params.position_id}
                                        onChange={onParamsChange}
                                        options={[
                                            { value: '', label: 'Semua Jabatan' },
                                            ...(usePage<any>().props.positions || []).map((pos: any) => ({
                                                value: pos.id.toString(),
                                                label: pos.name,
                                            })),
                                        ]}
                                    />
                                ),
                            },
                            {
                                header: 'Role',
                                render: (user: UserData) => (
                                    <span className="rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                        {user.roles[0]?.name || '-'}
                                    </span>
                                ),
                            },
                            {
                                name: 'is_active',
                                header: 'Status',
                                render: (user: UserData) => (
                                    <span className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${user.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'}`}>
                                        {user.is_active ? 'Aktif' : 'Tidak Aktif'}
                                    </span>
                                ),
                                footer: (
                                    <FormSearchSelect
                                        name="is_active"
                                        value={params.is_active}
                                        onChange={onParamsChange}
                                        options={[
                                            { value: '', label: 'Semua Status' },
                                            { value: '1', label: 'Aktif' },
                                            { value: '0', label: 'Tidak Aktif' },
                                        ]}
                                    />
                                ),
                            },
                            ...(usePage<PageProps>().props.permissions?.includes('kelola_pengguna')
                                ? [
                                    {
                                        header: 'Aksi',
                                        render: (user: UserData) => (
                                            <div className="flex justify-end gap-1">
                                                <Tooltip text="Edit">
                                                    <Button
                                                        href={`/user/${user.id}/edit`}
                                                        className="!bg-transparent !p-1 text-yellow-600 hover:bg-yellow-50 dark:text-yellow-400 dark:hover:bg-yellow-900/20"
                                                        icon={<Edit className="size-4" />}
                                                    />
                                                </Tooltip>
                                                <Tooltip text="Hapus">
                                                    <Button
                                                        onClick={() => {
                                                            setSelectedUser(user);
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
                ) : (
                    <div className="flex flex-col items-center justify-center py-12 text-center">
                        <div className="mb-4 rounded-full bg-red-100 p-3 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                            <Shield className="size-8" />
                        </div>
                        <h3 className="text-lg font-semibold text-slate-900 dark:text-white">Akses Ditolak</h3>
                        <p className="mt-1 text-slate-500 dark:text-slate-400">Anda tidak memiliki akses untuk melihat data pengguna</p>
                    </div>
                )}
            </ContentCard>

            {/* FAB for mobile */}
            <CheckPermissions permissions={['kelola_pengguna']}>
                <FloatingActionButton href="/user/create" label="Tambah Pengguna" />
            </CheckPermissions>
        </RootLayout>
    );
}
