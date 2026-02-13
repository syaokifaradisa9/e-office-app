import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import { useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import { HardDrive, Save, Shield, Building2, Search, TrendingUp, AlertTriangle, CheckCircle2 } from 'lucide-react';
import Button from '@/components/buttons/Button';
import CheckPermissions from '@/components/utils/CheckPermissions';
import FormInput from '@/components/forms/FormInput';
import FormSearch from '@/components/forms/FormSearch';
import Tooltip from '@/components/commons/Tooltip';
import { ArchievePermission } from '@/enums/ArchievePermission';

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
    const hasViewPermission = permissions?.includes(ArchievePermission.VIEW_DIVISION_STORAGE);

    const [editingId, setEditingId] = useState<number | null>(null);
    const [editValue, setEditValue] = useState<string>('');
    const [processing, setProcessing] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');

    const filteredDivisions = divisionsWithStorage.filter(d =>
        d.name.toLowerCase().includes(searchQuery.toLowerCase())
    );

    const onSearchChange = (e: { target: { value: string } }) => {
        setSearchQuery(e.target.value);
    };

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
                <ContentCard title="Akses Ditolak" mobileFullWidth>
                    <div className="flex flex-col items-center justify-center py-12 text-center">
                        <div className="mb-4 rounded-full bg-red-100 p-3 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                            <Shield className="size-8" />
                        </div>
                        <h3 className="text-lg font-semibold text-slate-900 dark:text-white">Akses Terbatas</h3>
                        <p className="mt-1 text-slate-500 dark:text-slate-400">Anda tidak memiliki izin untuk mengelola penyimpanan divisi.</p>
                    </div>
                </ContentCard>
            ) : (
                <ContentCard
                    title="Penyimpanan Divisi"
                    subtitle="Kelola dan pantau alokasi kuota penyimpanan untuk setiap divisi"
                    mobileFullWidth
                >
                    <div className="space-y-6">
                        {/* Search bar */}
                        <div className="flex items-center justify-end">
                            <div className="relative w-full max-w-sm">
                                <FormSearch
                                    name="search"
                                    placeholder="Cari divisi..."
                                    value={searchQuery}
                                    onChange={onSearchChange}
                                    className="!rounded-xl !pl-10"
                                />
                                <div className="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                                    <Search className="size-4 text-slate-400" />
                                </div>
                            </div>
                        </div>

                        {/* Cards Grid */}
                        <div className="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                            {filteredDivisions.map((division) => {
                                const isEditing = editingId === division.id;
                                const percentage = Math.min(division.usage_percentage, 100);
                                const status = percentage >= 90 ? 'critical' : percentage >= 70 ? 'warning' : 'stable';

                                return (
                                    <div
                                        key={division.id}
                                        className={`relative overflow-hidden rounded-2xl border bg-white p-5 transition-all duration-200 ${isEditing
                                            ? 'border-primary ring-2 ring-primary/10 shadow-lg'
                                            : 'border-slate-200 hover:border-primary/30 dark:border-slate-800 dark:bg-slate-900/50'
                                            }`}
                                    >
                                        <div className="flex flex-col gap-5">
                                            {/* Header */}
                                            <div className="flex items-start justify-between">
                                                <div className="flex items-center gap-3">
                                                    <div className={`flex size-10 items-center justify-center rounded-xl ${isEditing ? 'bg-primary text-white' : 'bg-slate-100 text-slate-500 dark:bg-slate-800'}`}>
                                                        <Building2 className="size-5" />
                                                    </div>
                                                    <div>
                                                        <h4 className="font-bold text-slate-900 dark:text-white line-clamp-1">
                                                            {division.name}
                                                        </h4>
                                                        <span className={`text-[10px] font-bold uppercase tracking-wider ${status === 'critical' ? 'text-rose-500' : status === 'warning' ? 'text-amber-500' : 'text-emerald-500'}`}>
                                                            {status === 'critical' ? 'Kapasitas Penuh' : status === 'warning' ? 'Hampir Penuh' : 'Penyimpanan Aman'}
                                                        </span>
                                                    </div>
                                                </div>

                                                {!isEditing && (
                                                    <CheckPermissions permissions={[ArchievePermission.MANAGE_DIVISION_STORAGE]}>
                                                        <Tooltip text="Atur Kapasitas">
                                                            <button
                                                                onClick={() => startEdit(division)}
                                                                className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-primary transition-colors dark:hover:bg-slate-800"
                                                            >
                                                                <HardDrive className="size-4" />
                                                            </button>
                                                        </Tooltip>
                                                    </CheckPermissions>
                                                )}
                                            </div>

                                            {/* Stats & Progress */}
                                            <div className="space-y-2">
                                                <div className="flex items-end justify-between text-xs">
                                                    <div className="text-slate-500">
                                                        <span className="font-bold text-slate-900 dark:text-white">{division.used_size_label}</span>
                                                        <span className="mx-1 text-slate-300">/</span>
                                                        {division.max_size > 0 ? division.max_size_label : <span className="text-lg leading-none">∞</span>}
                                                    </div>
                                                    <div className={`font-bold ${status === 'critical' ? 'text-rose-500' : status === 'warning' ? 'text-amber-500' : 'text-primary'}`}>
                                                        {division.max_size > 0 ? `${percentage}%` : '∞'}
                                                    </div>
                                                </div>

                                                <div className="h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                                    <div
                                                        className={`h-full transition-all duration-500 ${status === 'critical' ? 'bg-rose-500' : status === 'warning' ? 'bg-amber-500' : 'bg-primary'
                                                            }`}
                                                        style={{ width: `${percentage}%` }}
                                                    />
                                                </div>
                                            </div>

                                            {/* Edit Form */}
                                            {isEditing && (
                                                <div className="mt-2 space-y-3 animate-in fade-in zoom-in-95 duration-200">
                                                    <div className="relative">
                                                        <FormInput
                                                            name="max_size_gb"
                                                            type="number"
                                                            placeholder="0"
                                                            value={editValue}
                                                            onChange={(e) => setEditValue(e.target.value)}
                                                            className="!py-2 !text-sm font-bold"
                                                            autoFocus
                                                        />
                                                        <div className="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                                                            <span className="text-[10px] font-bold text-slate-400">GB</span>
                                                        </div>
                                                    </div>
                                                    <div className="flex gap-2">
                                                        <Button
                                                            label="Simpan"
                                                            onClick={() => saveEdit(division)}
                                                            isLoading={processing}
                                                            className="flex-1 !py-2 text-[10px] uppercase font-bold tracking-widest"
                                                            icon={<Save className="size-3" />}
                                                        />
                                                        <Button
                                                            label="Batal"
                                                            variant="secondary"
                                                            onClick={cancelEdit}
                                                            className="flex-1 !py-2 text-[10px] uppercase font-bold tracking-widest"
                                                        />
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>

                        {filteredDivisions.length === 0 && (
                            <div className="flex flex-col items-center justify-center py-16 text-center">
                                <div className="mb-4 rounded-full bg-slate-50 p-4 text-slate-300 dark:bg-slate-800">
                                    <Search className="size-8" />
                                </div>
                                <h3 className="font-bold text-slate-900 dark:text-white">Tidak ada hasil</h3>
                                <p className="text-xs text-slate-500">Coba kata kunci lain atau reset pencarian.</p>
                                <button
                                    onClick={() => setSearchQuery('')}
                                    className="mt-4 text-[10px] font-bold uppercase tracking-widest text-primary hover:underline"
                                >
                                    Reset Pencarian
                                </button>
                            </div>
                        )}
                    </div>
                </ContentCard>
            )}
        </RootLayout>
    );
}
