import { Folder, Shield, FileText, User, Users, Layers } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import SidebarLink from '@/components/layouts/SideBarLink';

export default function ArchieveSidebar() {
    const { permissions } = usePage<{ permissions: string[] }>().props;

    const showKategori = permissions?.includes('lihat_kategori_arsip') || permissions?.includes('kelola_kategori_arsip');
    const showKlasifikasi = permissions?.includes('lihat_klasifikasi_arsip') || permissions?.includes('kelola_klasifikasi_arsip');
    const showArsipDigital = permissions?.includes('lihat_semua_arsip') || permissions?.includes('kelola_semua_arsip');
    const showArsipDivisi = permissions?.includes('lihat_arsip_divisi') || permissions?.includes('kelola_arsip_divisi');
    const showArsipPribadi = permissions?.includes('lihat_arsip_pribadi');

    const hasAnyArchievePermission = showKategori || showKlasifikasi || showArsipDigital || showArsipDivisi || showArsipPribadi;

    if (!hasAnyArchievePermission) return null;

    return (
        <div className="mb-6 space-y-1">
            <div className="py-2">
                <h3 className="inventory-sidebar-label text-[10px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400">Sistem Arsip Dokumen</h3>
            </div>

            {showKategori && (
                <>
                    <SidebarLink name="Konteks Arsip" href="/archieve/contexts" icon={Layers} />
                    <SidebarLink name="Kategori Arsip" href="/archieve/categories" icon={Folder} />
                </>
            )}

            {showKlasifikasi && (
                <SidebarLink name="Klasifikasi Dokumen" href="/archieve/classifications" icon={FileText} />
            )}

            {showArsipDigital && (
                <SidebarLink name="Arsip Digital" href="/archieve/archieves" icon={Shield} />
            )}

            {showArsipDivisi && (
                <SidebarLink name="Arsip Divisi" href="/archieve/divisi" icon={Users} />
            )}

            {showArsipPribadi && (
                <SidebarLink name="Arsip Pribadi" href="/archieve/pribadi" icon={User} />
            )}
        </div>
    );
}
