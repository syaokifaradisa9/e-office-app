import { Link, usePage } from '@inertiajs/react';
import { ChevronRight, type LucideIcon } from 'lucide-react';
import { useEffect, useState } from 'react';

import { useSidebarCollapse } from './SidebarContext';

interface SidebarLinkProps {
    name: string;
    href: string;
    icon: LucideIcon;
    exactMatch?: boolean;
    children?: { name: string; href: string }[];
}

export default function SidebarLink({ name, href, icon: Icon, exactMatch = false, children }: SidebarLinkProps) {
    const [isOpen, setIsOpen] = useState(false);
    const [isTooltipVisible, setIsTooltipVisible] = useState(false);
    const { url } = usePage();
    const isCollapsed = useSidebarCollapse();

    const hasChildren = children && children.length > 0;
    const isActive = exactMatch ? url === href : url === href || url.startsWith(href + '/');
    // If exactMatch is true, we probably don't want child activation to trigger parent active state unless logic dictates.
    // But usually for valid sidebar, if child is active, parent is active (expanded).
    const isChildActive = hasChildren && children.some((child) => url === child.href || url.startsWith(child.href + '/'));

    useEffect(() => {
        if (!isCollapsed) {
            setIsTooltipVisible(false);
        }
    }, [isCollapsed]);

    useEffect(() => {
        if ((isActive || isChildActive) && hasChildren) {
            setIsOpen(true);
        }
    }, [isActive, isChildActive, hasChildren]);

    const alignmentClasses = isCollapsed ? 'justify-center px-0 py-2.5' : 'px-3 py-2.5';

    const baseClasses = [
        'group',
        'relative flex items-center w-full',
        alignmentClasses,
        'rounded-lg transition-all duration-200',
        (isActive || (hasChildren && isChildActive)) ? 'text-primary-foreground bg-primary' : 'text-slate-700/80 hover:bg-primary/5 dark:text-slate-300 dark:hover:bg-slate-700/30',
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

    if (!hasChildren) {
        return (
            <Link href={href} className={baseClasses} title={isCollapsed ? name : undefined} onMouseEnter={showTooltip} onMouseLeave={hideTooltip} onFocus={showTooltip} onBlur={hideTooltip}>
                <Icon className="size-5 flex-shrink-0" strokeWidth={1.5} />
                {renderLabel()}
            </Link>
        );
    }

    return (
        <div className="relative space-y-0.5" onMouseEnter={() => isCollapsed && setIsOpen(true)} onMouseLeave={() => isCollapsed && setIsOpen(false)}>
            <button
                onClick={() => setIsOpen(!isOpen)}
                className={baseClasses}
                title={isCollapsed ? name : undefined}
                aria-expanded={isOpen}
                onFocus={showTooltip}
                onBlur={hideTooltip}
            >
                <Icon className="size-5 flex-shrink-0" strokeWidth={1.5} />
                {renderLabel()}
                {!isCollapsed && (
                    <ChevronRight
                        className={`size-4.5 ml-auto transition-transform duration-200 text-slate-400 ${isOpen ? "rotate-90" : "group-hover:translate-x-0.5"
                            }`}
                    />
                )}
            </button>

            {isOpen && (
                <div className={isCollapsed
                    ? "absolute left-full top-0 ml-3 min-w-[220px] rounded-lg bg-white dark:bg-slate-800 shadow-lg border border-slate-200 dark:border-slate-700 py-2 px-2 space-y-0.5 z-40"
                    : "pl-8 pb-0.5 mt-0.5 space-y-0.5"}>
                    {children.map((child, index) => (
                        <Link
                            key={index}
                            href={child.href}
                            className={`flex items-center w-full rounded-lg transition-all duration-200 ${isCollapsed ? "px-3 py-2 text-sm font-medium" : "px-3 py-2"
                                } ${url === child.href || url.startsWith(child.href + '/')
                                    ? "text-primary bg-primary/10"
                                    : "text-slate-600 hover:bg-primary/5 dark:text-slate-300 dark:hover:bg-slate-700/30"
                                }`}
                        >
                            {!isCollapsed && <div className="w-1.5 h-1.5 rounded-full bg-current opacity-40 mr-3" />}
                            <span className="text-sm font-medium">{child.name}</span>
                        </Link>
                    ))}
                </div>
            )}
        </div>
    );
}
