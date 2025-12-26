import { Folder, FileText, Layers, HardDrive, FileArchive, Search } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import SidebarLink from '@/components/layouts/SideBarLink';

export default function ArchieveSidebar() {
    const { permissions } = usePage<{ permissions: string[] }>().props;
    const { url } = usePage();

    const showKategori = permissions?.includes('lihat_kategori_arsip') || permissions?.includes('kelola_kategori_arsip');
    const showKlasifikasi = permissions?.includes('lihat_klasifikasi_arsip') || permissions?.includes('kelola_klasifikasi_arsip');
    const showPenyimpanan = permissions?.includes('lihat_penyimpanan_divisi') || permissions?.includes('kelola_penyimpanan_divisi');

    const hasLihatSemuaArsip = permissions?.includes('lihat_semua_arsip');
    const hasLihatDivisiArsip = permissions?.includes('lihat_arsip_divisi');
    const hasLihatPribadiArsip = permissions?.includes('lihat_arsip_pribadi');
    const showArsipDokumen = hasLihatSemuaArsip || hasLihatDivisiArsip || hasLihatPribadiArsip;

    const showPencarianDokumen = permissions?.includes('pencarian_dokumen_keseluruhan') ||
        permissions?.includes('pencarian_dokumen_divisi') ||
        permissions?.includes('pencarian_dokumen_pribadi');

    const hasMasterData = showKategori || showKlasifikasi;
    const hasPengelolaan = showPenyimpanan || showArsipDokumen || showPencarianDokumen;

    if (!hasMasterData && !hasPengelolaan) return null;

    return (
        <div className="mb-6 space-y-4">
            {/* Data Master Arsip */}
            {hasMasterData && (
                <div className="space-y-1">
                    <div className="py-2">
                        <h3 className="inventory-sidebar-label text-[10px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400">Data Master Arsip</h3>
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
                </div>
            )}

            {/* Pengelolaan Arsip */}
            {hasPengelolaan && (
                <div className="space-y-1">
                    <div className="py-2">
                        <h3 className="inventory-sidebar-label text-[10px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400">Pengelolaan Arsip</h3>
                    </div>
                    {showPenyimpanan && (
                        <SidebarLink name="Penyimpanan Divisi" href="/archieve/division-storages" icon={HardDrive} />
                    )}
                    {showArsipDokumen && (
                        <SidebarLink
                            name="Arsip Dokumen"
                            href="/archieve/documents"
                            icon={FileArchive}
                            active={url === '/archieve/documents' || (url.startsWith('/archieve/documents/') && !url.startsWith('/archieve/documents/search'))}
                        />
                    )}
                    {showPencarianDokumen && (
                        <SidebarLink
                            name="Pencarian Dokumen"
                            href="/archieve/documents/search"
                            icon={Search}
                            active={url.startsWith('/archieve/documents/search')}
                        />
                    )}
                </div>
            )}
        </div>
    );
}
