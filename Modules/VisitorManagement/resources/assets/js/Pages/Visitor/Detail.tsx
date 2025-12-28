import RootLayout from '@/components/layouts/RootLayout';
import ContentCard from '@/components/layouts/ContentCard';
import { User, Phone, Building, Calendar, Info, MessageSquare, Star, CheckCircle, XCircle, Clock } from 'lucide-react';
import Button from '@/components/buttons/Button';
import Badge from '@/components/badges/Badge';

interface Rating {
    id: number;
    rating: number;
    question: { question: string };
}

interface Visitor {
    id: number;
    visitor_name: string;
    phone_number: string;
    organization: string;
    photo_url: string | null;
    division: { name: string };

    purpose: { name: string };
    purpose_detail: string;
    visitor_count: number;
    status: 'pending' | 'approved' | 'rejected' | 'completed';
    check_in_at: string;
    check_out_at: string | null;
    admin_note: string | null;
    confirmed_by?: { name: string };
    confirmed_at: string | null;
    feedback?: {
        feedback_note: string | null;
        ratings: Rating[];
    };
}

interface DetailProps {
    visitor: Visitor;
}

export default function VisitorDetail({ visitor }: DetailProps) {
    const getStatusBadgeVariant = (status: string) => {
        switch (status) {
            case 'pending': return 'warning';
            case 'approved': return 'success';
            case 'rejected': return 'danger';
            case 'completed': return 'primary';
            default: return 'secondary';
        }
    };

    return (
        <RootLayout title="Detail Pengunjung" backPath="/visitor">
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {/* Left Column: Photo & Basic Info */}
                <div className="lg:col-span-1 space-y-6">
                    <ContentCard title="Berita Acara & Foto">
                        <div className="flex flex-col items-center">
                            <div className="w-full aspect-square max-w-[250px] rounded-2xl bg-slate-100 dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm mb-6">
                                {visitor.photo_url ? (
                                    <img src={visitor.photo_url} alt={visitor.visitor_name} className="w-full h-full object-cover" />
                                ) : (
                                    <User className="w-full h-full p-12 text-slate-300" />
                                )}
                            </div>
                            <h2 className="text-xl font-bold text-slate-900 dark:text-white text-center">{visitor.visitor_name}</h2>
                            <p className="text-slate-500 dark:text-slate-400 text-center mb-4">{visitor.organization}</p>
                            <Badge color={getStatusBadgeVariant(visitor.status)} className="px-4 py-1 text-sm">
                                {visitor.status.toUpperCase()}
                            </Badge>
                        </div>

                        <div className="mt-8 space-y-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                            <div className="flex items-center gap-3">
                                <Phone className="size-4 text-emerald-500" />
                                <span className="text-sm font-medium">{visitor.phone_number}</span>
                            </div>
                            <div className="flex items-center gap-3">
                                <Building className="size-4 text-emerald-500" />
                                <span className="text-sm font-medium">{visitor.division?.name}</span>
                            </div>
                            <div className="flex items-center gap-3 text-slate-500">
                                <Calendar className="size-4" />
                                <span className="text-xs">
                                    Masuk: {new Date(visitor.check_in_at).toLocaleString('id-ID')}
                                </span>
                            </div>
                            {visitor.check_out_at && (
                                <div className="flex items-center gap-3 text-slate-500">
                                    <Clock className="size-4" />
                                    <span className="text-xs">
                                        Keluar: {new Date(visitor.check_out_at).toLocaleString('id-ID')}
                                    </span>
                                </div>
                            )}
                        </div>
                    </ContentCard>

                    {/* Admin Confirmation Info */}
                    {(visitor.confirmed_by || visitor.admin_note) && (
                        <ContentCard title="Konfirmasi Petugas">
                            <div className="space-y-4">
                                {visitor.confirmed_by && (
                                    <div>
                                        <p className="text-xs text-slate-500 mb-1">Dikonfirmasi Oleh:</p>
                                        <div className="flex items-center gap-2">
                                            <div className="size-6 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center">
                                                <CheckCircle className="size-3 text-emerald-600 dark:text-emerald-400" />
                                            </div>
                                            <span className="text-sm font-bold">{visitor.confirmed_by.name}</span>
                                        </div>
                                    </div>
                                )}
                                {visitor.admin_note && (
                                    <div className="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-700">
                                        <p className="text-xs text-slate-400 mb-1 italic">Catatan Petugas:</p>
                                        <p className="text-sm text-slate-700 dark:text-slate-300">"{visitor.admin_note}"</p>
                                    </div>
                                )}
                            </div>
                        </ContentCard>
                    )}
                </div>

                {/* Right Column: Visit Details & Feedback */}
                <div className="lg:col-span-2 space-y-6">
                    <ContentCard title="Rincian Kunjungan">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div className="space-y-6">
                                <div>
                                    <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Kategori Keperluan</h4>
                                    <p className="text-base font-semibold text-slate-900 dark:text-white">{visitor.purpose?.name}</p>
                                </div>

                                <div>
                                    <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Jumlah Tamu</h4>
                                    <p className="text-base font-semibold text-slate-900 dark:text-white">{visitor.visitor_count} Orang</p>
                                </div>
                            </div>
                            <div className="space-y-6">
                                <div>
                                    <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Keperluan Lengkap</h4>
                                    <p className="text-sm text-slate-700 dark:text-slate-300 leading-relaxed">{visitor.purpose_detail}</p>
                                </div>
                            </div>
                        </div>
                    </ContentCard>

                    {/* Feedback Section */}
                    {visitor.feedback && (
                        <ContentCard title="Ulasan Pengunjung">
                            <div className="space-y-8">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {visitor.feedback.ratings.map((rating) => (
                                        <div key={rating.id} className="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
                                            <p className="text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{rating.question.question}</p>
                                            <div className="flex gap-1">
                                                {[1, 2, 3, 4, 5].map((s) => (
                                                    <Star
                                                        key={s}
                                                        className={`size-4 ${rating.rating >= s ? 'text-amber-400 fill-amber-400' : 'text-slate-200 dark:text-slate-700'}`}
                                                    />
                                                ))}
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {visitor.feedback.feedback_note && (
                                    <div className="p-6 bg-pink-50/30 dark:bg-pink-900/10 rounded-3xl border border-pink-100/50 dark:border-pink-900/20">
                                        <h4 className="text-sm font-bold text-pink-600 dark:text-pink-400 mb-2 flex items-center gap-2">
                                            <MessageSquare className="size-4" /> Kritik & Saran Pengunjung
                                        </h4>
                                        <p className="text-sm text-slate-700 dark:text-slate-200 italic leading-relaxed">
                                            "{visitor.feedback.feedback_note}"
                                        </p>
                                    </div>
                                )}
                            </div>
                        </ContentCard>
                    )}
                </div>

            </div>
        </RootLayout>
    );
}
