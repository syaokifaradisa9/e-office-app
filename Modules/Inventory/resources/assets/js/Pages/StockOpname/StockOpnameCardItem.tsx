import { Link } from '@inertiajs/react';
import { Eye, Edit, Trash2, CheckCircle, ClipboardList, Calendar, MapPin } from 'lucide-react';

interface StockOpnameItem {
    id: number;
    opname_date: string;
    status: string;
    division_id: number | null;
    division?: { id: number; name: string } | null;
    user?: { id: number; name: string } | null;
}

interface Props {
    item: StockOpnameItem;
    canEdit: boolean;
    canConfirm: boolean;
    onDelete: () => void;
    onConfirm: () => void;
}

// Status translations
const statusLabels: Record<string, string> = {
    'Pending': 'Pending',
    'Proses': 'Proses',
    'Stock Opname': 'Opname Selesai',
    'Finish': 'Final',
};

export default function StockOpnameCardItem({ item, canEdit, canConfirm, onDelete, onConfirm }: Props) {
    // Status badge colors
    const statusColors: Record<string, string> = {
        'Pending': 'bg-blue-50 text-blue-600 dark:bg-blue-900/25 dark:text-blue-400',
        'Proses': 'bg-amber-50 text-amber-600 dark:bg-amber-900/25 dark:text-amber-400',
        'Stock Opname': 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/25 dark:text-emerald-400',
        'Finish': 'bg-purple-50 text-purple-600 dark:bg-purple-900/25 dark:text-purple-400',
    };

    const formatDate = (dateString: string) => {
        if (!dateString) return '-';

        const datePart = dateString.includes('T') ? dateString.split('T')[0] : dateString;
        const date = new Date(datePart.replace(/-/g, '/'));

        if (isNaN(date.getTime())) return dateString;

        return date.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    };

    const buttonCount = (() => {
        let count = 1; // Lihat always there
        if (canEdit) count += 2; // Edit & Delete
        if (canConfirm) count += 1;
        return count;
    })();

    return (
        <div className="group transition-colors duration-150 hover:bg-slate-50/80 dark:hover:bg-slate-700/20">
            <div className="flex items-start gap-4 px-4 py-4">
                {/* Icon Box */}
                <div className="mt-0.5 flex size-10 flex-shrink-0 items-center justify-center rounded-xl bg-primary/10 dark:bg-primary/15 text-primary">
                    <ClipboardList className="size-5" />
                </div>

                {/* Content */}
                <div className="min-w-0 flex-1">
                    {/* Header: Date & Status */}
                    <div className="flex items-start justify-between gap-2">
                        <div className="min-w-0">
                            <h3 className="truncate text-[15px] font-bold text-slate-800 dark:text-white">
                                {formatDate(item.opname_date)}
                            </h3>
                            <div className="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-[12px] font-medium text-slate-500 dark:text-slate-400">
                                <span className="flex items-center gap-1">
                                    <MapPin className="size-3" />
                                    {item.division ? item.division.name : 'Gudang Utama'}
                                </span>
                                <span className="text-slate-300 dark:text-slate-600">•</span>
                                <span className="flex items-center gap-1 lowercase first-letter:uppercase">
                                    {item.user?.name ?? '-'}
                                </span>
                            </div>
                        </div>
                        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-tight ${statusColors[item.status] || 'bg-slate-100 text-slate-600'}`}>
                            {statusLabels[item.status] || item.status}
                        </span>
                    </div>

                    {/* Actions */}
                    <div className={`mt-4 grid gap-2 ${buttonCount === 1 ? 'grid-cols-1' :
                        buttonCount === 2 ? 'grid-cols-2' :
                            buttonCount === 3 ? 'grid-cols-3' : 'grid-cols-2'
                        }`}>
                        {/* Lihat */}
                        <Link
                            href={`/inventory/stock-opname/${item.id}`}
                            className="flex items-center justify-center gap-1.5 rounded-lg border border-blue-200 px-3 py-2 text-[13px] font-medium text-blue-600 transition-colors hover:bg-blue-50 dark:border-blue-800/50 dark:text-blue-400 dark:hover:bg-blue-900/20"
                        >
                            <Eye className="size-3.5" />
                            Detail
                        </Link>

                        {/* Edit & Hapus */}
                        {canEdit && (
                            <>
                                <Link
                                    href={`/inventory/stock-opname/${item.id}/edit`}
                                    className="flex items-center justify-center gap-1.5 rounded-lg border border-amber-200 px-3 py-2 text-[13px] font-medium text-amber-600 transition-colors hover:bg-amber-50 dark:border-amber-800/50 dark:text-amber-400 dark:hover:bg-amber-900/20"
                                >
                                    <Edit className="size-3.5" />
                                    Edit
                                </Link>
                                <button
                                    onClick={onDelete}
                                    className="flex items-center justify-center gap-1.5 rounded-lg border border-red-200 px-3 py-2 text-[13px] font-medium text-red-500 transition-colors hover:bg-red-50 dark:border-red-800/50 dark:text-red-400 dark:hover:bg-red-900/20"
                                >
                                    <Trash2 className="size-3.5" />
                                    Hapus
                                </button>
                            </>
                        )}

                        {/* Konfirmasi */}
                        {canConfirm && (
                            <button
                                onClick={onConfirm}
                                className="flex items-center justify-center gap-1.5 rounded-lg border border-emerald-200 px-3 py-2 text-[13px] font-medium text-emerald-600 transition-colors hover:bg-emerald-50 dark:border-emerald-800/50 dark:text-emerald-400 dark:hover:bg-emerald-900/20"
                            >
                                <CheckCircle className="size-3.5" />
                                Konfirmasi
                            </button>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
