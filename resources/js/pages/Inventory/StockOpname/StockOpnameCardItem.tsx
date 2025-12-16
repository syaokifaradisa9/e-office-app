import { Link } from '@inertiajs/react';
import { Eye, Edit, Trash2, CheckCircle } from 'lucide-react';

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
    Pending: 'Menunggu',
    Confirmed: 'Dikonfirmasi',
};

export default function StockOpnameCardItem({ item, canEdit, canConfirm, onDelete, onConfirm }: Props) {
    // Status badge colors
    const statusColors: Record<string, string> = {
        Pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        Confirmed: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    };

    return (
        <div className="transition-colors hover:bg-gray-50 dark:hover:bg-slate-700/30">
            <div className="flex flex-col gap-1 px-4 py-4">
                {/* Header: Date + Status */}
                <div className="flex items-center justify-between gap-2">
                    <span className="truncate text-base font-semibold text-gray-900 dark:text-white">
                        {new Date(item.opname_date).toLocaleDateString('id-ID', {
                            day: 'numeric',
                            month: 'long',
                            year: 'numeric',
                        })}
                    </span>
                    <span
                        className={`flex-shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold ${statusColors[item.status] || 'bg-gray-100 text-gray-600'}`}
                    >
                        {statusLabels[item.status] || item.status}
                    </span>
                </div>

                {/* Subtitle: Location & User */}
                <div className="mb-2 flex items-center gap-2 text-sm text-gray-500 dark:text-slate-400">
                    <span className="truncate">
                        {item.division ? item.division.name : 'Gudang Utama'} • {item.user?.name ?? '-'}
                    </span>
                </div>

                {/* Footer: Actions */}
                <div className="mt-2 flex flex-wrap items-center justify-end gap-2 pt-2">
                    {/* Lihat - Always visible */}
                    <Link
                        href={`/inventory/stock-opname/${item.id}`}
                        className="flex items-center gap-1 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-600 transition-colors hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:hover:bg-blue-900/30"
                    >
                        <Eye className="size-3.5" />
                        Lihat
                    </Link>

                    {/* Edit */}
                    {canEdit && (
                        <>
                            <Link
                                href={`/inventory/stock-opname/${item.id}/edit`}
                                className="flex items-center gap-1 rounded-lg bg-yellow-50 px-3 py-1.5 text-xs font-medium text-yellow-600 transition-colors hover:bg-yellow-100 dark:bg-yellow-900/20 dark:text-yellow-400 dark:hover:bg-yellow-900/30"
                            >
                                <Edit className="size-3.5" />
                                Edit
                            </Link>
                            <button
                                onClick={onDelete}
                                className="flex items-center gap-1 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30"
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
                            className="flex items-center gap-1 rounded-lg bg-green-50 px-3 py-1.5 text-xs font-medium text-green-600 transition-colors hover:bg-green-100 dark:bg-green-900/20 dark:text-green-400 dark:hover:bg-green-900/30"
                        >
                            <CheckCircle className="size-3.5" />
                            Konfirmasi
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
}
