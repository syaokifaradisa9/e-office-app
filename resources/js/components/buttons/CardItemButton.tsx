import { Link } from '@inertiajs/react';
import React from 'react';

type Variant = 'info' | 'warning' | 'danger' | 'success' | 'primary';

interface Props {
    label: string | React.ReactNode;
    icon?: React.ReactNode;
    onClick?: () => void;
    href?: string;
    variant?: Variant;
    className?: string;
    target?: string;
    rel?: string;
    isInertia?: boolean;
}

const variants: Record<Variant, string> = {
    info: 'border-blue-200 text-blue-600 hover:bg-blue-50 active:bg-blue-100 dark:border-blue-800/50 dark:text-blue-400 dark:hover:bg-blue-900/20',
    warning: 'border-amber-200 text-amber-600 hover:bg-amber-50 active:bg-amber-100 dark:border-amber-800/50 dark:text-amber-400 dark:hover:bg-amber-900/20',
    danger: 'border-red-200 text-red-500 hover:bg-red-50 active:bg-red-100 dark:border-red-800/50 dark:text-red-400 dark:hover:bg-red-900/20',
    success: 'border-emerald-200 text-emerald-600 hover:bg-emerald-50 active:bg-emerald-100 dark:border-emerald-800/50 dark:text-emerald-400 dark:hover:bg-emerald-900/20',
    primary: 'border-primary/20 text-primary hover:bg-primary/5 active:bg-primary/10 dark:border-primary/30 dark:text-primary-400',
};

export default function CardItemButton({
    label,
    icon,
    onClick,
    href,
    variant = 'info',
    className = '',
    target,
    rel,
    isInertia = true
}: Props) {
    const baseClass = `flex items-center justify-center gap-1.5 rounded-lg border px-4 py-2 text-sm font-medium transition-colors ${variants[variant]} ${className}`;

    const content = (
        <>
            {icon && React.isValidElement(icon) && React.cloneElement(icon as React.ReactElement<{ className?: string }>, {
                className: `h-3.5 w-3.5 shrink-0 ${(icon.props as any).className || ''}`
            })}
            {label}
        </>
    );

    if (href) {
        if (isInertia) {
            return (
                <Link href={href} className={baseClass}>
                    {content}
                </Link>
            );
        }
        return (
            <a href={href} className={baseClass} target={target} rel={rel}>
                {content}
            </a>
        );
    }

    return (
        <button type="button" onClick={onClick} className={baseClass}>
            {content}
        </button>
    );
}
