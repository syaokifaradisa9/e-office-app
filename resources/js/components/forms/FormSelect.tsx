import { useState, useRef, useEffect, useMemo, type ReactNode, type ChangeEvent } from 'react';
import { ChevronDown, Search, Check } from 'lucide-react';

interface Option {
    value: string | number;
    label: string;
}

interface FormSelectProps {
    name: string;
    label?: string;
    onChange: (e: ChangeEvent<HTMLSelectElement> | { target: { name: string; value: string } }) => void;
    options?: Option[];
    placeholder?: string;
    error?: string;
    value?: string | number;
    className?: string;
    required?: boolean;
    disabled?: boolean;
    readonly?: boolean;
    icon?: ReactNode;
    helpText?: string;
    searchable?: boolean;
}

export default function FormSelect({
    name,
    label,
    onChange,
    options = [],
    placeholder = 'Select an option',
    error,
    value,
    className = '',
    required = false,
    disabled = false,
    readonly = false,
    icon,
    helpText,
    searchable = false,
}: FormSelectProps) {
    const [isFocused, setIsFocused] = useState(false);
    const [isOpen, setIsOpen] = useState(false);
    const [search, setSearch] = useState('');
    const dropdownRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!searchable) return;
        const handleClickOutside = (event: MouseEvent) => {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
                setIsOpen(false);
            }
        };

        if (isOpen) {
            document.addEventListener('mousedown', handleClickOutside);
        }
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, [isOpen, searchable]);

    const handleSelect = (optionValue: string) => {
        onChange({ target: { name, value: optionValue } } as any);
        setIsOpen(false);
        setSearch('');
    };

    const selectedOption = options.find((opt) => String(opt.value) === String(value));

    const filteredOptions = useMemo(() => {
        if (!search) return options;
        const lowerSearch = search.toLowerCase();
        return options.filter((opt) => opt.label.toLowerCase().includes(lowerSearch));
    }, [options, search]);

    const baseStyles = `
        w-full
        min-h-11
        px-4
        text-sm
        rounded-lg
        transition-all
        duration-200
        outline-none
        appearance-none
        ${disabled ? 'opacity-60 cursor-not-allowed' : ''}
        ${readonly ? 'cursor-not-allowed bg-gray-50 dark:bg-slate-800/40' : 'bg-white dark:bg-slate-800/40'}
    `;

    const getBorderStyles = () => {
        if (error) {
            return 'border-red-500/40 dark:border-red-500/40';
        }
        if (isFocused || isOpen) {
            return 'border-primary dark:border-primary/60';
        }
        return 'border-gray-300 dark:border-slate-600 hover:border-gray-300 dark:hover:border-slate-600/50';
    };

    const getFocusRingStyles = () => {
        if (error) {
            return (isFocused || isOpen) ? 'ring-2 ring-red-500/10 dark:ring-red-500/10' : '';
        }
        return (isFocused || isOpen) ? 'ring-2 ring-primary/20 dark:ring-primary/10' : '';
    };

    const getTextColorStyle = () => {
        if (disabled) return 'text-gray-400 dark:text-slate-500';
        if (value === undefined || value === null || value === '') {
            return 'text-gray-400 dark:text-slate-400';
        }
        return 'text-gray-900 dark:text-slate-200';
    };

    return (
        <div className={`space-y-1.5 ${className}`}>
            {label && (
                <div className="flex items-center justify-between">
                    <label
                        htmlFor={name}
                        className={`text-sm font-medium ${disabled ? 'text-gray-400 dark:text-slate-500' : 'text-gray-700 dark:text-slate-300'}`}
                    >
                        {label}
                        {required && <span className="ml-1 text-red-500/70 dark:text-red-400/70">*</span>}
                    </label>
                </div>
            )}

            <div className="relative" ref={searchable ? dropdownRef : null}>
                {icon && <div className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-slate-400 z-10 pointer-events-none">{icon}</div>}

                {!searchable ? (
                    <>
                        <select
                            id={name}
                            name={name}
                            value={value === undefined || value === null ? '' : value}
                            onChange={(e) => onChange(e)}
                            onFocus={() => setIsFocused(true)}
                            onBlur={() => setIsFocused(false)}
                            disabled={disabled}
                            required={required}
                            className={`flex items-center ${baseStyles} ${getTextColorStyle()} border ${getBorderStyles()} ${getFocusRingStyles()} ${icon ? 'pl-10' : ''}`}
                        >
                            <option value="" disabled hidden>
                                {placeholder}
                            </option>
                            {options.map((option, index) => (
                                <option key={index} value={option.value} className="bg-white py-2 text-gray-900 dark:bg-slate-800 dark:text-slate-200">
                                    {option.label}
                                </option>
                            ))}
                        </select>
                        <div className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-slate-400 z-10">
                            <ChevronDown className="h-4 w-4" />
                        </div>
                    </>
                ) : (
                    <>
                        <input type="hidden" name={name} value={String(value || '')} />
                        <button
                            type="button"
                            disabled={disabled || readonly}
                            className={`flex items-center justify-between ${baseStyles} ${getTextColorStyle()} border ${getBorderStyles()} ${getFocusRingStyles()} ${icon ? 'pl-10' : ''}`}
                            onClick={() => {
                                if (!disabled && !readonly) setIsOpen(!isOpen);
                            }}
                        >
                            <span className="truncate pr-4 text-left flex-1" style={{ width: 'calc(100% - 1.5rem)' }}>
                                {selectedOption ? selectedOption.label : placeholder}
                            </span>
                            <ChevronDown className={`size-4 shrink-0 transition-transform duration-200 ${isOpen ? 'rotate-180 text-primary' : 'text-slate-400'}`} />
                        </button>

                        {isOpen && (
                            <div className="absolute z-50 mt-1 max-h-60 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800 shadow-black/5 flex flex-col">
                                <div className="border-b border-slate-100 p-2 dark:border-slate-700 shrink-0">
                                    <div className="relative">
                                        <Search className="absolute left-2.5 top-1/2 size-3.5 -translate-y-1/2 text-slate-400" />
                                        <input
                                            type="text"
                                            className="w-full rounded-md border-0 bg-slate-50 py-1.5 pl-8 pr-3 text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary dark:bg-slate-900 dark:text-white dark:focus:bg-slate-900"
                                            placeholder="Cari opsi..."
                                            value={search}
                                            onChange={(e) => setSearch(e.target.value)}
                                            onClick={(e) => e.stopPropagation()}
                                            onKeyDown={(e) => {
                                                if (e.key === 'Escape') setIsOpen(false);
                                                if (e.key === 'Enter') e.preventDefault();
                                            }}
                                            autoFocus
                                        />
                                    </div>
                                </div>
                                <ul className="flex-1 overflow-y-auto py-1">
                                    {filteredOptions.length > 0 ? (
                                        filteredOptions.map((option) => {
                                            const isSelected = String(option.value) === String(value);
                                            return (
                                                <li
                                                    key={option.value}
                                                    className={`flex cursor-pointer items-center justify-between px-3 py-2 text-sm transition-colors ${isSelected
                                                            ? 'bg-primary/5 text-primary dark:bg-primary/20 font-medium'
                                                            : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-700/50'
                                                        }`}
                                                    onClick={() => handleSelect(String(option.value))}
                                                >
                                                    <span className="truncate block pr-2">{option.label}</span>
                                                    {isSelected && <Check className="size-3.5 shrink-0" />}
                                                </li>
                                            );
                                        })
                                    ) : (
                                        <li className="px-3 py-4 text-center text-sm text-slate-500 dark:text-slate-400">
                                            Tidak ada data ditemukan
                                        </li>
                                    )}
                                </ul>
                            </div>
                        )}
                    </>
                )}
            </div>

            {helpText && !error && <p className="text-sm text-gray-500 dark:text-slate-400/80">{helpText}</p>}

            {error && (
                <div className="flex items-center gap-1.5 pt-1">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="size-4 shrink-0 text-red-500/70 dark:text-red-400/70">
                        <path
                            fillRule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                            clipRule="evenodd"
                        />
                    </svg>
                    <p className="text-sm text-red-500/70 dark:text-red-400/70">{error}</p>
                </div>
            )}
        </div>
    );
}
