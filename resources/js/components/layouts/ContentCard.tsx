import { Link, usePage } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import type { ReactNode } from 'react';

interface ContentCardProps {
    title?: string;
    subtitle?: string;
    children: ReactNode;
    className?: string;
    backPath?: string;
    additionalButton?: ReactNode;
    mobileFullWidth?: boolean;
}

export default function ContentCard({ title, subtitle, children, className = '', backPath, additionalButton, mobileFullWidth }: ContentCardProps) {
    const { component } = usePage();
    const isMobileFullWidth = mobileFullWidth ?? true;

    return (
        <div
            className={`
                flex flex-col
                ${isMobileFullWidth
                    ? 'flex-1 rounded-none border-x-0 border-b-0 bg-gray-100 md:flex-none md:rounded-xl md:border md:border-b md:bg-white dark:bg-slate-900 md:dark:bg-slate-800'
                    : 'rounded-lg border bg-white md:rounded-xl dark:bg-slate-800'
                }
                border-gray-300/90 shadow-sm shadow-primary/5 dark:border-slate-700/50 dark:shadow-slate-900/50
                ${className}
            `}
        >
            {/* Header - hidden on mobile for non-index pages, or if mobileFullWidth is true */}
            {(title || backPath || additionalButton) && (
                <div
                    className={`flex items-center justify-between gap-3 border-b border-gray-300/70 px-4 py-3 md:px-6 md:py-4 dark:border-slate-700/50 ${!component?.endsWith('/Index') || isMobileFullWidth ? 'hidden md:flex' : 'flex'}`}
                >
                    <div className="flex min-w-0 items-center gap-2 md:gap-3">
                        {backPath && (
                            <Link
                                href={backPath}
                                className="-ml-1.5 flex size-8 items-center justify-center rounded-lg text-primary transition-colors hover:bg-primary/10 dark:text-primary dark:hover:bg-slate-700/50"
                            >
                                <ChevronLeft className="size-4 md:size-5" />
                            </Link>
                        )}
                        <div>
                            {title && <h2 className="truncate text-base font-semibold text-gray-900 md:text-lg dark:text-white">{title}</h2>}
                            {subtitle && <p className="text-xs text-gray-500 md:text-sm dark:text-slate-400">{subtitle}</p>}
                        </div>
                    </div>
                    {/* additionalButton only visible on desktop */}
                    {additionalButton && <div className="hidden md:block">{additionalButton}</div>}
                </div>
            )}
            <div className={`${!component?.endsWith('/Index') ? 'p-6 md:p-8' : isMobileFullWidth ? 'px-0 pb-5 pt-0 md:p-6' : 'p-4 pb-5'}`}>{children}</div>
        </div>
    );
}
