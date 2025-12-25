import { RefreshCw, LogOut } from 'lucide-react';
import { Link } from '@inertiajs/react';

interface Item {
    id: number;
    name: string;
    stock: number;
    unit_of_measure: string;
    multiplier: number;
    division?: { id: number; name: string } | null;
    category?: { id: number; name: string } | null;
    reference_item?: { id: number; name: string; unit_of_measure: string } | null;
}

interface Props {
    item: Item;
    canConvert: boolean;
    canIssue: boolean;
}

export default function StockMonitoringCardItem({ item, canConvert, canIssue }: Props) {
    const hasAnyAction = canConvert || canIssue;

    return (
        <div className="transition-colors hover:bg-gray-50 dark:hover:bg-slate-700/30">
            <div className="flex flex-col gap-1 px-4 py-4">
                {/* Header: Name */}
                <div className="flex items-center gap-2">
                    <span className="truncate text-base font-semibold text-gray-900 dark:text-white">{item.name}</span>
                </div>

                {/* Subtitle: Division & Category */}
                <div className="truncate text-sm text-gray-500 dark:text-slate-400">
                    {item.division?.name || 'Gudang Utama'} • {item.category?.name || '-'}
                </div>

                {/* Details: Quantity & Conversion */}
                <div className="mb-2 flex flex-wrap items-center gap-2 text-sm text-gray-600 dark:text-slate-300">
                    <span className={`font-medium ${item.stock <= 0 ? 'text-red-500' : ''}`}>
                        {item.stock} {item.unit_of_measure}
                    </span>
                    {item.multiplier > 1 && item.reference_item && (
                        <>
                            <span className="text-gray-300 dark:text-slate-600">•</span>
                            <span className="text-gray-400 dark:text-gray-500">
                                (1 {item.unit_of_measure} = {item.multiplier} {item.reference_item.unit_of_measure} {item.reference_item.name})
                            </span>
                        </>
                    )}
                </div>

                {/* Footer: Actions */}
                {hasAnyAction && (
                    <div className="mt-2 flex flex-wrap items-center justify-end gap-2 pt-2">
                        {/* Konversi Stok */}
                        {canConvert && (
                            <Link
                                href={`/inventory/stock-monitoring/${item.id}/convert`}
                                className="flex items-center gap-1 rounded-lg bg-green-50 px-3 py-1.5 text-xs font-medium text-green-600 transition-colors hover:bg-green-100 dark:bg-green-900/20 dark:text-green-400 dark:hover:bg-green-900/30"
                            >
                                <RefreshCw className="size-3.5" />
                                Konversi
                            </Link>
                        )}

                        {/* Pengeluaran Barang */}
                        {canIssue && (
                            <Link
                                href={`/inventory/stock-monitoring/${item.id}/issue`}
                                className="flex items-center gap-1 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30"
                            >
                                <LogOut className="size-3.5" />
                                Pengeluaran
                            </Link>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
