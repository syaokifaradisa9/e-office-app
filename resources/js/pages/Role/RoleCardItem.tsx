import { Edit, Trash2, Shield } from 'lucide-react';
import { Link, usePage } from '@inertiajs/react';

interface Permission {
    id: number;
    name: string;
}

interface Role {
    id: number;
    name: string;
    permissions_count: number;
    permissions: Permission[];
}

interface PageProps {
    permissions?: string[];
    [key: string]: unknown;
}

interface RoleCardItemProps {
    item: Role;
    onDelete: (item: Role) => void;
}

export default function RoleCardItem({ item, onDelete }: RoleCardItemProps) {
    const { permissions } = usePage<PageProps>().props;
    const hasManagePermission = permissions?.includes('kelola_role');

    return (
        <div className="group transition-colors duration-150 hover:bg-slate-50/80 dark:hover:bg-slate-700/20">
            <div className="flex items-start gap-3.5 px-4 py-4">
                {/* Icon */}
                <div className="mt-0.5 flex size-10 flex-shrink-0 items-center justify-center rounded-xl bg-primary/10 dark:bg-primary/15">
                    <Shield className="size-5 text-primary" />
                </div>

                {/* Content */}
                <div className="min-w-0 flex-1">
                    {/* Name & Badge */}
                    <div className="flex items-center gap-2">
                        <h3 className="truncate text-[15px] font-semibold text-slate-800 dark:text-white">{item.name}</h3>
                        {item.name === 'Superadmin' && (
                            <span className="flex-shrink-0 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-600 dark:bg-amber-900/25 dark:text-amber-400">
                                System
                            </span>
                        )}
                    </div>

                    {/* Stats */}
                    <div className="mt-2.5 flex items-center gap-1.5 text-[12px] text-slate-400 dark:text-slate-500">
                        <div className="flex items-center gap-1.5">
                            <span className="font-medium text-slate-500 dark:text-slate-400">{item.permissions_count || 0}</span>
                            <span>Hak Akses Terdaftar</span>
                        </div>
                    </div>

                    {/* Actions */}
                    {hasManagePermission && (
                        <div className={`mt-3 grid ${item.name !== 'Superadmin' ? 'grid-cols-2' : 'grid-cols-1'} gap-2`}>
                            <Link
                                href={`/role/${item.id}/edit`}
                                className="flex items-center justify-center gap-1.5 rounded-lg border border-amber-200 px-4 py-2 text-sm font-medium text-amber-600 transition-colors hover:bg-amber-50 active:bg-amber-100 dark:border-amber-800/50 dark:text-amber-400 dark:hover:bg-amber-900/20"
                            >
                                <Edit className="size-4" />
                                Edit
                            </Link>
                            {item.name !== 'Superadmin' && (
                                <button
                                    onClick={() => onDelete(item)}
                                    className="flex items-center justify-center gap-1.5 rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-500 transition-colors hover:bg-red-50 active:bg-red-100 dark:border-red-800/50 dark:text-red-400 dark:hover:bg-red-900/20"
                                >
                                    <Trash2 className="size-4" />
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
