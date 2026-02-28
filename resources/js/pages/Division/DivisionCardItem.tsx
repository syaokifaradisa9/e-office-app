import { Building2, Edit, Trash2, Users } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import Button from '@/components/buttons/Button';

interface Division {
    id: number;
    name: string;
    description: string | null;
    is_active: boolean;
    users_count: number;
}

interface PageProps {
    permissions?: string[];
    [key: string]: unknown;
}

interface DivisionCardItemProps {
    item: Division;
    onDelete: (item: Division) => void;
}

export default function DivisionCardItem({ item, onDelete }: DivisionCardItemProps) {
    const { permissions } = usePage<PageProps>().props;
    const hasManagePermission = permissions?.includes('kelola_divisi');

    return (
        <div className="group transition-colors duration-150 hover:bg-slate-50/80 dark:hover:bg-slate-700/20">
            <div className="flex items-start gap-3.5 px-4 py-4">
                {/* Icon */}
                <div className="mt-0.5 flex size-10 flex-shrink-0 items-center justify-center rounded-xl bg-primary/10 dark:bg-primary/15">
                    <Building2 className="size-5 text-primary" />
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
                        <p className="mt-1 line-clamp-1 text-[13px] leading-relaxed text-slate-500 dark:text-slate-400">{item.description}</p>
                    )}

                    {/* Stats */}
                    <div className="mt-2.5 flex items-center gap-1.5 text-[12px] text-slate-400 dark:text-slate-500">
                        <Users className="size-3.5" />
                        <span>{item.users_count || 0} Pegawai</span>
                    </div>

                    {/* Actions */}
                    {hasManagePermission && (
                        <div className="mt-3 grid grid-cols-2 gap-2">
                            <Button
                                href={`/division/${item.id}/edit`}
                                variant="outline"
                                className="!py-2 !bg-transparent !text-amber-600 !border-amber-200 hover:!bg-amber-50 dark:!text-amber-400 dark:!border-amber-800/50 dark:hover:!bg-amber-900/20"
                                icon={<Edit className="size-4" />}
                                label="Edit"
                            />
                            <Button
                                variant="outline"
                                className="!py-2 !bg-transparent !text-red-500 !border-red-200 hover:!bg-red-50 dark:!text-red-400 dark:!border-red-800/50 dark:hover:!bg-red-900/20"
                                icon={<Trash2 className="size-4" />}
                                label="Hapus"
                                onClick={() => onDelete(item)}
                            />
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
