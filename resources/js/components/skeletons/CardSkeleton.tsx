interface CardSkeletonProps {
    lines?: number;
    hasActions?: boolean;
}

// Default/Generic Skeleton
export default function CardSkeleton({ lines = 3, hasActions = true }: CardSkeletonProps) {
    return (
        <div className="animate-pulse">
            <div className="px-4 py-4">
                <div className="mb-3 h-5 w-2/3 rounded bg-gray-200 dark:bg-slate-700"></div>
                <div className="space-y-2">
                    {Array.from({ length: lines }).map((_, index) => (
                        <div key={index} className="h-4 rounded bg-gray-200 dark:bg-slate-700" style={{ width: `${50 + Math.random() * 30}%` }}></div>
                    ))}
                </div>
                {hasActions && (
                    <div className="mt-2 flex items-center justify-end gap-2 pt-3">
                        <div className="h-8 w-16 rounded-lg bg-gray-200 dark:bg-slate-700"></div>
                        <div className="h-8 w-16 rounded-lg bg-gray-200 dark:bg-slate-700"></div>
                    </div>
                )}
            </div>
        </div>
    );
}

// User Skeleton - matches UserCardItem layout
export function UserCardSkeleton() {
    return (
        <div className="animate-pulse">
            <div className="flex items-start gap-3.5 px-4 py-4">
                {/* Icon Box Placeholder */}
                <div className="mt-0.5 size-10 flex-shrink-0 rounded-xl bg-gray-200 dark:bg-slate-700"></div>

                {/* Content */}
                <div className="flex-1 space-y-3">
                    <div className="flex items-center gap-2">
                        <div className="h-5 w-1/3 rounded bg-gray-200 dark:bg-slate-700"></div>
                        <div className="h-4 w-12 rounded-full bg-gray-200 dark:bg-slate-700"></div>
                    </div>
                    <div className="h-4 w-2/3 rounded bg-gray-200 dark:bg-slate-700"></div>
                    <div className="flex gap-4">
                        <div className="h-3 w-16 rounded bg-gray-200 dark:bg-slate-700"></div>
                        <div className="h-3 w-20 rounded bg-gray-200 dark:bg-slate-700"></div>
                    </div>
                    {/* Actions Grid */}
                    <div className="grid grid-cols-2 gap-2 pt-1">
                        <div className="h-9 rounded-lg bg-gray-200 dark:bg-slate-700"></div>
                        <div className="h-9 rounded-lg bg-gray-200 dark:bg-slate-700"></div>
                    </div>
                </div>
            </div>
        </div>
    );
}

// Division Skeleton - matches DivisionCardItem layout
export function DivisionCardSkeleton() {
    return (
        <div className="animate-pulse">
            <div className="flex items-start gap-3.5 px-4 py-4">
                <div className="mt-0.5 size-10 flex-shrink-0 rounded-xl bg-gray-200 dark:bg-slate-700"></div>
                <div className="flex-1 space-y-3">
                    <div className="flex items-center gap-2">
                        <div className="h-5 w-1/3 rounded bg-gray-200 dark:bg-slate-700"></div>
                        <div className="h-4 w-12 rounded-full bg-gray-200 dark:bg-slate-700"></div>
                    </div>
                    <div className="h-4 w-1/2 rounded bg-gray-200 dark:bg-slate-700"></div>
                    <div className="grid grid-cols-2 gap-2 pt-1">
                        <div className="h-9 rounded-lg bg-gray-200 dark:bg-slate-700"></div>
                        <div className="h-9 rounded-lg bg-gray-200 dark:bg-slate-700"></div>
                    </div>
                </div>
            </div>
        </div>
    );
}

// Position Skeleton - matches PositionCardItem layout
export function PositionCardSkeleton() {
    return (
        <div className="animate-pulse">
            <div className="flex items-start gap-3.5 px-4 py-4">
                <div className="mt-0.5 size-10 flex-shrink-0 rounded-xl bg-gray-200 dark:bg-slate-700"></div>
                <div className="flex-1 space-y-3">
                    <div className="flex items-center gap-2">
                        <div className="h-5 w-1/3 rounded bg-gray-200 dark:bg-slate-700"></div>
                        <div className="h-4 w-12 rounded-full bg-gray-200 dark:bg-slate-700"></div>
                    </div>
                    <div className="h-4 w-1/2 rounded bg-gray-200 dark:bg-slate-700"></div>
                    <div className="grid grid-cols-2 gap-2 pt-1">
                        <div className="h-9 rounded-lg bg-gray-200 dark:bg-slate-700"></div>
                        <div className="h-9 rounded-lg bg-gray-200 dark:bg-slate-700"></div>
                    </div>
                </div>
            </div>
        </div>
    );
}

// Role Skeleton - matches RoleCardItem layout
export function RoleCardSkeleton() {
    return (
        <div className="animate-pulse">
            <div className="flex items-start gap-3.5 px-4 py-4">
                <div className="mt-0.5 size-10 flex-shrink-0 rounded-xl bg-gray-200 dark:bg-slate-700"></div>
                <div className="flex-1 space-y-3">
                    <div className="flex items-center gap-2">
                        <div className="h-5 w-1/3 rounded bg-gray-200 dark:bg-slate-700"></div>
                        <div className="h-4 w-12 rounded-full bg-gray-200 dark:bg-slate-700"></div>
                    </div>
                    <div className="h-4 w-1/4 rounded bg-gray-200 dark:bg-slate-700"></div>
                    <div className="grid grid-cols-2 gap-2 pt-1">
                        <div className="h-9 rounded-lg bg-gray-200 dark:bg-slate-700"></div>
                        <div className="h-9 rounded-lg bg-gray-200 dark:bg-slate-700"></div>
                    </div>
                </div>
            </div>
        </div>
    );
}

// WarehouseOrder Skeleton - matches WarehouseOrderCardItem layout
export function WarehouseOrderCardSkeleton() {
    return (
        <div className="animate-pulse">
            <div className="flex items-start gap-3.5 px-4 py-4">
                {/* Icon Box */}
                <div className="mt-0.5 size-10 flex-shrink-0 rounded-xl bg-gray-200 dark:bg-slate-700"></div>

                {/* Content */}
                <div className="flex-1 space-y-3">
                    <div className="space-y-1.5">
                        <div className="h-5 w-1/2 rounded bg-gray-200 dark:bg-slate-700"></div>
                        <div className="h-4 w-2/3 rounded bg-gray-200 dark:bg-slate-700"></div>
                    </div>

                    {/* Status + Date */}
                    <div className="flex items-center gap-2">
                        <div className="h-6 w-20 rounded-full bg-gray-200 dark:bg-slate-700"></div>
                        <div className="h-4 w-24 rounded bg-gray-200 dark:bg-slate-700"></div>
                    </div>

                    {/* Actions */}
                    <div className="grid grid-cols-2 gap-2 pt-1">
                        <div className="h-9 rounded-lg bg-gray-200 dark:bg-slate-700"></div>
                        <div className="h-9 rounded-lg bg-gray-200 dark:bg-slate-700"></div>
                    </div>
                </div>
            </div>
        </div>
    );
}

// Item Skeleton - matches ItemCardItem layout
export function ItemCardSkeleton() {
    return (
        <div className="animate-pulse">
            <div className="flex items-start gap-4 px-4 py-4">
                <div className="mt-0.5 size-10 flex-shrink-0 rounded-xl bg-gray-200 dark:bg-slate-700"></div>
                <div className="flex-1 space-y-3">
                    <div className="space-y-1.5">
                        <div className="h-5 w-1/2 rounded bg-gray-200 dark:bg-slate-700"></div>
                        <div className="h-4 w-1/3 rounded bg-gray-200 dark:bg-slate-700"></div>
                    </div>
                    <div className="h-5 w-24 rounded-full bg-gray-200 dark:bg-slate-700"></div>
                    <div className="grid grid-cols-2 gap-2 pt-1">
                        <div className="h-9 rounded-lg bg-gray-200 dark:bg-slate-700"></div>
                        <div className="h-9 rounded-lg bg-gray-200 dark:bg-slate-700"></div>
                    </div>
                </div>
            </div>
        </div>
    );
}

// StockOpname Skeleton - matches StockOpnameCardItem layout
export function StockOpnameCardSkeleton() {
    return (
        <div className="animate-pulse">
            <div className="flex flex-col gap-1 px-4 py-4">
                <div className="flex items-center justify-between gap-2">
                    <div className="h-5 w-1/2 rounded bg-gray-200 dark:bg-slate-700"></div>
                    <div className="h-5 w-16 rounded-full bg-gray-200 dark:bg-slate-700"></div>
                </div>
                <div className="mb-2 h-4 w-2/3 rounded bg-gray-200 dark:bg-slate-700"></div>
                <div className="mt-2 flex items-center justify-end gap-2 pt-2">
                    <div className="h-8 w-16 rounded-lg bg-gray-200 dark:bg-slate-700"></div>
                    <div className="h-8 w-20 rounded-lg bg-gray-200 dark:bg-slate-700"></div>
                </div>
            </div>
        </div>
    );
}

// StockMonitoring Skeleton - matches StockMonitoringCardItem layout
export function StockMonitoringCardSkeleton() {
    return (
        <div className="animate-pulse">
            <div className="flex items-start gap-4 px-4 py-4">
                <div className="mt-0.5 size-10 flex-shrink-0 rounded-xl bg-gray-200 dark:bg-slate-700"></div>
                <div className="flex-1 space-y-3">
                    <div className="space-y-1.5">
                        <div className="h-5 w-1/2 rounded bg-gray-200 dark:bg-slate-700"></div>
                        <div className="h-4 w-1/3 rounded bg-gray-200 dark:bg-slate-700"></div>
                    </div>
                    <div className="h-5 w-24 rounded-full bg-gray-200 dark:bg-slate-700"></div>
                    <div className="grid grid-cols-2 gap-2 pt-1">
                        <div className="h-9 rounded-lg bg-gray-200 dark:bg-slate-700"></div>
                        <div className="h-9 rounded-lg bg-gray-200 dark:bg-slate-700"></div>
                    </div>
                </div>
            </div>
        </div>
    );
}

// ItemTransaction Skeleton - matches ItemTransactionCardItem layout
export function ItemTransactionCardSkeleton() {
    return (
        <div className="animate-pulse">
            <div className="flex items-center gap-3 px-4 py-3">
                <div className="h-9 w-9 flex-shrink-0 rounded-lg bg-gray-200 dark:bg-slate-700"></div>
                <div className="min-w-0 flex-1">
                    <div className="flex items-center justify-between gap-2">
                        <div className="h-5 w-1/2 rounded bg-gray-200 dark:bg-slate-700"></div>
                        <div className="h-5 w-10 rounded bg-gray-200 dark:bg-slate-700"></div>
                    </div>
                    <div className="mt-1 h-4 w-2/3 rounded bg-gray-200 dark:bg-slate-700"></div>
                </div>
            </div>
        </div>
    );
}

// CategoryItem Skeleton - matches CategoryItemCardItem layout
export function CategoryItemCardSkeleton() {
    return (
        <div className="animate-pulse">
            <div className="flex items-start gap-3.5 px-4 py-4">
                <div className="mt-0.5 size-10 flex-shrink-0 rounded-xl bg-gray-200 dark:bg-slate-700"></div>
                <div className="flex-1 space-y-3">
                    <div className="h-5 w-1/3 rounded bg-gray-200 dark:bg-slate-700"></div>
                    <div className="h-4 w-2/3 rounded bg-gray-200 dark:bg-slate-700"></div>
                    <div className="grid grid-cols-2 gap-2 pt-1">
                        <div className="h-9 rounded-lg bg-gray-200 dark:bg-slate-700"></div>
                        <div className="h-9 rounded-lg bg-gray-200 dark:bg-slate-700"></div>
                    </div>
                </div>
            </div>
        </div>
    );
}


