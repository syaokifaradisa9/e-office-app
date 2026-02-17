import { usePage, Link } from '@inertiajs/react';
import { Edit, Trash2, Eye, Check, PackageCheck, ClipboardCheck, ClipboardList, Calendar, X } from 'lucide-react';
import { InventoryPermission } from '../../types/permissions';

interface WarehouseOrderItem {
    id: number;
    order_number: string;
    status: string;
    user_id: number;
    division_id: number;
    user?: { id: number; name: string } | null;
    division?: { id: number; name: string } | null;
    created_at: string;
}

interface PageProps {
    permissions?: string[];
    loggeduser?: { id: number; division_id: number | null };
    [key: string]: unknown;
}

interface Props {
    item: WarehouseOrderItem;
    onConfirm: (item: WarehouseOrderItem) => void;
    onDelete: (item: WarehouseOrderItem) => void;
    onReject: (item: WarehouseOrderItem) => void;
}

export default function WarehouseOrderCardItem({ item, onConfirm, onDelete, onReject }: Props) {
    const { permissions, loggeduser: currentUser } = usePage<PageProps>().props;

    const hasCreatePermission = permissions?.includes(InventoryPermission.CreateWarehouseOrder);
    const hasConfirmPermission = permissions?.includes(InventoryPermission.ConfirmWarehouseOrder);
    const hasHandoverPermission = permissions?.includes(InventoryPermission.HandoverItem);
    const hasReceivePermission = permissions?.includes(InventoryPermission.ReceiveItem);

    // Status config
    const statusConfig: Record<string, { label: string; className: string }> = {
        Pending: { label: 'Menunggu', className: 'bg-amber-50 text-amber-600 dark:bg-amber-900/25 dark:text-amber-400' },
        Confirmed: { label: 'Dikonfirmasi', className: 'bg-blue-50 text-blue-600 dark:bg-blue-900/25 dark:text-blue-400' },
        Delivered: { label: 'Diserahkan', className: 'bg-purple-50 text-purple-600 dark:bg-purple-900/25 dark:text-purple-400' },
        Finished: { label: 'Selesai', className: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/25 dark:text-emerald-400' },
        Rejected: { label: 'Ditolak', className: 'bg-red-50 text-red-600 dark:bg-red-900/25 dark:text-red-400' },
        Revision: { label: 'Revisi', className: 'bg-orange-50 text-orange-600 dark:bg-orange-900/25 dark:text-orange-400' },
    };

    const showConfirm = hasConfirmPermission && (item.status === 'Pending' || item.status === 'Revision');
    const showHandover = hasHandoverPermission && item.status === 'Confirmed';
    const showReceive =
        hasReceivePermission &&
        item.status === 'Delivered' &&
        (currentUser?.id === item.user_id || currentUser?.division_id === item.division_id);
    const showEditDelete =
        (item.status === 'Pending' || item.status === 'Revision' || item.status === 'Rejected') &&
        currentUser?.id === item.user_id &&
        hasCreatePermission;

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });
    };

    const status = statusConfig[item.status] || { label: item.status, className: 'bg-slate-100 text-slate-600' };

    const buttonCount = (() => {
        let count = 1; // Detail always
        if (showConfirm) count += 2;
        if (showHandover) count += 1;
        if (showReceive) count += 1;
        if (showEditDelete) count += 2;
        return count;
    })();

    return (
        <div className="group transition-colors duration-150 hover:bg-slate-50/80 dark:hover:bg-slate-700/20">
            <div className="flex items-start gap-3.5 px-4 py-4">
                {/* Icon */}
                <div className="mt-0.5 flex size-10 flex-shrink-0 items-center justify-center rounded-xl bg-primary/10 dark:bg-primary/15">
                    <ClipboardList className="size-5 text-primary" />
                </div>

                {/* Content */}
                <div className="min-w-0 flex-1">
                    {/* Row 1: Order Number & Status */}
                    <div className="flex items-center justify-between gap-2">
                        <h3 className="truncate text-[15px] font-semibold text-slate-800 dark:text-white">
                            {item.order_number || '-'}
                        </h3>
                        <div className={`flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold ${status.className}`}>
                            <div className="size-1.5 rounded-full bg-current opacity-50" />
                            <span>{status.label}</span>
                        </div>
                    </div>

                    {/* Row 2: User & Division */}
                    <p className="mt-1 text-[13px] text-slate-500 dark:text-slate-400">
                        <span className="font-medium text-slate-700 dark:text-slate-300">{item.user?.name ?? '-'}</span> &nbsp;Divisi <span className="font-medium text-slate-700 dark:text-slate-300">{item.division?.name ?? '-'}</span>
                    </p>

                    {/* Date */}
                    <div className="mt-2 flex items-center gap-1 text-[12px] text-slate-400 dark:text-slate-500">
                        <Calendar className="size-3" />
                        <span>{formatDate(item.created_at)}</span>
                    </div>

                    {/* Actions */}
                    <div className={`mt-4 grid gap-2 ${buttonCount === 1 ? 'grid-cols-1' :
                        buttonCount === 2 ? 'grid-cols-2' :
                            buttonCount === 3 ? 'grid-cols-3' : 'grid-cols-2'
                        }`}>
                        {/* Detail */}
                        <Link
                            href={`/inventory/warehouse-orders/${item.id}`}
                            className="flex items-center justify-center gap-1.5 rounded-lg border border-blue-200 px-3 py-2 text-[13px] font-medium text-blue-600 transition-colors hover:bg-blue-50 dark:border-blue-800/50 dark:text-blue-400 dark:hover:bg-blue-900/20"
                        >
                            <Eye className="size-3.5" />
                            Detail
                        </Link>

                        {/* Konfirmasi & Tolak */}
                        {showConfirm && (
                            <>
                                <button
                                    onClick={() => onConfirm(item)}
                                    className="flex items-center justify-center gap-1.5 rounded-lg border border-emerald-200 px-3 py-2 text-[13px] font-medium text-emerald-600 transition-colors hover:bg-emerald-50 dark:border-emerald-800/50 dark:text-emerald-400 dark:hover:bg-emerald-900/20"
                                >
                                    <Check className="size-3.5" />
                                    Terima
                                </button>
                                <button
                                    onClick={() => onReject(item)}
                                    className="flex items-center justify-center gap-1.5 rounded-lg border border-red-200 px-3 py-2 text-[13px] font-medium text-red-500 transition-colors hover:bg-red-50 dark:border-red-800/50 dark:text-red-400 dark:hover:bg-red-900/20"
                                >
                                    <X className="size-3.5" />
                                    Tolak
                                </button>
                            </>
                        )}

                        {/* Serahkan */}
                        {showHandover && (
                            <Link
                                href={`/inventory/warehouse-orders/${item.id}/delivery`}
                                className="flex items-center justify-center gap-1.5 rounded-lg border border-purple-200 px-3 py-2 text-[13px] font-medium text-purple-600 transition-colors hover:bg-purple-50 dark:border-purple-800/50 dark:text-purple-400 dark:hover:bg-purple-900/20"
                            >
                                <PackageCheck className="size-3.5" />
                                Serahkan
                            </Link>
                        )}

                        {/* Terima */}
                        {showReceive && (
                            <Link
                                href={`/inventory/warehouse-orders/${item.id}/receive`}
                                className="flex items-center justify-center gap-1.5 rounded-lg border border-emerald-200 px-3 py-2 text-[13px] font-medium text-emerald-600 transition-colors hover:bg-emerald-50 dark:border-emerald-800/50 dark:text-emerald-400 dark:hover:bg-emerald-900/20"
                            >
                                <ClipboardCheck className="size-3.5" />
                                Selesai
                            </Link>
                        )}

                        {/* Edit & Hapus */}
                        {showEditDelete && (
                            <>
                                <Link
                                    href={`/inventory/warehouse-orders/${item.id}/edit`}
                                    className="flex items-center justify-center gap-1.5 rounded-lg border border-amber-200 px-3 py-2 text-[13px] font-medium text-amber-600 transition-colors hover:bg-amber-50 dark:border-amber-800/50 dark:text-amber-400 dark:hover:bg-amber-900/20"
                                >
                                    <Edit className="size-3.5" />
                                    Edit
                                </Link>
                                <button
                                    onClick={() => onDelete(item)}
                                    className="flex items-center justify-center gap-1.5 rounded-lg border border-red-200 px-3 py-2 text-[13px] font-medium text-red-500 transition-colors hover:bg-red-50 dark:border-red-800/50 dark:text-red-400 dark:hover:bg-red-900/20"
                                >
                                    <Trash2 className="size-3.5" />
                                    Hapus
                                </button>
                            </>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
