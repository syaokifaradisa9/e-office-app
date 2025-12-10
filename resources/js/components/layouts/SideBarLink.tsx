import { Link, usePage } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { useEffect, useState } from 'react';

import { useSidebarCollapse } from './SidebarContext';

interface SidebarLinkProps {
    name: string;
    href: string;
    icon: LucideIcon;
    exactMatch?: boolean;
}

export default function SidebarLink({ name, href, icon: Icon, exactMatch = false }: SidebarLinkProps) {
    const [isTooltipVisible, setIsTooltipVisible] = useState(false);
    const { url } = usePage();
    const isCollapsed = useSidebarCollapse();

    const isActive = exactMatch ? url === href : url === href || url.startsWith(href + '/');

    useEffect(() => {
        if (!isCollapsed) {
            setIsTooltipVisible(false);
        }
    }, [isCollapsed]);

    const alignmentClasses = isCollapsed ? 'justify-center px-0 py-2.5' : 'px-3 py-2.5';

    const baseClasses = [
        'group',
        'relative flex items-center w-full',
        alignmentClasses,
        'rounded-lg transition-all duration-200',
        isActive ? 'text-primary-foreground bg-primary' : 'text-slate-700/80 hover:bg-primary/5 dark:text-slate-300 dark:hover:bg-slate-700/30',
    ]
        .join(' ')
        .replace(/\s+/g, ' ')
        .trim();

    const showTooltip = () => {
        if (isCollapsed) {
            setIsTooltipVisible(true);
        }
    };

    const hideTooltip = () => {
        if (isCollapsed) {
            setIsTooltipVisible(false);
        }
    };

    const renderLabel = () =>
        isCollapsed ? (
            <span
                className={`pointer-events-none absolute left-full top-1/2 z-40 ml-3 -translate-y-1/2 whitespace-nowrap rounded-md bg-slate-800 px-2 py-1 text-xs font-medium text-white shadow-lg transition-none dark:bg-slate-700 ${isTooltipVisible ? 'opacity-100' : 'opacity-0'}`}
                role="tooltip"
                aria-hidden={!isTooltipVisible}
            >
                {name}
            </span>
        ) : (
            <span className="ml-3 text-sm font-medium">{name}</span>
        );

    return (
        <Link href={href} className={baseClasses} title={isCollapsed ? name : undefined} onMouseEnter={showTooltip} onMouseLeave={hideTooltip} onFocus={showTooltip} onBlur={hideTooltip}>
            <Icon className="size-5 flex-shrink-0" strokeWidth={1.5} />
            {renderLabel()}
        </Link>
    );
}
