import { Edit, Trash2, Box, Shield, Calendar } from 'lucide-react';
import { Link, usePage } from '@inertiajs/react';
import { TicketingPermission } from '../../types/permissions';
import CheckPermissions from '@/components/utils/CheckPermissions';

interface AssetModel {
    id: number;
    name: string;
    type: string;
    division: string | null;
    created_at: string;
}

interface PageProps {
    permissions?: string[];
    [key: string]: unknown;
}

interface Props {
    item: AssetModel;
    onDelete: (item: AssetModel) => void;
}

export default function AssetModelCardItem({ item, onDelete }: Props) {
    const { permissions } = usePage<PageProps>().props;
    const canManage = permissions?.includes(TicketingPermission.ManageAssetModel);
    const canDelete = permissions?.includes(TicketingPermission.DeleteAssetModel);

    return (
        <div className="group transition-colors duration-150 hover:bg-slate-50/80 dark:hover:bg-slate-700/20">
            <div className="flex items-start gap-3.5 px-4 py-4">
                {/* Icon */}
                <div className="mt-0.5 flex size-10 flex-shrink-0 items-center justify-center rounded-xl bg-primary/10 dark:bg-primary/15">
                    <Box className="size-5 text-primary" />
                </div>

                {/* Content */}
                <div className="min-w-0 flex-1">
                    {/* Header: Name & Type Badge */}
                    <div className="flex items-start justify-between gap-2">
                        <div className="min-w-0">
                            <h3 className="truncate text-[15px] font-semibold text-slate-800 dark:text-white">{item.name}</h3>
                            <div className="mt-1 flex flex-wrap gap-2">
                                <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider ${item.type === 'Physic'
                                        ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                                        : 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400'
                                    }`}>
                                    {item.type === 'Physic' ? 'Fisik' : 'Digital'}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Metadata Grid */}
                    <div className="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <div className="flex items-center gap-2 text-[13px] text-slate-500 dark:text-slate-400">
                            <Shield className="size-3.5" />
                            <span className="truncate">{item.division || 'Tanpa Divisi'}</span>
                        </div>
                        <div className="flex items-center gap-2 text-[13px] text-slate-500 dark:text-slate-400">
                            <Calendar className="size-3.5" />
                            <span>{item.created_at}</span>
                        </div>
                    </div>

                    {/* Actions */}
                    {(canManage || canDelete) && (
                        <div className={`mt-4 grid gap-2 ${canManage && canDelete ? 'grid-cols-2' : 'grid-cols-1'}`}>
                            {canManage && (
                                <Link
                                    href={`/ticketing/asset-models/${item.id}/edit`}
                                    className="flex items-center justify-center gap-1.5 rounded-lg border border-amber-200 px-3 py-2 text-[13px] font-medium text-amber-600 transition-colors hover:bg-amber-50 dark:border-amber-800/50 dark:text-amber-400 dark:hover:bg-amber-900/20"
                                >
                                    <Edit className="size-3.5" />
                                    Edit
                                </Link>
                            )}
                            {canDelete && (
                                <button
                                    onClick={() => onDelete(item)}
                                    className="flex items-center justify-center gap-1.5 rounded-lg border border-red-200 px-3 py-2 text-[13px] font-medium text-red-500 transition-colors hover:bg-red-50 dark:border-red-800/50 dark:text-red-400 dark:hover:bg-red-900/20"
                                >
                                    <Trash2 className="size-3.5" />
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
