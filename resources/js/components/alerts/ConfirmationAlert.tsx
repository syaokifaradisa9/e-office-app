import { AlertTriangle, Check, Info, X } from 'lucide-react';
import { useEffect } from 'react';

interface ConfirmationAlertProps {
    isOpen: boolean;
    setOpenModalStatus: (status: boolean) => void;
    message: string;
    onConfirm: () => void;
    title?: string;
    confirmText?: string;
    cancelText?: string;
    type?: 'danger' | 'warning' | 'info' | 'success';
}

export default function ConfirmationAlert({
    isOpen,
    setOpenModalStatus,
    message,
    onConfirm,
    title = 'Konfirmasi',
    confirmText = 'Ya',
    cancelText = 'Batal',
    type = 'danger',
}: ConfirmationAlertProps) {
    useEffect(() => {
        const handleEscape = (e: KeyboardEvent) => {
            if (e.key === 'Escape') {
                setOpenModalStatus(false);
            }
        };

        if (isOpen) {
            document.addEventListener('keydown', handleEscape);
            document.body.style.overflow = 'hidden';
        }

        return () => {
            document.removeEventListener('keydown', handleEscape);
            document.body.style.overflow = 'unset';
        };
    }, [isOpen, setOpenModalStatus]);

    if (!isOpen) return null;

    const handleClose = () => {
        setOpenModalStatus(false);
    };

    const handleConfirm = () => {
        onConfirm();
        setOpenModalStatus(false);
    };

    const variants = {
        danger: {
            icon: <AlertTriangle className="h-6 w-6 text-red-500 dark:text-red-400" />,
            iconBg: 'bg-red-100 dark:bg-red-900/30',
            confirmButton: 'bg-red-500 hover:bg-red-600 focus:ring-red-500/20',
        },
        warning: {
            icon: <AlertTriangle className="h-6 w-6 text-amber-500 dark:text-amber-400" />,
            iconBg: 'bg-amber-100 dark:bg-amber-900/30',
            confirmButton: 'bg-amber-500 hover:bg-amber-600 focus:ring-amber-500/20',
        },
        info: {
            icon: <Info className="h-6 w-6 text-blue-500 dark:text-blue-400" />,
            iconBg: 'bg-blue-100 dark:bg-blue-900/30',
            confirmButton: 'bg-blue-500 hover:bg-blue-600 focus:ring-blue-500/20',
        },
        success: {
            icon: <Check className="h-6 w-6 text-green-500 dark:text-green-400" />,
            iconBg: 'bg-green-100 dark:bg-green-900/30',
            confirmButton: 'bg-green-500 hover:bg-green-600 focus:ring-green-500/20',
        },
    };

    return (
        <>
            <div className="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-black/50 p-4 backdrop-blur-sm">
                <div className="relative w-full max-w-md">
                    <div className="relative rounded-xl bg-white shadow-lg dark:bg-gray-800">
                        <button
                            type="button"
                            onClick={handleClose}
                            className="absolute right-4 top-4 rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                        >
                            <X className="h-5 w-5" />
                            <span className="sr-only">Close modal</span>
                        </button>

                        <div className="p-6">
                            <div className={`mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full ${variants[type].iconBg}`}>{variants[type].icon}</div>

                            <div className="text-center">
                                <h3 className="mb-2 text-lg font-semibold text-gray-900 dark:text-white">{title}</h3>
                                <p className="text-sm text-gray-500 dark:text-gray-400">{message}</p>
                            </div>

                            <div className="mt-6 flex justify-center gap-3">
                                <button
                                    type="button"
                                    onClick={handleClose}
                                    className="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                                >
                                    {cancelText}
                                </button>
                                <button
                                    type="button"
                                    onClick={handleConfirm}
                                    className={`rounded-lg px-4 py-2 text-sm font-medium text-white focus:outline-none focus:ring-2 ${variants[type].confirmButton}`}
                                >
                                    {confirmText}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
