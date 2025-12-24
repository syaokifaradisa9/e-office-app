<?php

namespace Modules\Inventory\Enums;

enum InventoryPermission: string
{
    // Dashboard
    case ViewMainWarehouseDashboard = 'lihat_dashboard_gudang_utama';
    case ViewDivisionWarehouseDashboard = 'lihat_dashboard_gudang_divisi';

    // Category Item
    case ViewCategory = 'lihat_kategori_barang';
    case ManageCategory = 'kelola_kategori_barang';

    // Item
    case ViewItem = 'lihat_barang';
    case ManageItem = 'kelola_barang';
    case IssueStock = 'keluarkan_stok';
    case ConvertItem = 'konversi_barang';

    // Warehouse Order
    case ViewWarehouseOrder = 'lihat_permintaan_barang';
    case ViewAllWarehouseOrder = 'lihat_semua_permintaan_barang';
    case CreateWarehouseOrder = 'buat_permintaan_barang';
    case ConfirmWarehouseOrder = 'konfirmasi_permintaan_barang';
    case HandoverItem = 'serah_terima_barang';
    case ReceiveItem = 'terima_barang';

    // Stock Opname
    case ViewWarehouseStockOpname = 'lihat_stock_opname_gudang';
    case ViewDivisionStockOpname = 'lihat_stock_opname_divisi';
    case ViewAllStockOpname = 'lihat_semua_stock_opname';
    case ManageWarehouseStockOpname = 'kelola_stock_opname_gudang';
    case ManageDivisionStockOpname = 'kelola_stock_opname_divisi';
    case ConfirmStockOpname = 'konfirmasi_stock_opname';

    // Monitoring
    case MonitorItemTransaction = 'monitor_transaksi_barang';
    case MonitorAllItemTransaction = 'monitor_semua_transaksi_barang';
    case MonitorStock = 'monitor_stok';
    case MonitorAllStock = 'monitor_semua_stok';

    // Report
    case ViewDivisionReport = 'lihat_laporan_gudang_divisi';
    case ViewAllReport = 'lihat_laporan_gudang_semua';

    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
