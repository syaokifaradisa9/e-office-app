import { usePage } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { ClipboardCheck, Check, User, Building2, Calendar, FileText, Package, ArrowUp, ArrowDown, Minus } from 'lucide-react';

import Button from '@/components/buttons/Button';
import RootLayout from '@/components/layouts/RootLayout';
import ContentCard from '@/components/layouts/ContentCard';

interface OpnameItem {
    id: number;
    item: {
        id: number;
        name: string;
        unit_of_measure: string;
    };
    system_stock: number;
    physical_stock: number;
    notes: string | null;
    final_stock: number | null;
    final_notes: string | null;
}

interface StockOpname {
    id: number;
    opname_date: string;
    notes: string | null;
    status: string;
    created_at: string;
    user: { id: number; name: string };
    division: { id: number; name: string } | null;
    items: OpnameItem[];
}

interface Props {
    opname: StockOpname;
}

interface PageProps {
    permissions?: string[];
    [key: string]: unknown;
}

const statusColors: Record<string, string> = {
    'Pending': 'bg-blue-100 text-blue-800 border-blue-200',
    'Proses': 'bg-yellow-100 text-yellow-800 border-yellow-200',
    'Stock Opname': 'bg-green-100 text-green-800 border-green-200',
    'Finish': 'bg-purple-100 text-purple-800 border-purple-200',
};

export default function StockOpnameShow({ opname }: Props) {
    const pageProps = usePage<PageProps>().props;
    const canProcess = pageProps.permissions?.includes('Proses Stock Opname');
    const canFinalize = pageProps.permissions?.includes('Finalisasi Stock Opname');

    const type = opname.division ? 'division' : 'warehouse';

    const showDetails = opname.status !== 'Pending';

    // Business Rule: Finalize allowed if status is 'Stock Opname' and NOT same day as opname_date
    const isPastOpnameDate = new Date(opname.opname_date).setHours(0, 0, 0, 0) < new Date().setHours(0, 0, 0, 0);
    const finalizeAllowed = opname.status === 'Stock Opname' && isPastOpnameDate;

    const formatDate = (dateString: string) => {
        if (!dateString) return '-';
        const datePart = dateString.includes('T') ? dateString.split('T')[0] : dateString;
        const date = new Date(datePart.replace(/-/g, '/'));
        if (isNaN(date.getTime())) return dateString;
        return date.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    };

    return (
        <RootLayout title="Detail Stock Opname" backPath={`/inventory/stock-opname/${type}`}>
            <ContentCard title="Detail Stock Opname" backPath={`/inventory/stock-opname/${type}`} mobileFullWidth bodyClassName="p-1 md:p-6">
                <div className="space-y-8">
                    {/* Header Info */}
                    <div>
                        <div className="mb-4 flex items-center justify-between">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-white">Informasi Umum</h3>
                            <span className={`inline-flex rounded-full border px-3 py-1 text-sm font-semibold ${statusColors[opname.status] || 'bg-gray-100 text-gray-800'}`}>
                                {opname.status}
                            </span>
                        </div>
                        <div className="grid gap-6 md:grid-cols-2">
                            <div className="flex items-start gap-3">
                                <Calendar className="size-5 text-gray-400" />
                                <div>
                                    <p className="text-sm text-gray-500 dark:text-slate-400">Tanggal Opname</p>
                                    <p className="font-medium text-gray-900 dark:text-white">
                                        {formatDate(opname.opname_date)}
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start gap-3">
                                <User className="size-5 text-gray-400" />
                                <div>
                                    <p className="text-sm text-gray-500 dark:text-slate-400">Petugas</p>
                                    <p className="font-medium text-gray-900 dark:text-white">{opname.user.name}</p>
                                </div>
                            </div>

                            <div className="flex items-start gap-3">
                                <Building2 className="size-5 text-gray-400" />
                                <div>
                                    <p className="text-sm text-gray-500 dark:text-slate-400">Divisi/Gudang</p>
                                    <p className="font-medium text-gray-900 dark:text-white">{opname.division?.name || 'Gudang Utama'}</p>
                                </div>
                            </div>

                            {opname.notes && (
                                <div className="flex items-start gap-3">
                                    <FileText className="size-5 text-gray-400" />
                                    <div>
                                        <p className="text-sm text-gray-500 dark:text-slate-400">Catatan</p>
                                        <p className="font-medium text-gray-900 dark:text-white">{opname.notes}</p>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    <hr className="border-gray-200 dark:border-slate-700" />

                    {/* Summary Cards */}
                    {showDetails && (
                        <>
                            <div className="grid gap-4 md:grid-cols-3">
                                <div className="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-slate-700 dark:bg-slate-800/50">
                                    <p className="text-sm text-gray-500 dark:text-slate-400">Total Barang</p>
                                    <p className="text-2xl font-bold text-gray-900 dark:text-white">{opname.items.length}</p>
                                </div>
                                <div className="rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-900 dark:bg-green-900/20">
                                    <p className="text-sm text-green-700 dark:text-green-400">Stok Lebih</p>
                                    <p className="text-2xl font-bold text-green-700 dark:text-green-400">
                                        {opname.items.filter((i) => i.physical_stock - i.system_stock > 0).length}
                                    </p>
                                </div>
                                <div className="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-900/20">
                                    <p className="text-sm text-red-700 dark:text-red-400">Stok Kurang</p>
                                    <p className="text-2xl font-bold text-red-700 dark:text-red-400">
                                        {opname.items.filter((i) => i.physical_stock - i.system_stock < 0).length}
                                    </p>
                                </div>
                            </div>
                            <hr className="border-gray-200 dark:border-slate-700" />
                        </>
                    )}

                    {/* Items List */}
                    <div>
                        <h3 className="mb-4 text-lg font-medium text-gray-900 dark:text-white">Daftar Barang</h3>

                        {/* Desktop Table */}
                        <div className="hidden overflow-hidden rounded-lg border border-gray-200 dark:border-slate-700 md:block">
                            <table className="w-full">
                                <thead className="bg-gray-50 dark:bg-slate-700/50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-slate-400">Barang</th>
                                        {showDetails && (
                                            <>
                                                <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-slate-400">Stok Awal</th>
                                                <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-slate-400">Stok Opname</th>
                                                <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-slate-400">Selisih SO</th>
                                                {opname.status === 'Finish' && (
                                                    <>
                                                        <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-slate-400 font-bold text-primary">Stok Final</th>
                                                        <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-slate-400">Selisih Fin.</th>
                                                    </>
                                                )}
                                            </>
                                        )}
                                        {!showDetails && (
                                            <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-slate-400">Aksi Rekon</th>
                                        )}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white dark:divide-slate-700 dark:bg-slate-800">
                                    {opname.items.map((item) => (
                                        <tr key={item.id}>
                                            <td className="whitespace-nowrap px-6 py-4">
                                                <div className="flex items-center gap-3">
                                                    <Package className="size-5 text-primary" />
                                                    <div>
                                                        <p className="font-medium text-gray-900 dark:text-white">{item.item.name}</p>
                                                        <p className="text-sm text-gray-500">{item.item.unit_of_measure}</p>
                                                        {item.notes && <p className="mt-1 text-xs italic text-gray-400">SO: {item.notes}</p>}
                                                        {item.final_notes && <p className="mt-1 text-xs italic text-blue-400 text-opacity-80">Fin: {item.final_notes}</p>}
                                                    </div>
                                                </div>
                                            </td>
                                            {showDetails && (
                                                <>
                                                    <td className="whitespace-nowrap px-6 py-4 text-right text-gray-500 dark:text-slate-400">{item.system_stock}</td>
                                                    <td className="whitespace-nowrap px-6 py-4 text-right font-medium text-gray-900 dark:text-white">{item.physical_stock}</td>
                                                    <td className="whitespace-nowrap px-6 py-4 text-right">
                                                        {(() => {
                                                            const diff = item.physical_stock - item.system_stock;
                                                            return (
                                                                <div className="flex items-center justify-end gap-1 text-sm">
                                                                    {diff < 0 ? (
                                                                        <span className="font-semibold text-red-600">{diff}</span>
                                                                    ) : diff > 0 ? (
                                                                        <span className="font-semibold text-green-600">+{diff}</span>
                                                                    ) : (
                                                                        <span className="text-gray-400">0</span>
                                                                    )}
                                                                </div>
                                                            );
                                                        })()}
                                                    </td>
                                                    {opname.status === 'Finish' && (
                                                        <>
                                                            <td className="whitespace-nowrap px-6 py-4 text-right font-bold text-primary">{item.final_stock}</td>
                                                            <td className="whitespace-nowrap px-6 py-4 text-right">
                                                                {(() => {
                                                                    const finDiff = (item.final_stock ?? 0) - item.system_stock;
                                                                    return (
                                                                        <div className="flex items-center justify-end gap-1 text-sm">
                                                                            {finDiff < 0 ? (
                                                                                <span className="font-semibold text-red-600">{finDiff}</span>
                                                                            ) : finDiff > 0 ? (
                                                                                <span className="font-semibold text-green-600">+{finDiff}</span>
                                                                            ) : (
                                                                                <span className="text-gray-400">0</span>
                                                                            )}
                                                                        </div>
                                                                    );
                                                                })()}
                                                            </td>
                                                        </>
                                                    )}
                                                </>
                                            )}
                                            {!showDetails && (
                                                <td className="whitespace-nowrap px-6 py-4 text-right text-gray-400 italic text-sm">Belum diproses</td>
                                            )}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Mobile List (New) */}
                        <div className="space-y-4 md:hidden">
                            {opname.items.map((item) => (
                                <div key={item.id} className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                                    <div className="mb-3 flex items-start justify-between">
                                        <div>
                                            <div className="font-medium text-gray-900 dark:text-white">{item.item.name}</div>
                                            <div className="text-xs text-gray-500">{item.item.unit_of_measure}</div>
                                        </div>
                                        {showDetails && (
                                            <div className={`rounded px-2 py-1 text-xs font-medium ${(() => {
                                                const diff = item.system_stock - item.physical_stock;
                                                return diff > 0 ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' :
                                                    diff < 0 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' :
                                                        'bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-gray-300';
                                            })()}`}>
                                                {(() => {
                                                    const diff = item.system_stock - item.physical_stock;
                                                    return `Selisih: ${diff > 0 ? '+' : ''}${diff}`;
                                                })()}
                                            </div>
                                        )}
                                    </div>

                                    <div className="grid grid-cols-2 gap-y-3 gap-x-4 border-t border-gray-100 pt-3 text-sm dark:border-slate-700">
                                        {showDetails && (
                                            <>
                                                <div>
                                                    <div className="text-[10px] text-gray-500 uppercase">Stok Awal</div>
                                                    <div className="font-medium dark:text-slate-300">{item.system_stock}</div>
                                                </div>
                                                <div>
                                                    <div className="text-[10px] text-gray-500 uppercase">Stok Opname</div>
                                                    <div className="font-medium dark:text-white">{item.physical_stock}</div>
                                                </div>
                                                <div>
                                                    <div className="text-[10px] text-gray-500 uppercase">Selisih SO</div>
                                                    {(() => {
                                                        const diff = item.physical_stock - item.system_stock;
                                                        return (
                                                            <div className={`font-semibold ${diff < 0 ? 'text-red-600' : diff > 0 ? 'text-green-600' : 'text-gray-400'}`}>
                                                                {diff > 0 ? `+${diff}` : diff}
                                                            </div>
                                                        );
                                                    })()}
                                                </div>
                                                {opname.status === 'Finish' && (
                                                    <>
                                                        <div className="bg-primary/5 p-1 rounded">
                                                            <div className="text-[10px] text-primary uppercase font-bold">Stok Final</div>
                                                            <div className="font-bold text-primary">{item.final_stock}</div>
                                                        </div>
                                                        <div>
                                                            <div className="text-[10px] text-gray-500 uppercase">Selisih Fin.</div>
                                                            {(() => {
                                                                const finDiff = (item.final_stock ?? 0) - item.system_stock;
                                                                return (
                                                                    <div className={`font-semibold ${finDiff < 0 ? 'text-red-600' : finDiff > 0 ? 'text-green-600' : 'text-gray-400'}`}>
                                                                        {finDiff > 0 ? `+${finDiff}` : finDiff}
                                                                    </div>
                                                                );
                                                            })()}
                                                        </div>
                                                    </>
                                                )}
                                            </>
                                        )}
                                        {!showDetails && (
                                            <div className="col-span-2 text-center text-xs text-gray-400 italic">Belum diproses</div>
                                        )}
                                    </div>

                                    {(item.notes || item.final_notes) && (
                                        <div className="mt-2 space-y-2">
                                            {item.notes && (
                                                <div className="rounded bg-gray-50 p-2 text-sm text-gray-600 dark:bg-slate-900/50 dark:text-slate-400">
                                                    <span className="mb-1 block text-[10px] font-bold text-gray-500 uppercase">Catatan SO:</span>
                                                    {item.notes}
                                                </div>
                                            )}
                                            {item.final_notes && (
                                                <div className="rounded bg-blue-50/30 border border-blue-100 dark:bg-blue-900/10 dark:border-blue-900/30 p-2 text-sm text-blue-700 dark:text-blue-400">
                                                    <span className="mb-1 block text-[10px] font-bold text-blue-500 uppercase">Catatan Finalisasi:</span>
                                                    {item.final_notes}
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>

                    {(canProcess || canFinalize) && (
                        <div className="border-t border-gray-200 pt-6 dark:border-slate-700">
                            {canProcess && opname.status === 'Pending' && (
                                <>
                                    <Button
                                        onClick={() => router.get(`/inventory/stock-opname/${type}/${opname.id}/process`)}
                                        label="Input Hasil Opname"
                                        icon={<ClipboardCheck className="size-4" />}
                                        className="w-full justify-center"
                                    />
                                    <p className="mt-2 text-sm text-gray-500">
                                        Mulai masukkan hasil perhitungan fisik stok untuk opname ini.
                                    </p>
                                </>
                            )}

                            {canFinalize && finalizeAllowed && (
                                <>
                                    <Button
                                        onClick={() => router.get(`/inventory/stock-opname/${type}/${opname.id}/finalize`)}
                                        label="Finalisasi Stok Opname"
                                        icon={<Check className="size-4" />}
                                        className="w-full justify-center"
                                        variant="primary"
                                    />
                                    <p className="mt-2 text-sm text-gray-500">
                                        Lakukan penyesuaian stok akhir sistem berdasarkan hasil opname ini.
                                    </p>
                                </>
                            )}
                        </div>
                    )}
                </div>
            </ContentCard>
        </RootLayout>
    );
}
