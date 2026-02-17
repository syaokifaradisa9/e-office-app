import RootLayout from '@/components/layouts/RootLayout';
import ContentCard from '@/components/layouts/ContentCard';
import Button from '@/components/buttons/Button';
import Badge from '@/components/badges/Badge';
import { Calendar, MessageCircle, Star, Check, User, Quote, ChevronRight } from 'lucide-react';
import { Head, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import CheckPermissions from '@/components/utils/CheckPermissions';
import ConfirmationAlert from '@/components/alerts/ConfirmationAlert';

interface Rating {
    question: string;
    rating: number;
}

interface Feedback {
    id: number;
    visitor_name: string;
    visit_date: string;
    avg_rating: number;
    feedback_note: string;
    is_read: boolean;
    ratings: Rating[];
}

interface Props {
    feedback: Feedback;
}

export default function FeedbackDetail({ feedback }: Props) {
    const [markAsReadModal, setMarkAsReadModal] = useState(false);

    function handleMarkAsRead() {
        setMarkAsReadModal(true);
    }

    function confirmMarkAsRead() {
        router.post(`/visitor/criticism-suggestions/${feedback.id}/mark-as-read`, {}, {
            onSuccess: () => {
                setMarkAsReadModal(false);
            }
        });
    }

    return (
        <RootLayout title="Detail Kritik dan Saran">
            <head title={`Detail Ulasan - ${feedback.visit_date}`} />

            <ConfirmationAlert
                isOpen={markAsReadModal}
                setOpenModalStatus={setMarkAsReadModal}
                title="Konfirmasi Tandai Dibaca"
                message="Apakah Anda yakin ingin menandai kritik dan saran ini sebagai sudah dibaca?"
                confirmText="Ya, Tandai Dibaca"
                cancelText="Batal"
                type="info"
                onConfirm={confirmMarkAsRead}
            />

            <div className="animate-in fade-in slide-in-from-bottom-4 duration-500 w-full pb-24 md:pb-0 -mt-10 md:mt-0">
                <ContentCard
                    title="Detail Ulasan Pengunjung"
                    subtitle={`Hasil penilaian kunjungan pada tanggal ${feedback.visit_date}`}
                    backPath="/visitor/criticism-suggestions"
                    mobileFullWidth={true}
                    additionalButton={
                        !feedback.is_read && (
                            <div className="hidden md:block">
                                <CheckPermissions permissions={['kelola_kritik_saran_pengunjung']}>
                                    <Button
                                        onClick={handleMarkAsRead}
                                        variant="primary"
                                        label="Tandai Sudah Dibaca"
                                        icon={<Check className="size-4" />}
                                    />
                                </CheckPermissions>
                            </div>
                        )
                    }
                >
                    <div className="space-y-6">
                        {/* Compact Summary Row - Save space and comfortable */}
                        <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-700/50">
                            {/* Left: Visitor Info */}
                            <div className="flex items-center gap-3.5 flex-1">
                                <div className="size-11 rounded-xl bg-white dark:bg-slate-800 flex items-center justify-center shrink-0 shadow-sm border border-slate-100 dark:border-slate-700">
                                    <User className="size-5.5 text-primary" />
                                </div>
                                <div className="min-w-0">
                                    <div className="flex items-center gap-2 mb-0.5">
                                        <h3 className="text-sm font-bold text-slate-900 dark:text-white truncate">Pengunjung</h3>
                                        <Badge color={feedback.is_read ? 'success' : 'warning'} className="scale-75 origin-left px-1.5 py-0">
                                            {feedback.is_read ? 'Dibaca' : 'Baru'}
                                        </Badge>
                                    </div>
                                    <div className="flex items-center gap-1.5 text-slate-500 dark:text-slate-400 text-[11px]">
                                        <Calendar className="size-3 text-primary/70" />
                                        <span>{feedback.visit_date}</span>
                                    </div>
                                </div>
                            </div>

                            {/* Divider for Mobile (Vertical) or Desktop (Horizontal) */}
                            <div className="hidden sm:block w-px h-8 bg-slate-200 dark:bg-slate-700"></div>
                            <div className="block sm:hidden h-px w-full bg-slate-200 dark:bg-slate-700/50"></div>

                            {/* Right: Average Rating */}
                            <div className="flex items-center justify-between sm:justify-end gap-6 px-1 sm:px-0">
                                <div className="flex flex-col">
                                    <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider leading-none mb-1">Rata-rata</span>
                                    <div className="flex items-center gap-2">
                                        <Star className="size-4 text-amber-400 fill-amber-400" />
                                        <span className="text-xl font-black text-slate-900 dark:text-white tracking-tight">{feedback.avg_rating}</span>
                                    </div>
                                </div>
                                <ChevronRight className="size-4 text-slate-300 sm:hidden" />
                            </div>
                        </div>

                        {/* Detailed Ratings */}
                        <div className="space-y-4">
                            <div className="flex items-center justify-between mb-2">
                                <div className="flex items-center gap-2">
                                    <div className="w-1 h-3 bg-primary rounded-full"></div>
                                    <h4 className="text-[11px] font-black uppercase tracking-widest text-slate-400">Poin Penilaian</h4>
                                </div>
                                <span className="text-[10px] font-medium text-slate-400">{feedback.ratings.length} Kriteria</span>
                            </div>
                            <div className="grid grid-cols-1 gap-2">
                                {feedback.ratings.map((rating, index) => (
                                    <div key={index} className="group flex items-center justify-between gap-4 p-3 rounded-xl bg-white dark:bg-slate-900/40 border border-slate-100 dark:border-slate-800 transition-all hover:bg-slate-50 dark:hover:bg-slate-800/60">
                                        <div className="flex items-start gap-2.5 min-w-0">
                                            <span className="flex size-5 shrink-0 items-center justify-center rounded-md bg-slate-100 dark:bg-slate-800 text-[9px] font-bold text-slate-400 border border-slate-200 dark:border-slate-700">
                                                {index + 1}
                                            </span>
                                            <p className="text-[13px] font-semibold text-slate-700 dark:text-slate-300 leading-tight truncate-limit-2 pt-0.5">
                                                {rating.question}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-2 shrink-0">
                                            <div className="flex gap-0.5">
                                                {[1, 2, 3, 4, 5].map((s) => (
                                                    <Star
                                                        key={s}
                                                        className={`size-3 ${s <= rating.rating ? 'fill-amber-400 text-amber-400' : 'text-slate-200 dark:text-slate-700'}`}
                                                    />
                                                ))}
                                            </div>
                                            <span className="text-[13px] font-black text-slate-900 dark:text-white min-w-[1rem] text-right">
                                                {rating.rating}
                                            </span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Feedback Note */}
                        <div className="space-y-4 pb-4">
                            <div className="flex items-center gap-2 mb-2">
                                <div className="w-1 h-3 bg-primary rounded-full"></div>
                                <h4 className="text-[11px] font-black uppercase tracking-widest text-slate-400">Pesan & Kesan</h4>
                            </div>
                            <div className="relative p-5 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700/50">
                                <Quote className="absolute top-3 left-3 size-8 text-primary/5" />
                                <p className="relative z-10 text-[13px] text-slate-600 dark:text-slate-400 leading-relaxed italic">
                                    "{feedback.feedback_note}"
                                </p>
                            </div>
                        </div>
                    </div>
                </ContentCard>

                {/* Mobile Bottom Action Bar */}
                {!feedback.is_read && (
                    <div className="md:hidden fixed bottom-0 left-0 right-0 p-4 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 z-[60] shadow-[0_-8px_30px_rgb(0,0,0,0.04)] animate-in slide-in-from-bottom-full duration-300">
                        <CheckPermissions permissions={['kelola_kritik_saran_pengunjung']}>
                            <Button
                                onClick={handleMarkAsRead}
                                variant="primary"
                                className="w-full py-3.5 shadow-xl shadow-primary/20 flex items-center justify-center gap-2 text-sm font-bold"
                                label="Konfirmasi Telah Dibaca"
                                icon={<Check className="size-5" />}
                            />
                        </CheckPermissions>
                    </div>
                )}
            </div>
        </RootLayout>
    );
}
