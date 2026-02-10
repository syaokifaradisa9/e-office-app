<?php

namespace Modules\Inventory\Enums;

enum InventoryPermission: string
{
    // Dashboard
    case ViewMainWarehouseDashboard = 'lihat_dashboard_gudang_utama';
    case ViewDivisionWarehouseDashboard = 'lihat_dashboard_gudang_divisi';
    case ViewAllWarehouseDashboard = 'lihat_dashboard_gudang_keseluruhan';

    // Category Item
    case ViewCategory = 'lihat_kategori';
    case ManageCategory = 'kelola_kategori';

    // Item (Gudang Utama)
    case ViewItem = 'lihat_barang';
    case ManageItem = 'kelola_barang';
    case IssueItemGudang = 'pengeluaran_barang_gudang';
    case ConvertItemGudang = 'konversi_barang_gudang';

    // Warehouse Order
    case ViewWarehouseOrderDivisi = 'lihat_permintaan_barang_divisi';
    case ViewAllWarehouseOrder = 'lihat_semua_permintaan_barang';
    case CreateWarehouseOrder = 'buat_permintaan_barang';
    case ConfirmWarehouseOrder = 'konfirmasi_permintaan_barang';
    case HandoverItem = 'serah_terima_barang';
    case ReceiveItem = 'terima_barang';

    // Stock Opname
    case ViewWarehouseStockOpname = 'lihat_stock_opname_gudang';
    case ViewDivisionStockOpname = 'lihat_stock_opname_divisi';
    case ViewAllStockOpname = 'lihat_semua_stock_opname';
    case CreateStockOpname = 'tambah_stock_opname';
    case ProcessStockOpname = 'proses_stock_opname';
    case FinalizeStockOpname = 'finalisasi_stock_opname';

    // Monitoring
    case MonitorItemTransaction = 'monitor_transaksi_barang';
    case MonitorAllItemTransaction = 'monitor_semua_transaksi_barang';
    case MonitorStock = 'lihat_stok_divisi';
    case MonitorAllStock = 'lihat_semua_stok';
    case IssueStock = 'pengeluaran_stok_barang';
    case ConvertStock = 'konversi_stok_barang';

    // Report
    case ViewDivisionReport = 'lihat_laporan_gudang_divisi';
    case ViewAllReport = 'lihat_laporan_gudang_semua';

    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
