
import React from 'react';
import RootLayout from '@/components/layouts/RootLayout';
import ContentCard from '@/components/layouts/ContentCard';
import Button from '@/components/buttons/Button';
import FormTextArea from '@/components/forms/FormTextArea';
import FormInput from '@/components/forms/FormInput';
import FormFile from '@/components/forms/FormFile';
import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';

interface Props {
    maintenance: any;
}

export default function RefinementAdd({ maintenance }: Props) {
    const { data, setData, post, processing, errors, setError, clearErrors } = useForm<{
        date: string;
        description: string;
        result: string;
        note: string;
        attachments: any;
    }>({
        date: new Date().toISOString().split('T')[0],
        description: '',
        result: '',
        note: '',
        attachments: null,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        clearErrors('attachments');
        if (data.attachments) {
            for (let i = 0; i < data.attachments.length; i++) {
                // Check if any file is > 5MB
                if (data.attachments[i].size > 5 * 1024 * 1024) {
                    setError('attachments', 'Ukuran maksimal setiap file lampiran adalah 5MB.');
                    return;
                }
            }
        }

        post(`/ticketing/maintenances/${maintenance.id}/refinement`);
    };

    return (
        <RootLayout title={`Tambah Perbaikan ${maintenance.asset_item.merk} ${maintenance.asset_item.model}`} backPath={`/ticketing/maintenances/${maintenance.id}/refinement`}>
            <ContentCard
                title={`Tambah Data Perbaikan ${maintenance.asset_item.merk} ${maintenance.asset_item.model}`}
                subtitle="Input rincian tindakan teknis yang telah dilakukan"
                backPath={`/ticketing/maintenances/${maintenance.id}/refinement`}
            >
                <form onSubmit={handleSubmit} className="space-y-6">
                    <FormInput
                        name="date"
                        label="Tanggal Pelaksanaan"
                        type="date"
                        value={data.date}
                        onChange={(e: any) => setData('date', e.target.value)}
                        error={errors.date}
                        required
                    />

                    <FormTextArea
                        name="description"
                        label="Deskripsi Perbaikan / Tindakan Teknis"
                        placeholder="Jelaskan secara rinci tindakan teknik yang dilakukan..."
                        rows={4}
                        value={data.description}
                        onChange={(e: any) => setData('description', e.target.value)}
                        error={errors.description}
                        required
                    />

                    <FormTextArea
                        name="result"
                        label="Hasil Akhir / Status"
                        placeholder="Contoh: Normal, Penggantian Selesai, dsb."
                        value={data.result}
                        onChange={(e: any) => setData('result', e.target.value)}
                        error={errors.result}
                        required
                        rows={2}
                    />

                    <FormTextArea
                        name="note"
                        label="Catatan"
                        placeholder="Catatan kecil atau rekomendasi selanjutnya..."
                        rows={2}
                        value={data.note}
                        onChange={(e: any) => setData('note', e.target.value)}
                        error={errors.note}
                    />

                    <FormFile
                        name="attachments"
                        label="Lampiran / Bukti (Maks 5MB per File)"
                        onChange={(e: any) => setData('attachments', e.target.files)}
                        multiple
                        error={errors.attachments as string}
                    />

                    <div className="pt-6 border-t border-slate-100 dark:border-white/5">
                        <Button
                            type="submit"
                            label="Simpan Data Perbaikan"
                            icon={<Save className="size-4" />}
                            isLoading={processing}
                            className="w-full"
                        />
                    </div>
                </form>
            </ContentCard>
        </RootLayout>
    );
}
