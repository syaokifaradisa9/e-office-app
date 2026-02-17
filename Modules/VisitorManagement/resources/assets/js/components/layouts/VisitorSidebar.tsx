import { Users, ClipboardList, MessageSquare, FileText } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import SidebarLink from '@/components/layouts/SideBarLink';
import { useSidebarCollapse } from '@/components/layouts/SidebarContext';

export default function VisitorSidebar() {
    const { permissions } = usePage<{ permissions: string[] }>().props;
    const isCollapsed = useSidebarCollapse();

    const hasAnyVisitorPermission =
        // Visitor Data
        permissions?.includes('lihat_data_pengunjung') || permissions?.includes('konfirmasi_kunjungan') || permissions?.includes('buat_undangan_tamu') ||
        // Master Data
        permissions?.includes('lihat_master_manajemen_pengunjung') || permissions?.includes('kelola_master_manajemen_pengunjung') ||
        // Feedback Question
        permissions?.includes('lihat_pertanyaan_feedback') || permissions?.includes('kelola_pertanyaan_feedback') ||
        // Criticism Feedback
        permissions?.includes('lihat_kritik_saran_pengunjung') ||
        // Report
        permissions?.includes('lihat_laporan_pengunjung');

    if (!hasAnyVisitorPermission) return null;

    return (
        <div className="mb-6 space-y-6">

            {/* Group 1: Master Data */}
            {(permissions?.includes('lihat_master_manajemen_pengunjung') || permissions?.includes('kelola_master_manajemen_pengunjung') ||
                permissions?.includes('lihat_pertanyaan_feedback') || permissions?.includes('kelola_pertanyaan_feedback')) && (
                    <div className="space-y-1">
                        <div className="py-2">
                            <h3 className={`visitor-sidebar-label text-[10px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400 ${isCollapsed ? 'text-center' : ''}`}>Master Data Pengunjung</h3>
                        </div>
                        {(permissions?.includes('lihat_master_manajemen_pengunjung') || permissions?.includes('kelola_master_manajemen_pengunjung')) && (
                            <SidebarLink name="Keperluan Kunjungan" href="/visitor/purposes" icon={ClipboardList} />
                        )}
                        {(permissions?.includes('lihat_pertanyaan_feedback') || permissions?.includes('kelola_pertanyaan_feedback')) && (
                            <SidebarLink name="Pertanyaan Feedback" href="/visitor/feedback-questions" icon={MessageSquare} />
                        )}
                    </div>
                )}

            {/* Group 2: Data Pengunjung */}
            {(permissions?.includes('lihat_data_pengunjung') || permissions?.includes('konfirmasi_kunjungan') || permissions?.includes('buat_undangan_tamu')) && (
                <div className="space-y-1">
                    <div className="py-2">
                        <h3 className={`visitor-sidebar-label text-[10px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400 ${isCollapsed ? 'text-center' : ''}`}>Data Pengunjung</h3>
                    </div>
                    <SidebarLink name="Daftar Pengunjung" href="/visitor" icon={Users} exactMatch />
                    {permissions?.includes('lihat_kritik_saran_pengunjung') && (
                        <SidebarLink name="Kritik dan Saran" href="/visitor/criticism-suggestions" icon={MessageSquare} />
                    )}
                </div>
            )}

            {/* Group 3: Laporan */}
            {permissions?.includes('lihat_laporan_pengunjung') && (
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
