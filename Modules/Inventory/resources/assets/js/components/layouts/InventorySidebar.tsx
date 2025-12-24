import { ArrowLeftRight, BarChart3, ClipboardCheck, Folder, Package, ShoppingCart } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import SidebarLink from '@/components/layouts/SideBarLink';

export default function InventorySidebar() {
    const { permissions } = usePage<{ permissions: string[] }>().props;

    const hasAnyInventoryPermission =
        // Kategori
        permissions?.includes('lihat_kategori') || permissions?.includes('kelola_kategori') ||
        // Barang
        permissions?.includes('lihat_barang') || permissions?.includes('kelola_barang') ||
        // Monitoring Stok
        permissions?.includes('monitor_stok') || permissions?.includes('monitor_semua_stok') ||
        // Permintaan Barang
        permissions?.includes('lihat_permintaan_barang') || permissions?.includes('lihat_semua_permintaan_barang') ||
        permissions?.includes('buat_permintaan_barang') || permissions?.includes('konfirmasi_permintaan_barang') ||
        permissions?.includes('serah_terima_barang') || permissions?.includes('terima_barang') ||
        // Transaksi
        permissions?.includes('monitor_transaksi_barang') || permissions?.includes('monitor_semua_transaksi_barang') ||
        // Stock Opname
        permissions?.includes('lihat_stock_opname_gudang') || permissions?.includes('lihat_stock_opname_divisi') ||
        permissions?.includes('lihat_semua_stock_opname');

    if (!hasAnyInventoryPermission) return null;

    return (
        <div className="mb-6">
            <div className="py-2">
                <h3 className="inventory-sidebar-label text-xs font-medium tracking-wider text-slate-500 dark:text-slate-400">Inventory</h3>
            </div>
            <div className="space-y-1">
                {/* Kategori Barang */}
                {(permissions?.includes('lihat_kategori') || permissions?.includes('kelola_kategori')) && (
                    <SidebarLink name="Kategori Barang" href="/inventory/categories" icon={Folder} />
                )}

                {/* Barang */}
                {(permissions?.includes('lihat_barang') || permissions?.includes('kelola_barang')) && (
                    <SidebarLink name="Barang" href="/inventory/items" icon={Package} />
                )}

                {/* Monitoring Stok */}
                {(permissions?.includes('monitor_stok') || permissions?.includes('monitor_semua_stok')) && (
                    <SidebarLink name="Monitoring Stok" href="/inventory/stock-monitoring" icon={BarChart3} />
                )}

                {/* Permintaan Barang */}
                {(permissions?.includes('lihat_permintaan_barang') ||
                    permissions?.includes('lihat_semua_permintaan_barang') ||
                    permissions?.includes('buat_permintaan_barang') ||
                    permissions?.includes('konfirmasi_permintaan_barang') ||
                    permissions?.includes('serah_terima_barang') ||
                    permissions?.includes('terima_barang')) && (
                        <SidebarLink name="Permintaan Barang" href="/inventory/warehouse-orders" icon={ShoppingCart} />
                    )}

                {/* Transaksi Barang */}
                {(permissions?.includes('monitor_transaksi_barang') || permissions?.includes('monitor_semua_transaksi_barang')) && (
                    <SidebarLink name="Transaksi Barang" href="/inventory/transactions" icon={ArrowLeftRight} />
                )}

                {/* Stock Opname */}
                {(() => {
                    const pWarehouse = permissions?.includes('lihat_stock_opname_gudang');
                    const pDivision = permissions?.includes('lihat_stock_opname_divisi');
                    const pAll = permissions?.includes('lihat_semua_stock_opname');

                    const count = [pWarehouse, pDivision, pAll].filter(Boolean).length;

                    if (count === 0) return null;

                    if (count === 1) {
                        let href = '/inventory/stock-opname';
                        if (pWarehouse) href = '/inventory/stock-opname/warehouse';
                        if (pDivision) href = '/inventory/stock-opname/division';
                        if (pAll) href = '/inventory/stock-opname/all';

                        return <SidebarLink name="Stock Opname" href={href} icon={ClipboardCheck} />;
                    }

                    return (
                        <SidebarLink
                            name="Stock Opname"
                            href="/inventory/stock-opname"
                            icon={ClipboardCheck}
                            children={[
                                pDivision && { name: 'Divisi', href: '/inventory/stock-opname/division' },
                                pWarehouse && { name: 'Gudang', href: '/inventory/stock-opname/warehouse' },
                                pAll && { name: 'Keseluruhan', href: '/inventory/stock-opname/all' }
                            ].filter(Boolean) as any}
                        />
                    );
                })()}
            </div>
        </div>
    );
}
