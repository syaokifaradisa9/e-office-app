import React from 'react';
import { Check } from 'lucide-react';

interface Option {
    value: string | number;
    label: string;
}

interface FormCheckboxGroupProps {
    name: string;
    label?: string;
    options: Option[];
    value: (string | number)[];
    onChange: (value: (string | number)[]) => void;
    error?: string;
    disabled?: boolean;
    helpText?: string;
    required?: boolean;
    columns?: 1 | 2 | 3;
}

export default function FormCheckboxGroup({
    name,
    label,
    options = [],
    value = [],
    onChange,
    error,
    disabled = false,
    helpText,
    required = false,
    columns = 2,
}: FormCheckboxGroupProps) {
    const handleToggle = (optionValue: string | number) => {
        if (disabled) return;

        const newValue = value.includes(optionValue)
            ? value.filter((v) => v !== optionValue)
            : [...value, optionValue];
        onChange(newValue);
    };

    const gridCols = {
        1: 'grid-cols-1',
        2: 'grid-cols-1 sm:grid-cols-2',
        3: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
    }[columns];

    return (
        <div className="space-y-2.5">
            {label && (
                <label className="text-sm font-semibold text-slate-700 dark:text-slate-300">
                    {label}
                    {required && <span className="ml-1 text-red-500">*</span>}
                </label>
            )}

            <div className={`grid ${gridCols} gap-3`}>
                {options.length > 0 ? (
                    options.map((option) => {
                        const isChecked = value.includes(option.value);
                        return (
                            <div
                                key={option.value}
                                onClick={() => handleToggle(option.value)}
                                className={`
                                    group relative flex cursor-pointer items-center gap-3 rounded-xl border p-3 transition-all duration-200
                                    ${isChecked
                                        ? 'border-primary/50 bg-primary/5 dark:border-primary/40 dark:bg-primary/10'
                                        : 'border-slate-200 bg-white hover:border-slate-300 dark:border-slate-700 dark:bg-slate-800/50 dark:hover:border-slate-600'
                                    }
                                    ${disabled ? 'cursor-not-allowed opacity-60' : ''}
                                `}
                            >
                                <div className={`
                                    flex size-5 shrink-0 items-center justify-center rounded border transition-all duration-200
                                    ${isChecked
                                        ? 'border-primary bg-primary text-white'
                                        : 'border-slate-300 bg-transparent dark:border-slate-600'
                                    }
                                `}>
                                    {isChecked && <Check className="size-3.5 stroke-[3px]" />}
                                </div>
                                <span className={`text-sm font-medium transition-colors ${isChecked ? 'text-primary dark:text-primary-400' : 'text-slate-600 dark:text-slate-400'}`}>
                                    {option.label}
                                </span>
                            </div>
                        );
                    })
                ) : (
                    <div className="col-span-full rounded-xl border border-dashed border-slate-200 p-4 text-center dark:border-slate-700">
                        <p className="text-sm italic text-slate-400">
                            {disabled ? 'Silakan pilih divisi terlebih dahulu' : 'Tidak ada pilihan tersedia'}
                        </p>
                    </div>
                )}
            </div>

            {helpText && !error && <p className="text-xs text-slate-500 dark:text-slate-400/80">{helpText}</p>}
            {error && <p className="text-xs font-medium text-red-500">{error}</p>}
        </div>
    );
}
