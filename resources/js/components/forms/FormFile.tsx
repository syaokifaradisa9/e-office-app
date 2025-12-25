import { useState, useRef, ChangeEvent } from 'react';
import { Upload, X, FileText, Image as ImageIcon, Plus, Trash2 } from 'lucide-react';

interface FilePreview {
    name: string;
    size: number;
    url: string;
}

interface FormFileProps {
    name: string;
    label?: string;
    onChange?: (e: { target: { name: string; value: FileList | null; files: FileList; type: string } }) => void;
    error?: string;
    accept?: string;
    multiple?: boolean;
    disabled?: boolean;
    required?: boolean;
    helpText?: string;
    className?: string;
}

export default function FormFile({
    name,
    label,
    onChange,
    error,
    accept,
    multiple = false,
    disabled = false,
    required = false,
    helpText,
    className = '',
}: FormFileProps) {
    const [files, setFiles] = useState<File[]>([]);
    const [previewUrls, setPreviewUrls] = useState<FilePreview[]>([]);
    const [isDragging, setIsDragging] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const handleFileChange = (e: ChangeEvent<HTMLInputElement>) => {
        const newlySelectedFiles = Array.from(e.target.files || []);
        processFiles(newlySelectedFiles);
    };

    const processFiles = (newFiles: File[]) => {
        if (newFiles.length > 0) {
            let updatedFiles: File[] = [];

            if (multiple) {
                updatedFiles = [...files, ...newFiles];
            } else {
                updatedFiles = [newFiles[0]];
            }

            const uniqueFiles = updatedFiles.filter(
                (file, index, self) =>
                    index === self.findIndex((f) => f.name === file.name && f.size === file.size && f.lastModified === file.lastModified),
            );

            updateStateAndNotify(uniqueFiles);
        }
    };

    const updateStateAndNotify = (newFiles: File[]) => {
        setFiles(newFiles);

        // Generate previews
        newFiles.forEach((file) => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    setPreviewUrls((prev) => {
                        const exists = prev.some((p) => p.name === file.name && p.size === file.size);
                        if (!exists) {
                            return [...prev, { name: file.name, size: file.size, url: e.target?.result as string }];
                        }
                        return prev;
                    });
                };
                reader.readAsDataURL(file);
            }
        });

        if (!multiple) {
            setPreviewUrls([]);
        }

        if (onChange) {
            const dataTransfer = new DataTransfer();
            newFiles.forEach((file) => dataTransfer.items.add(file));

            onChange({
                target: {
                    name: name,
                    value: dataTransfer.files,
                    files: dataTransfer.files,
                    type: 'file',
                },
            });
        }
    };

    const removeFile = (indexToRemove: number) => {
        const fileToRemove = files[indexToRemove];
        const newFiles = files.filter((_, index) => index !== indexToRemove);

        setFiles(newFiles);
        setPreviewUrls((prev) => prev.filter((p) => !(p.name === fileToRemove.name && p.size === fileToRemove.size)));

        if (onChange) {
            const dataTransfer = new DataTransfer();
            newFiles.forEach((file) => dataTransfer.items.add(file));
            onChange({
                target: {
                    name: name,
                    value: dataTransfer.files,
                    files: dataTransfer.files,
                    type: 'file',
                },
            });
        }

        if (fileInputRef.current) fileInputRef.current.value = '';
    };

    const clearAllFiles = () => {
        setFiles([]);
        setPreviewUrls([]);
        if (fileInputRef.current) fileInputRef.current.value = '';

        if (onChange) {
            onChange({
                target: {
                    name: name,
                    value: null,
                    files: new DataTransfer().files,
                    type: 'file',
                },
            });
        }
    };

    const onDragOver = (e: React.DragEvent) => {
        e.preventDefault();
        if (!disabled) setIsDragging(true);
    };

    const onDragLeave = (e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(false);
    };

    const onDrop = (e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(false);
        if (disabled) return;

        const droppedFiles = Array.from(e.dataTransfer.files);

        const filteredFiles = accept
            ? droppedFiles.filter((file) => {
                const acceptTypes = accept.split(',').map((t) => t.trim());
                return acceptTypes.some((type) => {
                    if (type.endsWith('/*')) {
                        return file.type.startsWith(type.replace('/*', ''));
                    }
                    return file.type === type || file.name.endsWith(type);
                });
            })
            : droppedFiles;

        if (filteredFiles.length > 0) {
            processFiles(filteredFiles);
        }
    };

    return (
        <div className={`space-y-2 ${className}`}>
            {label && (
                <div className="flex items-center justify-between">
                    <label htmlFor={name} className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {label} {required && <span className="text-red-500">*</span>}
                    </label>
                    {files.length > 0 && (
                        <button
                            type="button"
                            onClick={clearAllFiles}
                            className="flex items-center gap-1 text-xs text-red-500 hover:text-red-700"
                        >
                            <Trash2 className="size-3" /> Clear all
                        </button>
                    )}
                </div>
            )}

            <div
                className={`
                    relative rounded-xl border-2 border-dashed p-6 transition-all duration-200 ease-in-out
                    ${isDragging ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-600'}
                    ${error ? 'border-red-500 bg-red-50 dark:bg-red-900/10' : 'bg-gray-50 dark:bg-gray-800/50'}
                    ${disabled ? 'cursor-not-allowed opacity-60' : 'hover:bg-gray-100 dark:hover:bg-gray-800'}
                `}
                onDragOver={onDragOver}
                onDragLeave={onDragLeave}
                onDrop={onDrop}
            >
                <input
                    ref={fileInputRef}
                    id={name}
                    name={name}
                    type="file"
                    className="hidden"
                    onChange={handleFileChange}
                    accept={accept}
                    multiple={multiple}
                    disabled={disabled}
                />

                {files.length === 0 ? (
                    <div className="cursor-pointer text-center" onClick={() => !disabled && fileInputRef.current?.click()}>
                        <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-white text-gray-400 shadow-sm dark:bg-gray-700">
                            <Upload className="h-6 w-6 text-blue-500" />
                        </div>
                        <div className="text-sm text-gray-600 dark:text-gray-400">
                            <span className="font-semibold text-blue-600 hover:text-blue-500">Click to upload</span> or drag and
                            drop
                        </div>
                        <p className="mt-1 text-xs text-gray-500">
                            {accept ? accept.split(',').join(', ') : 'Any file'}
                            {multiple ? ' (Multiple allowed)' : ''}
                        </p>
                    </div>
                ) : (
                    <div className="space-y-4">
                        {/* File List */}
                        <div className="custom-scrollbar max-h-60 space-y-2 overflow-y-auto pr-2">
                            {files.map((file, index) => (
                                <div
                                    key={`${file.name}-${index}`}
                                    className="group flex items-center justify-between rounded-lg border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-600 dark:bg-gray-700"
                                >
                                    <div className="flex items-center gap-3 overflow-hidden">
                                        <div className="rounded-lg bg-blue-50 p-2 dark:bg-blue-900/30">
                                            {file.type.startsWith('image/') ? (
                                                <ImageIcon className="size-5 text-blue-500" />
                                            ) : (
                                                <FileText className="size-5 text-gray-500" />
                                            )}
                                        </div>
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium text-gray-900 dark:text-white">
                                                {file.name}
                                            </p>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                                {(file.size / 1024 / 1024).toFixed(2)} MB
                                            </p>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => removeFile(index)}
                                        className="rounded-full p-1.5 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/20"
                                    >
                                        <X className="size-4" />
                                    </button>
                                </div>
                            ))}
                        </div>

                        {/* Add More Button */}
                        {multiple && (
                            <button
                                type="button"
                                onClick={() => fileInputRef.current?.click()}
                                className="flex w-full items-center justify-center gap-2 rounded-lg border border-dashed border-blue-200 bg-blue-50 py-2 text-sm font-medium text-blue-600 transition-colors hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-400 dark:hover:bg-blue-900/30"
                            >
                                <Plus className="size-4" /> Add more files
                            </button>
                        )}
                    </div>
                )}
            </div>

            {/* Image Previews Grid */}
            {previewUrls.length > 0 && (
                <div className="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
                    {previewUrls.map((preview, index) => (
                        <div
                            key={index}
                            className="group relative aspect-square overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700"
                        >
                            <img src={preview.url} alt={preview.name} className="h-full w-full object-cover" />
                            <div className="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 transition-opacity group-hover:opacity-100">
                                <p className="w-full truncate px-2 text-center text-xs text-white">{preview.name}</p>
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {helpText && <p className="text-sm text-gray-500 dark:text-gray-400">{helpText}</p>}
            {error && <p className="text-sm text-red-600 dark:text-red-400">{error}</p>}
        </div>
    );
}
