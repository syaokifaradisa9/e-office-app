import { Head, usePage } from '@inertiajs/react';
import { useEffect, useState, type ReactNode } from 'react';
import toast, { Toaster } from 'react-hot-toast';

import SideBar from './SideBar';
import TopBar from './TopBar';

interface FlashMessage {
    type?: 'success' | 'error';
    message?: string;
}

interface PageProps {
    flash?: FlashMessage;
    [key: string]: unknown;
}

interface RootLayoutProps {
    title?: string;
    children: ReactNode;
    noPadding?: boolean;
    forceCollapse?: boolean;
    backPath?: string;
    onBackClick?: () => void;
    mobileSearchBar?: ReactNode;
    desktopSearchBar?: ReactNode;
}

export default function RootLayout({ title, children, noPadding = false, forceCollapse = false, backPath, onBackClick, mobileSearchBar, desktopSearchBar }: RootLayoutProps) {
    const [isSidebarOpen, setSidebarOpen] = useState(false);
    const [isSidebarCollapsed, setIsSidebarCollapsed] = useState(forceCollapse);
    const [isAutoCollapsed, setIsAutoCollapsed] = useState(false);

    useEffect(() => {
        if (forceCollapse) {
            setIsSidebarCollapsed(true);
            return;
        }

        const handleResize = () => {
            const width = window.innerWidth;
            if (width >= 768 && width < 1024) {
                setIsSidebarCollapsed(true);
                setIsAutoCollapsed(true);
            } else if (width >= 1024) {
                if (isAutoCollapsed) {
                    const stored = localStorage.getItem('sidebar_collapsed');
                    setIsSidebarCollapsed(stored === 'true');
                    setIsAutoCollapsed(false);
                }
            }
        };

        const stored = localStorage.getItem('sidebar_collapsed');
        const width = window.innerWidth;
        if (width >= 768 && width < 1024) {
            setIsSidebarCollapsed(true);
            setIsAutoCollapsed(true);
        } else {
            setIsSidebarCollapsed(stored === 'true');
        }

        window.addEventListener('resize', handleResize);
        return () => window.removeEventListener('resize', handleResize);
    }, [forceCollapse, isAutoCollapsed]);

    const toggleSidebarCollapse = () => {
        setIsSidebarCollapsed((prev) => {
            const nextValue = !prev;
            localStorage.setItem('sidebar_collapsed', nextValue ? 'true' : 'false');
            setIsAutoCollapsed(false);
            return nextValue;
        });
    };

    const { flash } = usePage<PageProps>().props;

    useEffect(() => {
        if (flash?.message) {
            if (flash.type === 'success') {
                toast.success(flash.message, { id: 'flash-toast' });
            }
            if (flash.type === 'error') {
                toast.error(flash.message, { id: 'flash-toast' });
            }
        }
    }, [flash]);

    return (
        <>
            <div className="fixed inset-0 bg-[#F5FAFA] dark:bg-slate-900" aria-hidden="true" />
            <div className="relative flex min-h-screen flex-col">
                <Toaster position="bottom-right" />
                <Head>
                    <title>{title ?? ''}</title>
                </Head>

                <TopBar
                    title={title}
                    isSidebarOpen={isSidebarOpen}
                    toggleSidebar={() => setSidebarOpen(!isSidebarOpen)}
                    isSidebarCollapsed={isSidebarCollapsed}
                    toggleSidebarCollapse={toggleSidebarCollapse}
                    backPath={backPath}
                    onBackClick={onBackClick}
                    mobileSearchBar={mobileSearchBar}
                    desktopSearchBar={desktopSearchBar}
                />

                <div className={`flex flex-1 md:pt-16 ${mobileSearchBar ? 'pt-[92px]' : 'pt-13'}`}>
                    <SideBar isOpen={isSidebarOpen} setIsOpen={setSidebarOpen} isCollapsed={isSidebarCollapsed} hasMobileSearchBar={!!mobileSearchBar} />
                    <main
                        className={`flex flex-1 flex-col overflow-y-auto ${noPadding ? 'p-0' : mobileSearchBar ? 'px-0 pt-0 md:px-8 md:pb-8 md:pt-4' : 'px-4 pb-6 pt-6 md:px-8 md:pb-8 md:pt-4'} ${isSidebarCollapsed ? (noPadding ? 'md:pl-[80px]' : 'md:pl-[calc(theme(spacing.8)+80px)]') : noPadding ? 'md:pl-[256px]' : 'md:pl-[calc(theme(spacing.8)+256px)]'}`}
                    >
                        <div className={`mx-auto w-full max-w-[1920px] flex-1 flex-col ${mobileSearchBar ? 'space-y-0 md:space-y-6' : 'space-y-0 md:space-y-6'}`}>{children}</div>
                    </main>
                </div>

                {isSidebarOpen && <div className="fixed inset-0 z-10 bg-black/40 lg:hidden" onClick={() => setSidebarOpen(false)} aria-hidden="true" />}
            </div>
        </>
    );
}
