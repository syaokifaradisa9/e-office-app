import { Edit, Trash2 } from 'lucide-react';
import { Link, usePage } from '@inertiajs/react';
import { InventoryPermission } from '../../types/permissions';

interface CategoryItem {
    id: number;
    name: string;
    description?: string | null;
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
        <div className="transition-colors hover:bg-gray-50 dark:hover:bg-slate-700/30">
            <div className="flex flex-col gap-1 px-4 py-4">
                {/* Header: Name */}
                <div className="truncate text-base font-semibold text-gray-900 dark:text-white">{item.name}</div>

                {/* Subtitle: Description */}
                {item.description && (
                    <div className="mb-2 flex items-center gap-2 text-sm text-gray-500 dark:text-slate-400">
                        <span className="truncate">{item.description}</span>
                    </div>
                )}
                {!item.description && <div className="mb-2"></div>}

                {/* Footer: Actions Only (Right side) */}
                {hasManagePermission && (
                    <div className="mt-2 flex items-center justify-end gap-2 pt-2">
                        <Link
                            href={`/inventory/category-items/${item.id}/edit`}
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
                    </div>
                )}
            </div>
        </div>
    );
}
