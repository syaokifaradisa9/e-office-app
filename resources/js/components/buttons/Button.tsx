import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

interface ButtonProps {
    label?: string;
    type?: 'button' | 'submit' | 'reset';
    onClick?: () => void;
    className?: string;
    isLoading?: boolean;
    icon?: ReactNode;
    href?: string;
    target?: string;
    disabled?: boolean;
    variant?: 'primary' | 'secondary' | 'outline' | 'dashed' | 'ghost' | 'link' | 'danger' | 'success';
    title?: string;
}

export default function Button({
    label,
    type = 'button',
    onClick,
    className = '',
    isLoading,
    icon,
    href,
    target,
    disabled,
    variant = 'primary',
    ...props
}: ButtonProps) {
    const renderContent = () => (
        <>
            {isLoading && (
                <svg className="-ml-1 mr-2 size-4 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                    <path
                        className="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    />
                </svg>
            )}
            {!isLoading && icon && <span className={label ? 'mr-2' : ''}>{icon}</span>}
            {label && <span className="text-sm font-medium">{isLoading ? 'Loading...' : label}</span>}
        </>
    );

    const variants = {
        primary: 'bg-primary hover:bg-primary/90 text-primary-foreground',
        secondary: 'bg-gray-100 text-gray-900 hover:bg-gray-200 dark:bg-slate-700 dark:text-slate-100 dark:hover:bg-slate-600',
        outline: 'border border-input bg-background hover:bg-accent hover:text-accent-foreground',
        dashed: 'border-2 border-dashed border-gray-300 text-gray-500 hover:border-primary hover:text-primary bg-transparent dark:border-slate-600 dark:text-slate-400 dark:hover:border-primary dark:hover:text-primary',
        ghost: 'hover:bg-accent hover:text-accent-foreground text-slate-700 dark:text-slate-300',
        link: 'text-primary underline-offset-4 hover:underline',
        danger: 'bg-red-500 text-white hover:bg-red-600',
        success: 'bg-green-600 text-white hover:bg-green-700',
    };

    const baseClasses = `
        inline-flex items-center justify-center
        px-5 py-2.5
        rounded-lg
        transition-all duration-200
        focus:outline-none focus:ring-4 focus:ring-primary/20
        ${isLoading || disabled ? 'bg-slate-300 cursor-not-allowed dark:bg-slate-700 text-slate-500' : variants[variant] || variants.primary}
        ${className}
    `.trim();

    if (href) {
        if (href.startsWith('http') || target === '_blank') {
            return (
                <a href={href} target={target} rel={target === '_blank' ? 'noopener noreferrer' : undefined} className={baseClasses} {...props}>
                    {renderContent()}
                </a>
            );
        }

        return (
            <Link href={href} className={baseClasses} {...props}>
                {renderContent()}
            </Link>
        );
    }

    return (
        <button type={type} onClick={!isLoading && !disabled ? onClick : undefined} className={baseClasses} disabled={isLoading || disabled} {...props}>
            {renderContent()}
        </button>
    );
}
