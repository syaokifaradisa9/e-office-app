
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
    const { data, setData, post, processing, errors } = useForm({
        date: new Date().toISOString().split('T')[0],
        description: '',
        result: '',
        note: '',
        attachments: null as FileList | null,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/ticketing/maintenances/${maintenance.id}/refinement`);
    };

    return (
        <RootLayout title={`Tambah Perbaikan ${maintenance.asset_item.merk} ${maintenance.asset_item.model}`} backPath={`/ticketing/maintenances/${maintenance.id}/refinement`}>
            <ContentCard
                title={`Tambah Data Perbaikan ${maintenance.asset_item.merk} ${maintenance.asset_item.model}`}
                subtitle="Input rincian tindakan teknis yang telah dilakukan"
                backPath={`/ticketing/maintenances/${maintenance.id}/refinement`}
            >
                <form onSubmit={handleSubmit} className="space-y-8">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8">
                        {/* Kiri: Info Dasar */}
                        <div className="space-y-6">
                            <FormInput
                                name="date"
                                label="Tanggal Pelaksanaan"
                                type="date"
                                value={data.date}
                                onChange={(e: any) => setData('date', e.target.value)}
                                error={errors.date}
                                required
                                className="!bg-slate-50 dark:!bg-white/5 border-none shadow-sm h-12"
                            />

                            <FormTextArea
                                name="result"
                                label="Hasil Akhir / Status"
                                placeholder="Contoh: Normal, Penggantian Selesai, dsb."
                                value={data.result}
                                onChange={(e: any) => setData('result', e.target.value)}
                                error={errors.result}
                                required
                                rows={3}
                                className="!bg-slate-50 dark:!bg-white/5 border-none shadow-sm p-4"
                            />

                            <FormFile
                                name="attachments"
                                label="Lampiran / Bukti"
                                onChange={(e: any) => setData('attachments', e.target.files)}
                                multiple
                                error={errors.attachments as string}
                                className="!bg-slate-50 dark:!bg-white/5 border-dashed border-2 border-slate-200 dark:border-white/10 rounded-2xl p-6"
                            />
                        </div>

                        {/* Kanan: Deskripsi */}
                        <div className="space-y-6">
                            <FormTextArea
                                name="description"
                                label="Deskripsi Perbaikan / Tindakan Teknis"
                                placeholder="Jelaskan secara rinci tindakan teknik yang dilakukan..."
                                rows={6}
                                value={data.description}
                                onChange={(e: any) => setData('description', e.target.value)}
                                error={errors.description}
                                required
                                className="!bg-slate-50 dark:!bg-white/5 border-none shadow-sm resize-none p-4"
                            />

                            <FormTextArea
                                name="note"
                                label="Catatan Tambahan (Opsional)"
                                placeholder="Catatan kecil atau rekomendasi selanjutnya..."
                                rows={4}
                                value={data.note}
                                onChange={(e: any) => setData('note', e.target.value)}
                                error={errors.note}
                                className="!bg-slate-50 dark:!bg-white/5 border-none shadow-sm resize-none p-4"
                            />
                        </div>
                    </div>

                    <div className="pt-6 border-t border-slate-100 dark:border-white/5 flex justify-end">
                        <Button
                            type="submit"
                            label="Simpan Data Perbaikan"
                            icon={<Save className="size-4" />}
                            isLoading={processing}
                            className="!rounded-full px-10 py-3 font-bold text-sm shadow-xl shadow-primary/20 transition-all hover:scale-105"
                        />
                    </div>
                </form>
            </ContentCard>
        </RootLayout>
    );
}
