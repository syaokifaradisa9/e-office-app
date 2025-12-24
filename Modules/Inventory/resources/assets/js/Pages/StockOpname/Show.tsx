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
    difference: number;
    notes: string | null;
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
    Draft: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    Confirmed: 'bg-green-100 text-green-800 border-green-200',
};

export default function StockOpnameShow({ opname }: Props) {
    const pageProps = usePage<PageProps>().props;
    const canConfirm = pageProps.permissions?.includes('konfirmasi_stock_opname');

    const type = opname.division ? 'division' : 'warehouse';

    const showDetails = opname.status !== 'Draft' || canConfirm;

    return (
        <RootLayout title="Detail Stock Opname" backPath={`/inventory/stock-opname/${type}`}>
            <ContentCard title="Detail Stock Opname" backPath={`/inventory/stock-opname/${type}`} mobileFullWidth>
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
                                        {new Date(opname.opname_date).toLocaleDateString('id-ID', {
                                            day: 'numeric',
                                            month: 'long',
                                            year: 'numeric',
                                        })}
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
                                        {opname.items.filter((i) => i.difference > 0).length}
                                    </p>
                                </div>
                                <div className="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-900/20">
                                    <p className="text-sm text-red-700 dark:text-red-400">Stok Kurang</p>
                                    <p className="text-2xl font-bold text-red-700 dark:text-red-400">
                                        {opname.items.filter((i) => i.difference < 0).length}
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
                                                <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-slate-400">Stok Sistem</th>
                                            </>
                                        )}
                                        <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-slate-400">Stok Fisik</th>
                                        {showDetails && (
                                            <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-slate-400">Selisih</th>
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
                                                        {item.notes && <p className="mt-1 text-xs italic text-gray-400">{item.notes}</p>}
                                                    </div>
                                                </div>
                                            </td>
                                            {showDetails && (
                                                <td className="whitespace-nowrap px-6 py-4 text-right text-gray-500 dark:text-slate-400">{item.system_stock}</td>
                                            )}
                                            <td className="whitespace-nowrap px-6 py-4 text-right font-medium text-gray-900 dark:text-white">{item.physical_stock}</td>
                                            {showDetails && (
                                                <td className="whitespace-nowrap px-6 py-4 text-right">
                                                    <div className="flex items-center justify-end gap-1">
                                                        {item.difference > 0 ? (
                                                            <>
                                                                <ArrowUp className="size-4 text-green-500" />
                                                                <span className="font-semibold text-green-600">+{item.difference}</span>
                                                            </>
                                                        ) : item.difference < 0 ? (
                                                            <>
                                                                <ArrowDown className="size-4 text-red-500" />
                                                                <span className="font-semibold text-red-600">{item.difference}</span>
                                                            </>
                                                        ) : (
                                                            <>
                                                                <Minus className="size-4 text-gray-400" />
                                                                <span className="text-gray-500">0</span>
                                                            </>
                                                        )}
                                                    </div>
                                                </td>
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
                                            <div className={`rounded px-2 py-1 text-xs font-medium ${item.difference < 0 ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' :
                                                item.difference > 0 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' :
                                                    'bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-gray-300'
                                                }`}>
                                                Selisih: {item.difference > 0 ? '+' : ''}{item.difference}
                                            </div>
                                        )}
                                    </div>

                                    <div className="grid grid-cols-2 gap-4 border-t border-gray-100 pt-3 text-sm dark:border-slate-700">
                                        {showDetails && (
                                            <div>
                                                <div className="text-xs text-gray-500">Stok Sistem</div>
                                                <div className="font-medium dark:text-slate-300">{item.system_stock}</div>
                                            </div>
                                        )}
                                        <div className={!showDetails ? 'col-span-2' : ''}>
                                            <div className="text-xs text-gray-500">Stok Fisik</div>
                                            <div className="font-medium dark:text-white">{item.physical_stock}</div>
                                        </div>
                                    </div>

                                    {item.notes && (
                                        <div className="mt-2 rounded bg-gray-50 p-2 text-sm text-gray-600 dark:bg-slate-900/50 dark:text-slate-400">
                                            <span className="mb-1 block text-xs font-medium text-gray-500">Catatan:</span>
                                            {item.notes}
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>

                    {canConfirm && opname.status === 'Draft' && (
                        <div className="border-t border-gray-200 pt-6 dark:border-slate-700">
                            <Button
                                onClick={() => router.post(`/inventory/stock-opname/${opname.id}/confirm`)}
                                label="Konfirmasi Stock Opname"
                                icon={<Check className="size-4" />}
                                className="w-full justify-center"
                            />
                            <p className="mt-2 text-sm text-gray-500">
                                Konfirmasi akan menyesuaikan stok barang sesuai dengan stok fisik yang tercatat.
                            </p>
                        </div>
                    )}
                </div>
            </ContentCard>
        </RootLayout>
    );
}
