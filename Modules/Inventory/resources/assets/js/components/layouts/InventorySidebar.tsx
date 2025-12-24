import { BarChart3, Folder, Package, ShoppingCart } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import SidebarLink from '@/components/layouts/SideBarLink';

export default function InventorySidebar() {
    const { permissions } = usePage<{ permissions: string[] }>().props;

    const hasAnyInventoryPermission =
        // Kategori
        permissions?.includes('lihat_kategori') || permissions?.includes('kelola_kategori') ||
        // Barang
        permissions?.includes('lihat_barang') || permissions?.includes('kelola_barang') || permissions?.includes('konversi_barang') || permissions?.includes('keluarkan_stok') ||
        // Monitoring Stok
        permissions?.includes('monitor_stok') || permissions?.includes('monitor_semua_stok') ||
        // Permintaan Barang
        permissions?.includes('lihat_permintaan_barang_divisi') || permissions?.includes('lihat_semua_permintaan_barang') || permissions?.includes('buat_permintaan_barang');

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
                {(permissions?.includes('lihat_barang') || permissions?.includes('kelola_barang') || permissions?.includes('konversi_barang') || permissions?.includes('keluarkan_stok')) && (
                    <SidebarLink name="Barang" href="/inventory/items" icon={Package} />
                )}

                {/* Monitoring Stok */}
                {(permissions?.includes('monitor_stok') || permissions?.includes('monitor_semua_stok')) && (
                    <SidebarLink name="Monitoring Stok" href="/inventory/stock-monitoring" icon={BarChart3} />
                )}

                {/* Permintaan Barang */}
                {(permissions?.includes('lihat_permintaan_barang_divisi') || permissions?.includes('lihat_semua_permintaan_barang') || permissions?.includes('buat_permintaan_barang')) && (
                    <SidebarLink name="Permintaan Barang" href="/inventory/warehouse-orders" icon={ShoppingCart} />
                )}
            </div>
        </div>
    );
}
