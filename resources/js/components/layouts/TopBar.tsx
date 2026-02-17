import { Link, usePage } from '@inertiajs/react';
import { ChevronLeft, Menu } from 'lucide-react';
import type { ReactNode } from 'react';

import ProfileDropdown from './ProfileDropdown';

interface TopBarProps {
    title?: string;
    toggleSidebar: () => void;
    isSidebarCollapsed: boolean;
    toggleSidebarCollapse: () => void;
    isSidebarOpen: boolean;
    backPath?: string;
    onBackClick?: () => void;
    mobileSearchBar?: ReactNode;
    desktopSearchBar?: ReactNode;
}

export default function TopBar({ title, toggleSidebar, toggleSidebarCollapse, backPath, onBackClick, mobileSearchBar, desktopSearchBar }: TopBarProps) {
    const { component } = usePage();

    const getPageTitle = (): string => {
        if (title) return title;
        if (!component) return 'E-Office';

        const titleMap: Record<string, string> = {
            'Dashboard': 'Dashboard',
            'Division/Index': 'Divisi',
            'Division/Create': 'Tambah Divisi',
            'Position/Index': 'Jabatan',
            'Position/Create': 'Tambah Jabatan',
            'User/Index': 'Pengguna',
            'User/Create': 'Tambah Pengguna',
            'Role/Index': 'Role & Permission',
            'Role/Create': 'Tambah Role',
            'Profile/Edit': 'Ubah Profil',
            'Profile/Password': 'Ubah Password',
        };

        return titleMap[component] || 'E-Office';
    };

    return (
        <header className="fixed inset-x-0 top-0 z-40">
            {/* Desktop Header */}
            <div className="hidden border-b border-gray-300/90 dark:border-slate-700/50 md:block">
                <div className="bg-white/80 backdrop-blur-sm dark:bg-slate-800/80">
                    <div className="max-w-8xl mx-auto flex h-16 w-full items-center justify-between gap-4 pl-0 pr-4 md:pl-0">
                        <div className="flex w-64 items-center gap-3 border-r border-primary/10 md:pl-4">
                            <button
                                onClick={toggleSidebarCollapse}
                                className="rounded-lg p-1.5 text-slate-600 transition-colors duration-200 hover:bg-[#E6F5F5] dark:text-slate-400 dark:hover:bg-slate-700/50"
                            >
                                <Menu className="size-6" />
                            </button>
                        </div>

                        {/* Desktop Search Bar */}
                        {desktopSearchBar && (
                            <div className="flex flex-1 items-center px-4">
                                <div className="w-full max-w-2xl">{desktopSearchBar}</div>
                            </div>
                        )}

                        <div className={`${!desktopSearchBar ? 'ml-auto' : ''} flex items-center gap-3 pr-4`}>
                            <ProfileDropdown />
                        </div>
                    </div>
                </div>
            </div>

            {/* Mobile Header */}
            <div className="md:hidden">
                <div className="bg-white shadow-sm dark:bg-slate-800">
                    <div className="flex h-13 items-center justify-between px-2">
                        {/* Left: Back Button or Hamburger Menu */}
                        {(backPath || onBackClick) ? (
                            onBackClick ? (
                                <button
                                    onClick={onBackClick}
                                    className="rounded-lg p-2 text-slate-600 transition-all duration-200 hover:bg-gray-100 active:scale-95 active:bg-gray-200 dark:text-white dark:hover:bg-slate-700"
                                    aria-label="Go back"
                                >
                                    <ChevronLeft className="size-5" />
                                </button>
                            ) : (
                                <Link
                                    href={backPath!}
                                    className="rounded-lg p-2 text-slate-600 transition-all duration-200 hover:bg-gray-100 active:scale-95 active:bg-gray-200 dark:text-white dark:hover:bg-slate-700"
                                    aria-label="Go back"
                                >
                                    <ChevronLeft className="size-5" />
                                </Link>
                            )
                        ) : (
                            <button
                                onClick={toggleSidebar}
                                className="rounded-lg p-2 text-slate-600 transition-all duration-200 hover:bg-gray-100 active:scale-95 active:bg-gray-200 dark:text-white dark:hover:bg-slate-700"
                                aria-label="Toggle menu"
                            >
                                <Menu className="size-5" />
                            </button>
                        )}

                        {/* Center: Page Title */}
                        <div className="flex flex-1 items-center justify-center">
                            <h1 className="max-w-[200px] truncate text-base font-semibold tracking-wide text-slate-800 dark:text-white">{getPageTitle()}</h1>
                        </div>

                        {/* Right: Profile */}
                        <div className="flex items-center">
                            <ProfileDropdown isMobile={true} />
                        </div>
                    </div>

                    {/* Mobile Search Bar - Inside TopBar */}
                    {mobileSearchBar && <div className="px-4 pb-1 pt-0.5">{mobileSearchBar}</div>}
                </div>
            </div>
        </header>
    );
}
