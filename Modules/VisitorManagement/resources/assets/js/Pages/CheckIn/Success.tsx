import React from 'react';
import PublicLayout from '../../Layouts/PublicLayout';
import Button from '@/components/buttons/Button';
import {
    CheckCircle2,
    User,
    Building2,
    Clock,
    Home,
    Check,
    MapPin,
    Sparkles,
} from 'lucide-react';

interface SuccessProps {
    visitor: {
        id: number;
        visitor_name: string;
        organization: string;
        check_in_at: string;
        photo_url?: string;
        division: { name: string };
        status: string;
    };
}

export default function Success({ visitor }: SuccessProps) {
    const checkInDate = new Date(visitor.check_in_at);

    return (
        <PublicLayout title="Check-In Berhasil" hideHeader fullWidth>
            <div className="flex min-h-screen flex-col bg-gradient-to-br from-slate-100 via-slate-50 to-slate-100 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950">
                <div className="flex flex-1 items-start justify-center px-4 py-6 sm:items-center sm:px-6 sm:py-8 lg:px-8">
                    <div className="w-full max-w-5xl animate-in fade-in zoom-in duration-500">
                        {/* Step Indicator - Completed */}
                        <div className="mb-6 flex items-center justify-center">
                            <div className="flex items-center rounded-full bg-white/80 px-4 py-2 shadow-lg ring-1 ring-slate-200/50 backdrop-blur-sm dark:bg-slate-800/80 dark:ring-slate-700">
                                {/* Step 1 - Completed */}
                                <div className="flex items-center gap-2">
                                    <div className="flex size-8 items-center justify-center rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-md shadow-emerald-500/30">
                                        <Check className="size-4" />
                                    </div>
                                    <span className="text-sm font-semibold text-emerald-600 dark:text-emerald-400">Isi Data</span>
                                </div>

                                {/* Connector - Filled */}
                                <div className="mx-3 h-0.5 w-8 rounded-full bg-emerald-500" />

                                {/* Step 2 - Completed */}
                                <div className="flex items-center gap-2">
                                    <div className="flex size-8 items-center justify-center rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-md shadow-emerald-500/30">
                                        <Check className="size-4" />
                                    </div>
                                    <span className="text-sm font-semibold text-emerald-600 dark:text-emerald-400">Ambil Foto</span>
                                </div>

                                {/* Connector - Filled */}
                                <div className="mx-3 h-0.5 w-8 rounded-full bg-emerald-500" />

                                {/* Step 3 - Current */}
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
                                        {/* Pulse ring animation */}
                                        <div className="absolute inset-0 animate-ping rounded-full bg-emerald-400/30" style={{ animationDuration: '2s' }} />
                                    </div>
                                    <div>
                                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Check-In Berhasil!</h1>
                                        <p className="text-sm text-slate-500 dark:text-slate-400">
                                            Data kunjungan Anda telah terdaftar dalam sistem
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {/* Content */}
                            <div className="p-6 sm:p-8">
                                <div className="grid gap-8 lg:grid-cols-5">
                                    {/* Visitor Photo */}
                                    <div className="lg:col-span-2">
                                        {visitor.photo_url ? (
                                            <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 shadow-lg ring-1 ring-slate-200/80 dark:from-slate-800 dark:to-slate-700 dark:ring-slate-700">
                                                <img
                                                    src={visitor.photo_url}
                                                    alt={visitor.visitor_name}
                                                    className="aspect-[3/4] h-full w-full object-cover"
                                                />
                                                {/* Gradient overlay at bottom */}
                                                <div className="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent p-4 pt-12">
                                                    <div className="flex items-center gap-2">
                                                        <div className="flex size-6 items-center justify-center rounded-full bg-emerald-500">
                                                            <Check className="size-3.5 text-white" />
                                                        </div>
                                                        <p className="text-xs font-semibold uppercase tracking-widest text-white/90">
                                                            Foto Terverifikasi
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        ) : (
                                            <div className="flex aspect-[3/4] flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                                                <div className="rounded-full bg-slate-100 p-4 dark:bg-slate-700">
                                                    <User className="size-10 text-slate-400" />
                                                </div>
                                                <p className="mt-3 text-sm font-medium text-slate-400">Tanpa Foto</p>
                                            </div>
                                        )}
                                    </div>

                                    {/* Visitor Info */}
                                    <div className="flex flex-col lg:col-span-3">
                                        {/* Info Grid */}
                                        <div className="flex-1 space-y-5">
                                            {/* Nama */}
                                            <div className="group flex items-start gap-4 rounded-xl p-3 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                                <div className="rounded-xl bg-gradient-to-br from-emerald-100 to-emerald-50 p-2.5 text-emerald-600 shadow-sm ring-1 ring-emerald-100 dark:from-emerald-500/20 dark:to-emerald-400/10 dark:text-emerald-400 dark:ring-emerald-500/20">
                                                    <User className="size-5" />
                                                </div>
                                                <div className="flex-1">
                                                    <p className="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                                        Nama Lengkap
                                                    </p>
                                                    <p className="mt-0.5 text-lg font-bold leading-tight text-slate-900 dark:text-white">
                                                        {visitor.visitor_name}
                                                    </p>
                                                </div>
                                            </div>

                                            {/* Instansi */}
                                            <div className="group flex items-start gap-4 rounded-xl p-3 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                                <div className="rounded-xl bg-gradient-to-br from-blue-100 to-blue-50 p-2.5 text-blue-600 shadow-sm ring-1 ring-blue-100 dark:from-blue-500/20 dark:to-blue-400/10 dark:text-blue-400 dark:ring-blue-500/20">
                                                    <Building2 className="size-5" />
                                                </div>
                                                <div className="flex-1">
                                                    <p className="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                                        Instansi
                                                    </p>
                                                    <p className="mt-0.5 text-lg font-bold leading-tight text-slate-900 dark:text-white">
                                                        {visitor.organization || '-'}
                                                    </p>
                                                </div>
                                            </div>

                                            {/* Tujuan Divisi */}
                                            <div className="group flex items-start gap-4 rounded-xl p-3 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                                <div className="rounded-xl bg-gradient-to-br from-purple-100 to-purple-50 p-2.5 text-purple-600 shadow-sm ring-1 ring-purple-100 dark:from-purple-500/20 dark:to-purple-400/10 dark:text-purple-400 dark:ring-purple-500/20">
                                                    <MapPin className="size-5" />
                                                </div>
                                                <div className="flex-1">
                                                    <p className="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                                        Tujuan Divisi
                                                    </p>
                                                    <p className="mt-0.5 text-lg font-bold leading-tight text-slate-900 dark:text-white">
                                                        {visitor.division.name}
                                                    </p>
                                                </div>
                                            </div>

                                            {/* Waktu Masuk */}
                                            <div className="group flex items-start gap-4 rounded-xl p-3 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                                <div className="rounded-xl bg-gradient-to-br from-amber-100 to-amber-50 p-2.5 text-amber-600 shadow-sm ring-1 ring-amber-100 dark:from-amber-500/20 dark:to-amber-400/10 dark:text-amber-400 dark:ring-amber-500/20">
                                                    <Clock className="size-5" />
                                                </div>
                                                <div className="flex-1">
                                                    <p className="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                                        Waktu Masuk
                                                    </p>
                                                    <p className="mt-0.5 text-lg font-bold leading-tight text-slate-900 dark:text-white">
                                                        {checkInDate.toLocaleTimeString('id-ID', {
                                                            hour: '2-digit',
                                                            minute: '2-digit',
                                                        })}{' '}
                                                        WIB
                                                    </p>
                                                    <p className="mt-0.5 text-xs text-slate-400">
                                                        {checkInDate.toLocaleDateString('id-ID', {
                                                            weekday: 'long',
                                                            day: 'numeric',
                                                            month: 'long',
                                                            year: 'numeric',
                                                        })}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        {/* Next Steps Box */}
                                        <div className="mt-6 rounded-2xl bg-gradient-to-br from-slate-50 to-slate-100/50 p-5 ring-1 ring-slate-100 dark:from-slate-800/80 dark:to-slate-800/40 dark:ring-slate-700/50">
                                            <div className="flex items-center gap-3">
                                                <div className="size-2.5 animate-pulse rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/50" />
                                                <h3 className="text-xs font-bold uppercase tracking-widest text-slate-700 dark:text-slate-300">
                                                    Langkah Selanjutnya
                                                </h3>
                                            </div>
                                            <p className="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                                                Silakan menuju meja petugas keamanan untuk pemeriksaan identitas. Jangan lupa untuk
                                                melakukan{' '}
                                                <span className="font-bold text-emerald-600 dark:text-emerald-400">Check-Out</span>{' '}
                                                sebelum meninggalkan area kantor.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Footer */}
                            <div className="border-t border-slate-100 bg-slate-50/50 px-6 py-5 dark:border-slate-800 dark:bg-slate-800/30">
                                <Button
                                    href="/visitor/check-in"
                                    label="Kembali ke Halaman Awal"
                                    icon={<Home className="size-4" />}
                                    className="h-12 w-full rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-6 text-sm font-semibold shadow-lg shadow-emerald-500/25 transition-all hover:shadow-emerald-500/40 active:scale-[0.98]"
                                />
                            </div>
                        </div>

                        {/* Footer text */}
                        <div className="mt-6 text-center animate-in fade-in slide-in-from-bottom-4 duration-700 delay-300">
                            <p className="text-xs font-medium text-slate-400">
                                &copy; {new Date().getFullYear()} E-Office Digital Visitor System
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
