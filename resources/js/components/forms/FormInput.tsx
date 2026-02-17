import { Eye, EyeOff, Calendar } from 'lucide-react';
import { forwardRef, useRef, useState, type InputHTMLAttributes, type ReactNode } from 'react';

interface FormInputProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'prefix'> {
    name: string;
    label?: string;
    error?: string;
    helpText?: string;
    icon?: ReactNode;
    prefix?: ReactNode;
    suffix?: ReactNode;
    capture?: 'user' | 'environment';
}

function FormInputComponent(
    {
        name,
        label,
        type = 'text',
        value,
        placeholder,
        onChange,
        onKeyDown,
        error,
        readOnly = false,
        required = false,
        disabled = false,
        autoFocus = false,
        icon,
        helpText,
        className = '',
        prefix,
        suffix,
        min,
        max,
        step,
        capture,
        ...props
    }: FormInputProps,
    forwardedRef: React.ForwardedRef<HTMLInputElement>,
) {
    const [showPassword, setShowPassword] = useState(false);
    const [isFocused, setIsFocused] = useState(false);
    const dateInputRef = useRef<HTMLInputElement>(null);

    const isDateType = (inputType: string) => ['date', 'month', 'time', 'datetime-local'].includes(inputType);

    const baseStyles = `
        h-11 text-sm transition-all duration-200 outline-none flex-1 flex items-center leading-normal
        ${disabled ? 'opacity-60 cursor-not-allowed' : ''}
        ${readOnly ? 'cursor-not-allowed bg-gray-50 dark:bg-slate-800/40' : 'bg-white dark:bg-slate-800/40'}
    `;

    const getInputPadding = () => {
        let paddingClasses = 'px-4';
        if (icon) paddingClasses = `${paddingClasses} pl-10`;
        if (type === 'password' || isDateType(type)) paddingClasses = `${paddingClasses} pr-10`;
        return paddingClasses;
    };

    const getBorderStyles = () => {
        if (error) return 'border-red-500/40 dark:border-red-500/40';
        if (isFocused) return 'border-primary dark:border-primary/60';
        return 'border-gray-400/50 dark:border-slate-600 dark:hover:border-slate-600/50';
    };

    const getFocusRingStyles = () => {
        if (error) return 'focus:ring-red-500/10 dark:focus:ring-red-500/10';
        return 'focus:ring-primary/20 dark:focus:ring-primary/10';
    };

    const getInputClassName = () => `
        ${baseStyles}
        ${getFocusRingStyles()}
        ${getInputPadding()}
        w-full
        focus:ring-2
        text-gray-900
        dark:text-slate-200
        placeholder:text-gray-400
        dark:placeholder:text-slate-400
        border-0
        min-w-0
        text-sm
        leading-[44px]
        flex
        items-center
        h-11
        ${isDateType(type) ? 'cursor-pointer hover:bg-gray-50 dark:hover:bg-slate-800/60' : ''}
    `;

    const handleDateFieldClick = (e: React.MouseEvent) => {
        if (!disabled && !readOnly && dateInputRef.current) {
            e.preventDefault();
            dateInputRef.current.showPicker();
        }
    };

    const renderPrefix = () => {
        if (!prefix) return null;
        return (
            <div className="flex h-full items-center justify-center whitespace-nowrap border-r border-gray-400/50 text-gray-500 dark:border-slate-600 dark:text-slate-300">
                <div className="flex h-full items-center justify-center px-4">
                    <span className="inline-flex items-center text-sm leading-[44px]">{prefix}</span>
                </div>
            </div>
        );
    };

    const renderSuffix = () => {
        if (!suffix) return null;
        return (
            <div className="flex h-full items-center justify-center whitespace-nowrap border-l border-gray-400/50 text-gray-500 dark:border-slate-600 dark:text-slate-300">
                <div className="flex h-full items-center justify-center px-4">
                    <span className="inline-flex items-center text-sm leading-[44px]">{suffix}</span>
                </div>
            </div>
        );
    };

    const renderLabel = () => {
        if (!label) return null;
        return (
            <div className="flex items-center justify-between">
                <label
                    htmlFor={name}
                    className={`text-sm font-medium ${disabled ? 'text-gray-400 dark:text-slate-500' : 'text-gray-700 dark:text-slate-300'}`}
                >
                    {label}
                    {required && <span className="ml-1 text-red-500/70 dark:text-red-400/70">*</span>}
                </label>
            </div>
        );
    };

    const renderHelpText = () => {
        if (!helpText || error) return null;
        return <p className="text-sm text-gray-700 dark:text-slate-300/80">{helpText}</p>;
    };

    const renderError = () => {
        if (!error) return null;
        return (
            <div className="flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="size-4 text-red-500/70 dark:text-red-400/70">
                    <path
                        fillRule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                        clipRule="evenodd"
                    />
                </svg>
                <p className="text-sm text-red-500/70 dark:text-red-400/70">{error}</p>
            </div>
        );
    };

    const renderDateTimeInput = () => (
        <div className="relative flex-1" onClick={handleDateFieldClick}>
            <input
                ref={dateInputRef}
                id={name}
                type={type}
                name={name}
                value={value}
                onChange={onChange}
                disabled={disabled}
                readOnly={readOnly}
                required={required}
                placeholder={placeholder}
                min={min}
                max={max}
                onFocus={() => setIsFocused(true)}
                onBlur={() => setIsFocused(false)}
                className={getInputClassName()}
                {...props}
            />
            <button
                type="button"
                onClick={handleDateFieldClick}
                className="absolute right-3 top-1/2 z-10 -translate-y-1/2 text-gray-400 transition-colors hover:text-gray-600 dark:text-slate-400 dark:hover:text-slate-300"
            >
                <Calendar className="size-4" />
            </button>
        </div>
    );

    return (
        <div className={`space-y-1.5 ${className}`}>
            {renderLabel()}
            <div className="relative w-full">
                {icon && <div className="absolute left-3 top-1/2 z-10 -translate-y-1/2 text-gray-400 dark:text-slate-400">{icon}</div>}

                <div className={`flex overflow-hidden rounded-lg border ${getBorderStyles()}`}>
                    {prefix && renderPrefix()}
                    {isDateType(type) ? (
                        renderDateTimeInput()
                    ) : (
                        <input
                            ref={forwardedRef}
                            id={name}
                            type={type === 'password' ? (showPassword ? 'text' : 'password') : type}
                            name={name}
                            value={value}
                            onChange={onChange}
                            onKeyDown={onKeyDown}
                            disabled={disabled}
                            readOnly={readOnly}
                            required={required}
                            placeholder={placeholder}
                            min={min}
                            max={max}
                            step={step}
                            autoFocus={autoFocus}
                            onFocus={() => setIsFocused(true)}
                            onBlur={() => setIsFocused(false)}
                            className={getInputClassName()}
                            capture={capture}
                            {...props}
                        />
                    )}
                    {suffix && renderSuffix()}
                </div>

                {type === 'password' && (
                    <button
                        type="button"
                        onClick={() => setShowPassword(!showPassword)}
                        className="absolute right-3 top-1/2 z-10 -translate-y-1/2 text-gray-400 transition-colors hover:text-gray-600 dark:text-slate-400 dark:hover:text-slate-300"
                    >
                        {showPassword ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
                    </button>
                )}
            </div>

            {renderHelpText()}
            {renderError()}
        </div>
    );
}

const FormInput = forwardRef(FormInputComponent);
export default FormInput;
