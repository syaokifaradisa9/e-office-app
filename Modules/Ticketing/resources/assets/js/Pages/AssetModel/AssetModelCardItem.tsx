import { Edit, Trash2, Box, Shield, ListChecks } from 'lucide-react';
import Button from '@/components/buttons/Button';

interface AssetModel {
    id: number;
    name: string;
    type: string; // Returns 'Physic' or 'Digital' from ->value
    division: string | null;
    checklists_count?: number;
    maintenance_count?: number;
}

interface Props {
    item: AssetModel;
    canManage?: boolean;
    canDelete?: boolean;
    canViewChecklist?: boolean;
    onDelete: (item: AssetModel) => void;
}

export default function AssetModelCardItem({ item, canManage, canDelete, canViewChecklist, onDelete }: Props) {
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
                    <div className="min-w-0">
                        <h3 className="truncate text-[15px] font-semibold text-slate-800 dark:text-white">{item.name}</h3>
                        <div className="mt-1 flex flex-wrap gap-2">
                            <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider ${item.type === 'Physic'
                                ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                                : 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400'
                                }`}>
                                {item.type === 'Physic' ? 'Fisik' : 'Digital'}
                            </span>
                            <span className="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-900/20 dark:text-amber-400">
                                {item.maintenance_count || 0} X MAINTENANCE / TAHUN
                                {(item.maintenance_count || 0) > 0 && ` (${item.checklists_count || 0} CHECKLIST)`}
                            </span>
                        </div>
                    </div>

                    {/* Metadata Grid */}
                    <div className="mt-3 grid grid-cols-1 gap-2">
                        <div className="flex items-center gap-2 text-[13px] text-slate-500 dark:text-slate-400">
                            <Shield className="size-3.5" />
                            <span className="truncate">{item.division || 'Tanpa Divisi'}</span>
                        </div>
                    </div>

                    {/* Actions Stack */}
                    <div className="mt-4 space-y-2">
                        {/* Primary Action: Checklist (Full Width) */}
                        {canViewChecklist && (item.maintenance_count || 0) > 0 && (
                            <Button
                                href={`/ticketing/asset-models/${item.id}/checklists`}
                                variant="outline"
                                className="w-full !py-2.5 !bg-transparent !text-primary !border-primary/30 hover:!bg-primary/5 dark:!border-primary/20 dark:hover:!bg-primary/10"
                                icon={<ListChecks className="size-4" />}
                                label="Kelola Checklist"
                            />
                        )}

                        {/* Secondary Actions: Edit & Delete (2 Columns) */}
                        {(canManage || canDelete) && (
                            <div className="grid grid-cols-2 gap-2">
                                {canManage ? (
                                    <Button
                                        href={`/ticketing/asset-models/${item.id}/edit`}
                                        variant="outline"
                                        className="!py-2 !bg-transparent !text-amber-600 !border-amber-200 hover:!bg-amber-50 dark:!text-amber-400 dark:!border-amber-800/50 dark:hover:!bg-amber-900/20"
                                        icon={<Edit className="size-4" />}
                                        label="Edit"
                                    />
                                ) : <div />}
                                {canDelete ? (
                                    <Button
                                        variant="outline"
                                        className="!py-2 !bg-transparent !text-red-500 !border-red-200 hover:!bg-red-50 dark:!text-red-400 dark:!border-red-800/50 dark:hover:!bg-red-900/20"
                                        icon={<Trash2 className="size-4" />}
                                        label="Hapus"
                                        onClick={() => onDelete(item)}
                                    />
                                ) : <div />}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
