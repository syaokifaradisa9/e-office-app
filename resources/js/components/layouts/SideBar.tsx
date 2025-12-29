import { ArrowLeftRight, BarChart3, Briefcase, Building2, ClipboardCheck, Folder, LayoutDashboard, Package, Shield, ShoppingCart, Users } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import { useLayoutEffect, useRef } from 'react';

import InventorySidebar from '../../../../Modules/Inventory/resources/assets/js/components/layouts/InventorySidebar';
import ArchieveSidebar from '../../../../Modules/Archieve/resources/assets/js/components/layouts/ArchieveSidebar';
import VisitorSidebar from '../../../../Modules/VisitorManagement/resources/assets/js/components/layouts/VisitorSidebar';
import { SidebarCollapseContext } from './SidebarContext';
import SidebarLink from '@/components/layouts/SideBarLink';

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
                                    {(() => {
                                        const { permissions } = usePage<{ permissions: string[] }>().props;
                                        const showDivisi = permissions?.includes('lihat_divisi') || permissions?.includes('kelola_divisi');
                                        const showJabatan = permissions?.includes('lihat_jabatan') || permissions?.includes('kelola_jabatan');
                                        const showPegawai = permissions?.includes('lihat_pengguna') || permissions?.includes('kelola_pengguna');
                                        const showRole = permissions?.includes('lihat_role') || permissions?.includes('kelola_role');

                                        if (!showDivisi && !showJabatan && !showPegawai && !showRole) return null;

                                        return (
                                            <div className="mb-6">
                                                <div className="py-2">
                                                    <h3 className={`text-xs font-medium tracking-wider text-slate-500 dark:text-slate-400 ${isCollapsed ? 'hidden' : ''}`}>Data Master</h3>
                                                </div>
                                                <div className="space-y-1">
                                                    {showDivisi && <SidebarLink name="Divisi" href="/division" icon={Building2} />}
                                                    {showJabatan && <SidebarLink name="Jabatan" href="/position" icon={Briefcase} />}
                                                    {showPegawai && <SidebarLink name="Pegawai" href="/user" icon={Users} />}
                                                    {showRole && <SidebarLink name="Role & Permission" href="/role" icon={Shield} />}
                                                </div>
                                            </div>
                                        );
                                    })()}

                                    {/* Inventory */}
                                    <InventorySidebar />

                                    {/* Archieve */}
                                    <ArchieveSidebar />

                                    {/* Visitor Management */}
                                    <VisitorSidebar />
                                </div>
                            </div>
                        </div>
                        {/* Footer - Always at bottom */}
                        {!isCollapsed && (
                            <div className="mt-auto flex-shrink-0 p-3">
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
                </aside>
            </SidebarCollapseContext.Provider>
        </>
    );
}
