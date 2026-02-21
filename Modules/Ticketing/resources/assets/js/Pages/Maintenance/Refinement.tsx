import RootLayout from '@/components/layouts/RootLayout';
import ContentCard from '@/components/layouts/ContentCard';
import Button from '@/components/buttons/Button';
import DataTable from '@/components/tables/Datatable';
import { router } from '@inertiajs/react';
import { CheckCircle2, History as HistoryIcon, Wrench, Plus } from 'lucide-react';
import { useState, useEffect } from 'react';
import { Link } from '@inertiajs/react';

interface ChecklistResult {
    checklist_id: number;
    label: string;
    description: string | null;
    value: 'Baik' | 'Tidak Baik';
    note: string;
    follow_up: string;
}

interface Attachment {
    name: string;
    url: string;
    size: number;
}

interface Maintenance {
    id: number;
    asset_item: {
        id: number;
        category_name: string;
        serial_number: string;
        merk: string;
        model: string;
    };
    estimation_date: string;
    actual_date: string | null;
    note: string | null;
    status: {
        value: string;
        label: string;
    };
    checklist_results: ChecklistResult[] | null;
    attachments: Attachment[] | null;
}

interface RefinementLog {
    id: number;
    date: string;
    description: string;
    note: string | null;
    result: string;
    attachments: Attachment[] | null;
    [key: string]: unknown;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginationData {
    data: RefinementLog[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number;
    to: number;
    total: number;
    [key: string]: unknown;
}

interface Props {
    maintenance: Maintenance;
}

interface FormDataType {
    date: string;
    description: string;
    note: string;
    result: string;
    attachments: FileList | null;
}

export default function MaintenanceRefinement({ maintenance }: Props) {
    const [processing, setProcessing] = useState(false);
    const [showForm, setShowForm] = useState(false);

    const [dataTable, setDataTable] = useState<PaginationData>({
        data: [],
        links: [],
        current_page: 1,
        last_page: 1,
        per_page: 5,
        from: 0,
        to: 0,
        total: 0,
    });

    const [params, setParams] = useState({
        search: '',
        limit: 5,
        page: 1,
        sort_by: 'date',
        sort_direction: 'desc' as 'asc' | 'desc',
    });

    const [isLoading, setIsLoading] = useState(true);

    const loadDatatable = async () => {
        setIsLoading(true);
        try {
            const queryParams = new URLSearchParams({
                ...params,
                page: params.page.toString(),
                limit: params.limit.toString(),
            }).toString();
            const response = await fetch(`/ticketing/maintenances/${maintenance.id}/refinement/datatable?${queryParams}`);
            const result = await response.json();
            setDataTable(result);
        } catch (error) {
            console.error(error);
        } finally {
            setIsLoading(false);
        }
    };

    useEffect(() => {
        loadDatatable();
    }, [params]);

    function onParamsChange(e: { target: { name: string; value: string } }) {
        setParams({ ...params, [e.target.name]: e.target.value, page: 1 });
    }

    function onChangePage(e: React.MouseEvent<HTMLAnchorElement>) {
        e.preventDefault();
        const url = new URL(e.currentTarget.href);
        const pageNum = url.searchParams.get('page');
        if (pageNum) setParams(prev => ({ ...prev, page: parseInt(pageNum) }));
    }

    const handleFinishRepair = () => {
        setProcessing(true);
        router.post(`/ticketing/maintenances/${maintenance.id}/confirm`, {}, {
            onFinish: () => setProcessing(false)
        });
    };

    const columns = [
        {
            header: 'Tanggal',
            name: 'date',
            width: '120px',
            render: (item: RefinementLog) => (
                <span className="text-slate-700 dark:text-white font-medium">
                    {new Date(item.date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}
                </span>
            )
        },
        {
            header: 'Deskripsi Perbaikan',
            name: 'description',
            render: (item: RefinementLog) => (
                <div className="space-y-2">
                    <p className="text-slate-700 dark:text-white font-medium leading-relaxed">{item.description}</p>
                    {item.attachments && item.attachments.length > 0 && (
                        <div className="flex -space-x-2 overflow-hidden py-1">
                            {item.attachments.map((file, idx) => (
                                <a key={idx} href={file.url} target="_blank" rel="noopener" className="relative group">
                                    <div className="size-7 rounded-full border-2 border-white dark:border-white/10 bg-slate-100 overflow-hidden shadow-sm">
                                        <img src={file.url} alt="" className="size-full object-cover" title={file.name} />
                                    </div>
                                </a>
                            ))}
                        </div>
                    )}
                </div>
            )
        },
        {
            header: 'Hasil',
            name: 'result',
            width: '150px',
            render: (item: RefinementLog) => (
                <span className="text-sm text-slate-700 dark:text-white font-medium">{item.result}</span>
            )
        },
        {
            header: 'Catatan',
            name: 'note',
            width: '180px',
            render: (item: RefinementLog) => (
                <span className="text-sm text-slate-700 dark:text-white font-medium italic">
                    {item.note ? `"${item.note}"` : '-'}
                </span>
            )
        },
        {
            header: 'Aksi',
            name: 'actions',
            width: '100px',
            render: (item: RefinementLog) => (
                <div className="flex justify-center gap-2">
                    <button className="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-white/5 text-slate-400 dark:text-white/40 hover:text-primary transition-colors text-xs font-bold">
                        Hapus
                    </button>
                </div>
            )
        }
    ];

    const InfoRow = ({ label, value, alignTop = false }: { label: string, value: React.ReactNode, alignTop?: boolean }) => (
        <div className={`flex flex-col sm:flex-row ${alignTop ? 'sm:items-start' : 'sm:items-baseline'} py-1.5 border-b border-slate-100 dark:border-white/5 last:border-0 transition-colors`}>
            <div className={`sm:w-1/4 mb-1 sm:mb-0 ${alignTop ? 'sm:pt-1' : ''}`}>
                <span className="text-sm font-bold text-slate-700 dark:text-white leading-none">{label}</span>
            </div>
            <div className="sm:w-3/4 flex items-baseline gap-4">
                <span className="hidden sm:inline text-slate-700 dark:text-white font-light">:</span>
                <div className="text-sm font-semibold text-slate-700 dark:text-white w-full pl-0 sm:pl-2">
                    {value}
                </div>
            </div>
        </div>
    );

    const problemChecklists = maintenance.checklist_results?.filter(item => item.value === 'Tidak Baik') || [];

    return (
        <RootLayout title={`Perbaikan Asset ${maintenance.asset_item.merk} ${maintenance.asset_item.model}`} backPath="/ticketing/maintenances">
            <ContentCard
                title={`Perbaikan Asset ${maintenance.asset_item.merk} ${maintenance.asset_item.model}`}
                subtitle="Maintenance Process & Repair History"
                backPath="/ticketing/maintenances"
                additionalButton={
                    <Button
                        onClick={handleFinishRepair}
                        label="Selesaikan"
                        icon={<CheckCircle2 className="size-4" />}
                        isLoading={processing}
                        className="!bg-primary hover:!bg-primary/90 !text-white !rounded-xl px-6 py-2 shadow-lg shadow-primary/10 active:scale-95 transition-all font-bold text-xs"
                    />
                }
            >
                <div className="space-y-12">
                    <div className="divide-y divide-slate-100 dark:divide-white/5">
                        <InfoRow label="Merk Asset" value={maintenance.asset_item.merk} />
                        <InfoRow label="Model Asset" value={maintenance.asset_item.model} />
                        <InfoRow label="Nomor Seri" value={<span className="font-mono text-primary">{maintenance.asset_item.serial_number}</span>} />
                        <InfoRow
                            label="Catatan Maintenance"
                            alignTop
                            value={
                                <span className="text-slate-700 dark:text-white font-medium leading-relaxed">
                                    {maintenance.note ? `"${maintenance.note}"` : 'Tidak ada catatan awal.'}
                                </span>
                            }
                        />
                        <div className="py-2 border-b border-slate-100 dark:border-white/5 last:border-0 transition-colors">
                            <div className="mb-3">
                                <span className="text-sm font-bold text-slate-700 dark:text-white">Identifikasi Masalah</span>
                            </div>
                            <div className="space-y-6 pl-2">
                                {problemChecklists.length > 0 ? problemChecklists.map((problem, idx) => (
                                    <div key={idx} className="group">
                                        <div className="flex-1 space-y-1.5">
                                            <h4 className="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-2">
                                                <div className="size-1.5 rounded-full bg-rose-500" />
                                                {problem.label}
                                            </h4>
                                            <p className="text-sm text-slate-700 dark:text-white leading-relaxed pl-3.5">
                                                {problem.note || 'Tidak ada deskripsi temuan.'}
                                            </p>
                                            <div className="flex items-center gap-2 pt-1 pl-3.5 opacity-80">
                                                <Wrench className="size-3 text-primary" />
                                                <span className="text-xs font-bold text-primary">Follow Up:</span>
                                                <span className="text-xs text-slate-700 dark:text-white font-medium">{problem.follow_up}</span>
                                            </div>
                                        </div>
                                    </div>
                                )) : (
                                    <span className="text-sm text-slate-700 dark:text-white italic">Bersih. Tidak ada masalah pada pemeriksaan checklist.</span>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* History Section */}
                    <div className="space-y-4 pt-4">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className="p-2 rounded-xl bg-slate-50 dark:bg-white/5">
                                    <HistoryIcon className="size-4 text-slate-500 dark:text-white/60" />
                                </div>
                                <h3 className="text-base font-black text-slate-800 dark:text-white">Riwayat Perbaikan</h3>
                            </div>
                            <Link
                                href={`/ticketing/maintenances/${maintenance.id}/refinement/create`}
                            >
                                <Button
                                    variant="primary"
                                    icon={<Plus className="size-3.5" />}
                                    className="!rounded-full !py-2 !px-4 text-[10px] font-bold shadow-sm bg-primary/10 !text-primary border-none hover:bg-primary/20"
                                    label="Tambah"
                                />
                            </Link>
                        </div>


                        <div className="overflow-hidden">
                            <DataTable
                                dataTable={dataTable}
                                columns={columns}
                                onParamsChange={onParamsChange}
                                onChangePage={onChangePage}
                                limit={params.limit}
                                isLoading={isLoading}
                                searchValue={params.search}
                                onSearchChange={onParamsChange}
                                sortBy={params.sort_by}
                                sortDirection={params.sort_direction}
                            />
                        </div>
                    </div>
                </div>
            </ContentCard>
        </RootLayout>
    );
}
