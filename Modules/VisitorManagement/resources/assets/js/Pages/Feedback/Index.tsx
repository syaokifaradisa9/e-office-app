// Force reload
import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import DataTable from '@/components/tables/Datatable';
import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { Check, MessageSquare, Star, Search, FileSpreadsheet, Eye, X, ShieldCheck, Clock, User, MessageCircle } from 'lucide-react';
import Button from '@/components/buttons/Button';
import CheckPermissions from '@/components/utils/CheckPermissions';
import Badge from '@/components/badges/Badge';
import toast from 'react-hot-toast';
import Tooltip from '@/components/commons/Tooltip';
import Modal from '@/components/modals/Modal';
import FeedbackCardItem from './FeedbackCardItem';
import MobileSearchBar from '@/components/forms/MobileSearchBar';
import FormSearch from '@/components/forms/FormSearch';
import FormSearchSelect from '@/components/forms/FormSearchSelect';
import ConfirmationAlert from '@/components/alerts/ConfirmationAlert';

interface Feedback {
    id: number;
    visitor_name: string;
    visit_date: string;
    avg_rating: number;
    feedback_note: string;
    is_read: boolean;
    actions: {
        mark_as_read: boolean;
    };
}

interface PaginationData {
    data: Feedback[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number;
    to: number;
    total: number;
    links: any[];
    [key: string]: any;
}

export default function VisitorFeedbackIndex() {
    const [dataTable, setDataTable] = useState<PaginationData | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [params, setParams] = useState({
        search: '',
        status: '',
        page: 1,
        limit: 10,
    });

    const [deleteModal, setDeleteModal] = useState({
        open: false,
        id: null as number | null
    });

    const [markAsReadModal, setMarkAsReadModal] = useState({
        open: false,
        id: null as number | null
    });

    async function loadDatatable() {
        setIsLoading(true);
        const queryParams = new URLSearchParams();
        Object.entries(params).forEach(([key, value]) => {
            if (value) queryParams.append(key, value.toString());
        });

        const url = `/visitor/criticism-suggestions/datatable?${queryParams.toString()}`;

        try {
            const response = await fetch(url);
            const data = await response.json();
            setDataTable(data);
        } catch (err) {
            console.error("Failed to load datatable", err);
            toast.error("Gagal memuat data");
        } finally {
            setIsLoading(false);
        }
    }

    useEffect(() => {
        loadDatatable();
    }, [params]);

    function handleMarkAsRead(id: number) {
        setMarkAsReadModal({ open: true, id });
    }

    function confirmMarkAsRead() {
        if (!markAsReadModal.id) return;

        router.post(`/visitor/criticism-suggestions/${markAsReadModal.id}/mark-as-read`, {}, {
            onSuccess: () => {
                setMarkAsReadModal({ open: false, id: null });
                loadDatatable();
            }
        });
    }

    function handleDelete(id: number) {
        setDeleteModal({ open: true, id });
    }

    function confirmDelete() {
        if (!deleteModal.id) return;

        router.delete(`/visitor/criticism-suggestions/${deleteModal.id}/delete`, {
            onSuccess: () => {
                setDeleteModal({ open: false, id: null });
                loadDatatable();
            }
        });
    }

    function onParamsChange(e: { target: { name: string; value: string } }) {
        const { name, value } = e.target;
        setParams({ ...params, [name]: value as any, page: 1 });
    }

    const columns = [
        {
            header: 'Tanggal Kunjungan',
            render: (row: Feedback) => (
                <span className="font-medium text-slate-700 dark:text-slate-200">{row.visit_date}</span>
            ),
            footer: <FormSearch name="visit_date" onChange={onParamsChange} placeholder="Filter Tanggal" />
        },
        {
            header: 'Rata-rata Rating',
            render: (row: Feedback) => (
                <div className="flex items-center gap-1.5">
                    <div className="flex items-center gap-0.5">
                        {[1, 2, 3, 4, 5].map((s) => (
                            <Star
                                key={s}
                                className={`size-3.5 ${s <= Math.round(row.avg_rating) ? 'fill-amber-400 text-amber-400' : 'text-slate-300'}`}
                            />
                        ))}
                    </div>
                    <span className="text-sm font-bold text-slate-700 dark:text-slate-200 ml-1">
                        {row.avg_rating}
                    </span>
                </div>
            )
        },
        {
            header: 'Kritik dan Saran',
            width: '450px',
            render: (row: Feedback) => (
                <div className="max-w-2xl">
                    <p className="text-sm text-slate-600 dark:text-slate-400 line-clamp-2 italic">
                        "{row.feedback_note}"
                    </p>
                </div>
            ),
            footer: <FormSearch name="feedback_note" onChange={onParamsChange} placeholder="Filter Pesan" />
        },
        {
            header: 'Status',
            render: (row: Feedback) => (
                <Badge color={row.is_read ? 'success' : 'warning'}>
                    {row.is_read ? 'Dibaca' : 'Belum Dibaca'}
                </Badge>
            ),
            footer: (
                <FormSearchSelect
                    name="status"
                    value={params.status}
                    onChange={onParamsChange}
                    placeholder="Semua Status"
                    options={[
                        { value: '', label: 'Semua Status' },
                        { value: '0', label: 'Belum Dibaca' },
                        { value: '1', label: 'Dibaca' },
                    ]}
                />
            )
        },
        {
            header: 'Aksi',
            bodyClassname: 'text-right',
            render: (row: Feedback) => (
                <div className="flex justify-end gap-1">
                    <Tooltip text="Detail">
                        <Button
                            variant="ghost"
                            href={`/visitor/criticism-suggestions/detail/${row.id}`}
                            className="!bg-transparent !p-1 text-slate-500 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-800/50"
                            icon={<Eye className="size-4" />}
                        />
                    </Tooltip>

                    <CheckPermissions permissions={['kelola_kritik_saran_pengunjung']}>
                        {!row.is_read && (
                            <>
                                <Tooltip text="Tandai Dibaca">
                                    <Button
                                        variant="ghost"
                                        onClick={() => handleMarkAsRead(row.id)}
                                        className="!bg-transparent hover:!bg-transparent !p-1.5"
                                        icon={<Check className="size-4 text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300" />}
                                    />
                                </Tooltip>

                                <Tooltip text="Hapus">
                                    <Button
                                        variant="ghost"
                                        onClick={() => handleDelete(row.id)}
                                        className="!bg-transparent hover:!bg-transparent !p-1.5"
                                        icon={<X className="size-4 text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300" />}
                                    />
                                </Tooltip>
                            </>
                        )}
                    </CheckPermissions>
                </div>
            )
        }
    ];

    return (
        <RootLayout
            title="Kritik dan Saran"
            mobileSearchBar={
                <MobileSearchBar
                    searchValue={params.search}
                    onSearchChange={onParamsChange}
                    placeholder="Cari kritik & saran..."
                    actionButton={
                        <div className="flex items-center gap-1">
                            <a href="/visitor/criticism-suggestions/export" target="_blank" className="p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200" rel="noreferrer">
                                <FileSpreadsheet className="size-4" />
                            </a>
                        </div>
                    }
                />
            }
        >
            <ConfirmationAlert
                isOpen={deleteModal.open}
                setOpenModalStatus={(open) => setDeleteModal({ ...deleteModal, open })}
                title="Konfirmasi Hapus"
                message="Apakah Anda yakin ingin menghapus kritik dan saran ini? Tindakan ini tidak dapat dibatalkan."
                confirmText="Ya, Hapus"
                cancelText="Batal"
                type="danger"
                onConfirm={confirmDelete}
            />
            <ConfirmationAlert
                isOpen={markAsReadModal.open}
                setOpenModalStatus={(open) => setMarkAsReadModal({ ...markAsReadModal, open })}
                title="Konfirmasi Tandai Dibaca"
                message="Apakah Anda yakin ingin menandai kritik dan saran ini sebagai sudah dibaca?"
                confirmText="Ya, Tandai Dibaca"
                cancelText="Batal"
                type="info"
                onConfirm={confirmMarkAsRead}
            />
            <ContentCard
                title="Kritik dan Saran"
                subtitle="Daftar ulasan dan saran konstruktif dari pengunjung"
                mobileFullWidth
                bodyClassName="px-0 pb-24 pt-2 md:p-6"
            >
                <div className="mb-4 p-3 rounded-xl bg-blue-50 dark:bg-blue-500/5 border border-blue-100 dark:border-blue-500/10 flex items-start gap-3">
                    <div className="size-8 rounded-lg bg-white dark:bg-slate-800 flex items-center justify-center shrink-0 shadow-sm">
                        <ShieldCheck className="size-4 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div className="flex-1">
                        <p className="text-sm font-bold text-blue-900 dark:text-blue-100">Privasi Pengunjung Terjaga</p>
                        <p className="text-xs text-blue-700/70 dark:text-blue-400/70 leading-relaxed mt-0.5">
                            Nama dan identitas pemberi kritik/saran disembunyikan secara otomatis untuk menjaga privasi pengunjung. Data ini digunakan semata-mata untuk evaluasi peningkatan kualitas pelayanan.
                        </p>
                    </div>
                </div>

                <DataTable
                    dataTable={dataTable || { data: [] }}
                    columns={columns as any}
                    isLoading={isLoading}
                    limit={params.limit}
                    searchValue={params.search}
                    cardItem={(item: Feedback) => (
                        <FeedbackCardItem
                            item={item}
                            onMarkAsRead={(id) => handleMarkAsRead(id)}
                            onDelete={(id) => handleDelete(id)}
                        />
                    )}
                    onParamsChange={onParamsChange}
                    onSearchChange={(e) => {
                        setParams({ ...params, search: e.target.value, page: 1 });
                    }}
                    onChangePage={(e) => {
                        e.preventDefault();
                        const page = new URL((e.currentTarget as HTMLAnchorElement).href).searchParams.get('page');
                        if (page) setParams({ ...params, page: parseInt(page) });
                    }}
                    additionalHeaderElements={
                        <Tooltip text="Export Excel">
                            <Button
                                href="/visitor/criticism-suggestions/export"
                                variant="ghost"
                                className="flex h-9 w-9 items-center justify-center p-0 hover:bg-slate-50 dark:hover:bg-slate-800"
                                icon={<FileSpreadsheet className="size-4" />}
                                target="_blank"
                                title=""
                            />
                        </Tooltip>
                    }
                />
            </ContentCard>
        </RootLayout>
    );
}
