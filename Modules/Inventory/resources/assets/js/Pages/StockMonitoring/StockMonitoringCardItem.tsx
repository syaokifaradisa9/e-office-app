import { RefreshCw, LogOut, Package } from 'lucide-react';
import { Link } from '@inertiajs/react';

interface Item {
    id: number;
    name: string;
    stock: number;
    unit_of_measure: string;
    multiplier: number;
    division?: { id: number; name: string } | null;
    category?: { id: number; name: string } | null;
    reference_item?: { unit_of_measure: string } | null;
}

interface Props {
    item: Item;
    canConvert: boolean;
    canIssue: boolean;
}

export default function StockMonitoringCardItem({ item, canConvert, canIssue }: Props) {
    const hasAnyAction = canConvert || canIssue;

    return (
        <div className="group transition-colors duration-150 hover:bg-slate-50/80 dark:hover:bg-slate-700/20">
            <div className="flex items-start gap-3.5 px-4 py-4">
                {/* Icon */}
                <div className="mt-0.5 flex size-10 flex-shrink-0 items-center justify-center rounded-xl bg-primary/10 dark:bg-primary/15">
                    <Package className="size-5 text-primary" />
                </div>

                {/* Content */}
                <div className="min-w-0 flex-1">
                    {/* Header: Name & Division */}
                    <div className="min-w-0">
                        <div className="flex items-start justify-between gap-2">
                            <div className="min-w-0">
                                <h3 className="truncate text-[15px] font-semibold text-slate-800 dark:text-white">{item.name}</h3>
                                <p className="mt-0.5 text-[12px] font-medium text-slate-500 dark:text-slate-400">
                                    {item.division?.name || 'Gudang Utama'} • {item.category?.name || '-'}
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Conversion Details */}
                    {item.multiplier > 1 && item.reference_item && (
                        <p className="mt-1 text-[11px] leading-relaxed text-slate-400 dark:text-slate-500">
                            1 {item.unit_of_measure} = {item.multiplier} {item.reference_item.unit_of_measure}
                        </p>
                    )}

                    {/* Stock Indicator */}
                    <div className="mt-3 flex items-center">
                        <div className={`flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[12px] font-semibold ${item.stock <= 0 ? 'bg-red-50 text-red-600 dark:bg-red-900/25 dark:text-red-400' : item.stock <= 10 ? 'bg-yellow-50 text-yellow-600 dark:bg-yellow-900/25 dark:text-yellow-400' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/25 dark:text-emerald-400'}`}>
                            <div className="size-1.5 rounded-full bg-current opacity-50" />
                            <span>Stok: {item.stock} {item.unit_of_measure}</span>
                        </div>
                    </div>

                    {/* Actions */}
                    {hasAnyAction && (
                        <div className={`mt-4 grid gap-2 ${(() => {
                                const count = [canConvert, canIssue].filter(Boolean).length;
                                return `grid-cols-${count || 1}`;
                            })()
                            }`}>
                            {canConvert && (
                                <Link
                                    href={`/inventory/stock-monitoring/${item.id}/convert`}
                                    className="flex items-center justify-center gap-1.5 rounded-lg border border-emerald-200 px-3 py-2 text-[13px] font-medium text-emerald-600 transition-colors hover:bg-emerald-50 dark:border-emerald-800/50 dark:text-emerald-400 dark:hover:bg-emerald-900/20"
                                >
                                    <RefreshCw className="size-3.5" />
                                    Konversi
                                </Link>
                            )}
                            {canIssue && (
                                <Link
                                    href={`/inventory/stock-monitoring/${item.id}/issue`}
                                    className="flex items-center justify-center gap-1.5 rounded-lg border border-red-200 px-3 py-2 text-[13px] font-medium text-red-500 transition-colors hover:bg-red-50 dark:border-red-800/50 dark:text-red-400 dark:hover:bg-red-900/20"
                                >
                                    <LogOut className="size-3.5" />
                                    Keluar
                                </Link>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
