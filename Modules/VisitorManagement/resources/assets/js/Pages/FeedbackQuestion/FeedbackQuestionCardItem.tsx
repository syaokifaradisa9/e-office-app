import { Edit, Trash2, MessageSquareText, ToggleLeft, ToggleRight } from 'lucide-react';
import { Link } from '@inertiajs/react';
import CardItemButton from '@/components/buttons/CardItemButton';

interface FeedbackQuestion {
    id: number;
    question: string;
    is_active: boolean;
    created_at?: string;
}

interface Props {
    item: FeedbackQuestion;
    onDelete: (item: FeedbackQuestion) => void;
    onToggleStatus: (item: FeedbackQuestion) => void;
    canManage: boolean;
}

export default function FeedbackQuestionCardItem({ item, onDelete, onToggleStatus, canManage }: Props) {
    const actionCount = canManage ? 2 : 0;

    return (
        <div className="group transition-colors duration-150 hover:bg-slate-50/80 dark:hover:bg-slate-700/20">
            <div className="flex items-start gap-3.5 px-4 py-4">
                {/* Icon */}
                <div className="mt-0.5 flex size-10 flex-shrink-0 items-center justify-center rounded-xl bg-primary/10 dark:bg-primary/15">
                    <MessageSquareText className="size-5 text-primary" />
                </div>

                {/* Content */}
                <div className="min-w-0 flex-1">
                    {/* Question & Status */}
                    <div className="flex items-start justify-between gap-2">
                        <h3 className="line-clamp-2 text-[15px] font-semibold text-slate-800 dark:text-white leading-snug">{item.question}</h3>
                        {canManage ? (
                            <button
                                onClick={() => onToggleStatus(item)}
                                className={`inline-flex shrink-0 items-center gap-1 my-0.5 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider transition-colors ${item.is_active
                                    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                    : 'bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400'
                                    }`}
                            >
                                {item.is_active ? <ToggleRight className="size-3" /> : <ToggleLeft className="size-3" />}
                                {item.is_active ? 'Aktif' : 'Nonaktif'}
                            </button>
                        ) : (
                            <span
                                className={`inline-flex shrink-0 items-center my-0.5 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider ${item.is_active
                                    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                    : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                    }`}
                            >
                                {item.is_active ? 'Aktif' : 'Nonaktif'}
                            </span>
                        )}
                    </div>

                    {/* Metadata */}
                    {item.created_at && (
                        <p className="mt-1 text-[12px] text-slate-400 dark:text-slate-500">
                            Dibuat pada {new Date(item.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}
                        </p>
                    )}

                    {/* Actions */}
                    {canManage && (
                        <div className={`mt-4 grid gap-2 grid-cols-${actionCount}`}>
                            <CardItemButton
                                href={`/visitor/feedback-questions/${item.id}/edit`}
                                label="Edit"
                                icon={<Edit />}
                                variant="warning"
                            />
                            <CardItemButton
                                onClick={() => onDelete(item)}
                                label="Hapus"
                                icon={<Trash2 />}
                                variant="danger"
                            />
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
