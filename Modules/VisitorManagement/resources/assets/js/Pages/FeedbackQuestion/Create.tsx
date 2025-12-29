import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';

import Button from '@/components/buttons/Button';
import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';

interface FeedbackQuestion {
    id: number;
    question: string;
    is_active: boolean;
}

interface Props {
    feedbackQuestion?: FeedbackQuestion;
}

export default function FeedbackQuestionCreate({ feedbackQuestion }: Props) {
    const isEdit = !!feedbackQuestion;

    const { data, setData, post, put, processing, errors } = useForm({
        question: feedbackQuestion?.question || '',
        is_active: feedbackQuestion?.is_active ?? true,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            put(`/visitor/feedback-questions/${feedbackQuestion.id}/update`);
        } else {
            post('/visitor/feedback-questions/store');
        }
    };

    return (
        <RootLayout title={isEdit ? 'Edit Pertanyaan Feedback' : 'Tambah Pertanyaan Feedback'} backPath="/visitor/feedback-questions">
            <ContentCard
                title={isEdit ? 'Edit Pertanyaan Feedback' : 'Tambah Pertanyaan Feedback Baru'}
                backPath="/visitor/feedback-questions"
                mobileFullWidth
            >
                <p className="mb-6 text-sm text-gray-500 dark:text-slate-400">Isi pertanyaan feedback yang akan ditampilkan kepada pengunjung</p>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="space-y-1.5">
                        <label className="text-sm font-medium text-gray-700 dark:text-slate-300">
                            Pertanyaan <span className="text-red-500">*</span>
                        </label>
                        <textarea
                            placeholder="Contoh: Bagaimana penilaian Anda terhadap pelayanan kami?"
                            value={data.question}
                            onChange={(e) => setData('question', e.target.value)}
                            rows={4}
                            required
                            className="w-full rounded-lg border border-gray-400/50 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 dark:border-slate-600 dark:bg-slate-800/40 dark:text-slate-200 dark:placeholder:text-slate-400"
                        />
                        {errors.question && <p className="text-sm text-red-500">{errors.question}</p>}
                    </div>

                    <div className="flex items-center gap-3">
                        <input
                            type="checkbox"
                            id="is_active"
                            checked={data.is_active}
                            onChange={(e) => setData('is_active', e.target.checked)}
                            className="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                        />
                        <label htmlFor="is_active" className="text-sm font-medium text-gray-700 dark:text-slate-300">
                            Pertanyaan Aktif
                        </label>
                    </div>

                    <div className="flex justify-end gap-3 border-t border-gray-200 pt-6 dark:border-slate-700">
                        <Button href="/visitor/feedback-questions" label="Batal" variant="secondary" />
                        <Button type="submit" label={isEdit ? 'Simpan Perubahan' : 'Simpan'} icon={<Save className="h-4 w-4" />} isLoading={processing} />
                    </div>
                </form>
            </ContentCard>
        </RootLayout>
    );
}
