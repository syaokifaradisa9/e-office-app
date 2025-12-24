import { Edit, Trash2, RefreshCw, LogOut } from 'lucide-react';
import { Link, usePage } from '@inertiajs/react';

interface Item {
    id: number;
    name: string;
    stock: number;
    unit_of_measure: string;
    multiplier: number;
    reference_item_id: number | null;
    category?: { id: number; name: string } | null;
    reference_item?: { id: number; name: string; unit_of_measure: string } | null;
}

interface PageProps {
    permissions?: string[];
    [key: string]: unknown;
}

interface Props {
    item: Item;
    onDelete: (item: Item) => void;
}

export default function ItemCardItem({ item, onDelete }: Props) {
    const { permissions } = usePage<PageProps>().props;
    const hasManagePermission = permissions?.includes('kelola_barang');
    const hasIssuePermission = permissions?.includes('keluarkan_stok');
    const hasConvertPermission = permissions?.includes('konversi_barang');

    const hasConvertAction = hasConvertPermission && item.multiplier > 1 && item.reference_item_id;
    const hasIssueAction = hasIssuePermission && item.stock > 0;

    const hasAnyAction = hasManagePermission || hasConvertAction || hasIssueAction;

    return (
        <div className="transition-colors hover:bg-gray-50 dark:hover:bg-slate-700/30">
            <div className="flex flex-col gap-1 px-4 py-4">
                {/* Header: Name */}
                <div className="truncate text-base font-semibold text-gray-900 dark:text-white">{item.name}</div>

                {/* Subtitle: Category */}
                <div className="truncate text-sm text-gray-500 dark:text-slate-400">{item.category?.name || '-'}</div>

                {/* Details: Quantity & Conversion */}
                <div className="mb-2 flex flex-wrap items-center gap-2 text-sm text-gray-600 dark:text-slate-300">
                    <span className={`font-medium ${item.stock <= 0 ? 'text-red-500' : ''}`}>
                        {item.stock} {item.unit_of_measure}
                    </span>
                    {item.multiplier > 1 && item.reference_item && (
                        <>
                            <span className="text-gray-300 dark:text-slate-600">•</span>
                            <span className="text-gray-400 dark:text-gray-500">
                                (1 {item.unit_of_measure} = {item.multiplier} {item.reference_item.unit_of_measure} {item.reference_item.name})
                            </span>
                        </>
                    )}
                </div>

                {/* Footer: Actions Only (Right side) */}
                {hasAnyAction && (
                    <div className="mt-2 flex flex-wrap items-center justify-end gap-2 pt-2">
                        {/* Convert Action */}
                        {hasConvertAction && (
                            <Link
                                href={`/inventory/items/${item.id}/convert`}
                                className="flex items-center gap-1 rounded-lg bg-green-50 px-3 py-1.5 text-xs font-medium text-green-600 transition-colors hover:bg-green-100 dark:bg-green-900/20 dark:text-green-400 dark:hover:bg-green-900/30"
                            >
                                <RefreshCw className="size-3.5" />
                                Konversi
                            </Link>
                        )}

                        {/* Issue Action */}
                        {hasIssueAction && (
                            <Link
                                href={`/inventory/items/${item.id}/issue`}
                                className="flex items-center gap-1 rounded-lg bg-orange-50 px-3 py-1.5 text-xs font-medium text-orange-600 transition-colors hover:bg-orange-100 dark:bg-orange-900/20 dark:text-orange-400 dark:hover:bg-orange-900/30"
                            >
                                <LogOut className="size-3.5" />
                                Pengeluaran
                            </Link>
                        )}

                        {/* Edit Action */}
                        {hasManagePermission && (
                            <Link
                                href={`/inventory/items/${item.id}/edit`}
                                className="flex items-center gap-1 rounded-lg bg-yellow-50 px-3 py-1.5 text-xs font-medium text-yellow-600 transition-colors hover:bg-yellow-100 dark:bg-yellow-900/20 dark:text-yellow-400 dark:hover:bg-yellow-900/30"
                            >
                                <Edit className="size-3.5" />
                                Edit
                            </Link>
                        )}

                        {/* Delete Action */}
                        {hasManagePermission && (
                            <button
                                onClick={() => onDelete(item)}
                                className="flex items-center gap-1 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30"
                            >
                                <Trash2 className="size-3.5" />
                                Hapus
                            </button>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
