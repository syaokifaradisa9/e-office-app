import { Edit, Trash2, FileText, Plus, ChevronRight } from 'lucide-react';
import { Link, usePage } from '@inertiajs/react';
import { ArchievePermission } from '@/enums/ArchievePermission';

interface Classification {
    id: number;
    parent_id: number | null;
    code: string;
    name: string;
    description: string | null;
    parent?: {
        name: string;
    };
    created_at?: string;
}

interface PageProps {
    permissions?: string[];
    [key: string]: unknown;
}

interface Props {
    item: Classification;
    onDelete: (item: Classification) => void;
}

export default function ClassificationCardItem({ item, onDelete }: Props) {
    const { permissions } = usePage<PageProps>().props;
    const hasManagePermission = permissions?.includes(ArchievePermission.MANAGE_CLASSIFICATION);

    return (
        <div className="group transition-colors duration-150 hover:bg-slate-50/80 dark:hover:bg-slate-700/20">
            <div className="flex items-start gap-3.5 px-4 py-4">
                {/* Icon */}
                <div className="mt-0.5 flex size-10 flex-shrink-0 items-center justify-center rounded-xl bg-primary/10 dark:bg-primary/15">
                    <FileText className="size-5 text-primary" />
                </div>

                {/* Content */}
                <div className="min-w-0 flex-1">
                    {/* Code & Name */}
                    <div className="flex flex-col">
                        <span className="font-mono text-xs font-bold text-primary">{item.code}</span>
                        <h3 className="truncate text-[15px] font-semibold text-slate-800 dark:text-white">{item.name}</h3>
                    </div>

                    {/* Parent Info */}
                    {item.parent && (
                        <div className="mt-1 flex items-center gap-1.5 text-[12px] text-slate-500 dark:text-slate-400">
                            <span className="truncate">{item.parent.name}</span>
                            <ChevronRight className="size-3" />
                            <span className="truncate font-medium text-slate-600 dark:text-slate-300">{item.name}</span>
                        </div>
                    )}

                    {/* Description */}
                    {item.description && (
                        <p className="mt-2 line-clamp-2 text-[13px] leading-relaxed text-slate-500 dark:text-slate-400">
                            {item.description}
                        </p>
                    )}

                    {/* Actions */}
                    {hasManagePermission && (
                        <div className={`mt-3 grid gap-2 ${(() => {
                            const count = [hasManagePermission, hasManagePermission, hasManagePermission].filter(Boolean).length;
                            return `grid-cols-${count || 1}`;
                        })()
                            }`}>
                            <Link
                                href={`/archieve/classifications/create?parent_id=${item.id}`}
                                className="flex items-center justify-center gap-1.5 rounded-lg border border-primary/20 px-4 py-2 text-sm font-medium text-primary transition-colors hover:bg-primary/5 active:bg-primary/10 dark:border-primary/30 dark:text-primary dark:hover:bg-primary/10"
                            >
                                <Plus className="size-4" />
                                Sub
                            </Link>
                            <Link
                                href={`/archieve/classifications/${item.id}/edit`}
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
