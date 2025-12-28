import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import DataTable from '@/components/tables/Datatable';
import { useEffect, useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import { MessageSquareText, Edit, Plus, Trash2, Shield, ToggleLeft, ToggleRight } from 'lucide-react';
import ConfirmationAlert from '@/components/alerts/ConfirmationAlert';
import FormSearch from '@/components/forms/FormSearch';
import Button from '@/components/buttons/Button';
import CheckPermissions from '@/components/utils/CheckPermissions';
import MobileSearchBar from '@/components/forms/MobileSearchBar';
import FloatingActionButton from '@/components/buttons/FloatingActionButton';
import { DivisionCardSkeleton } from '@/components/skeletons/CardSkeleton';
import Tooltip from '@/components/commons/Tooltip';

interface FeedbackQuestion {
    id: number;
    question: string;
    is_active: boolean;
    created_at?: string;
}

interface PaginationData {
    data: FeedbackQuestion[];
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

export default function FeedbackQuestionIndex() {
    const { permissions } = usePage<PageProps>().props;
    const hasViewPermission = permissions?.includes('lihat_pertanyaan_feedback');
    const hasManagePermission = permissions?.includes('kelola_pertanyaan_feedback');

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
        sort_by: 'id',
        sort_direction: 'desc',
    });

    const [openConfirm, setOpenConfirm] = useState(false);
    const [selectedItem, setSelectedItem] = useState<FeedbackQuestion | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    async function loadDatatable() {
        if (!hasViewPermission && !hasManagePermission) {
            setIsLoading(false);
            return;
        }
        setIsLoading(true);
        let url = `/visitor/feedback-questions/datatable`;
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

    function handleToggleStatus(item: FeedbackQuestion) {
        router.post(`/visitor/feedback-questions/${item.id}/toggle`, {}, {
            preserveState: true,
            onSuccess: () => loadDatatable(),
        });
    }

    return (
        <RootLayout
            title="Pertanyaan Feedback"
            mobileSearchBar={
                hasViewPermission || hasManagePermission ? (
                    <MobileSearchBar
                        searchValue={params.search}
                        onSearchChange={onParamsChange}
                        placeholder="Cari pertanyaan..."
                    />
                ) : undefined
            }
        >
            <ConfirmationAlert
                isOpen={openConfirm}
                setOpenModalStatus={setOpenConfirm}
                title="Konfirmasi Hapus"
                message={`Hapus pertanyaan "${selectedItem?.question?.substring(0, 50)}..."? Tindakan ini tidak dapat dibatalkan.`}
                confirmText="Ya, Hapus"
                cancelText="Batal"
                type="danger"
                onConfirm={() => {
                    if (selectedItem?.id) {
                        router.delete(`/visitor/feedback-questions/${selectedItem.id}/delete`, {
                            onSuccess: () => loadDatatable(),
                        });
                    }
                }}
            />

            <ContentCard
                title="Pertanyaan Feedback"
                subtitle="Kelola pertanyaan yang ditampilkan kepada pengunjung saat memberikan umpan balik"
                mobileFullWidth
                additionalButton={
                    <CheckPermissions permissions={['kelola_pertanyaan_feedback']}>
                        <Button
                            className="hidden w-full md:flex"
                            label="Tambah Pertanyaan"
                            href="/visitor/feedback-questions/create"
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
                        <p className="mt-1 text-slate-500 dark:text-slate-400">Anda tidak memiliki akses untuk melihat data pertanyaan feedback</p>
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
                                name: 'question',
                                header: 'Pertanyaan',
                                render: (item: FeedbackQuestion) => (
                                    <div className="flex items-start gap-2">
                                        <MessageSquareText className="mt-0.5 size-4 flex-shrink-0 text-primary" />
                                        <span className="font-medium">{item.question}</span>
                                    </div>
                                ),
                                footer: <FormSearch name="question" onChange={onParamsChange} placeholder="Filter Pertanyaan" />,
                            },
                            {
                                name: 'is_active',
                                header: 'Status',
                                render: (item: FeedbackQuestion) => (
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
                            },
                            ...(hasManagePermission
                                ? [
                                    {
                                        header: 'Aksi',
                                        render: (item: FeedbackQuestion) => (
                                            <div className="flex justify-end gap-1">
                                                <Tooltip text="Edit">
                                                    <Button
                                                        href={`/visitor/feedback-questions/${item.id}/edit`}
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

            <CheckPermissions permissions={['kelola_pertanyaan_feedback']}>
                <FloatingActionButton href="/visitor/feedback-questions/create" label="Tambah Pertanyaan" />
            </CheckPermissions>
        </RootLayout>
    );
}
