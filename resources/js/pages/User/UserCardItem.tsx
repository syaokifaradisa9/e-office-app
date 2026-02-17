import { User, Edit, Trash2, Briefcase, Building2, Shield, Mail, Phone } from 'lucide-react';
import { Link, usePage } from '@inertiajs/react';

interface Role {
    id: number;
    name: string;
}

interface UserData {
    id: number;
    name: string;
    email: string;
    phone?: string;
    is_active: boolean;
    division?: { id: number; name: string };
    position?: { id: number; name: string };
    roles: Role[];
}

interface PageProps {
    permissions?: string[];
    [key: string]: unknown;
}

interface UserCardItemProps {
    item: UserData;
    onDelete: (item: UserData) => void;
}

export default function UserCardItem({ item, onDelete }: UserCardItemProps) {
    const { permissions } = usePage<PageProps>().props;
    const hasManagePermission = permissions?.includes('kelola_pengguna');

    return (
        <div className="group transition-colors duration-150 hover:bg-slate-50/80 dark:hover:bg-slate-700/20">
            <div className="flex items-start gap-3.5 px-4 py-4">
                {/* Icon */}
                <div className="mt-0.5 flex size-10 flex-shrink-0 items-center justify-center rounded-xl bg-primary/10 dark:bg-primary/15">
                    <User className="size-5 text-primary" />
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

                    {/* Email */}
                    <div className="mt-1 flex items-center gap-1.5 text-[13px] text-slate-500 dark:text-slate-400">
                        <Mail className="size-3.5" />
                        <span className="truncate">{item.email}</span>
                    </div>

                    {/* Division & Position & Role */}
                    <div className="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[12px] text-slate-400 dark:text-slate-500">
                        {item.position && (
                            <div className="flex items-center gap-1">
                                <Briefcase className="size-3.5" />
                                <span>{item.position.name}</span>
                            </div>
                        )}
                        {item.division && (
                            <div className="flex items-center gap-1">
                                <Building2 className="size-3.5" />
                                <span>{item.division.name}</span>
                            </div>
                        )}
                        <div className="flex items-center gap-1">
                            <Shield className="size-3.5" />
                            <span>{item.roles[0]?.name || '-'}</span>
                        </div>
                    </div>

                    {/* Actions */}
                    {hasManagePermission && (
                        <div className="mt-3 grid grid-cols-2 gap-2">
                            <Link
                                href={`/user/${item.id}/edit`}
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
