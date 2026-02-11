import { usePage, Link } from '@inertiajs/react';
import { Edit, Trash2, Eye, Check, PackageCheck, ClipboardCheck } from 'lucide-react';
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

    // Status badge colors
    const statusColors: Record<string, string> = {
        Pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        Confirmed: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        Delivered: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
        Finished: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        Rejected: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        Revision: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    };

    // Status translations (English in DB, Indonesian for display)
    const statusLabels: Record<string, string> = {
        Pending: 'Menunggu',
        Confirmed: 'Dikonfirmasi',
        Delivered: 'Diserahkan',
        Finished: 'Selesai',
        Rejected: 'Ditolak',
        Revision: 'Revisi',
    };

    // Check if any actions are available
    const showConfirm = hasConfirmPermission && (item.status === 'Pending' || item.status === 'Revision');
    const showHandover = hasHandoverPermission && item.status === 'Confirmed';
    const showReceive =
        hasReceivePermission &&
        item.status === 'Delivered' &&
        (currentUser?.id === item.user_id || currentUser?.division_id === item.division_id);
    const showEditDelete =
        (item.status === 'Pending' || item.status === 'Revision' || item.status === 'Rejected') && currentUser?.id === item.user_id && hasCreatePermission;
    const hasAnyAction = showConfirm || showHandover || showReceive || showEditDelete;

    return (
        <div className="transition-colors hover:bg-gray-50 dark:hover:bg-slate-700/30">
            <div className="flex flex-col gap-1 px-4 py-4">
                {/* Header: Order Number + Status */}
                <div className="flex items-center justify-between gap-2">
                    <span className="truncate text-base font-semibold text-gray-900 dark:text-white">{item.order_number || '-'}</span>
                    <span
                        className={`flex-shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold ${statusColors[item.status] || 'bg-gray-100 text-gray-600'}`}
                    >
                        {statusLabels[item.status] || item.status}
                    </span>
                </div>

                {/* Subtitle: User & Division */}
                <div className="mb-2 flex items-center gap-2 text-sm text-gray-500 dark:text-slate-400">
                    <span className="truncate">
                        {item.user?.name ?? '-'} • {item.division?.name ?? '-'}
                    </span>
                </div>

                {/* Footer: Actions */}
                {hasAnyAction && (
                    <div className="mt-2 flex flex-wrap items-center justify-end gap-2 pt-2">
                        {/* Detail */}
                        <Link
                            href={`/inventory/warehouse-orders/${item.id}`}
                            className="flex items-center gap-1 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-600 transition-colors hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:hover:bg-blue-900/30"
                        >
                            <Eye className="size-3.5" />
                            Detail
                        </Link>

                        {/* Konfirmasi */}
                        {showConfirm && (
                            <>
                                <button
                                    onClick={() => onConfirm(item)}
                                    className="flex items-center gap-1 rounded-lg bg-green-50 px-3 py-1.5 text-xs font-medium text-green-600 transition-colors hover:bg-green-100 dark:bg-green-900/20 dark:text-green-400 dark:hover:bg-green-900/30"
                                >
                                    <Check className="size-3.5" />
                                    Konfirmasi
                                </button>
                                <button
                                    onClick={() => onReject(item)}
                                    className="flex items-center gap-1 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30"
                                >
                                    <Trash2 className="size-3.5" />
                                    Tolak
                                </button>
                            </>
                        )}

                        {/* Serahkan Barang */}
                        {showHandover && (
                            <Link
                                href={`/inventory/warehouse-orders/${item.id}/delivery`}
                                className="flex items-center gap-1 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-600 transition-colors hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:hover:bg-blue-900/30"
                            >
                                <PackageCheck className="size-3.5" />
                                Serahkan
                            </Link>
                        )}

                        {/* Terima Barang */}
                        {showReceive && (
                            <Link
                                href={`/inventory/warehouse-orders/${item.id}/receive`}
                                className="flex items-center gap-1 rounded-lg bg-green-50 px-3 py-1.5 text-xs font-medium text-green-600 transition-colors hover:bg-green-100 dark:bg-green-900/20 dark:text-green-400 dark:hover:bg-green-900/30"
                            >
                                <ClipboardCheck className="size-3.5" />
                                Terima
                            </Link>
                        )}

                        {/* Edit & Hapus */}
                        {showEditDelete && (
                            <>
                                <Link
                                    href={`/inventory/warehouse-orders/${item.id}/edit`}
                                    className="flex items-center gap-1 rounded-lg bg-yellow-50 px-3 py-1.5 text-xs font-medium text-yellow-600 transition-colors hover:bg-yellow-100 dark:bg-yellow-900/20 dark:text-yellow-400 dark:hover:bg-yellow-900/30"
                                >
                                    <Edit className="size-3.5" />
                                    Edit
                                </Link>
                                <button
                                    onClick={() => onDelete(item)}
                                    className="flex items-center gap-1 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30"
                                >
                                    <Trash2 className="size-3.5" />
                                    Hapus
                                </button>
                            </>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
