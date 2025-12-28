import Button from '@/components/buttons/Button';
import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Check, LogOut, MessageSquare, Star, User, Building2, Calendar, MapPin } from 'lucide-react';
import React from 'react';
import PublicLayout from '../../Layouts/PublicLayout';

interface Division {
    id: number;
    name: string;
}

interface PurposeCategory {
    id: number;
    name: string;
}

interface Visitor {
    id: number;
    visitor_name: string;
    phone_number: string;
    organization: string;
    status: string;
    check_in_at: string;
    purpose_detail: string;
    division?: Division;
    purpose?: PurposeCategory;
}

interface Question {
    id: number;
    question: string;
}

interface CheckOutFormProps {
    visitor: Visitor;
    questions: Question[];
}

export default function CheckOutForm({ visitor, questions }: CheckOutFormProps) {
    const { data, setData, post, processing, errors } = useForm({
        ratings: {} as Record<number, number>,
        feedback_note: '',
    });

    const handleRatingChange = (questionId: number, rating: number) => {
        setData('ratings', {
            ...data.ratings,
            [questionId]: rating,
        });
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/visitor/check-out/${visitor.id}`);
    };

    const formatTime = (dateString: string) => {
        return new Date(dateString).toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('id-ID', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };

    return (
        <PublicLayout title="Check-Out Pengunjung" fullWidth hideHeader>
            <div className="flex min-h-screen flex-col bg-gradient-to-br from-slate-100 via-slate-50 to-slate-100 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950">
                {/* Header */}
                <div className="border-b border-slate-200/80 bg-white/80 px-4 py-4 backdrop-blur-sm dark:border-slate-800 dark:bg-slate-900/80 sm:py-6">
                    <div className="mx-auto flex max-w-2xl items-center justify-between">
                        <Link
                            href="/visitor/check-out"
                            className="flex items-center gap-2 text-sm font-medium text-slate-600 transition-colors hover:text-slate-900 dark:text-slate-400 dark:hover:text-white"
                        >
                            <ArrowLeft className="size-4" />
                            <span className="hidden sm:inline">Kembali</span>
                        </Link>
                        <div className="flex items-center gap-2">
                            <div className="flex size-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30">
                                <LogOut className="size-5" />
                            </div>
                            <div>
                                <h1 className="font-semibold text-slate-900 dark:text-white">Check-Out</h1>
                                <p className="text-xs text-slate-500">Konfirmasi kepulangan</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Main Content */}
                <div className="flex flex-1 items-start justify-center px-4 py-8 sm:items-center">
                    <div className="w-full max-w-2xl">
                        <form onSubmit={handleSubmit}>
                            <div className="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 ring-1 ring-slate-200/50 dark:bg-slate-900 dark:shadow-none dark:ring-slate-800">
                                {/* Visitor Info */}
                                <div className="border-b border-slate-100 bg-gradient-to-r from-emerald-50 to-white p-6 dark:border-slate-800 dark:from-emerald-950/30 dark:to-slate-900">
                                    <div className="flex items-start gap-4">
                                        <div className="flex size-14 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900/50">
                                            <User className="size-7" />
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <h2 className="text-xl font-semibold text-slate-900 dark:text-white">
                                                {visitor.visitor_name}
                                            </h2>
                                            <div className="mt-2 flex flex-wrap gap-3 text-sm text-slate-600 dark:text-slate-400">
                                                <span className="flex items-center gap-1.5">
                                                    <Building2 className="size-4" />
                                                    {visitor.organization}
                                                </span>
                                                <span className="flex items-center gap-1.5">
                                                    <MapPin className="size-4" />
                                                    {visitor.division?.name}
                                                </span>
                                            </div>
                                            <div className="mt-2 flex items-center gap-1.5 text-sm text-slate-500">
                                                <Calendar className="size-4" />
                                                {formatDate(visitor.check_in_at)} • Check-in: {formatTime(visitor.check_in_at)}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* Feedback Section */}
                                <div className="p-6">
                                    <div className="mb-6">
                                        <h3 className="flex items-center gap-2 font-semibold text-slate-900 dark:text-white">
                                            <MessageSquare className="size-5" />
                                            Feedback Kunjungan
                                        </h3>
                                        <p className="mt-1 text-sm text-slate-500">
                                            Bantu kami meningkatkan layanan dengan memberikan penilaian
                                        </p>
                                    </div>

                                    {/* Questions */}
                                    <div className="space-y-8">
                                        {questions.map((q, idx) => (
                                            <div key={q.id} className="space-y-3">
                                                <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                                    <span className="mr-2 inline-flex size-6 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-600 dark:bg-emerald-900/30">
                                                        {idx + 1}
                                                    </span>
                                                    {q.question}
                                                </label>
                                                <div className="flex gap-2 pl-8">
                                                    {[1, 2, 3, 4, 5].map((star) => (
                                                        <button
                                                            key={star}
                                                            type="button"
                                                            onClick={() => handleRatingChange(q.id, star)}
                                                            className={`flex size-10 items-center justify-center rounded-lg transition-all ${(data.ratings[q.id] || 0) >= star
                                                                ? 'bg-amber-100 text-amber-500'
                                                                : 'bg-slate-100 text-slate-300 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-600'
                                                                }`}
                                                        >
                                                            <Star className={`size-5 ${(data.ratings[q.id] || 0) >= star ? 'fill-amber-500' : ''}`} />
                                                        </button>
                                                    ))}
                                                </div>
                                            </div>
                                        ))}
                                    </div>

                                    {/* Comment */}
                                    <div className="mt-8 border-t border-slate-100 pt-6 dark:border-slate-800">
                                        <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                            Komentar atau Saran (Opsional)
                                        </label>
                                        <textarea
                                            value={data.feedback_note}
                                            onChange={(e) => setData('feedback_note', e.target.value)}
                                            placeholder="Tuliskan pengalaman atau saran Anda..."
                                            rows={3}
                                            className="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition-all placeholder:text-slate-400 focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                        />
                                        {errors.feedback_note && <p className="mt-1 text-xs text-red-500">{errors.feedback_note}</p>}
                                    </div>
                                </div>

                                {/* Footer */}
                                <div className="flex items-center justify-between gap-4 border-t border-slate-100 bg-slate-50/50 px-6 py-4 dark:border-slate-800 dark:bg-slate-800/30">
                                    <Link
                                        href="/visitor/check-in/list"
                                        className="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                    >
                                        <ArrowLeft className="size-4" />
                                        Kembali
                                    </Link>
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        isLoading={processing}
                                        label="Konfirmasi Check-Out"
                                        icon={<Check className="size-4" />}
                                        className="flex-1 sm:flex-none"
                                    />
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
