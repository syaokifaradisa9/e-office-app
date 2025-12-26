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
    const hasManagePermission = permissions?.includes('kelola_penyimpanan_divisi');

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

    function getProgressColor(percentage: number) {
        if (percentage >= 90) return 'bg-red-500';
        if (percentage >= 70) return 'bg-amber-500';
        return 'bg-emerald-500';
    }

    return (
        <RootLayout title="Penyimpanan Divisi">
            <ContentCard title="Kuota Penyimpanan Divisi" mobileFullWidth>
                <p className="mb-6 text-sm text-gray-500 dark:text-slate-400">
                    Atur batas maksimal penyimpanan arsip untuk setiap divisi
                </p>

                {!hasViewPermission ? (
                    <div className="flex flex-col items-center justify-center py-12 text-center">
                        <div className="mb-4 rounded-full bg-red-100 p-3 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                            <Shield className="size-8" />
                        </div>
                        <h3 className="text-lg font-semibold text-slate-900 dark:text-white">Akses Ditolak</h3>
                        <p className="mt-1 text-slate-500 dark:text-slate-400">Anda tidak memiliki akses untuk melihat data penyimpanan divisi</p>
                    </div>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {divisionsWithStorage.map((division) => (
                            <div
                                key={division.id}
                                className="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all hover:border-primary/30 hover:shadow-md dark:border-slate-800 dark:bg-slate-900/50"
                            >
                                <div className="mb-4 flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                            <Building2 className="size-5" />
                                        </div>
                                        <div>
                                            <h4 className="font-bold text-slate-800 dark:text-white">{division.name}</h4>
                                            <p className="text-xs text-slate-500">
                                                {division.used_size_label} / {division.max_size > 0 ? division.max_size_label : 'Tidak terbatas'}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {/* Progress Bar */}
                                {division.max_size > 0 && (
                                    <div className="mb-4">
                                        <div className="mb-1 flex justify-between text-xs">
                                            <span className="text-slate-500">Penggunaan</span>
                                            <span className={`font-bold ${division.usage_percentage >= 90 ? 'text-red-500' : division.usage_percentage >= 70 ? 'text-amber-500' : 'text-emerald-500'}`}>
                                                {division.usage_percentage}%
                                            </span>
                                        </div>
                                        <div className="h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                            <div
                                                className={`h-full transition-all ${getProgressColor(division.usage_percentage)}`}
                                                style={{ width: `${Math.min(division.usage_percentage, 100)}%` }}
                                            />
                                        </div>
                                    </div>
                                )}

                                {/* Edit Form or Display */}
                                {editingId === division.id ? (
                                    <div className="space-y-3">
                                        <div className="flex items-center gap-2">
                                            <FormInput
                                                name="max_size_gb"
                                                type="number"
                                                placeholder="0"
                                                value={editValue}
                                                onChange={(e) => setEditValue(e.target.value)}
                                                className="!py-2"
                                            />
                                            <span className="text-sm font-medium text-slate-500">GB</span>
                                        </div>
                                        <div className="flex gap-2">
                                            <Button
                                                label="Simpan"
                                                icon={<Save className="size-3.5" />}
                                                onClick={() => saveEdit(division)}
                                                isLoading={processing}
                                                className="flex-1 !py-2 text-xs"
                                            />
                                            <Button
                                                label="Batal"
                                                variant="secondary"
                                                onClick={cancelEdit}
                                                className="!py-2 text-xs"
                                            />
                                        </div>
                                    </div>
                                ) : (
                                    <CheckPermissions permissions={['kelola_penyimpanan_divisi']}>
                                        <Button
                                            label={division.max_size > 0 ? 'Ubah Kuota' : 'Atur Kuota'}
                                            icon={<HardDrive className="size-3.5" />}
                                            onClick={() => startEdit(division)}
                                            variant={division.max_size > 0 ? 'secondary' : 'primary'}
                                            className="w-full !py-2 text-xs"
                                        />
                                    </CheckPermissions>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </ContentCard>
        </RootLayout>
    );
}
