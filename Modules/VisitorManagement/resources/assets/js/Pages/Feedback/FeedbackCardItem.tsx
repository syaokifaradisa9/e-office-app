import { Eye, Check, X, Star, Calendar, MessageCircle, ShieldCheck } from 'lucide-react';
import Badge from '@/components/badges/Badge';
import CardItemButton from '@/components/buttons/CardItemButton';
import CheckPermissions from '@/components/utils/CheckPermissions';
import { usePage } from '@inertiajs/react';

interface Feedback {
    id: number;
    visitor_name: string;
    visit_date: string;
    avg_rating: number;
    feedback_note: string;
    is_read: boolean;
    actions: {
        mark_as_read: boolean;
    };
}

interface Props {
    item: Feedback;
    onMarkAsRead: (id: number) => void;
    onDelete: (id: number) => void;
}

export default function FeedbackCardItem({ item, onMarkAsRead, onDelete }: Props) {
    const { permissions } = usePage<{ permissions: string[] }>().props;
    const canManage = permissions?.includes('kelola_kritik_saran_pengunjung');
    const actionCount = item.is_read || !canManage ? 1 : 3;

    return (
        <div className="group transition-colors duration-150 hover:bg-slate-50/80 dark:hover:bg-slate-700/20">
            <div className="flex items-start gap-3.5 px-4 py-4">
                {/* Icon Container - Anonymized */}
                <div className="mt-0.5 flex size-9 flex-shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500 border border-slate-200 dark:border-slate-700">
                    <ShieldCheck className="size-4.5" />
                </div>

                {/* Content */}
                <div className="min-w-0 flex-1">
                    {/* Header: Date & Status */}
                    <div className="flex items-center justify-between gap-2">
                        <div className="flex items-center gap-1.5">
                            <Calendar className="size-3.5 text-primary" />
                            <span className="text-[13px] font-bold text-slate-700 dark:text-slate-200">{item.visit_date}</span>
                        </div>
                        <Badge color={item.is_read ? 'success' : 'warning'} className="shrink-0 scale-75 origin-right">
                            {item.is_read ? 'Dibaca' : 'Baru'}
                        </Badge>
                    </div>

                    {/* Feedback Rating */}
                    <div className="mt-1.5 flex items-center gap-1.5">
                        <div className="flex gap-0.5">
                            {[1, 2, 3, 4, 5].map((s) => (
                                <Star
                                    key={s}
                                    className={`size-2.5 ${s <= Math.round(item.avg_rating) ? 'fill-amber-400 text-amber-400' : 'text-slate-200 dark:text-slate-700'}`}
                                />
                            ))}
                        </div>
                        <span className="text-[11px] font-bold text-slate-700 dark:text-slate-200">{item.avg_rating}</span>
                    </div>

                    {/* Feedback Message */}
                    <div className="mt-2 p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50">
                        <div className="flex gap-2">
                            <MessageCircle className="size-3 text-slate-400 shrink-0 mt-0.5" />
                            <p className="text-[11px] text-slate-600 dark:text-slate-400 line-clamp-3 italic leading-relaxed">
                                "{item.feedback_note}"
                            </p>
                        </div>
                    </div>

                    <div className={`mt-3 grid gap-2 grid-cols-${actionCount}`}>
                        <CardItemButton
                            href={`/visitor/criticism-suggestions/detail/${item.id}`}
                            label="Detail"
                            icon={<Eye />}
                            variant="info"
                        />

                        {!item.is_read && (
                            <CheckPermissions permissions={['kelola_kritik_saran_pengunjung']}>
                                <CardItemButton
                                    onClick={() => onMarkAsRead(item.id)}
                                    label="Setujui"
                                    icon={<Check />}
                                    variant="success"
                                />
                                <CardItemButton
                                    onClick={() => onDelete(item.id)}
                                    label="Hapus"
                                    icon={<X />}
                                    variant="danger"
                                />
                            </CheckPermissions>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
