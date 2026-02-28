import RootLayout from '@/components/layouts/RootLayout';
import ContentCard from '@/components/layouts/ContentCard';
import Button from '@/components/buttons/Button';
import FormInput from '@/components/forms/FormInput';
import FormTextArea from '@/components/forms/FormTextArea';
import FormFile from '@/components/forms/FormFile';
import { useForm, usePage } from '@inertiajs/react';
import { Save, Paperclip, X } from 'lucide-react';

interface TicketData {
    id: number;
    subject: string;
    asset_item: {
        merk: string;
        model: string;
    };
}

interface PageProps {
    ticket: TicketData;
    [key: string]: unknown;
}

export default function RefinementAdd() {
    const { ticket } = usePage<PageProps>().props;
    const { data, setData, post, processing, errors } = useForm({
        date: new Date().toISOString().split('T')[0],
        description: '',
        result: '',
        note: '',
        attachments: [] as File[],
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/ticketing/tickets/${ticket.id}/refinement`);
    };

    const handleFileChange = (e: any) => {
        setData('attachments', Array.from(e.target.files));
    };

    return (
        <RootLayout
            title={`Perbaikan Aset ${ticket.asset_item.merk} ${ticket.asset_item.model}`}
            backPath={`/ticketing/tickets/${ticket.id}/refinement`}
        >
            <ContentCard
                title={`Tambah Data Perbaikan`}
                subtitle={ticket.subject}
                backPath={`/ticketing/tickets/${ticket.id}/refinement`}
            >
                <form onSubmit={handleSubmit} className="space-y-6">
                    <FormInput
                        name="date"
                        label="Tanggal Perbaikan"
                        type="date"
                        value={data.date}
                        onChange={(e) => setData('date', e.target.value)}
                        error={errors.date}
                        required
                    />

                    <FormTextArea
                        name="description"
                        label="Deskripsi Perbaikan"
                        placeholder="Jelaskan tindakan perbaikan yang dilakukan..."
                        rows={4}
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        error={errors.description}
                        required
                    />

                    <FormTextArea
                        name="result"
                        label="Hasil Perbaikan"
                        placeholder="Jelaskan hasil dari perbaikan..."
                        rows={2}
                        value={data.result}
                        onChange={(e) => setData('result', e.target.value)}
                        error={errors.result}
                        required
                    />

                    <FormTextArea
                        name="note"
                        label="Catatan Perbaikan"
                        placeholder="Catatan kecil atau rekomendasi selanjutnya..."
                        rows={2}
                        value={data.note}
                        onChange={(e) => setData('note', e.target.value)}
                        error={errors.note}
                    />

                    <FormFile
                        name="attachments"
                        label="Lampiran"
                        multiple
                        accept="image/*"
                        onChange={handleFileChange}
                        error={errors.attachments}
                    />

                    <Button
                        type="submit"
                        label="Simpan Data Perbaikan"
                        icon={<Save className="size-4" />}
                        className="w-full"
                        disabled={processing}
                        isLoading={processing}
                    />
                </form>
            </ContentCard>
        </RootLayout>
    );
}
