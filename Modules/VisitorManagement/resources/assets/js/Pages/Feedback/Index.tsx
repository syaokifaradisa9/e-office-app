import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import DataTable from '@/components/tables/Datatable';
import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { Check, MessageSquare, Star, Search, FileSpreadsheet } from 'lucide-react';
import Button from '@/components/buttons/Button';
import CheckPermissions from '@/components/utils/CheckPermissions';
import Badge from '@/components/badges/Badge';
import toast from 'react-hot-toast';

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
        page: 1,
        limit: 10,
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
        router.post(`/visitor/criticism-suggestions/${id}/mark-as-read`, {}, {
            onSuccess: () => {
                toast.success('Berhasil menandai sebagai dibaca');
                loadDatatable();
            }
        });
    }

    const columns = [
        {
            header: 'Tanggal Kunjungan',
            render: (row: Feedback) => (
                <span className="font-medium text-slate-700 dark:text-slate-200">{row.visit_date}</span>
            )
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
            )
        },
        {
            header: 'Status',
            render: (row: Feedback) => (
                <Badge color={row.is_read ? 'success' : 'warning'}>
                    {row.is_read ? 'Dibaca' : 'Belum Dibaca'}
                </Badge>
            )
        },
        {
            header: 'Aksi',
            width: '80px',
            render: (row: Feedback) => (
                <div className="flex items-center gap-1">
                    {!row.is_read && (
                        <CheckPermissions permissions={['kelola_kritik_saran_pengunjung']}>
                            <Button
                                variant="ghost"
                                onClick={() => handleMarkAsRead(row.id)}
                                className="text-emerald-600 hover:bg-emerald-50 p-2 min-h-0"
                                icon={<Check className="size-4" />}
                                title="Tandai Dibaca"
                            />
                        </CheckPermissions>
                    )}
                </div>
            )
        }
    ];

    return (
        <RootLayout title="Kritik dan Saran">
            <div className="space-y-6 animate-in fade-in duration-500">
                <ContentCard
                    title="Kritik dan Saran"
                    subtitle="Daftar ulasan dan saran konstruktif dari pengunjung"
                >
                    <DataTable
                        dataTable={dataTable || { data: [] }}
                        columns={columns as any}
                        isLoading={isLoading}
                        limit={params.limit}
                        searchValue={params.search}
                        onParamsChange={(e) => {
                            const { name, value } = e.target;
                            setParams({ ...params, [name]: value as any, page: 1 });
                        }}
                        onSearchChange={(e) => {
                            setParams({ ...params, search: e.target.value, page: 1 });
                        }}
                        onChangePage={(e) => {
                            e.preventDefault();
                            const page = new URL((e.currentTarget as HTMLAnchorElement).href).searchParams.get('page');
                            if (page) setParams({ ...params, page: parseInt(page) });
                        }}
                        additionalHeaderElements={
                            <Button
                                href="/visitor/criticism-suggestions/export"
                                variant="ghost"
                                className="flex h-9 w-9 items-center justify-center p-0 hover:bg-slate-50 dark:hover:bg-slate-800"
                                icon={<FileSpreadsheet className="size-4" />}
                                target="_blank"
                                title="Export Excel"
                            />
                        }
                    />
                </ContentCard>
            </div>
        </RootLayout>
    );
}
