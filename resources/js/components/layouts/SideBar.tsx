import { Briefcase, Building2, LayoutDashboard, Shield, Users } from 'lucide-react';
import { useLayoutEffect, useRef } from 'react';

import { SidebarCollapseContext } from './SidebarContext';
import SidebarLink from './SideBarLink';

interface SideBarProps {
    isOpen: boolean;
    setIsOpen: (open: boolean) => void;
    isCollapsed: boolean;
    hasMobileSearchBar?: boolean;
}

export default function SideBar({ isOpen, setIsOpen, isCollapsed, hasMobileSearchBar = false }: SideBarProps) {
    const scrollRef = useRef<HTMLDivElement>(null);

    useLayoutEffect(() => {
        const savedScroll = sessionStorage.getItem('sidebarScroll');
        if (scrollRef.current && savedScroll) {
            scrollRef.current.scrollTop = Number(savedScroll);
        }
    }, []);

    const handleScroll = (e: React.UIEvent<HTMLDivElement>) => {
        sessionStorage.setItem('sidebarScroll', String(e.currentTarget.scrollTop));
    };

    return (
        <>
            {isOpen && <div className="fixed inset-0 z-25 bg-slate-900/60 md:hidden" onClick={() => setIsOpen(false)} />}

            <SidebarCollapseContext.Provider value={isCollapsed}>
                <aside
                    className={`
                        fixed left-0 w-64 bg-white dark:bg-slate-800 transform transition-all duration-300 ease-in-out border-r border-gray-300/90 dark:border-slate-700/50
                        ${hasMobileSearchBar ? 'top-[108px]' : 'top-14'} bottom-0 z-30 border-t border-t-gray-200 dark:border-t-slate-700
                        md:top-0 md:bottom-0 md:z-0 md:translate-x-0 md:border-t-0
                        ${isCollapsed ? 'md:w-20' : 'md:w-64'}
                        ${isOpen ? 'translate-x-0' : '-translate-x-full'}
                    `}
                >
                    <div className="flex h-full flex-col">
                        {/* Header - Desktop only */}
                        <div className="relative hidden h-16 flex-shrink-0 items-center justify-start border-b border-gray-300/90 px-2 dark:border-slate-700/50 md:flex"></div>
                        <div className="flex min-h-0 flex-1 flex-col">
                            <div ref={scrollRef} onScroll={handleScroll} className={`custom-scrollbar flex-1 overflow-y-auto ${isCollapsed ? 'overflow-x-hidden' : ''}`}>
                                <div className={`${hasMobileSearchBar ? 'pt-8' : 'pt-3'} py-5 transition-all duration-300 lg:pt-0 ${isCollapsed ? 'px-2' : 'px-3'}`}>
                                    {/* Beranda */}
                                    <div className="mb-6">
                                        <div className="py-2">
                                            <h3 className={`text-xs font-medium tracking-wider text-slate-500 dark:text-slate-400 ${isCollapsed ? 'hidden' : ''}`}>Beranda</h3>
                                        </div>
                                        <div className="space-y-1">
                                            <SidebarLink name="Dashboard" href="/dashboard" icon={LayoutDashboard} />
                                        </div>
                                    </div>

                                    {/* Data Master */}
                                    <div className="mb-6">
                                        <div className="py-2">
                                            <h3 className={`text-xs font-medium tracking-wider text-slate-500 dark:text-slate-400 ${isCollapsed ? 'hidden' : ''}`}>Data Master</h3>
                                        </div>
                                        <div className="space-y-1">
                                            <SidebarLink name="Divisi" href="/division" icon={Building2} />
                                            <SidebarLink name="Jabatan" href="/position" icon={Briefcase} />
                                            <SidebarLink name="Pegawai" href="/user" icon={Users} />
                                            <SidebarLink name="Role & Permission" href="/role" icon={Shield} />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {!isCollapsed && (
                                <div className="flex-shrink-0 p-3">
                                    <div className="rounded-lg bg-[#E6F5F5] px-3 py-2.5 dark:bg-slate-700/30">
                                        <div className="mt-1.5 flex flex-col items-center">
                                            <div className="flex text-xs text-slate-500 dark:text-slate-400">
                                                E-Office
                                                <svg className="mx-1 size-3.5 animate-pulse text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                                                </svg>
                                            </div>
                                            <div className="text-xs text-slate-500 dark:text-slate-400">Sistem Manajemen Kantor</div>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </aside>
            </SidebarCollapseContext.Provider>
        </>
    );
}
