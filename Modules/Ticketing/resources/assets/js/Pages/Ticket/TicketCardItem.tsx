import { AlertCircle, Calendar, Check, Clock, XCircle, History as HistoryIcon, Wrench, Info, Ban, Star, ArrowUpRight, FileEdit } from 'lucide-react';
import Button from '@/components/buttons/Button';

interface Ticket {
    id: number;
    subject: string;
    description: string;
    status: { value: string; label: string };
    priority: { value: string; label: string } | null;
    real_priority: { value: string; label: string } | null;
    asset_item: {
        id: number;
        category_name: string;
        merk: string;
        model: string;
        serial_number: string;
    };
    user: string | null;
    created_at: string;
    rating: number | null;
}

interface TicketCardItemProps {
    item: Ticket;
    canConfirm?: boolean;
    canProcess?: boolean;
    canRepair?: boolean;
    canFinish?: boolean;
    onFinish?: (id: number) => void;
}

export default function TicketCardItem({ item, canConfirm, canProcess, canRepair, canFinish, onFinish }: TicketCardItemProps) {
    const getStatusStyles = (status: string) => {
        switch (status) {
            case 'process':
                return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
            case 'finish':
                return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
            case 'refinement':
                return 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400';
            case 'damaged':
                return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400';
            case 'closed':
                return 'bg-slate-100 text-slate-600 dark:bg-slate-700/40 dark:text-slate-300';
            default:
                return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
        }
    };

    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'process': return <Wrench className="size-3" />;
            case 'finish': return <Check className="size-3" />;
            case 'refinement': return <HistoryIcon className="size-3" />;
            case 'damaged': return <Ban className="size-3" />;
            case 'closed': return <Check className="size-3" />;
            default: return <Clock className="size-3" />;
        }
    };

    const getPriorityStyles = (priority: string) => {
        switch (priority) {
            case 'high':
                return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400';
            case 'medium':
                return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
            default:
                return 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400';
        }
    };

    const displayPriority = item.real_priority || item.priority;

    return (
        <div className="group transition-colors duration-150 hover:bg-slate-50/80 dark:hover:bg-slate-700/20">
            <div className="flex items-start gap-3.5 px-4 py-4">
                <div className="mt-0.5 flex size-10 flex-shrink-0 items-center justify-center rounded-xl bg-primary/10 dark:bg-primary/15">
                    <AlertCircle className="size-5 text-primary" />
                </div>

                <div className="min-w-0 flex-1">
                    <h3 className="truncate text-[15px] font-semibold text-slate-800 dark:text-white">
                        {item.subject}
                    </h3>

                    <p className="mt-0.5 truncate text-[13px] text-slate-500 dark:text-slate-400">
                        {item.asset_item.category_name} · {item.asset_item.merk} {item.asset_item.model}
                    </p>

                    <div className="mt-2 flex flex-wrap items-center gap-2">
                        <span className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider ${getStatusStyles(item.status.value)}`}>
                            {getStatusIcon(item.status.value)}
                            {item.status.value === 'closed' ? 'Closed' : item.status.label}
                        </span>
                        {displayPriority && (
                            <span className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider ${getPriorityStyles(displayPriority.value)}`}>
                                {displayPriority.label}
                            </span>
                        )}
                        <span className="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-medium text-slate-600 dark:bg-slate-700/60 dark:text-slate-300">
                            <Calendar className="size-3" />
                            {new Date(item.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}
                        </span>
                        {item.rating && (
                            <span className="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-bold text-amber-600 dark:bg-amber-900/20 dark:text-amber-400">
                                <Star className="size-3 fill-amber-400 text-amber-400" />
                                {item.rating}/5
                            </span>
                        )}
                    </div>

                    <div className="mt-3 flex flex-wrap gap-2">
                        {item.status.value === 'pending' && canConfirm && (
                            <>
                                <Button
                                    href={`/ticketing/tickets/${item.id}/confirm/accept`}
                                    label="Terima"
                                    icon={<Check className="size-4" />}
                                    className="flex-1 !py-2 !bg-emerald-50!text-emerald-600 !border-emerald-200 hover:!bg-emerald-100 hover:!text-emerald-700 dark:!bg-emerald-500/10 dark:hover:!bg-emerald-500/20"
                                    variant="outline"
                                />
                                <Button
                                    href={`/ticketing/tickets/${item.id}/confirm/reject`}
                                    label="Tolak"
                                    icon={<XCircle className="size-4" />}
                                    className="flex-1 !py-2 !bg-rose-50 !text-rose-600 !border-rose-200 hover:!bg-rose-100 hover:!text-rose-700 dark:!bg-rose-500/10 dark:hover:!bg-rose-500/20"
                                    variant="outline"
                                />
                            </>
                        )}
                        <Button
                            href={`/ticketing/tickets/${item.id}/show`}
                            variant="outline"
                            className="flex-1 !py-2 !bg-transparent !text-slate-500 !border-slate-200 dark:!text-slate-400 dark:!border-slate-700 hover:!bg-slate-100 dark:hover:!bg-slate-800"
                            label="Detail"
                            icon={<ArrowUpRight className="size-4" />}
                        />
                        {['process', 'finish', 'refinement'].includes(item.status.value) && canProcess && (
                            <Button
                                href={`/ticketing/tickets/${item.id}/process`}
                                label="Proses"
                                icon={<FileEdit className="size-4" />}
                                className="flex-1 !py-2"
                            />
                        )}
                        {item.status.value === 'refinement' && canRepair && (
                            <Button
                                href={`/ticketing/tickets/${item.id}/refinement`}
                                label="Perbaikan"
                                icon={<Wrench className="size-4" />}
                                className="flex-1 !py-2 !bg-purple-50 !text-purple-600 !border-purple-200 hover:!bg-purple-100 dark:!bg-purple-500/10 dark:hover:!bg-purple-500/20"
                                variant="outline"
                            />
                        )}
                        {item.status.value === 'finish' && canFinish && onFinish && (
                            <Button
                                onClick={() => onFinish(item.id)}
                                label="Tutup Tiket"
                                icon={<Check className="size-4" />}
                                className="flex-1 !py-2 !bg-emerald-50 !text-emerald-600 !border-emerald-200 hover:!bg-emerald-100 hover:!text-emerald-700 dark:!bg-emerald-500/10 dark:hover:!bg-emerald-500/20"
                                variant="outline"
                            />
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
