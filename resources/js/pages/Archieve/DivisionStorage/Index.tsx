import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import { useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import { HardDrive, Save, Shield, Building2 } from 'lucide-react';
import Button from '@/components/buttons/Button';
import CheckPermissions from '@/components/utils/CheckPermissions';
import FormInput from '@/components/forms/FormInput';

interface DivisionWithStorage {
    id: number;
    name: string;
    storage_id: number | null;
    max_size: number;
    max_size_gb: number;
    max_size_label: string;
    used_size: number;
    used_size_label: string;
    usage_percentage: number;
}

interface PageProps {
    permissions?: string[];
    divisionsWithStorage: DivisionWithStorage[];
    [key: string]: unknown;
}

export default function DivisionStorageIndex() {
    const { permissions, divisionsWithStorage } = usePage<PageProps>().props;
    const hasViewPermission = permissions?.includes('lihat_penyimpanan_divisi');

    const [editingId, setEditingId] = useState<number | null>(null);
    const [editValue, setEditValue] = useState<string>('');
    const [processing, setProcessing] = useState(false);

    function startEdit(division: DivisionWithStorage) {
        setEditingId(division.id);
        setEditValue(division.max_size_gb.toString());
    }

    function cancelEdit() {
        setEditingId(null);
        setEditValue('');
    }

    function saveEdit(division: DivisionWithStorage) {
        setProcessing(true);

        const data = {
            division_id: division.id,
            max_size_gb: parseFloat(editValue) || 0,
        };

        if (division.storage_id) {
            router.put(`/archieve/division-storages/${division.storage_id}`, data, {
                onSuccess: () => {
                    setEditingId(null);
                    setEditValue('');
                    setProcessing(false);
                },
                onError: () => setProcessing(false),
            });
        } else {
            router.post('/archieve/division-storages', data, {
                onSuccess: () => {
                    setEditingId(null);
                    setEditValue('');
                    setProcessing(false);
                },
                onError: () => setProcessing(false),
            });
        }
    }

    return (
        <RootLayout title="Penyimpanan Divisi">
            {!hasViewPermission ? (
                <ContentCard title="Kuota Penyimpanan Divisi">
                    <div className="flex flex-col items-center justify-center py-20 text-center">
                        <div className="mb-4 rounded-full bg-slate-100 p-4 text-slate-400 dark:bg-slate-800">
                            <Shield className="size-10" />
                        </div>
                        <h3 className="text-xl font-bold text-slate-900 dark:text-white">Akses Terbatas</h3>
                        <p className="mt-2 text-slate-500 dark:text-slate-400">Silahkan hubungi administrator untuk akses penyimpanan divisi.</p>
                    </div>
                </ContentCard>
            ) : (
                <div className="space-y-8 pb-12">
                    {/* Header Minimalist */}
                    <div className="flex flex-col gap-6 border-b border-slate-200 pb-8 dark:border-slate-800 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <h2 className="text-3xl font-black tracking-tight text-slate-900 dark:text-white">Penyimpanan Divisi</h2>
                            <p className="mt-2 text-sm font-medium text-slate-500">Alokasikan kapasitas ruang arsip digital untuk setiap divisi.</p>
                        </div>

                        <div className="flex flex-wrap gap-3">
                            {[
                                { label: 'Total Divisi', value: divisionsWithStorage.length, icon: Building2 },
                                { label: 'Kritis', value: divisionsWithStorage.filter(d => d.usage_percentage >= 90).length, color: 'text-rose-500', icon: Shield },
                            ].map((stat, i) => (
                                <div key={i} className="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-2.5 dark:border-slate-800 dark:bg-slate-900/50">
                                    <stat.icon className="size-4 text-slate-400" />
                                    <div className="flex flex-col">
                                        <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 leading-none mb-1">{stat.label}</span>
                                        <span className={`text-base font-black leading-none ${stat.color || 'text-slate-900 dark:text-white'}`}>{stat.value}</span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        {divisionsWithStorage.map((division) => {
                            const isEditing = editingId === division.id;
                            const percentage = Math.min(division.usage_percentage, 100);
                            const status = percentage >= 90 ? 'critical' : percentage >= 70 ? 'warning' : 'stable';

                            return (
                                <div
                                    key={division.id}
                                    className={`group flex flex-col rounded-3xl border border-slate-200 bg-white p-6 transition-all duration-300 hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900/40 dark:hover:border-slate-700 ${isEditing ? 'ring-2 ring-primary ring-offset-4 dark:ring-offset-slate-950' : ''
                                        }`}
                                >
                                    <div className="mb-6 flex items-start justify-between">
                                        <div className="flex items-center gap-4">
                                            <div className="flex size-14 items-center justify-center rounded-2xl bg-slate-50 text-slate-300 transition-colors group-hover:bg-primary/5 group-hover:text-primary dark:bg-slate-800">
                                                <Building2 className="size-7" strokeWidth={1.5} />
                                            </div>
                                            <div>
                                                <h4 className="text-lg font-bold text-slate-900 dark:text-white leading-tight">
                                                    {division.name}
                                                </h4>
                                                <p className="mt-1 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                                    {division.used_size_label} <span className="opacity-20 mx-1">/</span> {division.max_size > 0 ? division.max_size_label : '∞'}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Progress Area */}
                                    <div className="mb-8 flex-1">
                                        <div className="mb-2.5 flex items-center justify-between">
                                            <span className="text-[10px] font-black uppercase tracking-widest text-slate-400">Penyimpanan Terpakai</span>
                                            <span className={`text-sm font-black tabular-nums ${status === 'critical' ? 'text-rose-500' : status === 'warning' ? 'text-amber-500' : 'text-emerald-500'
                                                }`}>
                                                {division.usage_percentage}%
                                            </span>
                                        </div>
                                        <div className="relative h-1 w-full rounded-full bg-slate-100 dark:bg-slate-800">
                                            <div
                                                className={`absolute inset-y-0 left-0 rounded-full transition-all duration-1000 ease-in-out ${status === 'critical' ? 'bg-rose-500' : status === 'warning' ? 'bg-amber-500' : 'bg-primary'
                                                    }`}
                                                style={{ width: `${percentage}%` }}
                                            />
                                        </div>
                                    </div>

                                    <div className="mt-auto">
                                        {isEditing ? (
                                            <div className="space-y-4 animate-in fade-in zoom-in-95 duration-200">
                                                <div className="relative">
                                                    <FormInput
                                                        name="max_size_gb"
                                                        type="number"
                                                        placeholder="0"
                                                        value={editValue}
                                                        onChange={(e) => setEditValue(e.target.value)}
                                                        className="!py-2.5 !text-sm font-bold focus:!border-primary"
                                                    />
                                                    <span className="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 uppercase tracking-tighter">GB</span>
                                                </div>
                                                <div className="flex gap-2">
                                                    <Button
                                                        label="Simpan"
                                                        onClick={() => saveEdit(division)}
                                                        isLoading={processing}
                                                        className="flex-1 !py-2.5 text-xs font-bold"
                                                    />
                                                    <Button
                                                        label="Batal"
                                                        variant="secondary"
                                                        onClick={cancelEdit}
                                                        className="!py-2.5 text-xs font-bold"
                                                    />
                                                </div>
                                            </div>
                                        ) : (
                                            <CheckPermissions permissions={['kelola_penyimpanan_divisi']}>
                                                <button
                                                    onClick={() => startEdit(division)}
                                                    className="inline-flex items-center gap-2 text-xs font-black uppercase tracking-widest text-primary hover:opacity-70 transition-opacity"
                                                >
                                                    <HardDrive className="size-3.5" />
                                                    {division.max_size > 0 ? 'Edit Kuota' : 'Setel Kuota'}
                                                </button>
                                            </CheckPermissions>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}
        </RootLayout>
    );
}
