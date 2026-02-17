import { Edit, Trash2, Tag } from 'lucide-react';
import { Link, usePage } from '@inertiajs/react';
import { InventoryPermission } from '../../types/permissions';

interface CategoryItem {
    id: number;
    name: string;
    description?: string | null;
    is_active: boolean;
}

interface PageProps {
    permissions?: string[];
    [key: string]: unknown;
}

interface Props {
    item: CategoryItem;
    onDelete: (item: CategoryItem) => void;
}

export default function CategoryItemCardItem({ item, onDelete }: Props) {
    const { permissions } = usePage<PageProps>().props;
    const hasManagePermission = permissions?.includes(InventoryPermission.ManageCategory);

    return (
        <div className="group transition-colors duration-150 hover:bg-slate-50/80 dark:hover:bg-slate-700/20">
            <div className="flex items-start gap-3.5 px-4 py-4">
                {/* Icon */}
                <div className="mt-0.5 flex size-10 flex-shrink-0 items-center justify-center rounded-xl bg-primary/10 dark:bg-primary/15">
                    <Tag className="size-5 text-primary" />
                </div>

                {/* Content */}
                <div className="min-w-0 flex-1">
                    {/* Name & Status */}
                    <div className="flex items-center gap-2">
                        <h3 className="truncate text-[15px] font-semibold text-slate-800 dark:text-white">{item.name}</h3>
                        <span className={`flex-shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide ${item.is_active ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/25 dark:text-emerald-400' : 'bg-red-50 text-red-500 dark:bg-red-900/25 dark:text-red-400'}`}>
                            {item.is_active ? 'Aktif' : 'Nonaktif'}
                        </span>
                    </div>

                    {/* Description */}
                    {item.description && (
                        <p className="mt-1 line-clamp-2 text-[13px] leading-relaxed text-slate-500 dark:text-slate-400">
                            {item.description}
                        </p>
                    )}

                    {/* Actions */}
                    {hasManagePermission && (
                        <div className={`mt-3 grid gap-2 ${(() => {
                                const count = [hasManagePermission, hasManagePermission].filter(Boolean).length;
                                return `grid-cols-${count || 1}`;
                            })()
                            }`}>
                            <Link
                                href={`/inventory/categories/${item.id}/edit`}
                                className="flex items-center justify-center gap-1.5 rounded-lg border border-amber-200 px-4 py-2 text-sm font-medium text-amber-600 transition-colors hover:bg-amber-50 active:bg-amber-100 dark:border-amber-800/50 dark:text-amber-400 dark:hover:bg-amber-900/20"
                            >
                                <Edit className="size-4" />
                                Edit
                            </Link>
                            <button
                                onClick={() => onDelete(item)}
                                className="flex items-center justify-center gap-1.5 rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-500 transition-colors hover:bg-red-50 active:bg-red-100 dark:border-red-800/50 dark:text-red-400 dark:hover:bg-red-900/20"
                            >
                                <Trash2 className="size-4" />
                                Hapus
                            </button>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
