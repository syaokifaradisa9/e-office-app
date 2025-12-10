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
    const hasManagePermission = permissions?.includes('manajemen-pengguna');

    return (
        <div className="transition-colors hover:bg-gray-50 dark:hover:bg-slate-700/30">
            <div className="flex flex-col px-4 py-4">
                {/* Header: Name */}
                <div className="mb-0.5 flex items-center gap-2">
                    <User className="size-4 text-primary" />
                    <div className="truncate text-base font-semibold text-gray-900 dark:text-white">{item.name}</div>
                </div>

                {/* Email */}
                <div className="flex items-center gap-1.5 text-sm text-gray-500 dark:text-slate-400">
                    <Mail className="size-3.5" />
                    <span>{item.email}</span>
                </div>

                {/* Division & Position Row */}
                <div className="mb-1 mt-2 flex flex-wrap items-center gap-4 text-sm text-gray-500 dark:text-slate-400">
                    {item.position && (
                        <div className="flex items-center gap-1.5">
                            <Briefcase className="size-4" />
                            <span>{item.position.name}</span>
                        </div>
                    )}
                    {item.division && (
                        <div className="flex items-center gap-1.5">
                            <Building2 className="size-4" />
                            <span>{item.division.name}</span>
                        </div>
                    )}
                </div>

                {/* Role & Status Row */}
                <div className="mb-2 flex flex-wrap items-center gap-4 text-sm text-gray-500 dark:text-slate-400">
                    <div className="flex items-center gap-1.5">
                        <Shield className="size-4" />
                        <span>{item.roles[0]?.name || '-'}</span>
                    </div>
                    <span className={`rounded-full px-2 py-0.5 text-xs font-semibold ${item.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'}`}>
                        {item.is_active ? 'Aktif' : 'Tidak Aktif'}
                    </span>
                </div>

                {/* Footer: Actions */}
                {hasManagePermission && (
                    <div className="flex items-center justify-end gap-2 pt-2">
                        <Link
                            href={`/user/${item.id}/edit`}
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
