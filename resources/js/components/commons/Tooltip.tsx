interface TooltipProps {
    children: React.ReactNode;
    text: string;
}

export default function Tooltip({ children, text }: TooltipProps) {
    return (
        <div className="group relative inline-block">
            {children}
            <span className="absolute bottom-full left-1/2 z-10 mb-2 hidden -translate-x-1/2 whitespace-nowrap rounded bg-gray-800 px-2 py-1 text-xs text-white group-hover:block">
                {text}
            </span>
        </div>
    );
}
