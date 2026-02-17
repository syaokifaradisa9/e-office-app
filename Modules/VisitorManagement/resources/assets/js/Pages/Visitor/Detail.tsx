import RootLayout from '@/components/layouts/RootLayout';
import ContentCard from '@/components/layouts/ContentCard';
import { User, Phone, Building, Calendar, Info, CheckCircle, Clock, UserCheck, ShieldCheck, ClipboardList, Users } from 'lucide-react';
import Badge from '@/components/badges/Badge';

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
    status: 'pending' | 'approved' | 'rejected' | 'completed' | 'invited' | 'cancelled';
    check_in_at: string;
    check_out_at: string | null;
    admin_note: string | null;
    confirmed_by?: { name: string };
    confirmed_at: string | null;
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
            case 'invited': return 'info';
            case 'cancelled': return 'secondary';
            default: return 'secondary';
        }
    };

    const getStatusLabel = (status: string) => {
        switch (status) {
            case 'pending': return 'Menunggu';
            case 'approved': return 'Disetujui';
            case 'rejected': return 'Ditolak';
            case 'completed': return 'Selesai';
            case 'invited': return 'Diundang';
            case 'cancelled': return 'Dibatalkan';
            default: return status;
        }
    };

    return (
        <RootLayout title="Detail Pengunjung" backPath="/visitor">
            <ContentCard
                title="Informasi Detail Pengunjung"
                subtitle="Rincian lengkap data kunjungan dan identitas tamu"
                backPath="/visitor"
            >
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">

                    {/* Part 1: Identity & Photo */}
                    <div className="lg:col-span-4 flex flex-col items-center lg:items-start lg:border-r lg:border-slate-100 lg:dark:border-slate-800 lg:pr-8">
                        <div className="w-full aspect-square max-w-[280px] rounded-3xl bg-slate-100 dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm mb-6 relative">
                            {visitor.photo_url ? (
                                <img src={visitor.photo_url} alt={visitor.visitor_name} className="w-full h-full object-cover" />
                            ) : (
                                <div className="w-full h-full flex items-center justify-center bg-slate-50 dark:bg-slate-900/50">
                                    <User className="size-20 text-slate-300 dark:text-slate-700" />
                                </div>
                            )}
                            <div className="absolute top-4 right-4">
                                <Badge color={getStatusBadgeVariant(visitor.status)} className="shadow-lg backdrop-blur-md bg-opacity-90">
                                    {getStatusLabel(visitor.status)}
                                </Badge>
                            </div>
                        </div>

                        <div className="text-center lg:text-left w-full">
                            <h2 className="text-2xl font-bold text-slate-900 dark:text-white leading-tight">{visitor.visitor_name}</h2>
                            <p className="text-slate-500 dark:text-slate-400 font-medium mt-1">{visitor.organization || 'Pribadi'}</p>

                            <div className="mt-6 space-y-3">
                                <div className="flex items-center gap-3 text-slate-600 dark:text-slate-300">
                                    <div className="size-8 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center shrink-0">
                                        <Phone className="size-4 text-emerald-600 dark:text-emerald-400" />
                                    </div>
                                    <span className="text-sm font-semibold">{visitor.phone_number}</span>
                                </div>
                                <div className="flex items-center gap-3 text-slate-600 dark:text-slate-300">
                                    <div className="size-8 rounded-lg bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center shrink-0">
                                        <Building className="size-4 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <span className="text-sm font-semibold">{visitor.division?.name}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Part 2: Visit Details */}
                    <div className="lg:col-span-8 flex flex-col gap-8">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div className="space-y-6">
                                <div>
                                    <div className="flex items-center gap-2 mb-2 text-slate-400 dark:text-slate-500">
                                        <ClipboardList className="size-4" />
                                        <h4 className="text-[10px] font-bold uppercase tracking-[0.1em]">Kategori Keperluan</h4>
                                    </div>
                                    <p className="text-base font-bold text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-800/50 p-3 rounded-xl border border-slate-100 dark:border-slate-700/50">
                                        {visitor.purpose?.name}
                                    </p>
                                </div>

                                <div>
                                    <div className="flex items-center gap-2 mb-2 text-slate-400 dark:text-slate-500">
                                        <Users className="size-4" />
                                        <h4 className="text-[10px] font-bold uppercase tracking-[0.1em]">Jumlah Tamu</h4>
                                    </div>
                                    <p className="text-base font-bold text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-800/50 p-3 rounded-xl border border-slate-100 dark:border-slate-700/50">
                                        {visitor.visitor_count} Orang
                                    </p>
                                </div>
                            </div>

                            <div className="space-y-6">
                                <div>
                                    <div className="flex items-center gap-2 mb-2 text-slate-400 dark:text-slate-500">
                                        <Info className="size-4" />
                                        <h4 className="text-[10px] font-bold uppercase tracking-[0.1em]">Keperluan Lengkap</h4>
                                    </div>
                                    <div className="min-h-[116px] text-sm text-slate-700 dark:text-slate-300 leading-relaxed bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl border border-slate-100 dark:border-slate-700/50 whitespace-pre-wrap">
                                        {visitor.purpose_detail}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Timing Section */}
                        <div className="bg-primary/5 dark:bg-primary/10 rounded-2xl p-5 border border-primary/10 dark:border-primary/20">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="flex items-center gap-3">
                                    <div className="size-10 rounded-xl bg-white dark:bg-slate-800 flex items-center justify-center shadow-sm shrink-0 border border-slate-100 dark:border-slate-700">
                                        <Calendar className="size-5 text-primary" />
                                    </div>
                                    <div>
                                        <p className="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase">Waktu Masuk</p>
                                        <p className="text-[13px] font-semibold dark:text-white">
                                            {visitor.check_in_at ? new Date(visitor.check_in_at).toLocaleString('id-ID', { day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit' }) : '-'}
                                        </p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-3">
                                    <div className="size-10 rounded-xl bg-white dark:bg-slate-800 flex items-center justify-center shadow-sm shrink-0 border border-slate-100 dark:border-slate-700">
                                        <Clock className="size-5 text-slate-400" />
                                    </div>
                                    <div>
                                        <p className="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase">Waktu Keluar</p>
                                        <p className="text-[13px] font-semibold dark:text-white">
                                            {visitor.check_out_at ? new Date(visitor.check_out_at).toLocaleString('id-ID', { day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit' }) : 'Masih Berkunjung'}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Confirmation Info */}
                        {(visitor.confirmed_by || visitor.admin_note) && (
                            <div className="bg-slate-50 dark:bg-slate-800/30 rounded-2xl p-5 border border-slate-200/50 dark:border-slate-700/50">
                                <div className="flex flex-col gap-4">
                                    {visitor.confirmed_by && (
                                        <div className="flex items-center gap-3">
                                            <div className="size-8 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center shrink-0 border border-emerald-200 dark:border-emerald-800/50">
                                                <ShieldCheck className="size-4 text-emerald-600 dark:text-emerald-400" />
                                            </div>
                                            <div>
                                                <p className="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase">Petugas Penerima</p>
                                                <p className="text-sm font-bold dark:text-white">{visitor.confirmed_by.name}</p>
                                            </div>
                                        </div>
                                    )}
                                    {visitor.admin_note && (
                                        <div className="mt-2 p-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-100 dark:border-slate-700 shadow-sm relative">
                                            <div className="absolute -top-2 left-4 px-2 bg-white dark:bg-slate-800 text-[9px] font-bold text-slate-400 uppercase tracking-widest border-x border-slate-100 dark:border-slate-700">
                                                Catatan Konfirmasi
                                            </div>
                                            <p className="text-sm text-slate-600 dark:text-slate-400 italic">"{visitor.admin_note}"</p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </ContentCard>
        </RootLayout>
    );
}
