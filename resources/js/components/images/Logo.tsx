import { Building2 } from 'lucide-react';

interface LogoProps {
    className?: string;
    showText?: boolean;
    iconSize?: string;
    textSize?: string;
}

export default function Logo({ className = '', showText = true, iconSize = 'size-8', textSize = 'text-2xl' }: LogoProps) {
    return (
        <div className={`flex items-center gap-3 ${className}`}>
            <div className="flex items-center justify-center rounded-xl bg-primary/10 p-2">
                <Building2 className={`${iconSize} text-primary`} strokeWidth={1.5} />
            </div>
            {showText && <div className={`font-bold ${textSize} tracking-tight text-slate-800 dark:text-white`}>e-Office</div>}
        </div>
    );
}
