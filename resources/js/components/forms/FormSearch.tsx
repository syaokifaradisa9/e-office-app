import { useState, useEffect } from 'react';

interface FormSearchProps {
    name: string;
    type?: string;
    placeholder?: string;
    onChange: (e: { preventDefault: () => void; target: { name: string; value: string } }) => void;
    className?: string;
    value?: string;
}

export default function FormSearch({ name, type = 'text', placeholder, onChange, className, value }: FormSearchProps) {
    const [localValue, setLocalValue] = useState(value || '');

    useEffect(() => {
        setLocalValue(value || '');
    }, [value]);

    useEffect(() => {
        const timeoutId = setTimeout(() => {
            if (localValue !== (value || '')) {
                onChange({
                    preventDefault: () => { },
                    target: {
                        name: name,
                        value: localValue,
                    },
                });
            }
        }, 500);

        return () => clearTimeout(timeoutId);
    }, [localValue, value, name, onChange]);

    return (
        <input
            name={name}
            onChange={(e) => setLocalValue(e.target.value)}
            type={type}
            value={localValue}
            placeholder={placeholder}
            className={`w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-xs text-gray-900 transition-all duration-200 focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:placeholder-slate-400 ${className}`}
        />
    );
}
