import { BarChart3, ClipboardList, MessageSquare, MessageSquareText, Settings, UserPlus, Users } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import SidebarLink from '@/components/layouts/SideBarLink';

export default function VisitorSidebar() {
    const { url } = usePage();
    const { permissions } = usePage<{ permissions: string[] }>().props;

    // Check for any visitor management permission
    const hasAnyVisitorPermission =
        // Dashboard
        permissions?.includes('lihat_dashboard_pengunjung') ||
        // Data Pengunjung
        permissions?.includes('lihat_data_pengunjung') ||
        permissions?.includes('konfirmasi_kunjungan') ||
        // Ulasan
        permissions?.includes('lihat_ulasan_pengunjung') ||
        // Laporan
        permissions?.includes('lihat_laporan_pengunjung') ||
        // Undangan
        permissions?.includes('buat_undangan_tamu') ||
        // Master Data
        permissions?.includes('lihat_master_manajemen_pengunjung') ||
        permissions?.includes('kelola_master_manajemen_pengunjung') ||
        // Pertanyaan Feedback
        permissions?.includes('lihat_pertanyaan_feedback') ||
        permissions?.includes('kelola_pertanyaan_feedback');

    if (!hasAnyVisitorPermission) return null;

    // Check specific permission groups
    const showDashboard = permissions?.includes('lihat_dashboard_pengunjung');
    const showDataPengunjung = permissions?.includes('lihat_data_pengunjung') || permissions?.includes('konfirmasi_kunjungan');
    const showUlasan = permissions?.includes('lihat_ulasan_pengunjung');
    const showLaporan = permissions?.includes('lihat_laporan_pengunjung');
    const showUndangan = permissions?.includes('buat_undangan_tamu');
    const showMasterData = permissions?.includes('lihat_master_manajemen_pengunjung') || permissions?.includes('kelola_master_manajemen_pengunjung');
    const showFeedbackQuestions = permissions?.includes('lihat_pertanyaan_feedback') || permissions?.includes('kelola_pertanyaan_feedback');

    // Manually calculate active state for Visitor list to avoid overlap with sub-routes
    const isVisitorListActive = url === '/visitor' || /^\/visitor\/\d+(\/success)?$/.test(url);

    return (
        <div className="mb-6 space-y-6">
            {/* Group 1: Manajemen Pengunjung */}
            {(showDashboard || showDataPengunjung || showUndangan) && (
                <div className="space-y-1">
                    <div className="py-2">
                        <h3 className="visitor-sidebar-label text-[10px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400">
                            Manajemen Pengunjung
                        </h3>
                    </div>
                    {showDashboard && (
                        <SidebarLink name="Dashboard Pengunjung" href="/visitor/dashboard" icon={BarChart3} />
                    )}
                    {showDataPengunjung && (
                        <SidebarLink name="Pengunjung" href="/visitor" icon={Users} active={isVisitorListActive} />
                    )}
                    {showUndangan && (
                        <SidebarLink name="Buat Undangan" href="/visitor?tab=invitation" icon={UserPlus} />
                    )}
                </div>
            )}

            {/* Group 2: Laporan & Ulasan */}
            {(showUlasan || showLaporan) && (
                <div className="space-y-1">
                    <div className="py-2">
                        <h3 className="visitor-sidebar-label text-[10px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400">
                            Laporan & Ulasan
                        </h3>
                    </div>
                    {showUlasan && (
                        <SidebarLink name="Ulasan Pengunjung" href="/visitor/feedback" icon={MessageSquare} />
                    )}
                    {showLaporan && (
                        <SidebarLink name="Laporan Pengunjung" href="/visitor/reports" icon={ClipboardList} />
                    )}
                </div>
            )}

            {/* Group 3: Data Master Pengunjung */}
            {(showMasterData || showFeedbackQuestions) && (
                <div className="space-y-1">
                    <div className="py-2">
                        <h3 className="visitor-sidebar-label text-[10px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400">
                            Data Master Pengunjung
                        </h3>
                    </div>
                    {showMasterData && (
                        <SidebarLink name="Keperluan Kunjungan" href="/visitor/purposes" icon={Settings} />
                    )}
                    {showFeedbackQuestions && (
                        <SidebarLink name="Pertanyaan Feedback" href="/visitor/feedback-questions" icon={MessageSquareText} />
                    )}
                </div>
            )}
        </div>
    );
}

