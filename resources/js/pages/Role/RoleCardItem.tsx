import { Edit, Trash2 } from 'lucide-react';
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
        <div className="transition-colors hover:bg-gray-50 dark:hover:bg-slate-700/30">
            <div className="flex flex-col gap-1 px-4 py-4">
                {/* Header: Name */}
                <div className="truncate text-base font-semibold text-gray-900 dark:text-white">{item.name}</div>

                {/* Subtitle: Permissions Count */}
                <div className="mb-2 flex items-center gap-2 text-sm text-gray-500 dark:text-slate-400">
                    <span>{item.permissions_count} permissions</span>
                </div>

                {/* Footer: Actions Only (Right side) */}
                {hasManagePermission && (
                    <div className="mt-2 flex items-center justify-end gap-2 pt-2">
                        <Link
                            href={`/role/${item.id}/edit`}
                            className="flex items-center gap-1 rounded-lg bg-yellow-50 px-3 py-1.5 text-xs font-medium text-yellow-600 transition-colors hover:bg-yellow-100 dark:bg-yellow-900/20 dark:text-yellow-400 dark:hover:bg-yellow-900/30"
                        >
                            <Edit className="size-3.5" />
                            Edit
                        </Link>
                        {item.name !== 'Superadmin' && (
                            <button
                                onClick={() => onDelete(item)}
                                className="flex items-center gap-1 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30"
                            >
                                <Trash2 className="size-3.5" />
                                Hapus
                            </button>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
