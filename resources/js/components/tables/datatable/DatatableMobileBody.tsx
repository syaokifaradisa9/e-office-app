import CardSkeleton from '../../skeletons/CardSkeleton';

interface DatatableMobileBodyProps<T> {
    dataTable: {
        data?: T[];
        [key: string]: unknown;
    };
    cardItem: (item: T) => React.ReactNode;
    isLoading?: boolean;
    skeletonCount?: number;
    SkeletonComponent?: React.ComponentType | null;
}

export default function DatatableMobileBody<T>({ dataTable, cardItem, isLoading = false, skeletonCount = 5, SkeletonComponent = null }: DatatableMobileBodyProps<T>) {
    if (isLoading) {
        const Skeleton = SkeletonComponent || CardSkeleton;
        return (
            <div className="divide-y divide-gray-200 dark:divide-slate-700">
                {Array.from({ length: skeletonCount }).map((_, index) => (
                    <div key={index}>
                        <Skeleton />
                    </div>
                ))}
            </div>
        );
    }

    return (
        <div className="space-y-3 bg-gray-100 px-3 dark:bg-slate-900">
            {dataTable.data?.length ? (
                dataTable.data.map((item, index) => (
                    <div key={index} className={`overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-800 ${index === 0 ? 'mt-3' : ''}`}>
                        {cardItem(item)}
                    </div>
                ))
            ) : (
                <div className="rounded-xl bg-white px-4 py-8 text-center text-slate-500 dark:bg-slate-800 dark:text-slate-400">Data Masih Kosong</div>
            )}
        </div>
    );
}
