import { BarChart3, Folder, Package, ShoppingCart, History, ClipboardCheck, FileBarChart } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import SidebarLink from '@/components/layouts/SideBarLink';

export default function InventorySidebar() {
    const { permissions, is_stock_opname_pending } = usePage<{ permissions: string[], is_stock_opname_pending: boolean }>().props;

    const hasAnyInventoryPermission =
        // Dashboard
        permissions?.includes('lihat_dashboard_gudang_utama') || permissions?.includes('lihat_dashboard_gudang_divisi') ||
        // Kategori
        permissions?.includes('lihat_kategori') || permissions?.includes('kelola_kategori') ||
        // Barang
        permissions?.includes('lihat_barang') || permissions?.includes('kelola_barang') || permissions?.includes('konversi_barang_gudang') || permissions?.includes('pengeluaran_barang_gudang') ||
        // Data Stok
        permissions?.includes('lihat_stok_divisi') || permissions?.includes('lihat_semua_stok') ||
        // Permintaan Barang
        permissions?.includes('lihat_permintaan_barang_divisi') || permissions?.includes('lihat_semua_permintaan_barang') || permissions?.includes('buat_permintaan_barang') ||
        // Transaksi
        permissions?.includes('monitor_transaksi_barang') || permissions?.includes('monitor_semua_transaksi_barang') ||
        // Stok Opname
        permissions?.includes('lihat_stock_opname_gudang') || permissions?.includes('lihat_stock_opname_divisi') || permissions?.includes('lihat_semua_stock_opname') ||
        // Laporan
        permissions?.includes('lihat_laporan_gudang_divisi') || permissions?.includes('lihat_laporan_gudang_semua');

    if (!hasAnyInventoryPermission) return null;

    return (
        <div className="mb-6 space-y-6">

            {/* Group 1: Data Master Gudang */}
            <div className="space-y-1">
                <div className="py-2">
                    <h3 className="inventory-sidebar-label text-[10px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400">Data Master Gudang</h3>
                </div>
                {(permissions?.includes('lihat_kategori') || permissions?.includes('kelola_kategori')) && (
                    <SidebarLink name="Kategori Barang" href="/inventory/categories" icon={Folder} />
                )}
                {(permissions?.includes('lihat_barang') || permissions?.includes('kelola_barang') || permissions?.includes('konversi_barang_gudang') || permissions?.includes('pengeluaran_barang_gudang')) && (
                    <SidebarLink name="Barang Gudang" href="/inventory/items" icon={Package} />
                )}
                {!is_stock_opname_pending && (permissions?.includes('lihat_stok_divisi') || permissions?.includes('lihat_semua_stok')) && (
                    <SidebarLink name="Monitoring Stok Barang" href="/inventory/stock-monitoring" icon={BarChart3} />
                )}
            </div>

            {/* Group 2: Pengolahan Data Barang */}
            <div className="space-y-1">
                <div className="py-2">
                    <h3 className="inventory-sidebar-label text-[10px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400">Pengolahan Data Barang</h3>
                </div>
                {!is_stock_opname_pending && (permissions?.includes('lihat_permintaan_barang_divisi') || permissions?.includes('lihat_semua_permintaan_barang') || permissions?.includes('buat_permintaan_barang')) && (
                    <SidebarLink name="Permintaan Barang" href="/inventory/warehouse-orders" icon={ShoppingCart} />
                )}
                {(permissions?.includes('monitor_transaksi_barang') || permissions?.includes('monitor_semua_transaksi_barang')) && (
                    <SidebarLink name="Transaksi Barang" href="/inventory/transactions" icon={History} />
                )}
            </div>

            {/* Group 3: Stock Opname Barang */}
            <div className="space-y-1">
                <div className="py-2">
                    <h3 className="inventory-sidebar-label text-[10px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400">Stock Opname Barang</h3>
                </div>
                {permissions?.includes('lihat_stock_opname_divisi') && (
                    <SidebarLink name="Stock Opname Divisi" href="/inventory/stock-opname/division" icon={ClipboardCheck} />
                )}
                {permissions?.includes('lihat_stock_opname_gudang') && (
                    <SidebarLink name="Stock Opname Gudang" href="/inventory/stock-opname/warehouse" icon={ClipboardCheck} />
                )}
                {permissions?.includes('lihat_semua_stock_opname') && (
                    <SidebarLink name="Semua Stock Opname" href="/inventory/stock-opname/all" icon={ClipboardCheck} />
                )}
            </div>

            {/* Group 4: Laporan Sistem Gudang */}
            {(permissions?.includes('lihat_laporan_gudang_divisi') || permissions?.includes('lihat_laporan_gudang_semua')) && (
                <div className="space-y-1">
                    <div className="py-2">
                        <h3 className="inventory-sidebar-label text-[10px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400">Laporan Sistem Gudang</h3>
                    </div>
                    {permissions?.includes('lihat_laporan_gudang_divisi') && (
                        <SidebarLink name="Laporan Divisi" href="/inventory/reports/division" icon={FileBarChart} />
                    )}
                    {permissions?.includes('lihat_laporan_gudang_semua') && (
                        <SidebarLink name="Laporan Keseluruhan" href="/inventory/reports/all" icon={FileBarChart} />
                    )}
                </div>
            )}
        </div>
    );
}
