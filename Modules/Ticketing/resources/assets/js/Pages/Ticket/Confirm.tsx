import React from 'react';
import RootLayout from '@/components/layouts/RootLayout';
import ContentCard from '@/components/layouts/ContentCard';
import Button from '@/components/buttons/Button';
import FormTextArea from '@/components/forms/FormTextArea';
import FormSelect from '@/components/forms/FormSelect';
import FormSearchSelect from '@/components/forms/FormSearchSelect';
import { useForm, usePage } from '@inertiajs/react';
import { AlertCircle, FileCheck, XCircle, Box } from 'lucide-react';

interface TicketData {
    id: number;
    subject: string;
    description: string;
    priority: { value: string; label: string } | null;
    priority_reason: string | null;
    asset_item: {
        id: number;
        category_name: string;
        merk: string;
        model: string;
        serial_number: string;
    };
    note: string | null;
}

interface PageProps {
    ticket: TicketData;
    priorities: { value: string; label: string }[];
    action: string;
    [key: string]: unknown;
}

export default function Confirm() {
    const { ticket, priorities, action } = usePage<PageProps>().props;

    const { data, setData, post, processing, errors } = useForm({
        action: action === 'reject' ? 'reject' : 'accept',
        note: '',
        real_priority: ticket.priority?.value || '',
        priority_reason: '',
    });

    const isAccepting = data.action === 'accept';

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/ticketing/tickets/${ticket.id}/confirm/${data.action}`);
    };

    return (
        <RootLayout
            title={`Konfirmasi Tiket: ${ticket.subject}`}
            backPath={`/ticketing/tickets/${ticket.id}/show`}
        >
            <div className="space-y-6">
                <ContentCard
                    title="Form Konfirmasi Tiket"
                    subtitle="Silakan tinjau dan konfirmasi tiket yang diajukan. Anda dapat menerima atau menolak tiket ini beserta penyesuaian prioritasnya."
                    backPath="/ticketing/tickets"
                >
                    <div className="space-y-6 mb-8 pb-6 border-b border-slate-100 dark:border-white/5">
                        <div className="p-4 sm:p-5 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700/50 rounded-xl space-y-5">
                            {/* Asset Info */}
                            {ticket.asset_item && (
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
                            )}

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
                        </div>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="space-y-1.5">
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Status Konfirmasi <span className="text-rose-500">*</span></label>
                            <FormSelect
                                name="action"
                                value={data.action}
                                onChange={(e) => setData('action', e.target.value)}
                                options={[
                                    { value: 'accept', label: 'Terima Tiket' },
                                    { value: 'reject', label: 'Tolak Tiket' },
                                ]}
                            />
                            {errors.action && <p className="text-xs text-rose-500">{errors.action}</p>}
                        </div>

                        <FormTextArea
                            name="note"
                            label={isAccepting ? "Catatan Tambahan (Opsional)" : "Alasan Penolakan"}
                            placeholder={isAccepting ? "Masukkan catatan penerimaan tiket..." : "Jelaskan mengapa tiket ini ditolak..."}
                            rows={3}
                            value={data.note}
                            onChange={(e) => setData('note', e.target.value)}
                            error={errors.note}
                            required={!isAccepting}
                        />

                        {isAccepting && (
                            <div className="space-y-4 pt-4 border-t border-slate-100 dark:border-white/5">
                                <h4 className="text-sm font-semibold text-slate-700 dark:text-slate-300">Konfirmasi Prioritas</h4>

                                <div className="space-y-1.5">
                                    <label className="block text-sm font-semibold text-slate-700 dark:text-slate-300">Prioritas Aktual <span className="text-rose-500">*</span></label>
                                    <FormSearchSelect
                                        name="real_priority"
                                        value={data.real_priority}
                                        onChange={(e) => setData('real_priority', e.target.value)}
                                        options={priorities}
                                    />
                                    {errors.real_priority && <p className="text-xs text-rose-500">{errors.real_priority}</p>}
                                    {ticket.priority && (
                                        <div className="flex items-start gap-1.5 mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                                            <div className="shrink-0 mt-0.5">ℹ️</div>
                                            <div>
                                                Diajukan sebagai prioritas <strong className="font-medium text-slate-700 dark:text-slate-300">"{ticket.priority.label}"</strong>
                                                {ticket.priority_reason && (
                                                    <span> dengan alasan: <span className="italic">"{ticket.priority_reason}"</span></span>
                                                )}
                                            </div>
                                        </div>
                                    )}
                                </div>

                                {data.real_priority != ticket.priority?.value && (
                                    <FormTextArea
                                        name="priority_reason"
                                        label="Alasan Perubahan Prioritas"
                                        placeholder="Masukkan alasan mengapa prioritas ini dipilih..."
                                        rows={2}
                                        value={data.priority_reason}
                                        onChange={(e) => setData('priority_reason', e.target.value)}
                                        error={errors.priority_reason}
                                        required
                                    />
                                )}
                            </div>
                        )}

                        <div className="pt-6 border-t border-slate-100 dark:border-white/5 flex gap-3">
                            <Button
                                type="submit"
                                label={isAccepting ? "Konfirmasi & Proses Tiket" : "Tolak Tiket"}
                                icon={isAccepting ? <FileCheck className="size-4" /> : <XCircle className="size-4" />}
                                className="w-full"
                                variant={isAccepting ? "primary" : "danger"}
                                disabled={processing}
                                isLoading={processing}
                            />
                        </div>
                    </form>
                </ContentCard>
            </div>
        </RootLayout>
    );
}
