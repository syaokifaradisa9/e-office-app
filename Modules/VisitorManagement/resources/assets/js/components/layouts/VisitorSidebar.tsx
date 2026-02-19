import { Users, ClipboardList, MessageSquare, FileText } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import SidebarLink from '@/components/layouts/SideBarLink';
import { useSidebarCollapse } from '@/components/layouts/SidebarContext';
import { VisitorPermission } from '@/enums/VisitorPermission';

export default function VisitorSidebar() {
    const { permissions } = usePage<{ permissions: string[] }>().props;
    const isCollapsed = useSidebarCollapse();

    const hasAnyVisitorPermission = permissions?.some(p =>
        Object.values(VisitorPermission).includes(p as any)
    );

    if (!hasAnyVisitorPermission) return null;

    return (
        <div className="mb-6 space-y-6">

            {/* Group 1: Master Data */}
            {(permissions?.includes(VisitorPermission.VIEW_MASTER) || permissions?.includes(VisitorPermission.MANAGE_MASTER) ||
                permissions?.includes(VisitorPermission.VIEW_FEEDBACK_QUESTION) || permissions?.includes(VisitorPermission.MANAGE_FEEDBACK_QUESTION)) && (
                    <div className="space-y-1">
                        <div className="py-2">
                            <h3 className={`visitor-sidebar-label text-[10px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400 ${isCollapsed ? 'text-center' : ''}`}>Master Data Pengunjung</h3>
                        </div>
                        {(permissions?.includes(VisitorPermission.VIEW_MASTER) || permissions?.includes(VisitorPermission.MANAGE_MASTER)) && (
                            <SidebarLink name="Keperluan Kunjungan" href="/visitor/purposes" icon={ClipboardList} />
                        )}
                        {(permissions?.includes(VisitorPermission.VIEW_FEEDBACK_QUESTION) || permissions?.includes(VisitorPermission.MANAGE_FEEDBACK_QUESTION)) && (
                            <SidebarLink name="Pertanyaan Feedback" href="/visitor/feedback-questions" icon={MessageSquare} />
                        )}
                    </div>
                )}

            {/* Group 2: Data Pengunjung */}
            {(permissions?.includes(VisitorPermission.VIEW_DATA) || permissions?.includes(VisitorPermission.CONFIRM_VISIT) || permissions?.includes(VisitorPermission.CREATE_INVITATION)) && (
                <div className="space-y-1">
                    <div className="py-2">
                        <h3 className={`visitor-sidebar-label text-[10px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400 ${isCollapsed ? 'text-center' : ''}`}>Data Pengunjung</h3>
                    </div>
                    <SidebarLink name="Daftar Pengunjung" href="/visitor" icon={Users} exactMatch />
                    {permissions?.includes(VisitorPermission.VIEW_CRITICISM_FEEDBACK) && (
                        <SidebarLink name="Kritik dan Saran" href="/visitor/criticism-suggestions" icon={MessageSquare} />
                    )}
                </div>
            )}

            {/* Group 3: Laporan */}
            {permissions?.includes(VisitorPermission.VIEW_REPORT) && (
                <div className="space-y-1">
                    <div className="py-2">
                        <h3 className={`visitor-sidebar-label text-[10px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400 ${isCollapsed ? 'text-center' : ''}`}>Laporan Pengunjung</h3>
                    </div>
                    <SidebarLink name="Laporan" href="/visitor/reports" icon={FileText} />
                </div>
            )}
        </div>
    );
}
