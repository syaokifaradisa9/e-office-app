import { ReactNode } from 'react';

interface Props {
    children: ReactNode;
    color?: 'primary' | 'secondary' | 'success' | 'danger' | 'warning' | 'info' | 'dark';
    className?: string;
}

export default function Badge({ children, color = 'primary', className = '' }: Props) {
    const colors = {
        primary: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        secondary: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        success: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        danger: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        warning: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        info: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-300',
        dark: 'bg-gray-800 text-white dark:bg-gray-200 dark:text-gray-900',
    };

    return (
        <span
            className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colors[color]} ${className}`}
        >
            {children}
        </span>
    );
}
