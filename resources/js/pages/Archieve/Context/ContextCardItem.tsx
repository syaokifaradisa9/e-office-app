import { Edit, Trash2, Layers } from 'lucide-react';
import { Link, usePage } from '@inertiajs/react';
import { ArchievePermission } from '@/enums/ArchievePermission';
import CardItemButton from '@/components/buttons/CardItemButton';

interface Context {
    id: number;
    name: string;
    description: string | null;
    created_at?: string;
}

interface PageProps {
    permissions?: string[];
    [key: string]: unknown;
}

interface Props {
    item: Context;
    onDelete: (item: Context) => void;
}

export default function ContextCardItem({ item, onDelete }: Props) {
    const { permissions } = usePage<PageProps>().props;
    const hasManagePermission = permissions?.includes(ArchievePermission.MANAGE_CATEGORY);

    return (
        <div className="group transition-colors duration-150 hover:bg-slate-50/80 dark:hover:bg-slate-700/20">
            <div className="flex items-start gap-3.5 px-4 py-4">
                {/* Icon */}
                <div className="mt-0.5 flex size-10 flex-shrink-0 items-center justify-center rounded-xl bg-primary/10 dark:bg-primary/15">
                    <Layers className="size-5 text-primary" />
                </div>

                {/* Content */}
                <div className="min-w-0 flex-1">
                    {/* Name */}
                    <div className="flex items-center gap-2">
                        <h3 className="truncate text-[15px] font-semibold text-slate-800 dark:text-white">{item.name}</h3>
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
                            <CardItemButton
                                href={`/archieve/contexts/${item.id}/edit`}
                                label="Edit"
                                icon={<Edit />}
                                variant="warning"
                            />
                            <CardItemButton
                                onClick={() => onDelete(item)}
                                label="Hapus"
                                icon={<Trash2 />}
                                variant="danger"
                            />
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
