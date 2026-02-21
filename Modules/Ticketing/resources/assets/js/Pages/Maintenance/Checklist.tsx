import RootLayout from '@/components/layouts/RootLayout';
import ContentCard from '@/components/layouts/ContentCard';
import FormInput from '@/components/forms/FormInput';
import FormTextArea from '@/components/forms/FormTextArea';
import FormFile from '@/components/forms/FormFile';
import Button from '@/components/buttons/Button';
import { useForm } from '@inertiajs/react';
import { Save, X, CheckSquare, AlertCircle, CheckCircle2, XCircle, Wrench } from 'lucide-react';
import { useEffect } from 'react';

interface ChecklistItem {
    id: number;
    label: string;
    description: string | null;
}

interface Maintenance {
    id: number;
    asset_item: {
        id: number;
        category_name: string;
        merk: string;
        model: string;
        serial_number: string;
        asset_category: {
            checklists: ChecklistItem[];
        };
    };
    estimation_date: string;
    actual_date: string | null;
    note: string | null;
    status: {
        value: string;
        label: string;
    };
    checklist_results: Array<{
        checklist_id: number;
        label: string;
        value: 'Baik' | 'Tidak Baik';
        note: string;
        follow_up: string;
    }> | null;
    attachments: Array<{
        name: string;
        url: string;
        size: number;
    }> | null;
}

interface Props {
    maintenance: Maintenance;
}

export default function MaintenanceChecklist({ maintenance }: Props) {
    const isConfirmed = maintenance.status.value === 'confirmed';

    const { data, setData, post, processing, errors } = useForm({
        actual_date: (maintenance.actual_date || maintenance.estimation_date)?.split('T')[0] || '',
        note: maintenance.note || '',
        needs_further_repair: maintenance.status.value === 'refinement',
        checklists: (maintenance.checklist_results as any) || maintenance.asset_item.asset_category.checklists.map(c => ({
            checklist_id: c.id,
            label: c.label,
            description: c.description,
            value: 'Baik' as 'Baik' | 'Tidak Baik',
            note: '',
            follow_up: '',
        })),
        attachments: [] as File[],
    });

    const hasNotGood = data.checklists.some((item: any) => item.value === 'Tidak Baik');

    const updateChecklist = (index: number, key: string, value: any) => {
        if (isConfirmed) return;
        const newChecklists = [...data.checklists];
        newChecklists[index] = { ...newChecklists[index], [key]: value };
        setData('checklists', newChecklists);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isConfirmed) return;
        post(`/ticketing/maintenances/${maintenance.id}/store-checklist`);
    };

    return (
        <RootLayout title="Checklist Maintenance" backPath="/ticketing/maintenances">
            <ContentCard
                title="Form Checklist Maintenance"
                subtitle={`Asset: ${maintenance.asset_item.category_name} (${maintenance.asset_item.serial_number})`}
                backPath="/ticketing/maintenances"
                mobileFullWidth
            >
                <form onSubmit={handleSubmit} className="space-y-8">
                    {/* Banner for Confirmed Status */}
                    {isConfirmed && (
                        <div className="flex items-center gap-3 rounded-xl bg-emerald-50 p-4 border border-emerald-100 dark:bg-emerald-900/10 dark:border-emerald-900/20">
                            <div className="flex size-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400">
                                <CheckCircle2 className="size-6" />
                            </div>
                            <div>
                                <h4 className="text-sm font-bold text-emerald-900 dark:text-emerald-100">Maintenance Terkonfirmasi</h4>
                                <p className="text-xs text-emerald-700 dark:text-emerald-400">
                                    Data maintenance ini telah dikonfirmasi dan tidak dapat diubah kembali.
                                </p>
                            </div>
                        </div>
                    )}

                    {/* Basic Info */}
                    <div className="space-y-6">
                        <div className="flex items-center gap-2 border-l-4 border-primary pl-3">
                            <h3 className="text-base font-bold text-slate-800 dark:text-white">Informasi Maintenance</h3>
                        </div>

                        <div className="grid grid-cols-1 gap-6">
                            <FormInput
                                name="actual_date"
                                type="date"
                                label="Tanggal Pelaksanaan"
                                value={data.actual_date}
                                onChange={(e) => setData('actual_date', e.target.value)}
                                error={errors.actual_date}
                                required
                                disabled={isConfirmed}
                            />
                            <FormTextArea
                                name="note"
                                label="Catatan Umum"
                                placeholder="Masukkan catatan umum maintenance jika ada..."
                                value={data.note}
                                onChange={(e) => setData('note', e.target.value)}
                                error={errors.note}
                                disabled={isConfirmed}
                            />
                        </div>
                    </div>

                    {/* Checklist Items */}
                    <div className="space-y-6">
                        <div className="flex items-center gap-2 border-l-4 border-primary pl-3">
                            <CheckSquare className="size-5 text-primary" />
                            <h3 className="text-base font-bold text-slate-800 dark:text-white">Item Pemeriksaan</h3>
                        </div>

                        <div className="space-y-4">
                            {data.checklists.map((item: any, index: number) => (
                                <div key={item.checklist_id} className="rounded-xl border border-slate-200 bg-slate-50 p-6 dark:border-slate-700 dark:bg-slate-800/40">
                                    <div className="flex flex-col gap-6 md:flex-row md:items-start md:justify-between">
                                        <div className="flex-1 space-y-1">
                                            <h4 className="text-sm font-bold text-slate-900 dark:text-white">{item.label}</h4>
                                            <p className="text-xs text-slate-500">{item.description || "Pastikan kondisi komponen ini dalam keadaan optimal sesuai standar."}</p>
                                        </div>

                                        <div className="grid grid-cols-2 gap-2 w-full md:w-auto">
                                            <button
                                                type="button"
                                                disabled={isConfirmed}
                                                onClick={() => updateChecklist(index, 'value', 'Baik')}
                                                className={`flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-xs font-bold transition-all ${item.value === 'Baik'
                                                    ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/20'
                                                    : 'bg-white text-slate-400 border border-slate-200 dark:bg-slate-800 dark:border-slate-700'
                                                    } ${isConfirmed ? 'cursor-not-allowed opacity-80' : ''} w-full`}
                                            >
                                                <CheckCircle2 className="size-4" />
                                                Baik
                                            </button>
                                            <button
                                                type="button"
                                                disabled={isConfirmed}
                                                onClick={() => updateChecklist(index, 'value', 'Tidak Baik')}
                                                className={`flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-xs font-bold transition-all ${item.value === 'Tidak Baik'
                                                    ? 'bg-rose-500 text-white shadow-lg shadow-rose-500/20'
                                                    : 'bg-white text-slate-400 border border-slate-200 dark:bg-slate-800 dark:border-slate-700'
                                                    } ${isConfirmed ? 'cursor-not-allowed opacity-80' : ''} w-full`}
                                            >
                                                <XCircle className="size-4" />
                                                Tidak Baik
                                            </button>
                                        </div>
                                    </div>

                                    <div className="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                                        <FormTextArea
                                            name={`checklists.${index}.note`}
                                            label="Catatan Item"
                                            placeholder="Berikan keterangan detail kondisi item ini..."
                                            value={item.note}
                                            onChange={(e) => updateChecklist(index, 'note', e.target.value)}
                                            rows={2}
                                            disabled={isConfirmed}
                                        />

                                        {item.value === 'Tidak Baik' && (
                                            <div className="space-y-2 animate-in fade-in slide-in-from-top-2 duration-300">
                                                <FormTextArea
                                                    name={`checklists.${index}.follow_up`}
                                                    label="Rencana Tindak Lanjut (Follow Up)"
                                                    placeholder="Tuliskan langkah perbaikan yang dibutuhkan..."
                                                    value={item.follow_up}
                                                    onChange={(e) => updateChecklist(index, 'follow_up', e.target.value)}
                                                    rows={2}
                                                    required
                                                    disabled={isConfirmed}
                                                />
                                            </div>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Evidence / Attachments */}
                    <div className="space-y-6">
                        <div className="flex items-center gap-2 border-l-4 border-primary pl-3">
                            <h3 className="text-base font-bold text-slate-800 dark:text-white">Bukti Maintenance</h3>
                        </div>

                        <FormFile
                            name="attachments"
                            label="Upload Foto / Dokumen Bukti"
                            multiple
                            accept="image/*,.pdf"
                            onChange={(e) => setData('attachments', Array.from(e.target.files || []))}
                            error={errors.attachments as unknown as string}
                            disabled={isConfirmed}
                            helpText="Upload foto pengerjaan atau dokumen pendukung lainnya. (Format: JPG, PNG, PDF)"
                            defaultFiles={maintenance.attachments || []}
                        />

                        {isConfirmed && maintenance.attachments && maintenance.attachments.length > 0 && (
                            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
                                {maintenance.attachments.map((file, idx) => (
                                    <a
                                        key={idx}
                                        href={file.url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="group relative aspect-square overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700"
                                    >
                                        <img src={file.url} alt={file.name} className="h-full w-full object-cover" />
                                        <div className="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 transition-opacity group-hover:opacity-100">
                                            <p className="w-full truncate px-2 text-center text-[10px] text-white font-medium">{file.name}</p>
                                        </div>
                                    </a>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Needs Further Repair Checkbox - only shown when there are 'Tidak Baik' items */}
                    {hasNotGood && !isConfirmed && (
                        <div className="animate-in fade-in slide-in-from-top-2 duration-300">
                            <div
                                onClick={() => setData('needs_further_repair', !data.needs_further_repair)}
                                className={`flex cursor-pointer items-center gap-4 rounded-xl border-2 p-5 transition-all duration-200 ${data.needs_further_repair
                                    ? 'border-amber-400 bg-amber-50 dark:border-amber-500/50 dark:bg-amber-900/10'
                                    : 'border-slate-200 bg-slate-50 hover:border-slate-300 dark:border-slate-700 dark:bg-slate-800/40 dark:hover:border-slate-600'
                                    }`}
                            >
                                <div className={`flex size-6 shrink-0 items-center justify-center rounded-md border-2 transition-all ${data.needs_further_repair
                                    ? 'border-amber-500 bg-amber-500 text-white'
                                    : 'border-slate-300 bg-white dark:border-slate-600 dark:bg-slate-800'
                                    }`}>
                                    {data.needs_further_repair && (
                                        <svg className="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={3}>
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    )}
                                </div>
                                <div className="flex-1">
                                    <div className="flex items-center gap-2">
                                        <Wrench className={`size-4 ${data.needs_further_repair ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400'}`} />
                                        <h4 className={`text-sm font-bold ${data.needs_further_repair ? 'text-amber-800 dark:text-amber-200' : 'text-slate-700 dark:text-slate-300'}`}>
                                            Perlu Perbaikan Lebih Lanjut
                                        </h4>
                                    </div>
                                    <p className={`mt-1 text-xs ${data.needs_further_repair ? 'text-amber-700 dark:text-amber-400' : 'text-slate-500 dark:text-slate-400'}`}>
                                        Perbaikan yang memerlukan proses lebih lanjut dalam waktu tertentu atau membutuhkan proses tertentu. Jika perbaikan sudah selesai dan aset berjalan dengan baik kembali, tidak perlu mencentang opsi ini.
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Action Buttons */}
                    <div className="flex justify-end gap-3 border-t border-slate-100 pt-6 dark:border-slate-800">
                        <Button
                            href="/ticketing/maintenances"
                            label={isConfirmed ? "Kembali" : "Batal"}
                            variant="secondary"
                            icon={<X className="size-4" />}
                        />
                        {!isConfirmed && (
                            <Button
                                type="submit"
                                label="Simpan Hasil Pemeriksaan"
                                icon={<Save className="size-4" />}
                                isLoading={processing}
                            />
                        )}
                    </div>
                </form>
            </ContentCard>
        </RootLayout>
    );
}
