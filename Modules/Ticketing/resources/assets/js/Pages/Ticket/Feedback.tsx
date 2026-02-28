import RootLayout from '@/components/layouts/RootLayout';
import ContentCard from '@/components/layouts/ContentCard';
import Button from '@/components/buttons/Button';
import FormTextArea from '@/components/forms/FormTextArea';
import { useForm, usePage } from '@inertiajs/react';
import { Star, ArrowLeft, MessageSquare, Box, FileText, Send } from 'lucide-react';
import { FormEvent } from 'react';

interface ComponentProps {
    ticket: {
        id: number;
        subject: string;
        description: string;
        asset_item: {
            id: number;
            category_name: string;
            merk: string;
            model: string;
            serial_number: string;
        };
    };
}

export default function TicketFeedback() {
    const { ticket } = usePage<ComponentProps & { [key: string]: unknown }>().props;

    const { data, setData, post, processing, errors } = useForm({
        rating: 0,
        feedback_description: '',
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        post(`/ticketing/tickets/${ticket.id}/feedback`);
    };

    const InfoRow = ({ label, value, icon }: { label: string; value: React.ReactNode; icon?: React.ReactNode }) => (
        <div className="flex items-start gap-3 py-3 border-b border-slate-100 dark:border-white/5 last:border-0 border-dashed">
            {icon && <div className="mt-0.5 text-slate-400 dark:text-slate-500">{icon}</div>}
            <div className="min-w-0 flex-1">
                <span className="text-sm font-medium text-slate-500 dark:text-slate-400">{label}</span>
                <div className="mt-0.5 text-sm font-medium text-slate-800 dark:text-white break-words">{value || <span className="italic text-slate-400">-</span>}</div>
            </div>
        </div>
    );

    return (
        <RootLayout title={`Feedback Tiket #${ticket.id}`} backPath="/ticketing/tickets">
            <ContentCard
                title="Beri Rating & Ulasan"
                subtitle="Berikan feedback Anda mengenai penyelesaian tiket laporan kendala ini"
                backPath="/ticketing/tickets"
            >
                <div className="mb-8 space-y-0">
                    <InfoRow label="Subject Laporan" value={ticket.subject} icon={<MessageSquare className="size-4" />} />
                    <InfoRow label="Deskripsi Masalah" value={ticket.description} icon={<FileText className="size-4" />} />
                    <InfoRow
                        label="Peralatan / Aset"
                        value={`${ticket.asset_item.category_name} - ${ticket.asset_item.merk} ${ticket.asset_item.model} (SN: ${ticket.asset_item.serial_number})`}
                        icon={<Box className="size-4" />}
                    />
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="space-y-3">
                        <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Rating Kinerja Penanganan <span className="text-rose-500">*</span>
                        </label>
                        <div className="flex flex-col gap-2">
                            <div className="flex items-center gap-1.5 cursor-pointer">
                                {[1, 2, 3, 4, 5].map((star) => (
                                    <button
                                        key={star}
                                        type="button"
                                        onClick={() => setData('rating', star)}
                                        className="group p-1 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors focus:outline-none"
                                    >
                                        <Star
                                            className={`size-10 transition-colors ${star <= data.rating
                                                ? 'fill-amber-400 text-amber-400 drop-shadow-sm'
                                                : 'text-slate-300 dark:text-slate-600 group-hover:text-amber-200 dark:group-hover:text-amber-700/50'
                                                }`}
                                        />
                                    </button>
                                ))}
                            </div>
                            {errors.rating && (
                                <span className="text-xs text-rose-500">{errors.rating}</span>
                            )}
                            <p className="text-xs text-slate-500 dark:text-slate-400">
                                {data.rating > 0
                                    ? ['Sangat Buruk', 'Buruk', 'Cukup', 'Baik', 'Sangat Baik'][data.rating - 1]
                                    : 'Klik bintang untuk memberikan rating tingkat kepuasan Anda'
                                }
                            </p>
                        </div>
                    </div>

                    <FormTextArea
                        label="Kritik & Saran (Optional)"
                        name="feedback_description"
                        value={data.feedback_description}
                        onChange={(e) => setData('feedback_description', e.target.value)}
                        error={errors.feedback_description}
                        placeholder="Berikan kritik, saran, atau komentar Anda mengenai pelayanan kami..."
                        rows={4}
                    />

                    <div className="pt-4">
                        <Button
                            type="submit"
                            label="Kirim Feedback"
                            icon={<Send className="size-4" />}
                            className="w-full justify-center py-2.5"
                            disabled={processing || data.rating === 0}
                            isLoading={processing}
                        />
                    </div>
                </form>
            </ContentCard>
        </RootLayout>
    );
}
