import { Box, Calendar, CheckCircle2, Clock, XCircle, History as HistoryIcon, Wrench, Info } from 'lucide-react';
import Button from '@/components/buttons/Button';

interface Maintenance {
    id: number;
    asset_item: {
        id: number;
        model_name: string;
        merk: string;
        model: string;
        serial_number: string;
    };
    estimation_date: string;
    actual_date: string | null;
    status: {
        value: string;
        label: string;
    };
    note: string | null;
    user: string | null;
    is_actionable: boolean;
}

interface MaintenanceCardItemProps {
    item: Maintenance;
    canProcess?: boolean;
    canConfirm?: boolean;
    onConfirm?: (id: number) => void;
}

export default function MaintenanceCardItem({ item, canProcess, canConfirm, onConfirm }: MaintenanceCardItemProps) {
    const getStatusStyles = (status: string) => {
        switch (status) {
            case 'finish':
                return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
            case 'confirmed':
                return 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400';
            case 'refinement':
                return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
            case 'cancelled':
                return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400';
            default:
                return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
        }
    };

    const specs = [item.asset_item.merk, item.asset_item.model, item.asset_item.serial_number].filter(Boolean);
    const showActions = canProcess && (item.status.value === 'pending' || item.status.value === 'refinement' || item.status.value === 'finish') && item.is_actionable;
    const showConfirm = canConfirm && item.status.value === 'finish';

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
                        {item.asset_item.model_name}
                    </h3>

                    {/* Specs */}
                    {specs.length > 0 && (
                        <p className="mt-0.5 truncate text-[13px] text-slate-500 dark:text-slate-400">
                            {specs.join(' · ')}
                        </p>
                    )}

                    {/* Badges */}
                    <div className="mt-2 flex flex-wrap items-center gap-2">
                        <span className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider ${getStatusStyles(item.status.value)}`}>
                            {item.status.value === 'pending' && <Clock className="size-3" />}
                            {item.status.value === 'finish' && <CheckCircle2 className="size-3" />}
                            {item.status.value === 'confirmed' && <CheckCircle2 className="size-3" />}
                            {item.status.value === 'refinement' && <HistoryIcon className="size-3" />}
                            {item.status.value === 'cancelled' && <XCircle className="size-3" />}
                            {item.status.label}
                        </span>
                        <span className="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-medium text-slate-600 dark:bg-slate-700/60 dark:text-slate-300">
                            <Calendar className="size-3" />
                            {new Date(item.estimation_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}
                        </span>
                    </div>

                    {/* Actions */}
                    {(showActions || showConfirm) && (
                        <div className="mt-3 space-y-2">
                            {showActions && (
                                <Button
                                    href={`/ticketing/maintenances/${item.id}/complete`}
                                    variant="outline"
                                    className="w-full !py-2 !bg-transparent !text-primary !border-primary/30 hover:!bg-primary/5 dark:!border-primary/20 dark:hover:!bg-primary/10"
                                    label="Maintenance Sekarang"
                                    icon={<Wrench className="size-4" />}
                                />
                            )}
                            {showConfirm && (
                                <Button
                                    onClick={() => onConfirm?.(item.id)}
                                    variant="outline"
                                    className="w-full !py-2 !bg-transparent !text-emerald-600 !border-emerald-200 hover:!bg-emerald-50 dark:!text-emerald-400 dark:!border-emerald-800/50 dark:hover:!bg-emerald-900/20"
                                    label="Konfirmasi"
                                    icon={<CheckCircle2 className="size-4" />}
                                />
                            )}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
