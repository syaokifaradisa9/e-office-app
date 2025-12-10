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
            <div className="flex flex-col px-4 py-4">
                {/* Header: Name */}
                <div className="mb-0.5 h-5 w-1/2 rounded bg-gray-200 dark:bg-slate-700"></div>

                {/* Email */}
                <div className="mb-2 h-4 w-2/3 rounded bg-gray-200 dark:bg-slate-700"></div>

                {/* Row: Position & Division */}
                <div className="mb-1 mt-2 flex items-center gap-4">
                    <div className="flex items-center gap-1.5">
                        <div className="h-4 w-4 rounded bg-gray-200 dark:bg-slate-700"></div>
                        <div className="h-3 w-16 rounded bg-gray-200 dark:bg-slate-700"></div>
                    </div>
                    <div className="flex items-center gap-1.5">
                        <div className="h-4 w-4 rounded bg-gray-200 dark:bg-slate-700"></div>
                        <div className="h-3 w-20 rounded bg-gray-200 dark:bg-slate-700"></div>
                    </div>
                </div>

                {/* Row: Role & Phone */}
                <div className="mb-2 flex items-center gap-4">
                    <div className="flex items-center gap-1.5">
                        <div className="h-4 w-4 rounded bg-gray-200 dark:bg-slate-700"></div>
                        <div className="h-3 w-14 rounded bg-gray-200 dark:bg-slate-700"></div>
                    </div>
                    <div className="flex items-center gap-1.5">
                        <div className="h-4 w-4 rounded bg-gray-200 dark:bg-slate-700"></div>
                        <div className="h-3 w-24 rounded bg-gray-200 dark:bg-slate-700"></div>
                    </div>
                </div>

                {/* Footer: Actions */}
                <div className="flex items-center justify-end gap-2 pt-2">
                    <div className="h-8 w-16 rounded-lg bg-gray-200 dark:bg-slate-700"></div>
                    <div className="h-8 w-16 rounded-lg bg-gray-200 dark:bg-slate-700"></div>
                </div>
            </div>
        </div>
    );
}

// Division Skeleton - matches DivisionCardItem layout
export function DivisionCardSkeleton() {
    return (
        <div className="animate-pulse">
            <div className="flex flex-col gap-1 px-4 py-4">
                {/* Header: Name */}
                <div className="mb-1 h-5 w-1/2 rounded bg-gray-200 dark:bg-slate-700"></div>

                {/* Subtitle: Stats */}
                <div className="mb-2 flex items-center gap-2">
                    <div className="h-4 w-16 rounded bg-gray-200 dark:bg-slate-700"></div>
                    <div className="h-4 w-2 rounded bg-gray-200 dark:bg-slate-700"></div>
                    <div className="h-4 w-20 rounded bg-gray-200 dark:bg-slate-700"></div>
                </div>

                {/* Footer: Actions */}
                <div className="flex items-center justify-end gap-2 pt-2">
                    <div className="h-8 w-16 rounded-lg bg-gray-200 dark:bg-slate-700"></div>
                    <div className="h-8 w-16 rounded-lg bg-gray-200 dark:bg-slate-700"></div>
                </div>
            </div>
        </div>
    );
}

// Position Skeleton - matches PositionCardItem layout
export function PositionCardSkeleton() {
    return (
        <div className="animate-pulse">
            <div className="flex flex-col gap-1 px-4 py-4">
                {/* Header: Name */}
                <div className="mb-1 h-5 w-1/2 rounded bg-gray-200 dark:bg-slate-700"></div>

                {/* Subtitle: User count */}
                <div className="mb-2 h-4 w-28 rounded bg-gray-200 dark:bg-slate-700"></div>

                {/* Footer: Actions */}
                <div className="flex items-center justify-end gap-2 pt-2">
                    <div className="h-8 w-16 rounded-lg bg-gray-200 dark:bg-slate-700"></div>
                    <div className="h-8 w-16 rounded-lg bg-gray-200 dark:bg-slate-700"></div>
                </div>
            </div>
        </div>
    );
}

// Role Skeleton - matches RoleCardItem layout
export function RoleCardSkeleton() {
    return (
        <div className="animate-pulse">
            <div className="flex flex-col gap-1 px-4 py-4">
                {/* Header: Name */}
                <div className="mb-1 h-5 w-1/3 rounded bg-gray-200 dark:bg-slate-700"></div>

                {/* Subtitle: Permissions count */}
                <div className="mb-2 h-4 w-28 rounded bg-gray-200 dark:bg-slate-700"></div>

                {/* Footer: Actions */}
                <div className="flex items-center justify-end gap-2 pt-2">
                    <div className="h-8 w-16 rounded-lg bg-gray-200 dark:bg-slate-700"></div>
                    <div className="h-8 w-16 rounded-lg bg-gray-200 dark:bg-slate-700"></div>
                </div>
            </div>
        </div>
    );
}
