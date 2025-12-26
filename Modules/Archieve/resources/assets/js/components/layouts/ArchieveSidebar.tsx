import { Folder, FileText, Layers, HardDrive, FileArchive, Search, BarChart3 } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import SidebarLink from '@/components/layouts/SideBarLink';

export default function ArchieveSidebar() {
    const { permissions } = usePage<{ permissions: string[] }>().props;
    const { url } = usePage();

    // Master Data permissions
    const showKategori = permissions?.includes('lihat_kategori_arsip') || permissions?.includes('kelola_kategori_arsip');
    const showKlasifikasi = permissions?.includes('lihat_klasifikasi_arsip') || permissions?.includes('kelola_klasifikasi_arsip');
    const showPenyimpanan = permissions?.includes('lihat_penyimpanan_divisi') || permissions?.includes('kelola_penyimpanan_divisi');

    // Document permissions
    const hasLihatSemuaArsip = permissions?.includes('lihat_semua_arsip');
    const hasLihatDivisiArsip = permissions?.includes('lihat_arsip_divisi');
    const hasLihatPribadiArsip = permissions?.includes('lihat_arsip_pribadi');
    const showArsipDokumen = hasLihatSemuaArsip || hasLihatDivisiArsip || hasLihatPribadiArsip;

    const showPencarianDokumen = permissions?.includes('pencarian_dokumen_keseluruhan') ||
        permissions?.includes('pencarian_dokumen_divisi') ||
        permissions?.includes('pencarian_dokumen_pribadi');

    // Report permissions
    const showLaporanDivisi = permissions?.includes('lihat_laporan_arsip_divisi');
    const showLaporanAll = permissions?.includes('lihat_laporan_arsip_keseluruhan');
    const showLaporan = showLaporanDivisi || showLaporanAll;

    const hasMasterData = showKategori || showKlasifikasi;
    const hasPengelolaan = showPenyimpanan || showArsipDokumen || showPencarianDokumen;

    if (!hasMasterData && !hasPengelolaan && !showLaporan) return null;

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

            {/* Laporan Arsip */}
            {showLaporan && (
                <div className="space-y-1">
                    <div className="py-2">
                        <h3 className="inventory-sidebar-label text-[10px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400">Laporan Arsip</h3>
                    </div>
                    {showLaporanDivisi && (
                        <SidebarLink
                            name="Laporan Divisi"
                            href="/archieve/reports"
                            icon={BarChart3}
                            active={url === '/archieve/reports'}
                        />
                    )}
                    {showLaporanAll && (
                        <SidebarLink
                            name="Laporan Keseluruhan"
                            href="/archieve/reports/all"
                            icon={BarChart3}
                            active={url === '/archieve/reports/all'}
                        />
                    )}
                </div>
            )}
        </div>
    );
}
