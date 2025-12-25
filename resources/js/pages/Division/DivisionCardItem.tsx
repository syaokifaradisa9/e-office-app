import { Building2, Edit, Trash2, Users } from 'lucide-react';
import { Link, usePage } from '@inertiajs/react';

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
        <div className="transition-colors hover:bg-gray-50 dark:hover:bg-slate-700/30">
            <div className="flex flex-col gap-1 px-4 py-4">
                {/* Header: Name with Icon */}
                <div className="flex items-center gap-2">
                    <Building2 className="size-4 text-primary" />
                    <div className="truncate text-base font-semibold text-gray-900 dark:text-white">{item.name}</div>
                </div>

                {/* Description */}
                <div className="text-sm text-gray-500 dark:text-slate-400">{item.description || '-'}</div>

                {/* Stats Row */}
                <div className="mb-2 flex items-center gap-4 text-sm text-gray-500 dark:text-slate-400">
                    <div className="flex items-center gap-1">
                        <Users className="size-4" />
                        <span>{item.users_count || 0} Pegawai</span>
                    </div>
                    <span className={`rounded-full px-2 py-0.5 text-xs font-semibold ${item.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'}`}>
                        {item.is_active ? 'Aktif' : 'Tidak Aktif'}
                    </span>
                </div>

                {/* Footer: Actions */}
                {hasManagePermission && (
                    <div className="mt-2 flex items-center justify-end gap-2 pt-2">
                        <Link
                            href={`/division/${item.id}/edit`}
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
