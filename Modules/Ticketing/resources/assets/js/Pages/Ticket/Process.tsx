import RootLayout from '@/components/layouts/RootLayout';
import ContentCard from '@/components/layouts/ContentCard';
import Button from '@/components/buttons/Button';
import FormTextArea from '@/components/forms/FormTextArea';
import { useForm, usePage } from '@inertiajs/react';
import FormFile from '@/components/forms/FormFile';
import FormInput from '@/components/forms/FormInput';
import { Box, AlertCircle, Save, X } from 'lucide-react';
import Modal from '@/components/modals/Modal';
import { useState } from 'react';

interface TicketData {
    id: number;
    subject: string;
    description: string;
    status: {
        value: string;
        label: string;
    };
    asset_item: {
        id: number;
        category_name: string;
        merk: string;
        model: string;
        serial_number: string;
    };
    attachments: { name: string; url: string }[] | null;
    process_attachments: { name: string; url: string; path: string }[] | null;
    diagnose: string | null;
    follow_up: string | null;
    note: string | null;
    confirm_note: string | null;
    process_note: string | null;
    has_refinement: boolean;
}

interface PageProps {
    ticket: TicketData;
    [key: string]: unknown;
}

export default function TicketProcess() {
    const { ticket } = usePage<PageProps>().props;
    const { data, setData, post, processing, errors } = useForm({
        diagnose: ticket.diagnose || '',
        follow_up: ticket.follow_up || '',
        processed_at: new Date().toISOString().split('T')[0],
        note: ticket.process_note || '',
        process_attachments: [] as File[],
        deleted_attachments: [] as string[],
        needs_further_repair: ticket.has_refinement || ticket.status?.value === 'refinement',
    });
    const [selectedPhoto, setSelectedPhoto] = useState<string | null>(null);
    const [existingPhotos, setExistingPhotos] = useState<any[]>(ticket.process_attachments || []);

    const handleRemoveExistingPhoto = (pathToRemove: string) => {
        setExistingPhotos(prev => prev.filter(photo => photo.path !== pathToRemove));
        setData('deleted_attachments', [...data.deleted_attachments, pathToRemove]);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/ticketing/tickets/${ticket.id}/process`);
    };

    return (
        <RootLayout
            title={`Proses Penanganan: ${ticket.asset_item.category_name} Merek ${ticket.asset_item.merk} Model ${ticket.asset_item.model} SN ${ticket.asset_item.serial_number}`}
            backPath={`/ticketing/tickets/${ticket.id}/show`}
        >
            <div className="space-y-6">
                <ContentCard
                    title="Form Proses"
                    subtitle="Silakan isi form ini untuk memproses tindak lanjut laporan masalah"
                    backPath="/ticketing/tickets"
                >
                    <div className="space-y-5 mb-8 pb-6 border-b border-slate-100 dark:border-slate-800/60">
                        {/* Ticket Context Container */}
                        <div className="p-4 sm:p-5 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700/50 rounded-xl space-y-5">
                            {/* Asset Info */}
                            <div className="flex items-start gap-3 pb-4 border-b border-slate-200 dark:border-slate-700/50">
                                <div className="p-2 bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 rounded-lg shrink-0 mt-0.5">
                                    <Box className="size-5" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-slate-500 dark:text-slate-400">Aset Terkait</p>
                                    <p className="text-sm font-normal text-slate-800 dark:text-slate-200 mt-0.5">
                                        {ticket.asset_item.category_name} Merek {ticket.asset_item.merk} Model {ticket.asset_item.model} SN {ticket.asset_item.serial_number}
                                    </p>
                                </div>
                            </div>

                            {/* Subject & Description */}
                            <div className="space-y-4">
                                <div>
                                    <span className="text-sm font-medium text-slate-500 dark:text-slate-400 block mb-1">Subjek Laporan</span>
                                    <p className="text-sm font-normal text-slate-800 dark:text-white leading-relaxed">{ticket.subject}</p>
                                </div>
                                <div>
                                    <span className="text-sm font-medium text-slate-500 dark:text-slate-400 block mb-1">Deskripsi Masalah</span>
                                    <p className="text-sm font-normal text-slate-700 dark:text-slate-300 leading-relaxed">
                                        {ticket.description}
                                    </p>
                                </div>
                                {ticket.note && (
                                    <div>
                                        <span className="text-sm font-medium text-slate-500 dark:text-slate-400 block mb-1">Catatan Pelapor</span>
                                        <p className="text-sm font-normal text-slate-700 dark:text-slate-300 leading-relaxed italic">
                                            "{ticket.note}"
                                        </p>
                                    </div>
                                )}
                            </div>
                            {/* Photos */}
                            {ticket.attachments && ticket.attachments.length > 0 && (
                                <div className="pt-4 border-t border-slate-200 dark:border-slate-700/50">
                                    <span className="text-sm font-semibold text-slate-700 dark:text-slate-300 block mb-2.5">Foto Kondisi Aset</span>
                                    <div className="flex flex-wrap gap-2.5">
                                        {ticket.attachments.map((file: any, idx: number) => (
                                            <button
                                                key={idx}
                                                type="button"
                                                onClick={() => setSelectedPhoto(file.url)}
                                                className="size-20 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden hover:opacity-80 transition-all hover:scale-105 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/50"
                                            >
                                                <img src={file.url} alt={file.name} className="size-full object-cover" />
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Confirm/Reject Note */}
                        {ticket.confirm_note && (
                            <div className="p-4 bg-amber-50 dark:bg-amber-500/10 border-l-4 border-amber-500 rounded-r-xl">
                                <div className="flex items-start gap-2.5">
                                    <AlertCircle className="size-4 text-amber-600 dark:text-amber-500 mt-0.5" />
                                    <div>
                                        <span className="font-semibold text-sm text-amber-800 dark:text-amber-400 block mb-0.5">Catatan Konfirmasi/Penolakan</span>
                                        <p className="text-sm text-amber-700 dark:text-amber-500/80">{ticket.confirm_note}</p>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                    {ticket.has_refinement && (
                        <div className="p-4 mb-6 rounded-xl bg-purple-50 dark:bg-purple-900/10 border border-purple-200 dark:border-purple-800/30">
                            <div className="flex items-start gap-3">
                                <AlertCircle className="size-5 text-purple-600 dark:text-purple-400 shrink-0 mt-0.5" />
                                <div>
                                    <h4 className="text-sm font-bold text-purple-800 dark:text-purple-300">Data Tidak Dapat Diubah</h4>
                                    <p className="text-sm text-purple-700/80 dark:text-purple-400/80 mt-0.5">
                                        Form proses ini hanya bersifat *read-only* karena tiket telah memiliki riwayat perbaikan (refinement).
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <FormInput
                            type="date"
                            name="processed_at"
                            label="Tanggal Penanganan Masalah"
                            className="w-full"
                            value={data.processed_at}
                            onChange={(e) => setData('processed_at', e.target.value)}
                            error={errors.processed_at}
                            required
                            disabled={ticket.has_refinement}
                        />

                        <FormTextArea
                            name="diagnose"
                            label="Diagnosa Masalah"
                            placeholder="Jelaskan diagnosa awal terhadap masalah..."
                            rows={4}
                            value={data.diagnose}
                            onChange={(e) => setData('diagnose', e.target.value)}
                            error={errors.diagnose}
                            required
                            disabled={ticket.has_refinement}
                        />

                        <FormTextArea
                            name="follow_up"
                            label="Follow Up / Tindakan"
                            placeholder="Jelaskan tindakan yang dilakukan..."
                            rows={4}
                            value={data.follow_up}
                            onChange={(e) => setData('follow_up', e.target.value)}
                            error={errors.follow_up}
                            required
                            disabled={ticket.has_refinement}
                        />

                        <FormTextArea
                            name="note"
                            label="Catatan"
                            placeholder="Catatan tambahan..."
                            rows={2}
                            value={data.note}
                            onChange={(e) => setData('note', e.target.value)}
                            error={errors.note}
                            disabled={ticket.has_refinement}
                        />

                        {/* Existing Process Attachments */}
                        {existingPhotos && existingPhotos.length > 0 && (
                            <div className="pt-2">
                                <span className="text-sm font-semibold text-slate-700 dark:text-slate-300 block mb-2">Foto Proses Sebelumnya:</span>
                                <div className="flex flex-wrap gap-2.5">
                                    {existingPhotos.map((file: any, idx: number) => (
                                        <div key={idx} className="relative group">
                                            <button
                                                type="button"
                                                onClick={() => setSelectedPhoto(file.url)}
                                                className="size-16 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden hover:opacity-80 transition-all shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/50 block"
                                            >
                                                <img src={file.url} alt={file.name} className="size-full object-cover" />
                                            </button>
                                            {!ticket.has_refinement && (
                                                <button
                                                    type="button"
                                                    onClick={() => handleRemoveExistingPhoto(file.path)}
                                                    className="absolute -top-2 -right-2 bg-rose-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity shadow-sm hover:bg-rose-600 focus:opacity-100"
                                                    title="Hapus foto ini"
                                                >
                                                    <X className="size-3" />
                                                </button>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* File Upload */}
                        {!ticket.has_refinement && (
                            <FormFile
                                name="process_attachments"
                                label={existingPhotos.length ? "Upload Foto Baru (Opsional - Akan Menambah Foto)" : "Foto Proses (Opsional)"}
                                onChange={(e: any) => setData('process_attachments', e.target.files)}
                                multiple
                                error={errors.process_attachments as string}
                            />
                        )}

                        {/* Needs further repair checkbox */}
                        <div className="flex items-start gap-3 p-4 rounded-xl bg-purple-50 dark:bg-purple-900/10 border border-purple-200 dark:border-purple-800/30">
                            <input
                                type="checkbox"
                                id="needs_further_repair"
                                checked={data.needs_further_repair}
                                onChange={(e) => setData('needs_further_repair', e.target.checked)}
                                className="mt-0.5 size-4 rounded border-purple-300 text-purple-600 focus:ring-purple-500 disabled:opacity-50"
                                disabled={ticket.has_refinement}
                            />
                            <label htmlFor="needs_further_repair" className={`cursor-pointer ${ticket.has_refinement ? 'opacity-50' : ''}`}>
                                <span className="text-sm font-bold text-purple-700 dark:text-purple-400">Perbaikan Lanjutan</span>
                                <p className="text-xs text-purple-600/70 dark:text-purple-400/60 mt-0.5">Centang jika asset memerlukan perbaikan lebih lanjut. Status tiket akan menjadi "Perbaikan".</p>
                            </label>
                        </div>

                        {!ticket.has_refinement && (
                            <Button
                                type="submit"
                                label={data.needs_further_repair ? 'Simpan & Lanjut ke Perbaikan' : 'Selesaikan Proses'}
                                icon={<Save className="size-4" />}
                                className="w-full"
                                disabled={processing}
                                isLoading={processing}
                            />
                        )}
                    </form>
                </ContentCard>
            </div>

            <Modal
                show={selectedPhoto !== null}
                onClose={() => setSelectedPhoto(null)}
                title="Foto Kondisi Aset"
                maxWidth="2xl"
            >
                {selectedPhoto && (
                    <div className="flex items-center justify-center p-2">
                        <img
                            src={selectedPhoto}
                            alt="Foto Aset"
                            className="max-h-[70vh] w-auto max-w-full rounded-lg object-contain shadow-sm"
                        />
                    </div>
                )}
            </Modal>
        </RootLayout>
    );
}
