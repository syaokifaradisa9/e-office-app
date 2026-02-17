import { Edit, Trash2, Folder, Layers } from 'lucide-react';
import { Link, usePage } from '@inertiajs/react';
import { ArchievePermission } from '@/enums/ArchievePermission';

interface Category {
    id: number;
    name: string;
    context_id: number;
    context?: {
        name: string;
    };
    description: string | null;
    created_at?: string;
}

interface PageProps {
    permissions?: string[];
    [key: string]: unknown;
}

interface Props {
    item: Category;
    onDelete: (item: Category) => void;
}

export default function CategoryCardItem({ item, onDelete }: Props) {
    const { permissions } = usePage<PageProps>().props;
    const hasManagePermission = permissions?.includes(ArchievePermission.MANAGE_CATEGORY);

    return (
        <div className="group transition-colors duration-150 hover:bg-slate-50/80 dark:hover:bg-slate-700/20">
            <div className="flex items-start gap-3.5 px-4 py-4">
                {/* Icon */}
                <div className="mt-0.5 flex size-10 flex-shrink-0 items-center justify-center rounded-xl bg-primary/10 dark:bg-primary/15">
                    <Folder className="size-5 text-primary" />
                </div>

                {/* Content */}
                <div className="min-w-0 flex-1">
                    {/* Name */}
                    <div className="flex items-center justify-between gap-2">
                        <h3 className="truncate text-[15px] font-semibold text-slate-800 dark:text-white">{item.name}</h3>
                    </div>

                    {/* Context Info */}
                    <div className="mt-1 flex items-center gap-1.5 text-[12px] text-slate-500 dark:text-slate-400">
                        <Layers className="size-3.5" />
                        <span className="truncate">{item.context?.name || 'Tanpa Konteks'}</span>
                    </div>

                    {/* Description */}
                    {item.description && (
                        <p className="mt-2 line-clamp-2 text-[13px] leading-relaxed text-slate-500 dark:text-slate-400">
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
                                href={`/archieve/categories/${item.id}/edit`}
                                className="flex items-center justify-center gap-1.5 rounded-lg border border-amber-200 px-4 py-2 text-sm font-medium text-amber-600 transition-colors hover:bg-amber-50 active:bg-amber-100 dark:border-amber-800/50 dark:text-amber-400 dark:hover:bg-amber-900/20"
                            >
                                <Edit className="size-4" />
                                Edit
                            </Link>
                            <button
                                onClick={() => onDelete(item)}
                                className="flex items-center justify-center gap-1.5 rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-500 transition-colors hover:bg-red-50 active:bg-red-100 dark:border-red-800/50 dark:text-red-400 dark:hover:bg-red-900/20"
                            >
                                <Trash2 className="size-5" />
                                Hapus
                            </button>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
