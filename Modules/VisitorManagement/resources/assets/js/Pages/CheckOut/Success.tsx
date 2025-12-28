import React from 'react';
import PublicLayout from '../../Layouts/PublicLayout';
import Button from '@/components/buttons/Button';
import { Heart, Home, CheckCircle2, User, Building2, LogOut } from 'lucide-react';

interface CheckOutSuccessProps {
    visitor: {
        id: number;
        visitor_name: string;
        organization: string;
        division: { name: string };
    };
}

export default function CheckOutSuccess({ visitor }: CheckOutSuccessProps) {
    return (
        <PublicLayout title="Check-Out Berhasil" hideHeader fullWidth>
            <div className="flex min-h-screen flex-col bg-gradient-to-br from-indigo-50 via-white to-emerald-50 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950">
                <div className="flex flex-1 items-center justify-center p-4 sm:p-6 lg:p-8">
                    <div className="w-full max-w-2xl animate-in fade-in zoom-in duration-700">
                        {/* Status Icon */}
                        <div className="mb-8 flex justify-center">
                            <div className="relative">
                                <div className="flex size-24 items-center justify-center rounded-3xl bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-2xl shadow-emerald-500/40 rotate-12 transition-transform hover:rotate-0 duration-500">
                                    <CheckCircle2 className="size-12" />
                                </div>
                                <div className="absolute -bottom-2 -right-2 flex size-10 items-center justify-center rounded-2xl bg-white dark:bg-slate-800 shadow-lg text-emerald-500">
                                    <LogOut className="size-5" />
                                </div>
                            </div>
                        </div>

                        {/* Gratitude Content */}
                        <div className="text-center space-y-4 mb-10">
                            <h1 className="text-4xl font-black text-slate-900 dark:text-white tracking-tight">
                                Sampai Jumpa, <span className="text-transparent bg-clip-text bg-gradient-to-r from-emerald-500 to-teal-600">{visitor.visitor_name}</span>!
                            </h1>
                            <p className="text-xl text-slate-600 dark:text-slate-400 font-medium max-w-lg mx-auto leading-relaxed">
                                Terima kasih banyak atas kunjungan Anda ke kantor kami dan masukan berharga yang telah diberikan.
                            </p>
                        </div>

                        {/* Summary Card */}
                        <div className="overflow-hidden rounded-[2rem] bg-white/80 dark:bg-slate-900/80 backdrop-blur-md shadow-xl shadow-slate-200/50 ring-1 ring-slate-200/50 dark:ring-slate-800 mb-10">
                            <div className="p-8">
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div className="flex items-center gap-4 p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50">
                                        <div className="size-12 rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/30 flex items-center justify-center">
                                            <Building2 className="size-6" />
                                        </div>
                                        <div>
                                            <p className="text-xs font-bold text-slate-400 uppercase tracking-widest">Organisasi</p>
                                            <p className="font-bold text-slate-900 dark:text-white">{visitor.organization}</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-4 p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50">
                                        <div className="size-12 rounded-xl bg-purple-100 text-purple-600 dark:bg-purple-900/30 flex items-center justify-center">
                                            <Heart className="size-6" />
                                        </div>
                                        <div>
                                            <p className="text-xs font-bold text-slate-400 uppercase tracking-widest">Feedback</p>
                                            <p className="font-bold text-emerald-600 dark:text-emerald-400 italic">Diterima</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="px-8 py-4 bg-emerald-500/5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-center gap-2">
                                <div className="size-1.5 rounded-full bg-emerald-500 animate-pulse" />
                                <span className="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-tighter">
                                    Sesi kunjungan Anda telah ditutup secara resmi
                                </span>
                            </div>
                        </div>

                        {/* Home Button */}
                        <div className="flex flex-col items-center gap-6">
                            <Button
                                href="/visitor/check-in"
                                label="Selesai & Kembali ke Beranda"
                                icon={<Home className="size-5" />}
                                className="h-16 px-12 rounded-2xl bg-slate-900 dark:bg-emerald-600 text-white text-lg font-black shadow-2xl shadow-slate-900/20 dark:shadow-emerald-500/30 transition-all hover:scale-105 active:scale-95"
                            />
                            <p className="text-sm font-medium text-slate-400 flex items-center gap-2">
                                <Heart className="size-4 text-pink-500 fill-pink-500" />
                                Masukan Anda membantu kami menjadi lebih baik.
                            </p>
                        </div>

                        {/* Footer */}
                        <div className="mt-16 text-center">
                            <p className="text-xs font-bold text-slate-400 tracking-tighter uppercase">
                                &copy; {new Date().getFullYear()} E-Office Digital Visitor System
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
