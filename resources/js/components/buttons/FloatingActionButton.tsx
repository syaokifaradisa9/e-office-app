import { Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';

interface FloatingActionButtonProps {
    href?: string;
    onClick?: () => void;
    icon?: React.ReactNode;
    label?: string;
    className?: string;
}

export default function FloatingActionButton({
    href,
    onClick,
    icon = <Plus className="size-5" strokeWidth={2.5} />,
    label = 'Tambah',
    className = '',
}: FloatingActionButtonProps) {
    const baseClassName = `
        fixed bottom-20 right-5 z-40
        flex items-center justify-center
        w-14 h-14 rounded-full
        bg-primary text-primary-foreground
        shadow-md
        transition-transform duration-150 ease-out
        active:scale-95
        md:hidden
        ${className}
    `
        .trim()
        .replace(/\s+/g, ' ');

    if (href) {
        return (
            <Link href={href} className={baseClassName} aria-label={label}>
                {icon}
            </Link>
        );
    }

    return (
        <button onClick={onClick} className={baseClassName} aria-label={label}>
            {icon}
        </button>
    );
}
