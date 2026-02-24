
import React from 'react';
import RootLayout from '@/components/layouts/RootLayout';
import ContentCard from '@/components/layouts/ContentCard';
import Button from '@/components/buttons/Button';
import FormTextArea from '@/components/forms/FormTextArea';
import FormInput from '@/components/forms/FormInput';
import FormFile from '@/components/forms/FormFile';
import { useForm } from '@inertiajs/react';
import { Save, Eye } from 'lucide-react';
import Modal from '@/components/modals/Modal';
import { useState } from 'react';

interface Props {
    maintenance: any;
    refinement: any;
}

export default function RefinementUpdate({ maintenance, refinement }: Props) {
    const { data, setData, post, processing, errors, setError, clearErrors } = useForm<{
        date: string;
        description: string;
        result: string;
        note: string;
        attachments: any;
        _method: string;
    }>({
        date: refinement.date ? new Date(refinement.date).toISOString().split('T')[0] : '',
        description: refinement.description || '',
        result: refinement.result || '',
        note: refinement.note || '',
        attachments: null,
        _method: 'PUT',
    });

    const [showPreview, setShowPreview] = useState(false);
    const [selectedImage, setSelectedImage] = useState<string | null>(null);

    const handlePreviewImage = (url: string) => {
        setSelectedImage(url);
        setShowPreview(true);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        clearErrors('attachments');
        if (data.attachments) {
            for (let i = 0; i < data.attachments.length; i++) {
                if (data.attachments[i].size > 2 * 1024 * 1024) {
                    setError('attachments', 'Ukuran maksimal setiap file lampiran adalah 2MB.');
                    return;
                }
            }
        }

        // Inertia.js handles file uploads best with POST + _method: 'PUT'
        post(`/ticketing/refinement/${refinement.id}/update`);
    };

    return (
        <RootLayout title={`Edit Perbaikan ${maintenance.asset_item.merk} ${maintenance.asset_item.model}`} backPath={`/ticketing/maintenances/${maintenance.id}/refinement`}>
            <ContentCard
                title={`Edit Data Perbaikan ${maintenance.asset_item.merk} ${maintenance.asset_item.model}`}
                subtitle="Ubah rincian tindakan teknis yang telah dilakukan"
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

                    {refinement.attachments && refinement.attachments.length > 0 && (
                        <div className="space-y-2">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-300">Lampiran Saat Ini (Klik untuk melihat)</label>
                            <div className="flex gap-2 overflow-x-auto pb-2 no-scrollbar">
                                {refinement.attachments.map((file: any, idx: number) => (
                                    <div
                                        key={idx}
                                        onClick={() => handlePreviewImage(file.url)}
                                        className="size-20 rounded-xl border border-slate-100 dark:border-white/5 bg-slate-50 dark:bg-white/5 overflow-hidden shadow-sm relative group cursor-pointer hover:border-primary/50 transition-all"
                                    >
                                        <img src={file.url} alt="" className="size-full object-cover transition-transform group-hover:scale-110" />
                                        <div className="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                            <Eye className="size-5 text-white" />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    <FormFile
                        name="attachments"
                        label="Tambah Lampiran / Bukti Baru (Maks 2MB per File)"
                        onChange={(e: any) => setData('attachments', e.target.files)}
                        multiple
                        error={errors.attachments as string}
                    />

                    <div className="pt-6 border-t border-slate-100 dark:border-white/5">
                        <Button
                            type="submit"
                            label="Perbarui Data Perbaikan"
                            icon={<Save className="size-4" />}
                            isLoading={processing}
                            className="w-full"
                        />
                    </div>
                </form>
            </ContentCard>

            <Modal
                show={showPreview}
                onClose={() => setShowPreview(false)}
                title="Preview Lampiran"
                maxWidth="2xl"
            >
                <div className="flex flex-col items-center">
                    {selectedImage && (
                        <img
                            src={selectedImage}
                            alt="Preview"
                            className="max-w-full max-h-[70vh] rounded-lg shadow-lg object-contain"
                        />
                    )}
                    <div className="mt-6 w-full">
                        <Button
                            variant="outline"
                            label="Tutup"
                            onClick={() => setShowPreview(false)}
                            className="w-full"
                        />
                    </div>
                </div>
            </Modal>
        </RootLayout>
    );
}
