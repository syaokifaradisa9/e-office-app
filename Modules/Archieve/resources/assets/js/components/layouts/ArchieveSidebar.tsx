import { Folder, FileText, Layers, HardDrive, FileArchive, Search, BarChart3 } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import SidebarLink from '@/components/layouts/SideBarLink';
import { useSidebarCollapse } from '@/components/layouts/SidebarContext';
import { ArchievePermission } from '@/enums/ArchievePermission';
import CheckPermissions from '@/components/utils/CheckPermissions';

export default function ArchieveSidebar() {
    const { url = '' } = usePage();
    const isCollapsed = useSidebarCollapse();

    return (
        <div className="mb-6 space-y-4">
            {/* Data Master Arsip */}
            <CheckPermissions
                permissions={[
                    ArchievePermission.VIEW_CATEGORY,
                    ArchievePermission.MANAGE_CATEGORY,
                    ArchievePermission.VIEW_CLASSIFICATION,
                    ArchievePermission.MANAGE_CLASSIFICATION
                ]}
            >
                <div className="space-y-1">
                    <div className="py-2">
                        <h3 className={`inventory-sidebar-label text-[10px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400 ${isCollapsed ? 'text-center' : ''}`}>Data Master Arsip</h3>
                    </div>
                    <CheckPermissions
                        permissions={[
                            ArchievePermission.VIEW_CATEGORY,
                            ArchievePermission.MANAGE_CATEGORY
                        ]}
                    >
                        <SidebarLink name="Konteks Arsip" href="/archieve/contexts" icon={Layers} />
                        <SidebarLink name="Kategori Arsip" href="/archieve/categories" icon={Folder} />
                    </CheckPermissions>
                    <CheckPermissions
                        permissions={[
                            ArchievePermission.VIEW_CLASSIFICATION,
                            ArchievePermission.MANAGE_CLASSIFICATION
                        ]}
                    >
                        <SidebarLink name="Klasifikasi Dokumen" href="/archieve/classifications" icon={FileText} />
                    </CheckPermissions>
                </div>
            </CheckPermissions>

            {/* Pengelolaan Arsip */}
            <CheckPermissions
                permissions={[
                    ArchievePermission.VIEW_DIVISION_STORAGE,
                    ArchievePermission.MANAGE_DIVISION_STORAGE,
                    ArchievePermission.VIEW_ALL,
                    ArchievePermission.MANAGE_ALL,
                    ArchievePermission.VIEW_DIVISION,
                    ArchievePermission.MANAGE_DIVISION,
                    ArchievePermission.VIEW_PERSONAL,
                    ArchievePermission.SEARCH_ALL_SCOPE,
                    ArchievePermission.SEARCH_DIVISION_SCOPE,
                    ArchievePermission.SEARCH_PERSONAL_SCOPE
                ]}
            >
                <div className="space-y-1">
                    <div className="py-2">
                        <h3 className={`inventory-sidebar-label text-[10px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400 ${isCollapsed ? 'text-center' : ''}`}>Pengelolaan Arsip</h3>
                    </div>

                    <CheckPermissions
                        permissions={[
                            ArchievePermission.VIEW_DIVISION_STORAGE,
                            ArchievePermission.MANAGE_DIVISION_STORAGE
                        ]}
                    >
                        <SidebarLink name="Penyimpanan Divisi" href="/archieve/division-storages" icon={HardDrive} />
                    </CheckPermissions>

                    <CheckPermissions
                        permissions={[
                            ArchievePermission.VIEW_ALL,
                            ArchievePermission.MANAGE_ALL,
                            ArchievePermission.VIEW_DIVISION,
                            ArchievePermission.MANAGE_DIVISION,
                            ArchievePermission.VIEW_PERSONAL
                        ]}
                    >
                        <SidebarLink
                            name="Arsip Dokumen"
                            href="/archieve/documents"
                            icon={FileArchive}
                            active={url === '/archieve/documents' || (url.startsWith('/archieve/documents/') && !url.startsWith('/archieve/documents/search'))}
                        />
                    </CheckPermissions>

                    <CheckPermissions
                        permissions={[
                            ArchievePermission.SEARCH_ALL_SCOPE,
                            ArchievePermission.SEARCH_DIVISION_SCOPE,
                            ArchievePermission.SEARCH_PERSONAL_SCOPE
                        ]}
                    >
                        <SidebarLink
                            name="Pencarian Dokumen"
                            href="/archieve/documents/search"
                            icon={Search}
                            active={url.startsWith('/archieve/documents/search')}
                        />
                    </CheckPermissions>
                </div>
            </CheckPermissions>

            {/* Laporan Arsip */}
            <CheckPermissions
                permissions={[
                    ArchievePermission.VIEW_REPORT_DIVISION,
                    ArchievePermission.VIEW_REPORT_ALL
                ]}
            >
                <div className="space-y-1">
                    <div className="py-2">
                        <h3 className={`inventory-sidebar-label text-[10px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400 ${isCollapsed ? 'text-center' : ''}`}>Laporan Arsip</h3>
                    </div>
                    <CheckPermissions permissions={[ArchievePermission.VIEW_REPORT_DIVISION]}>
                        <SidebarLink
                            name="Laporan Divisi"
                            href="/archieve/reports"
                            icon={BarChart3}
                            active={url === '/archieve/reports'}
                        />
                    </CheckPermissions>
                    <CheckPermissions permissions={[ArchievePermission.VIEW_REPORT_ALL]}>
                        <SidebarLink
                            name="Laporan Keseluruhan"
                            href="/archieve/reports/all"
                            icon={BarChart3}
                            active={url === '/archieve/reports/all'}
                        />
                    </CheckPermissions>
                </div>
            </CheckPermissions>
        </div>
    );
}
