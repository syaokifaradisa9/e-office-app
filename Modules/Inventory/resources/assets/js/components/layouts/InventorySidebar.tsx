import { BarChart3, Folder, Package, ShoppingCart, History, ClipboardCheck, FileBarChart } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import SidebarLink from '@/components/layouts/SideBarLink';
import { InventoryPermission } from '../../types/permissions';

export default function InventorySidebar() {
    const { permissions, is_stock_opname_pending } = usePage<{ permissions: string[], is_stock_opname_pending: boolean }>().props;

    // Define visibility for each menu item
    const showKategori = permissions?.includes(InventoryPermission.ViewCategory) ||
        permissions?.includes(InventoryPermission.ManageCategory);

    const showBarang = permissions?.includes(InventoryPermission.ViewItem) ||
        permissions?.includes(InventoryPermission.ManageItem) ||
        permissions?.includes(InventoryPermission.ConvertItemGudang) ||
        permissions?.includes(InventoryPermission.IssueItemGudang);

    const showMonitoringStok = !is_stock_opname_pending && (
        permissions?.includes(InventoryPermission.MonitorStock) ||
        permissions?.includes(InventoryPermission.MonitorAllStock)
    );

    const showPermintaan = !is_stock_opname_pending && (
        permissions?.includes(InventoryPermission.ViewWarehouseOrderDivisi) ||
        permissions?.includes(InventoryPermission.ViewAllWarehouseOrder) ||
        permissions?.includes(InventoryPermission.CreateWarehouseOrder) ||
        permissions?.includes(InventoryPermission.ConfirmWarehouseOrder) ||
        permissions?.includes(InventoryPermission.HandoverItem) ||
        permissions?.includes(InventoryPermission.ReceiveItem)
    );

    const showTransaksi = permissions?.includes(InventoryPermission.MonitorItemTransaction) ||
        permissions?.includes(InventoryPermission.MonitorAllItemTransaction);

    const showStockOpnameDivisi = permissions?.includes(InventoryPermission.ViewDivisionStockOpname) ||
        permissions?.includes(InventoryPermission.CreateStockOpname) ||
        permissions?.includes(InventoryPermission.ProcessStockOpname);

    const showStockOpnameGudang = permissions?.includes(InventoryPermission.ViewWarehouseStockOpname) ||
        permissions?.includes(InventoryPermission.CreateStockOpname) ||
        permissions?.includes(InventoryPermission.ProcessStockOpname);

    const showStockOpnameAll = permissions?.includes(InventoryPermission.ViewAllStockOpname);

    const showLaporanDivisi = permissions?.includes(InventoryPermission.ViewDivisionReport);
    const showLaporanAll = permissions?.includes(InventoryPermission.ViewAllReport);

    // Section visibility
    const hasDataMasterGudang = showKategori || showBarang || showMonitoringStok;
    const hasPengolahanData = showPermintaan || showTransaksi;
    const hasStockOpname = showStockOpnameDivisi || showStockOpnameGudang || showStockOpnameAll;
    const hasLaporan = showLaporanDivisi || showLaporanAll;

    if (!hasDataMasterGudang && !hasPengolahanData && !hasStockOpname && !hasLaporan) return null;

    return (
        <div className="mb-6 space-y-6">

            {/* Group 1: Data Master Gudang */}
            {hasDataMasterGudang && (
                <div className="space-y-1">
                    <div className="py-2">
                        <h3 className="inventory-sidebar-label text-[10px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400">Data Master Gudang</h3>
                    </div>
                    {showKategori && (
                        <SidebarLink name="Kategori Barang" href="/inventory/categories" icon={Folder} />
                    )}
                    {showBarang && (
                        <SidebarLink name="Barang Gudang" href="/inventory/items" icon={Package} />
                    )}
                    {showMonitoringStok && (
                        <SidebarLink name="Monitoring Stok Barang" href="/inventory/stock-monitoring" icon={BarChart3} />
                    )}
                </div>
            )}

            {/* Group 2: Pengolahan Data Barang */}
            {hasPengolahanData && (
                <div className="space-y-1">
                    <div className="py-2">
                        <h3 className="inventory-sidebar-label text-[10px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400">Pengolahan Data Barang</h3>
                    </div>
                    {showPermintaan && (
                        <SidebarLink name="Permintaan Barang" href="/inventory/warehouse-orders" icon={ShoppingCart} />
                    )}
                    {showTransaksi && (
                        <SidebarLink name="Transaksi Barang" href="/inventory/transactions" icon={History} />
                    )}
                </div>
            )}

            {/* Group 3: Stock Opname Barang */}
            {hasStockOpname && (
                <div className="space-y-1">
                    <div className="py-2">
                        <h3 className="inventory-sidebar-label text-[10px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400">Stock Opname Barang</h3>
                    </div>
                    {showStockOpnameDivisi && (
                        <SidebarLink name="Stock Opname Divisi" href="/inventory/stock-opname/division" icon={ClipboardCheck} />
                    )}
                    {showStockOpnameGudang && (
                        <SidebarLink name="Stock Opname Gudang" href="/inventory/stock-opname/warehouse" icon={ClipboardCheck} />
                    )}
                    {showStockOpnameAll && (
                        <SidebarLink name="Semua Stock Opname" href="/inventory/stock-opname/all" icon={ClipboardCheck} />
                    )}
                </div>
            )}

            {/* Group 4: Laporan Sistem Gudang */}
            {hasLaporan && (
                <div className="space-y-1">
                    <div className="py-2">
                        <h3 className="inventory-sidebar-label text-[10px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400">Laporan Sistem Gudang</h3>
                    </div>
                    {showLaporanDivisi && (
                        <SidebarLink name="Laporan Divisi" href="/inventory/reports/division" icon={FileBarChart} />
                    )}
                    {showLaporanAll && (
                        <SidebarLink name="Laporan Keseluruhan" href="/inventory/reports/all" icon={FileBarChart} />
                    )}
                </div>
            )}
        </div>
    );
}
