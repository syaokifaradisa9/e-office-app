import { ChevronDown } from 'lucide-react';

interface Option {
    value: string;
    label: string;
}

interface FormSearchSelectProps {
    name: string;
    value: string;
    onChange: (e: { target: { name: string; value: string } }) => void;
    options: Option[];
    placeholder?: string;
    className?: string;
}

export default function FormSearchSelect({ name, value, onChange, options, placeholder = 'Pilih...', className = '' }: FormSearchSelectProps) {
    return (
        <div className={`relative ${className}`}>
            <select
                name={name}
                value={value}
                onChange={(e) => onChange({ target: { name, value: e.target.value } })}
                className="w-full cursor-pointer appearance-none rounded-lg border border-gray-300 bg-white px-4 py-2.5 pr-8 text-xs text-gray-900 transition-all duration-200 focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
            <ChevronDown className="pointer-events-none absolute right-2.5 top-1/2 size-4 -translate-y-1/2 text-gray-400 dark:text-slate-400" />
        </div>
    );
}
