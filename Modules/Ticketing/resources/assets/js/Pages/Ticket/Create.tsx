import RootLayout from '@/components/layouts/RootLayout';
import ContentCard from '@/components/layouts/ContentCard';
import Button from '@/components/buttons/Button';
import FormInput from '@/components/forms/FormInput';
import FormTextArea from '@/components/forms/FormTextArea';
import FormSelect from '@/components/forms/FormSelect';
import FormFile from '@/components/forms/FormFile';
import { useForm, usePage } from '@inertiajs/react';
import { Send, AlertCircle, AlertTriangle, ShieldAlert } from 'lucide-react';

interface Asset {
    id: number;
    label: string;
}

interface PageProps {
    assets: Asset[];
    priorities: { value: string; label: string }[];
    [key: string]: unknown;
}

export default function TicketCreate() {
    const { assets, priorities } = usePage<PageProps>().props;
    const { data, setData, post, processing, errors } = useForm({
        asset_item_id: '',
        priority: '',
        priority_reason: '',
        subject: '',
        description: '',
        note: '',
        attachments: [] as File[],
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/ticketing/tickets');
    };

    return (
        <RootLayout
            title="Buat Laporan Masalah"
            backPath="/ticketing/tickets"
        >
            <ContentCard
                title="Buat Laporan Masalah"
                subtitle="Laporkan masalah pada asset Anda"
                backPath="/ticketing/tickets"
            >
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="space-y-2">
                        <label className="block text-sm font-semibold text-slate-700 dark:text-slate-300">Prioritas Laporan <span className="text-rose-500">*</span></label>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                            {priorities.map((p) => {
                                const isSelected = data.priority === p.value;
                                let icon = <AlertCircle className="size-5" />;
                                let desc = '';
                                let colorCls = '';

                                if (p.value === 'low') {
                                    desc = 'Masalah ringan, tidak mengganggu operasional utama.';
                                    colorCls = isSelected
                                        ? 'border-sky-500 bg-sky-50 ring-1 ring-sky-500 dark:bg-sky-500/10 text-sky-700 dark:text-sky-300'
                                        : 'border-slate-200 hover:border-sky-300 dark:border-slate-700 dark:hover:border-sky-700/50 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800/40';
                                    icon = <AlertCircle className={`size-5 ${isSelected ? 'text-sky-500' : 'text-slate-400'}`} />;
                                } else if (p.value === 'medium') {
                                    desc = 'Masalah menengah yang cukup mengganggu pekerjaan Anda.';
                                    colorCls = isSelected
                                        ? 'border-amber-500 bg-amber-50 ring-1 ring-amber-500 dark:bg-amber-500/10 text-amber-700 dark:text-amber-300'
                                        : 'border-slate-200 hover:border-amber-300 dark:border-slate-700 dark:hover:border-amber-700/50 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800/40';
                                    icon = <AlertTriangle className={`size-5 ${isSelected ? 'text-amber-500' : 'text-slate-400'}`} />;
                                } else if (p.value === 'high') {
                                    desc = 'Kritis, operasional terhenti dan perlu penanganan segera.';
                                    colorCls = isSelected
                                        ? 'border-rose-500 bg-rose-50 ring-1 ring-rose-500 dark:bg-rose-500/10 text-rose-700 dark:text-rose-300'
                                        : 'border-slate-200 hover:border-rose-300 dark:border-slate-700 dark:hover:border-rose-700/50 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800/40';
                                    icon = <ShieldAlert className={`size-5 ${isSelected ? 'text-rose-500' : 'text-slate-400'}`} />;
                                }

                                return (
                                    <button
                                        key={p.value}
                                        type="button"
                                        onClick={() => setData('priority', p.value)}
                                        className={`flex flex-col items-start gap-2 rounded-xl border p-4 text-left transition-all ${colorCls}`}
                                    >
                                        <div className="flex items-center gap-2">
                                            <div className="shrink-0">{icon}</div>
                                            <div className="font-semibold text-sm">{p.label}</div>
                                        </div>
                                        <div className={`text-[11px] leading-relaxed ${isSelected ? 'opacity-90 font-medium' : 'text-slate-500 dark:text-slate-400'}`}>
                                            {desc}
                                        </div>
                                    </button>
                                );
                            })}
                        </div>
                        {errors.priority && <p className="text-xs text-rose-500">{errors.priority}</p>}
                    </div>
                    {data.priority && (
                        <FormTextArea
                            name="priority_reason"
                            label="Alasan Pemilihan Prioritas"
                            placeholder="Jelaskan alasan Anda memilih tingkat prioritas ini..."
                            rows={2}
                            value={data.priority_reason}
                            onChange={(e) => setData('priority_reason', e.target.value)}
                            error={errors.priority_reason}
                            required
                        />
                    )}

                    <div className="space-y-1.5">
                        <label className="block text-sm font-semibold text-slate-700 dark:text-slate-300">Pilih Asset <span className="text-rose-500">*</span></label>
                        <FormSelect
                            name="asset_item_id"
                            value={data.asset_item_id}
                            onChange={(e) => setData('asset_item_id', e.target.value)}
                            options={assets.map(a => ({ value: String(a.id), label: a.label }))}
                            placeholder="Pilih Asset..."
                            searchable
                        />
                        {errors.asset_item_id && <p className="text-xs text-rose-500">{errors.asset_item_id}</p>}
                    </div>

                    <FormInput
                        name="subject"
                        label="Subject Masalah"
                        placeholder="Contoh: Monitor tidak menyala, Printer macet, dll."
                        value={data.subject}
                        onChange={(e) => setData('subject', e.target.value)}
                        error={errors.subject}
                        required
                    />

                    <FormTextArea
                        name="description"
                        label="Deskripsi Masalah"
                        placeholder="Jelaskan detail masalah yang Anda alami..."
                        rows={4}
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        error={errors.description}
                        required
                    />

                    {/* File Upload */}
                    <FormFile
                        name="attachments"
                        label="Foto Bukti (Opsional)"
                        onChange={(e: any) => setData('attachments', e.target.files)}
                        multiple
                        error={errors.attachments as string}
                    />

                    <FormTextArea
                        name="note"
                        label="Catatan"
                        placeholder="Catatan tambahan atau informasi penting..."
                        rows={2}
                        value={data.note}
                        onChange={(e) => setData('note', e.target.value)}
                        error={errors.note}
                    />

                    <Button
                        type="submit"
                        label="Kirim Laporan"
                        icon={<Send className="size-4" />}
                        className="w-full"
                        disabled={processing}
                        isLoading={processing}
                    />
                </form>
            </ContentCard>
        </RootLayout>
    );
}
