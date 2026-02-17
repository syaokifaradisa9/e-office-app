import { Link } from '@inertiajs/react';
import useMediaQuery from '../../../helpers/mediaquery';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface DatatableFooterProps {
    dataTable: {
        links?: PaginationLink[];
        current_page?: number;
        last_page?: number;
        from?: number;
        to?: number;
        total?: number;
        [key: string]: unknown;
    };
    onChangePage: (e: React.MouseEvent<HTMLAnchorElement>) => void;
}

export default function DatatableFooter({ dataTable, onChangePage }: DatatableFooterProps) {
    const isMediumScreen = useMediaQuery('(min-width: 768px)');

    const renderPagination = () => {
        if ((!dataTable.links || dataTable.links.length === 0) && (dataTable.total ?? 0) > 0) {
            // Fallback for when no links are provided (single page)
            return (
                <>
                    <span className="flex h-9 min-w-[36px] cursor-not-allowed items-center justify-center rounded-l-lg border-r border-gray-300 bg-gray-100 px-3 text-sm font-medium text-slate-400 dark:border-slate-500/30 dark:bg-slate-700/10 dark:text-slate-400">
                        &lt;
                    </span>
                    <span className="z-10 flex h-9 min-w-[36px] items-center justify-center border-r border-gray-300 bg-primary px-3 text-sm font-medium text-white dark:bg-primary/90">
                        1
                    </span>
                    <span className="flex h-9 min-w-[36px] cursor-not-allowed items-center justify-center rounded-r-lg bg-gray-100 px-3 text-sm font-medium text-slate-400 dark:bg-slate-700/10 dark:text-slate-400">
                        &gt;
                    </span>
                </>
            );
        }

        return dataTable.links?.map((link, index, array) => {
            const isFirst = index === 0;
            const isLast = index === array.length - 1;
            const pageNumber = parseInt(link.label);
            const currentPage = dataTable.current_page ?? 1;
            const totalPages = dataTable.last_page ?? 1;
            const isMobile = !isMediumScreen;

            // For small page counts (5 or less), show all pages
            // Otherwise use compact pagination on mobile
            const showAllPages = totalPages <= 5;
            const edgeCount = isMobile ? 2 : 3;
            const midCount = isMobile ? 3 : 3;

            const shouldShow =
                isFirst || isLast || showAllPages || index <= edgeCount || index >= array.length - edgeCount || (pageNumber && Math.abs(pageNumber - currentPage) <= Math.floor(midCount / 2));

            const showLeftEllipsis = currentPage > edgeCount + Math.floor(midCount / 2) + 1 && index === edgeCount;
            const showRightEllipsis = currentPage < totalPages - (edgeCount + Math.floor(midCount / 2)) && index === array.length - (edgeCount + 1);

            if (!shouldShow && !showLeftEllipsis && !showRightEllipsis) return null;

            if (showLeftEllipsis || showRightEllipsis) {
                return (
                    <span
                        key={`ellipsis-${index}`}
                        className="flex h-9 items-center justify-center border-r border-gray-300 bg-white px-2 text-sm text-slate-500 last:border-0 dark:border-slate-500/30 dark:bg-slate-700/20 dark:text-slate-300 md:px-3"
                    >
                        ...
                    </span>
                );
            }

            if (!link.url) {
                const rawLabel = (link.label || '').toString().trim();
                let displayLabel = rawLabel;
                if (['&laquo; Previous', 'pagination.previous'].includes(rawLabel)) {
                    displayLabel = '<';
                } else if (['Next &raquo;', 'pagination.next'].includes(rawLabel)) {
                    displayLabel = '>';
                }

                return (
                    <div
                        key={`page-${index}`}
                        className={`flex h-9 min-w-[32px] cursor-not-allowed items-center justify-center border-r border-gray-300 bg-gray-100 px-2 text-sm font-medium text-slate-400 transition-colors duration-150 last:border-0 dark:border-slate-500/30 dark:bg-slate-700/10 dark:text-slate-400 md:min-w-[36px] md:px-3 ${isFirst ? 'rounded-l-lg' : ''} ${isLast ? 'rounded-r-lg' : ''}`}
                    >
                        {displayLabel}
                    </div>
                );
            }

            const rawLabel = (link.label || '').toString().trim();
            let displayLabel = rawLabel;
            if (['&laquo; Previous', 'pagination.previous'].includes(rawLabel)) {
                displayLabel = '<';
            } else if (['Next &raquo;', 'pagination.next'].includes(rawLabel)) {
                displayLabel = '>';
            }

            return (
                <Link
                    key={`page-${index}`}
                    href={link.url}
                    onClick={onChangePage}
                    className={`flex h-9 min-w-[32px] items-center justify-center border-r border-gray-300 px-2 text-sm font-medium transition-colors duration-150 last:border-0 dark:border-slate-500/30 md:min-w-[36px] md:px-3 ${link.active
                            ? 'z-10 bg-primary text-white hover:bg-primary/90 dark:bg-primary/90 dark:hover:bg-primary'
                            : 'bg-white text-slate-600 hover:bg-gray-100/80 dark:bg-slate-700/20 dark:text-slate-300 dark:hover:bg-slate-700/40'
                        } ${isFirst ? 'rounded-l-lg' : ''} ${isLast ? 'rounded-r-lg' : ''}`}
                >
                    {displayLabel}
                </Link>
            );
        });
    };

    // Mobile: Fixed bottom bar
    if (!isMediumScreen) {
        return (
            <>
                {/* Spacer to prevent content from being hidden behind fixed footer */}
                <div className="h-14 bg-gray-100 dark:bg-slate-900"></div>

                {/* Fixed Footer */}
                <div className="fixed bottom-0 left-0 right-0 z-20 border-t border-gray-200 bg-white px-4 py-3 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] dark:border-slate-700 dark:bg-slate-800">
                    <div className="flex items-center justify-between">
                        {/* Left: Data info */}
                        <p className="text-sm text-slate-600 dark:text-slate-300">
                            <span className="font-medium text-slate-900 dark:text-slate-100">
                                {dataTable.from ?? 0}-{dataTable.to ?? 0}
                            </span>{' '}
                            dari <span className="font-medium text-slate-900 dark:text-slate-100">{dataTable.total ?? 0}</span> data
                        </p>

                        {/* Right: Pagination */}
                        <nav className="-space-x-px flex overflow-hidden rounded-lg border border-gray-300 dark:border-slate-500/30" aria-label="Pagination">
                            {renderPagination()}
                        </nav>
                    </div>
                </div>
            </>
        );
    }

    // Desktop: Regular layout
    return (
        <div className="mt-4 flex flex-col items-center justify-between gap-4 md:flex-row">
            <p className="text-sm text-slate-600 dark:text-slate-300">
                Menampilkan{' '}
                <span className="font-medium text-slate-900 dark:text-slate-100">
                    {dataTable.from ?? 0} - {dataTable.to ?? 0}
                </span>{' '}
                dari <span className="font-medium text-slate-900 dark:text-slate-100">{dataTable.total ?? 0}</span> Data
            </p>

            <nav className="-space-x-px flex overflow-hidden rounded-lg border border-gray-300 dark:border-slate-500/30" aria-label="Pagination">
                {renderPagination()}
            </nav>
        </div>
    );
}
