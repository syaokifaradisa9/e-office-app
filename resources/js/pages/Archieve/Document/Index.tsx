import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import DataTable from '@/components/tables/Datatable';
import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { Edit, Plus, Trash2, FileSpreadsheet, Shield, FileText, Download, Eye } from 'lucide-react';
import { router } from '@inertiajs/react';
import ConfirmationAlert from '@/components/alerts/ConfirmationAlert';
import FormSearch from '@/components/forms/FormSearch';
import Button from '@/components/buttons/Button';
import CheckPermissions from '@/components/utils/CheckPermissions';
import MobileSearchBar from '@/components/forms/MobileSearchBar';
import FloatingActionButton from '@/components/buttons/FloatingActionButton';
import { DivisionCardSkeleton } from '@/components/skeletons/CardSkeleton';
import Tooltip from '@/components/commons/Tooltip';
import FormSelect from '@/components/forms/FormSelect';

interface Category {
    id: number;
    name: string;
}

interface Division {
    id: number;
    name: string;
}

interface Classification {
    id: number;
    code: string;
    name: string;
}

interface Document {
    id: number;
    title: string;
    description: string | null;
    classification: Classification | null;
    categories: Category[];
    divisions: Division[];
    file_name: string;
    file_size_label: string;
    uploader?: { name: string };
    created_at: string;
}

interface PaginationData {
    data: Document[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number;
    to: number;
    total: number;
    [key: string]: unknown;
}

interface Context {
    id: number;
    name: string;
    categories: Category[];
}

interface PageProps {
    permissions?: string[];
    contexts: Context[];
    classifications: Classification[];
    divisions: Division[];
    viewType: 'all' | 'division' | 'personal';
    userDivisionId?: number;
    userId?: number;
    [key: string]: unknown;
}

interface Params {
    search: string;
    classification_id: string;
    limit: number;
    page: number;
    sort_by: string;
    sort_direction: 'asc' | 'desc';
    view_type: string;
}

export default function DocumentIndex() {
    const { permissions, viewType, userDivisionId, userId } = usePage<PageProps>().props;

    const hasManagePermission = permissions?.includes('kelola_semua_arsip')
        || permissions?.includes('kelola_arsip_divisi');

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
        classification_id: '',
        limit: 20,
        page: 1,
        sort_by: 'created_at',
        sort_direction: 'desc',
        view_type: viewType,
    });

    const [openConfirm, setOpenConfirm] = useState(false);
    const [selectedItem, setSelectedItem] = useState<Document | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    // Sync view_type with viewType prop
    useEffect(() => {
        if (params.view_type !== viewType) {
            setParams(prev => ({ ...prev, view_type: viewType }));
        }
    }, [viewType]);

    async function loadDatatable() {
        // Don't load if view_type doesn't match the expected viewType
        if (params.view_type !== viewType) return;

        setIsLoading(true);
        let url = `/archieve/documents/datatable`;
        const queryParams: string[] = [];

        Object.keys(params).forEach((key) => {
            if (params[key as keyof Params]) {
                queryParams.push(`${key}=${params[key as keyof Params]}`);
            }
        });

        if (queryParams.length > 0) {
            url += `?${queryParams.join('&')}`;
        }

        try {
            const response = await fetch(url);
            if (!response.ok) {
                console.error('Datatable fetch error:', response.status);
                setIsLoading(false);
                return;
            }
            const data = await response.json();
            setDataTable(data);
        } catch (error) {
            console.error('Datatable error:', error);
        }
        setIsLoading(false);
    }

    useEffect(() => {
        loadDatatable();
    }, [params, viewType]);

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
        let url = `/archieve/documents/print-excel`;
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

    function getTitle() {
        switch (viewType) {
            case 'division': return 'Arsip Dokumen Divisi';
            case 'personal': return 'Arsip Dokumen Pribadi';
            default: return 'Arsip Dokumen';
        }
    }

    return (
        <RootLayout
            title={getTitle()}
            mobileSearchBar={
                <MobileSearchBar
                    searchValue={params.search}
                    onSearchChange={onParamsChange}
                    placeholder="Cari dokumen..."
                    actionButton={
                        <a href={getPrintUrl()} target="_blank" className="p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200" rel="noreferrer">
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
                message={`Hapus dokumen "${selectedItem?.title}"? Tindakan ini tidak dapat dibatalkan.`}
                confirmText="Ya, Hapus"
                cancelText="Batal"
                type="danger"
                onConfirm={() => {
                    if (selectedItem?.id) {
                        router.delete(`/archieve/documents/${selectedItem.id}`, {
                            onSuccess: () => loadDatatable(),
                        });
                    }
                }}
            />
            <ContentCard
                title={getTitle()}
                mobileFullWidth
                additionalButton={
                    hasManagePermission ? (
                        <Button className="hidden w-full md:flex" label="Upload Dokumen" href="/archieve/documents/create" icon={<Plus className="size-4" />} />
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
                            name: 'title',
                            header: 'Judul Dokumen',
                            render: (item: Document) => (
                                <div className="flex flex-col">
                                    <span className="font-medium">{item.title}</span>
                                    <span className="text-xs text-slate-400">{item.file_name}</span>
                                </div>
                            ),
                            footer: <FormSearch name="title" onChange={onParamsChange} placeholder="Filter Judul" />,
                        },
                        {
                            name: 'classification',
                            header: 'Klasifikasi',
                            render: (item: Document) => (
                                <span className="text-sm">{item.classification ? `[${item.classification.code}] ${item.classification.name}` : '-'}</span>
                            ),
                        },
                        {
                            name: 'categories',
                            header: 'Kategori',
                            render: (item: Document) => (
                                <div className="flex flex-wrap gap-1">
                                    {item.categories.slice(0, 2).map((cat) => (
                                        <span key={cat.id} className="rounded bg-primary/10 px-1.5 py-0.5 text-[10px] font-medium text-primary">
                                            {cat.name}
                                        </span>
                                    ))}
                                    {item.categories.length > 2 && (
                                        <span className="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-500 dark:bg-slate-800">
                                            +{item.categories.length - 2}
                                        </span>
                                    )}
                                </div>
                            ),
                        },
                        ...(viewType === 'all' ? [{
                            name: 'divisions',
                            header: 'Divisi',
                            render: (item: Document) => (
                                <div className="flex flex-wrap gap-1">
                                    {item.divisions.slice(0, 2).map((div) => (
                                        <span key={div.id} className="rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                            {div.name}
                                        </span>
                                    ))}
                                    {item.divisions.length > 2 && (
                                        <span className="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-500 dark:bg-slate-800">
                                            +{item.divisions.length - 2}
                                        </span>
                                    )}
                                </div>
                            ),
                        }] : []),
                        {
                            name: 'file_size',
                            header: 'Ukuran',
                            render: (item: Document) => <span className="text-sm text-slate-500">{item.file_size_label}</span>,
                        },
                        {
                            name: 'created_at',
                            header: 'Tanggal Upload',
                            render: (item: Document) => (
                                <div className="flex flex-col">
                                    <span className="text-sm">{new Date(item.created_at).toLocaleDateString('id-ID')}</span>
                                    <span className="text-xs text-slate-400">{item.uploader?.name}</span>
                                </div>
                            ),
                        },
                        {
                            header: 'Aksi',
                            render: (item: Document) => (
                                <div className="flex justify-end gap-1">
                                    <Tooltip text="Download">
                                        <Button
                                            href={`/storage/${item.file_name}`}
                                            target="_blank"
                                            className="!bg-transparent !p-1 text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20"
                                            icon={<Download className="size-4" />}
                                        />
                                    </Tooltip>
                                    {hasManagePermission && (
                                        <>
                                            <Tooltip text="Edit">
                                                <Button
                                                    href={`/archieve/documents/${item.id}/edit`}
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
                                        </>
                                    )}
                                </div>
                            ),
                        },
                    ]}
                />
            </ContentCard>

            {hasManagePermission && (
                <FloatingActionButton href="/archieve/documents/create" label="Upload Dokumen" />
            )}
        </RootLayout>
    );
}
