import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import DataTable from '@/components/tables/Datatable';
import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { ClipboardCheck, Edit, Plus, Trash2, FileSpreadsheet } from 'lucide-react';
import { TicketingPermission } from '../../types/permissions';
import { router } from '@inertiajs/react';
import ConfirmationAlert from '@/components/alerts/ConfirmationAlert';
import FormSearch from '@/components/forms/FormSearch';
import Button from '@/components/buttons/Button';
import CheckPermissions from '@/components/utils/CheckPermissions';
import MobileSearchBar from '@/components/forms/MobileSearchBar';
import Tooltip from '@/components/commons/Tooltip';
import FloatingActionButton from '@/components/buttons/FloatingActionButton';

interface AssetModelInfo {
    id: number;
    name: string;
    type: string;
    division: string | null;
}

interface ChecklistItem {
    id: number;
    label: string;
    description: string | null;
}

interface PaginationData {
    data: ChecklistItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number;
    to: number;
    total: number;
    [key: string]: any;
}

interface PageProps {
    assetModel: AssetModelInfo;
    permissions?: string[];
    [key: string]: unknown;
}

interface Params {
    search: string;
    limit: number;
    page: number;
    sort_by: string;
    sort_direction: 'asc' | 'desc';
    label?: string;
    description?: string;
}

export default function ChecklistIndex() {
    const { assetModel, permissions } = usePage<PageProps>().props;
    const canManage = permissions?.includes(TicketingPermission.ManageChecklist);

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
        sort_by: 'label',
        sort_direction: 'asc',
    });

    const [openConfirm, setOpenConfirm] = useState(false);
    const [selectedItem, setSelectedItem] = useState<ChecklistItem | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    const baseUrl = `/ticketing/asset-models/${assetModel.id}/checklists`;

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
            title={`Checklist - ${assetModel.name}`}
            backPath="/ticketing/asset-models"
            mobileSearchBar={
                <MobileSearchBar
                    searchValue={params.search}
                    onSearchChange={onParamsChange}
                    placeholder="Cari checklist..."
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
                    message={`Hapus checklist "${selectedItem?.label}"? Tindakan ini tidak dapat dibatalkan.`}
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
                    title={`Checklist: ${assetModel.name}`}
                    subtitle={`Kelola daftar checklist untuk asset model ${assetModel.name} (${assetModel.type === 'Physic' ? 'Fisik' : 'Digital'}) - ${assetModel.division || 'Tanpa Divisi'}`}
                    backPath="/ticketing/asset-models"
                    mobileFullWidth
                    bodyClassName="px-0 pb-24 pt-2 md:p-6"
                    additionalButton={
                        canManage ? (
                            <Button className="hidden w-full md:flex" label="Tambah Checklist" href={`${baseUrl}/create`} icon={<Plus className="size-4" />} />
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
                        cardItem={(item: ChecklistItem) => (
                            <div className="group transition-colors duration-150 hover:bg-slate-50/80 dark:hover:bg-slate-700/20">
                                <div className="flex items-start gap-3.5 px-4 py-4">
                                    <div className="mt-0.5 flex size-10 flex-shrink-0 items-center justify-center rounded-xl bg-primary/10 dark:bg-primary/15">
                                        <ClipboardCheck className="size-5 text-primary" />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <h3 className="truncate text-[15px] font-semibold text-slate-800 dark:text-white">{item.label}</h3>
                                        {item.description && (
                                            <p className="mt-1 line-clamp-2 text-[13px] text-slate-500 dark:text-slate-400">{item.description}</p>
                                        )}
                                        {canManage && (
                                            <div className="mt-3 grid grid-cols-2 gap-2">
                                                <Button
                                                    href={`${baseUrl}/${item.id}/edit`}
                                                    className="flex items-center justify-center gap-1.5 rounded-lg border border-amber-200 px-3 py-2 text-[13px] font-medium !text-amber-600 !bg-transparent transition-colors hover:bg-amber-50 dark:border-amber-800/50 dark:!text-amber-400 dark:hover:bg-amber-900/20"
                                                    icon={<Edit className="size-3.5" />}
                                                    label="Edit"
                                                />
                                                <button
                                                    onClick={() => {
                                                        setSelectedItem(item);
                                                        setOpenConfirm(true);
                                                    }}
                                                    className="flex items-center justify-center gap-1.5 rounded-lg border border-red-200 px-3 py-2 text-[13px] font-medium text-red-500 transition-colors hover:bg-red-50 dark:border-red-800/50 dark:text-red-400 dark:hover:bg-red-900/20"
                                                >
                                                    <Trash2 className="size-3.5" />
                                                    Hapus
                                                </button>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
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
                                header: 'Keterangan',
                                render: (item: ChecklistItem) => (
                                    <div className="flex items-center gap-2">
                                        <ClipboardCheck className="size-4 text-primary" />
                                        <span className="font-medium">{item.label}</span>
                                    </div>
                                ),
                                footer: <FormSearch name="label" onChange={(e: any) => onParamsChange(e)} placeholder="Filter Keterangan" />,
                            },
                            {
                                name: 'description',
                                header: 'Deskripsi',
                                render: (item: ChecklistItem) => (
                                    <span className="text-gray-500 dark:text-slate-400">{item.description || '-'}</span>
                                ),
                                footer: <FormSearch name="description" onChange={(e: any) => onParamsChange(e)} placeholder="Filter Deskripsi" />,
                            },
                            ...(canManage
                                ? [
                                    {
                                        header: 'Aksi',
                                        render: (item: ChecklistItem) => (
                                            <div className="flex justify-end gap-1">
                                                <Tooltip text="Edit">
                                                    <Button
                                                        variant="ghost"
                                                        href={`${baseUrl}/${item.id}/edit`}
                                                        className="!p-1.5 !text-amber-500 hover:bg-amber-50 dark:!text-amber-400 dark:hover:bg-amber-900/20"
                                                        icon={<Edit className="size-4" />}
                                                    />
                                                </Tooltip>
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
                                            </div>
                                        ),
                                    },
                                ]
                                : []),
                        ]}
                    />
                </ContentCard>

                <CheckPermissions permissions={[TicketingPermission.ManageChecklist]}>
                    <FloatingActionButton href={`${baseUrl}/create`} label="Tambah Checklist" />
                </CheckPermissions>
            </>
        </RootLayout>
    );
}
