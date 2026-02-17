import { Edit, Trash2, Package, ArrowRightLeft, ExternalLink } from 'lucide-react';
import { Link, usePage } from '@inertiajs/react';
import { InventoryPermission } from '../../types/permissions';
import Button from '@/components/buttons/Button';

interface Item {
    id: number;
    name: string;
    category: string | null;
    unit_of_measure: string;
    stock: number;
    multiplier: number | null;
    reference_item: string | null;
    description?: string | null;
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
    const canManage = permissions?.includes(InventoryPermission.ManageItem);
    const canIssue = permissions?.includes(InventoryPermission.IssueItemGudang);
    const canConvert = permissions?.includes(InventoryPermission.ConvertItemGudang);

    return (
        <div className="group transition-colors duration-150 hover:bg-slate-50/80 dark:hover:bg-slate-700/20">
            <div className="flex items-start gap-3.5 px-4 py-4">
                {/* Icon */}
                <div className="mt-0.5 flex size-10 flex-shrink-0 items-center justify-center rounded-xl bg-primary/10 dark:bg-primary/15">
                    <Package className="size-5 text-primary" />
                </div>

                {/* Content */}
                <div className="min-w-0 flex-1">
                    {/* Header: Name & Category */}
                    <div className="min-w-0">
                        <h3 className="truncate text-[15px] font-semibold text-slate-800 dark:text-white">{item.name}</h3>
                        <p className="mt-0.5 text-[13px] text-slate-500 dark:text-slate-400">
                            {item.category || 'Tanpa Kategori'}
                        </p>
                    </div>

                    {/* Description */}
                    {item.description && (
                        <p className="mt-2 line-clamp-2 text-[13px] leading-relaxed text-slate-500 dark:text-slate-400">
                            {item.description}
                        </p>
                    )}

                    {/* Stock Indicator */}
                    <div className="mt-3 flex items-center">
                        <div className={`flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[12px] font-semibold ${item.stock <= 10 ? 'bg-red-50 text-red-600 dark:bg-red-900/25 dark:text-red-400' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/25 dark:text-emerald-400'}`}>
                            <div className="size-1.5 rounded-full bg-current opacity-50" />
                            <span>Stok: {item.stock} {item.unit_of_measure}</span>
                        </div>
                    </div>

                    {/* Actions */}
                    {(canManage || canIssue || canConvert) && (
                        <div className={`mt-4 grid gap-2 ${(() => {
                            const count = [
                                canConvert && item.multiplier && item.multiplier > 1 && item.stock > 0,
                                canIssue,
                                canManage, // Edit
                                canManage  // Hapus
                            ].filter(Boolean).length;
                            return `grid-cols-${count || 1}`;
                        })()
                            }`}>
                            {canConvert && item.multiplier && item.multiplier > 1 && item.stock > 0 && (
                                <Link
                                    href={`/inventory/items/${item.id}/convert`}
                                    className="flex items-center justify-center gap-1.5 rounded-lg border border-blue-200 px-3 py-2 text-[13px] font-medium text-blue-600 transition-colors hover:bg-blue-50 dark:border-blue-800/50 dark:text-blue-400 dark:hover:bg-blue-900/20"
                                >
                                    <ArrowRightLeft className="size-3.5" />
                                    Konversi
                                </Link>
                            )}
                            {canIssue && (
                                <Link
                                    href={`/inventory/items/${item.id}/issue`}
                                    className="flex items-center justify-center gap-1.5 rounded-lg border border-orange-200 px-3 py-2 text-[13px] font-medium text-orange-600 transition-colors hover:bg-orange-50 dark:border-orange-800/50 dark:text-orange-400 dark:hover:bg-orange-900/20"
                                >
                                    <ExternalLink className="size-3.5" />
                                    Keluar
                                </Link>
                            )}
                            {canManage && (
                                <Link
                                    href={`/inventory/items/${item.id}/edit`}
                                    className="flex items-center justify-center gap-1.5 rounded-lg border border-amber-200 px-3 py-2 text-[13px] font-medium text-amber-600 transition-colors hover:bg-amber-50 dark:border-amber-800/50 dark:text-amber-400 dark:hover:bg-amber-900/20"
                                >
                                    <Edit className="size-3.5" />
                                    Edit
                                </Link>
                            )}
                            {canManage && (
                                <button
                                    onClick={() => onDelete(item)}
                                    className="flex items-center justify-center gap-1.5 rounded-lg border border-red-200 px-3 py-2 text-[13px] font-medium text-red-500 transition-colors hover:bg-red-50 dark:border-red-800/50 dark:text-red-400 dark:hover:bg-red-900/20"
                                >
                                    <Trash2 className="size-3.5" />
                                    Hapus
                                </button>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
