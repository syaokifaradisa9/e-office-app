import { ArrowRightLeft, ArrowDownRight, ArrowUpRight, Package, Calendar, User } from 'lucide-react';

interface ItemTransaction {
    id: number;
    date: string;
    type: string;
    item: string;
    quantity: number;
    user: string | null;
    description: string | null;
    division?: string | null;
}

interface Props {
    item: ItemTransaction;
}

export default function ItemTransactionCardItem({ item }: Props) {
    // Determine icon and color based on transaction type
    let TypeIcon = ArrowRightLeft;
    let typeColor = 'text-slate-500 bg-slate-100 dark:bg-slate-800/50 dark:text-slate-400';
    let isPositive = false;

    if (item.type === 'Barang Masuk' || item.type === 'Konversi Masuk' || item.type === 'Stock Opname (Lebih)') {
        TypeIcon = ArrowDownRight;
        typeColor = 'text-emerald-600 bg-emerald-50 dark:bg-emerald-900/25 dark:text-emerald-400';
        isPositive = true;
    } else if (item.type === 'Barang Keluar' || item.type === 'Konversi Keluar' || item.type === 'Stock Opname (Kurang)') {
        TypeIcon = ArrowUpRight;
        typeColor = 'text-red-500 bg-red-50 dark:bg-red-900/25 dark:text-red-400';
        isPositive = false;
    } else {
        TypeIcon = ArrowRightLeft;
        typeColor = 'text-blue-600 bg-blue-50 dark:bg-blue-900/25 dark:text-blue-400';
    }

    const formatDate = (dateString: string) => {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });
    };

    return (
        <div className="group transition-colors duration-150 hover:bg-slate-50/80 dark:hover:bg-slate-700/20">
            <div className="flex items-start gap-3.5 px-4 py-4">
                {/* Type Icon Box */}
                <div className={`mt-0.5 flex size-10 flex-shrink-0 items-center justify-center rounded-xl transition-transform group-hover:scale-110 ${typeColor}`}>
                    <TypeIcon className="size-5" />
                </div>

                {/* Content */}
                <div className="min-w-0 flex-1">
                    {/* Header: Item Name & Quantity */}
                    <div className="flex items-start justify-between gap-2">
                        <div className="min-w-0">
                            <h3 className="truncate text-[15px] font-bold text-slate-800 dark:text-white">
                                {item.item || '-'}
                            </h3>
                            <div className="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-[12px] font-medium text-slate-500 dark:text-slate-400">
                                <span className="flex items-center gap-1">
                                    <Package className="size-3" />
                                    {item.division || 'Gudang Utama'}
                                </span>
                                <span className="text-slate-300 dark:text-slate-600">•</span>
                                <span className="flex items-center gap-1">
                                    <User className="size-3" />
                                    {item.user || '-'}
                                </span>
                            </div>
                        </div>
                        <div className={`flex-shrink-0 text-lg font-black tracking-tight ${isPositive ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500 dark:text-red-400'}`}>
                            {isPositive ? '+' : '-'}{item.quantity}
                        </div>
                    </div>

                    {/* Metadata Section */}
                    <div className="mt-3 flex items-center justify-between border-t border-slate-100 pt-3 dark:border-slate-800/50">
                        <div className="flex items-center gap-3">
                            {/* Type Badge */}
                            <span className={`inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-tight ${typeColor}`}>
                                {item.type}
                            </span>
                            {/* Date */}
                            <div className="flex items-center gap-1 text-[11px] font-medium text-slate-400 dark:text-slate-500">
                                <Calendar className="size-3" />
                                {formatDate(item.date)}
                            </div>
                        </div>
                    </div>

                    {/* Description if exists */}
                    {item.description && (
                        <p className="mt-2 line-clamp-1 text-[11px] italic text-slate-400 dark:text-slate-500">
                            "{item.description}"
                        </p>
                    )}
                </div>
            </div>
        </div>
    );
}
