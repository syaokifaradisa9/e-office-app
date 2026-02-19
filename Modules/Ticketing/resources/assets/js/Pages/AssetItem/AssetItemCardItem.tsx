import { Edit, Trash2, Box, Users } from 'lucide-react';
import Button from '@/components/buttons/Button';

interface AssetItem {
    id: number;
    asset_model: string;
    merk: string | null;
    model: string | null;
    serial_number: string | null;
    division: string;
    user: string;
    created_at: string;
}

interface Props {
    item: AssetItem;
    canManage?: boolean;
    canDelete?: boolean;
    onDelete: (item: AssetItem) => void;
}

export default function AssetItemCardItem({ item, canManage, canDelete, onDelete }: Props) {
    const specs = [item.merk, item.model, item.serial_number].filter(Boolean);
    const userCount = item.user ? item.user.split(', ').length : 0;

    return (
        <div className="group transition-colors duration-150 hover:bg-slate-50/80 dark:hover:bg-slate-700/20">
            <div className="flex items-start gap-3.5 px-4 py-4">
                {/* Icon */}
                <div className="mt-0.5 flex size-10 flex-shrink-0 items-center justify-center rounded-xl bg-primary/10 dark:bg-primary/15">
                    <Box className="size-5 text-primary" />
                </div>

                {/* Content */}
                <div className="min-w-0 flex-1">
                    {/* Title */}
                    <h3 className="truncate text-[15px] font-semibold text-slate-800 dark:text-white">
                        {item.asset_model} {item.division}
                    </h3>

                    {/* Specs */}
                    {specs.length > 0 && (
                        <p className="mt-0.5 truncate text-[13px] text-slate-500 dark:text-slate-400">
                            {specs.join(' · ')}
                        </p>
                    )}

                    {/* User count */}
                    {userCount > 0 && (
                        <div className="mt-2 flex">
                            <span className="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-0.5 text-[12px] font-medium text-slate-600 dark:bg-slate-700/60 dark:text-slate-300">
                                <Users className="size-3" />
                                {userCount} Pegawai
                            </span>
                        </div>
                    )}

                    {/* Actions */}
                    {(canManage || canDelete) && (
                        <div className="mt-3 grid grid-cols-2 gap-2">
                            {canManage && (
                                <Button
                                    href={`/ticketing/assets/${item.id}/edit`}
                                    variant="outline"
                                    className="!py-2 !bg-transparent !text-amber-600 !border-amber-200 hover:!bg-amber-50 dark:!text-amber-400 dark:!border-amber-800/50 dark:hover:!bg-amber-900/20"
                                    icon={<Edit className="size-4" />}
                                    label="Edit"
                                />
                            )}
                            {canDelete && (
                                <Button
                                    variant="outline"
                                    className="!py-2 !bg-transparent !text-red-500 !border-red-200 hover:!bg-red-50 dark:!text-red-400 dark:!border-red-800/50 dark:hover:!bg-red-900/20"
                                    icon={<Trash2 className="size-4" />}
                                    label="Hapus"
                                    onClick={() => onDelete(item)}
                                />
                            )}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
