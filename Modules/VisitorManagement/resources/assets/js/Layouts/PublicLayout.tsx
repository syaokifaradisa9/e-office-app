import { Head } from '@inertiajs/react';
import React, { ReactNode } from 'react';
import { Toaster } from 'react-hot-toast';

interface PublicLayoutProps {
    title: string;
    children: ReactNode;
    fullWidth?: boolean;
    hideHeader?: boolean;
}

export default function PublicLayout({ title, children, fullWidth = false, hideHeader = false }: PublicLayoutProps) {
    return (
        <div className="flex min-h-screen flex-col bg-slate-200 font-sans text-slate-900 dark:bg-slate-950 dark:text-slate-100">
            <Head title={title} />
            <Toaster position="top-center" />

            {!hideHeader && (
                <header className="border-b border-slate-200 bg-white/80 px-4 py-6 shadow-sm backdrop-blur-xl dark:border-slate-800 dark:bg-slate-900/80 sm:px-6 lg:px-8">
                    <div className="mx-auto flex max-w-7xl items-center justify-between">
                        <div className="flex items-center space-x-3">
                            <div className="flex size-10 items-center justify-center rounded-xl bg-emerald-500 shadow-lg shadow-emerald-500/20">
                                <span className="text-xl font-bold text-white">V</span>
                            </div>
                            <div>
                                <h1 className="text-xl font-bold tracking-tight">E-Office <span className="text-emerald-500 underline decoration-2 decoration-emerald-200 underline-offset-4">Visitor</span></h1>
                                <p className="text-xs font-medium text-slate-500 dark:text-slate-400">Sistem Buku Tamu Digital</p>
                            </div>
                        </div>
                    </div>
                </header>
            )}

            <main className={`flex flex-1 flex-col ${hideHeader ? '' : 'items-center justify-center px-4 py-6 sm:px-6 lg:px-8'}`}>
                <div className={fullWidth ? 'h-full w-full' : 'w-full max-w-4xl'}>
                    {children}
                </div>
            </main>

            {!hideHeader && (
                <footer className="border-t border-slate-200 px-4 py-6 text-center dark:border-slate-800">
                    <p className="text-sm text-slate-500 dark:text-slate-400">
                        &copy; {new Date().getFullYear()} E-Office. All rights reserved.
                    </p>
                </footer>
            )}
        </div>
    );
}
