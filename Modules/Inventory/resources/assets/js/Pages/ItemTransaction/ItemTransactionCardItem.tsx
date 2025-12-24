import { ArrowRightLeft, ArrowDownRight, ArrowUpRight } from 'lucide-react';

interface ItemTransaction {
    id: number;
    date: string;
    type: string;
    quantity: number;
    item?: {
        id: number;
        name: string;
        division?: { id: number; name: string } | null;
    } | null;
}

interface Props {
    item: ItemTransaction;
}

export default function ItemTransactionCardItem({ item }: Props) {
    // Determine icon and color based on transaction type
    let TypeIcon = ArrowRightLeft;
    let typeColor = 'text-gray-500 bg-gray-100 dark:bg-gray-900/30 dark:text-gray-400';

    if (item.type === 'Barang Masuk' || item.type === 'Konversi Masuk') {
        TypeIcon = ArrowDownRight;
        typeColor = 'text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400';
    } else if (item.type === 'Barang Keluar' || item.type === 'Konversi Keluar') {
        TypeIcon = ArrowUpRight;
        typeColor = 'text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400';
    } else if (item.type === 'Konversi' || item.type === 'Stock Opname') {
        TypeIcon = ArrowRightLeft;
        typeColor = 'text-blue-600 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400';
    } else if (item.type === 'Stock Opname (Lebih)') {
        TypeIcon = ArrowDownRight;
        typeColor = 'text-teal-600 bg-teal-100 dark:bg-teal-900/30 dark:text-teal-400';
    } else if (item.type === 'Stock Opname (Kurang)') {
        TypeIcon = ArrowUpRight;
        typeColor = 'text-orange-600 bg-orange-100 dark:bg-orange-900/30 dark:text-orange-400';
    }

    return (
        <div className="flex items-center gap-3 px-4 py-3 transition-colors hover:bg-gray-50 dark:hover:bg-slate-700/30">
            {/* Left: Type Icon */}
            <div className={`flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg ${typeColor}`}>
                <TypeIcon className="size-4" />
            </div>

            {/* Middle: Info */}
            <div className="min-w-0 flex-1">
                <div className="flex items-center justify-between gap-2">
                    <div className="truncate font-medium text-gray-800 dark:text-white">{item.item?.name || '-'}</div>
                    {/* Quantity Badge */}
                    <div className="flex-shrink-0 text-base font-bold text-gray-800 dark:text-white">{item.quantity}</div>
                </div>
                <div className="mt-0.5 flex items-center justify-between">
                    <div className="truncate text-sm text-gray-500 dark:text-slate-400">
                        {new Date(item.date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })} •{' '}
                        {item.item?.division?.name || 'Gudang Utama'}
                    </div>
                </div>
            </div>
        </div>
    );
}
