import Button from '@/components/buttons/Button';
import FormTextArea from '@/components/forms/FormTextArea';
import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ChevronLeft, Check, LogOut, MessageSquare, Star, User, Building2, Calendar, MapPin } from 'lucide-react';
import React from 'react';
import PublicLayout from '../../Layouts/PublicLayout';
import VisitorBackground from '../../components/VisitorBackground';

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

    const getRatingConfig = (rating: number) => {
        switch (rating) {
            case 1: return { bg: 'bg-rose-700', text: 'text-rose-700', shadow: 'shadow-rose-700/40', border: 'border-rose-200' };
            case 2: return { bg: 'bg-red-500', text: 'text-red-500', shadow: 'shadow-red-500/40', border: 'border-red-200' };
            case 3: return { bg: 'bg-blue-600', text: 'text-blue-600', shadow: 'shadow-blue-600/40', border: 'border-blue-200' };
            case 4: return { bg: 'bg-emerald-500', text: 'text-emerald-500', shadow: 'shadow-emerald-500/40', border: 'border-emerald-200' };
            case 5: return { bg: 'bg-emerald-600', text: 'text-emerald-600', shadow: 'shadow-emerald-600/40', border: 'border-emerald-300' };
            default: return { bg: 'bg-slate-100', text: 'text-slate-400', shadow: '', border: 'border-slate-100' };
        }
    };

    return (
        <PublicLayout title="Check-Out Pengunjung" fullWidth hideHeader>
            <div className="relative min-h-screen w-full overflow-hidden bg-slate-100 dark:bg-slate-900">
                {/* Abstract Background */}
                <VisitorBackground />

                {/* Main Content */}
                <div className="relative z-10 flex min-h-screen items-start justify-center px-4 py-6 sm:items-center sm:px-6 sm:py-8 lg:px-8">
                    <div className="w-full max-w-4xl animate-in fade-in zoom-in duration-300">
                        <form onSubmit={handleSubmit}>
                            <div className="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 ring-1 ring-slate-200/50 dark:bg-slate-900 dark:shadow-none dark:ring-slate-800">
                                {/* Header */}
                                <div className="border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-6 py-5 dark:border-slate-800 dark:from-slate-900 dark:to-slate-800/50">
                                    <div className="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                                        <div className="flex items-center gap-4">
                                            <Link
                                                href="/visitor/check-in/list"
                                                className="group flex size-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500 transition-all hover:bg-emerald-500 hover:text-white dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-emerald-600"
                                                title="Kembali ke Daftar"
                                            >
                                                <ChevronLeft className="size-6 transition-transform group-hover:-translate-x-0.5" />
                                            </Link>
                                            <div>
                                                <h2 className="text-lg font-semibold text-slate-900 dark:text-white">
                                                    Feedback Kunjungan
                                                </h2>
                                                <p className="mt-1 text-sm text-slate-500">
                                                    Bantu kami meningkatkan layanan dengan memberikan penilaian
                                                </p>
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-4 rounded-3xl bg-slate-100/50 p-2 pr-6 ring-1 ring-slate-200 dark:bg-slate-800/50 dark:ring-slate-700">
                                            <div className="flex size-12 items-center justify-center rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-lg shadow-emerald-500/20">
                                                <User className="size-6" />
                                            </div>
                                            <div>
                                                <p className="text-[10px] font-bold uppercase tracking-widest text-slate-400">Pengunjung</p>
                                                <p className="text-sm font-extrabold text-slate-900 dark:text-white">{visitor.visitor_name}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* Feedback Section */}
                                <div className="p-6">

                                    {/* Questions Grid */}
                                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                        {questions.map((q, idx) => (
                                            <div key={q.id} className="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-slate-100 bg-white p-6 transition-all hover:border-emerald-200 hover:shadow-xl hover:shadow-emerald-500/5 dark:border-slate-800 dark:bg-slate-900/50 dark:hover:border-emerald-900/50">
                                                <div className="relative z-10">
                                                    <div className="mb-2 flex items-center justify-between">
                                                        <span className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Parameter {idx + 1}</span>
                                                        <div className={`size-2.5 rounded-full transition-all duration-500 ${data.ratings[q.id] ? `${getRatingConfig(data.ratings[q.id]).bg} shadow-lg shadow-current` : 'bg-slate-200'}`} />
                                                    </div>
                                                    <label className="block text-base font-bold leading-tight text-slate-800 dark:text-slate-200">
                                                        {q.question}
                                                    </label>
                                                </div>
                                                <div className="relative z-10 mt-6">
                                                    <div className="flex items-center gap-2">
                                                        {[1, 2, 3, 4, 5].map((star) => {
                                                            const isSelected = (data.ratings[q.id] || 0) >= star;
                                                            const activeRating = data.ratings[q.id] || 0;
                                                            const config = getRatingConfig(activeRating);

                                                            return (
                                                                <button
                                                                    key={star}
                                                                    type="button"
                                                                    onClick={() => handleRatingChange(q.id, star)}
                                                                    className={`flex flex-1 items-center justify-center rounded-xl py-3 transition-all duration-300 hover:scale-105 active:scale-95 ${isSelected
                                                                        ? `${config.bg} text-white shadow-lg ${config.shadow}`
                                                                        : 'bg-slate-50 text-slate-300 hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-600'
                                                                        }`}
                                                                >
                                                                    <Star className={`size-5 ${isSelected ? 'fill-current' : ''}`} />
                                                                </button>
                                                            );
                                                        })}
                                                    </div>

                                                    {/* Rating Description */}
                                                    <div className="mt-3 h-5 text-center">
                                                        {data.ratings[q.id] && (
                                                            <span className={`animate-in fade-in slide-in-from-top-1 text-xs font-black uppercase tracking-widest ${getRatingConfig(data.ratings[q.id]).text}`}>
                                                                {data.ratings[q.id] === 1 && 'Sangat Buruk'}
                                                                {data.ratings[q.id] === 2 && 'Buruk'}
                                                                {data.ratings[q.id] === 3 && 'Cukup'}
                                                                {data.ratings[q.id] === 4 && 'Baik'}
                                                                {data.ratings[q.id] === 5 && 'Sangat Baik'}
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>

                                                {/* Decorative background number */}
                                                <span className="absolute -right-4 -top-8 select-none text-8xl font-black text-slate-50 opacity-[0.03] dark:text-white dark:opacity-[0.02]">
                                                    {idx + 1}
                                                </span>
                                            </div>
                                        ))}
                                    </div>

                                    {/* Comment */}
                                    <div className="mt-6">
                                        <FormTextArea
                                            label="Komentar atau Saran (Opsional)"
                                            name="feedback_note"
                                            value={data.feedback_note}
                                            onChange={(e) => setData('feedback_note', e.target.value)}
                                            error={errors.feedback_note}
                                            placeholder="Tuliskan pengalaman atau saran Anda..."
                                            rows={3}
                                        />
                                    </div>
                                </div>

                                {/* Footer */}
                                <div className="flex items-center justify-between gap-4 border-t border-slate-100 bg-slate-50/50 px-6 py-4 dark:border-slate-800 dark:bg-slate-800/30">
                                    <Button
                                        href="/visitor/check-in/list"
                                        variant="outline"
                                        label="Kembali"
                                        icon={<ArrowLeft className="size-4" />}
                                    />
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        isLoading={processing}
                                        label="Konfirmasi Check-Out"
                                        icon={<Check className="size-4" />}
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
