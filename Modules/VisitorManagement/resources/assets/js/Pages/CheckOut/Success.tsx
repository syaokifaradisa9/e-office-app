import React from 'react';
import PublicLayout from '../../Layouts/PublicLayout';
import VisitorBackground from '../../components/VisitorBackground';
import Button from '@/components/buttons/Button';
import { Heart, Home, CheckCircle2, Building2, MapPin, Sparkles, Check, Clock, LogOut } from 'lucide-react';

interface CheckOutSuccessProps {
    visitor: {
        id: number;
        visitor_name: string;
        organization: string;
        check_in_at: string;
        check_out_at: string;
        division: { name: string };
    };
}

export default function CheckOutSuccess({ visitor }: CheckOutSuccessProps) {
    const checkInDate = visitor.check_in_at ? new Date(visitor.check_in_at) : null;
    const checkOutDate = visitor.check_out_at ? new Date(visitor.check_out_at) : new Date();

    // Calculate visit duration
    const getDuration = () => {
        if (!checkInDate) return null;
        const diffMs = checkOutDate.getTime() - checkInDate.getTime();
        const diffMins = Math.floor(diffMs / 60000);
        const hours = Math.floor(diffMins / 60);
        const mins = diffMins % 60;
        if (hours > 0) {
            return `${hours} jam ${mins} menit`;
        }
        return `${mins} menit`;
    };

    return (
        <PublicLayout title="Check-Out Berhasil" hideHeader fullWidth>
            <div className="relative min-h-screen w-full overflow-hidden bg-slate-100 dark:bg-slate-900">
                {/* Abstract Background */}
                <VisitorBackground />

                <div className="relative z-10 flex min-h-screen items-center justify-center px-4 py-6 sm:px-6 lg:px-8">
                    <div className="w-full max-w-4xl animate-in fade-in zoom-in duration-500">
                        {/* Step Indicator - Completed */}
                        <div className="mb-6 flex items-center justify-center">
                            <div className="flex items-center rounded-full bg-white/80 px-4 py-2 shadow-lg ring-1 ring-slate-200/50 backdrop-blur-sm dark:bg-slate-800/80 dark:ring-slate-700">
                                {/* Step 1 - Completed */}
                                <div className="flex items-center gap-2">
                                    <div className="flex size-8 items-center justify-center rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-md shadow-emerald-500/30">
                                        <Check className="size-4" />
                                    </div>
                                    <span className="text-sm font-semibold text-emerald-600 dark:text-emerald-400">Feedback</span>
                                </div>

                                {/* Connector */}
                                <div className="mx-3 h-0.5 w-8 rounded-full bg-emerald-500" />

                                {/* Step 2 - Current */}
                                <div className="flex items-center gap-2">
                                    <div className="flex size-8 items-center justify-center rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-md shadow-emerald-500/30">
                                        <Sparkles className="size-4" />
                                    </div>
                                    <span className="text-sm font-semibold text-emerald-600 dark:text-emerald-400">Selesai</span>
                                </div>
                            </div>
                        </div>

                        {/* Success Card */}
                        <div className="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 ring-1 ring-slate-200/50 dark:bg-slate-900 dark:shadow-none dark:ring-slate-800">
                            {/* Header Section */}
                            <div className="border-b border-slate-100 bg-gradient-to-r from-emerald-50 via-emerald-50/50 to-white px-6 py-5 dark:border-slate-800 dark:from-emerald-950/30 dark:via-slate-900 dark:to-slate-800/50">
                                <div className="flex items-center gap-4">
                                    <div className="relative">
                                        <div className="flex size-14 items-center justify-center rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-lg shadow-emerald-500/30">
                                            <CheckCircle2 className="size-8" />
                                        </div>
                                        <div className="absolute inset-0 animate-ping rounded-full bg-emerald-400/30" style={{ animationDuration: '2s' }} />
                                        <div className="absolute -bottom-1 -right-1 flex size-6 items-center justify-center rounded-full bg-white text-emerald-500 shadow-md dark:bg-slate-800">
                                            <LogOut className="size-3" />
                                        </div>
                                    </div>
                                    <div>
                                        <h1 className="text-lg font-semibold text-slate-900 dark:text-white">Check-Out Berhasil!</h1>
                                        <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                            Terima kasih atas kunjungan dan feedback Anda
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {/* Content */}
                            <div className="p-6 sm:p-8">
                                <div className="grid gap-6 lg:grid-cols-2">
                                    {/* Left: Greeting & Info */}
                                    <div className="space-y-5">
                                        {/* Greeting */}
                                        <div>
                                            <h2 className="text-2xl font-black text-slate-900 dark:text-white">
                                                Sampai Jumpa, <span className="text-transparent bg-clip-text bg-gradient-to-r from-emerald-500 to-teal-600">{visitor.visitor_name}</span>!
                                            </h2>
                                            <p className="mt-2 text-slate-600 dark:text-slate-400">
                                                Semoga kunjungan Anda menyenangkan dan bermanfaat.
                                            </p>
                                        </div>

                                        {/* Feedback Received Box */}
                                        <div className="rounded-xl bg-gradient-to-br from-pink-50 to-rose-50/50 p-4 ring-1 ring-pink-100 dark:from-pink-950/30 dark:to-rose-950/20 dark:ring-pink-900/30">
                                            <div className="flex items-center gap-3">
                                                <Heart className="size-5 text-pink-500 fill-pink-500" />
                                                <div>
                                                    <p className="text-sm font-bold text-pink-700 dark:text-pink-300">
                                                        Feedback Anda telah kami terima
                                                    </p>
                                                    <p className="text-xs text-pink-600/70 dark:text-pink-400/70">
                                                        Masukan Anda membantu kami menjadi lebih baik
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Right: Info Cards */}
                                    <div className="space-y-3">
                                        {/* Organization */}
                                        <div className="group flex items-center gap-4 rounded-xl bg-slate-50 p-4 transition-colors hover:bg-slate-100 dark:bg-slate-800/50 dark:hover:bg-slate-800">
                                            <div className="rounded-xl bg-gradient-to-br from-blue-100 to-blue-50 p-3 text-blue-600 shadow-sm ring-1 ring-blue-100 dark:from-blue-500/20 dark:to-blue-400/10 dark:text-blue-400 dark:ring-blue-500/20">
                                                <Building2 className="size-5" />
                                            </div>
                                            <div className="flex-1">
                                                <p className="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Organisasi</p>
                                                <p className="mt-0.5 font-bold text-slate-900 dark:text-white">{visitor.organization || '-'}</p>
                                            </div>
                                        </div>

                                        {/* Division */}
                                        <div className="group flex items-center gap-4 rounded-xl bg-slate-50 p-4 transition-colors hover:bg-slate-100 dark:bg-slate-800/50 dark:hover:bg-slate-800">
                                            <div className="rounded-xl bg-gradient-to-br from-purple-100 to-purple-50 p-3 text-purple-600 shadow-sm ring-1 ring-purple-100 dark:from-purple-500/20 dark:to-purple-400/10 dark:text-purple-400 dark:ring-purple-500/20">
                                                <MapPin className="size-5" />
                                            </div>
                                            <div className="flex-1">
                                                <p className="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Divisi Tujuan</p>
                                                <p className="mt-0.5 font-bold text-slate-900 dark:text-white">{visitor.division?.name || '-'}</p>
                                            </div>
                                        </div>

                                        {/* Duration */}
                                        {getDuration() && (
                                            <div className="group flex items-center gap-4 rounded-xl bg-slate-50 p-4 transition-colors hover:bg-slate-100 dark:bg-slate-800/50 dark:hover:bg-slate-800">
                                                <div className="rounded-xl bg-gradient-to-br from-amber-100 to-amber-50 p-3 text-amber-600 shadow-sm ring-1 ring-amber-100 dark:from-amber-500/20 dark:to-amber-400/10 dark:text-amber-400 dark:ring-amber-500/20">
                                                    <Clock className="size-5" />
                                                </div>
                                                <div className="flex-1">
                                                    <p className="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Durasi Kunjungan</p>
                                                    <p className="mt-0.5 font-bold text-slate-900 dark:text-white">{getDuration()}</p>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Footer */}
                            <div className="border-t border-slate-100 bg-slate-50/50 px-6 py-5 dark:border-slate-800 dark:bg-slate-800/30">
                                <Button
                                    href="/visitor/check-in"
                                    label="Selesai & Kembali ke Beranda"
                                    icon={<Home className="size-4" />}
                                    className="h-12 w-full rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-6 text-sm font-semibold shadow-lg shadow-emerald-500/25 transition-all hover:shadow-emerald-500/40 active:scale-[0.98]"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
