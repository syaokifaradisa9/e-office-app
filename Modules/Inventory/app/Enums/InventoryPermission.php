<?php

namespace Modules\Inventory\Enums;

enum InventoryPermission: string
{
    // Dashboard
    case ViewMainWarehouseDashboard = 'Lihat Dashboard Gudang Utama';
    case ViewDivisionWarehouseDashboard = 'Lihat Dashboard Gudang Divisi';
    case ViewAllWarehouseDashboard = 'Lihat Dashboard Gudang Keseluruhan';

    // Category Item
    case ViewCategory = 'Lihat Data Kategori';
    case ManageCategory = 'Kelola Data Kategori';

    // Item (Gudang Utama)
    case ViewItem = 'Lihat Data Barang Gudang';
    case ManageItem = 'Kelola Data Barang Gudang';
    case IssueItemGudang = 'Pengeluaran Barang Gudang';
    case ConvertItemGudang = 'Konversi Barang Gudang';

    // Warehouse Order
    case ViewWarehouseOrderDivisi = 'Lihat Permintaan Barang Divisi';
    case ViewAllWarehouseOrder = 'Lihat Semua Permintaan Barang';
    case CreateWarehouseOrder = 'Buat Permintaan Barang';
    case ConfirmWarehouseOrder = 'Konfirmasi Permintaan Barang';
    case HandoverItem = 'Serah Terima Barang';
    case ReceiveItem = 'Terima Barang';

    // Stock Opname
    case ViewWarehouseStockOpname = 'Lihat Stock Opname Gudang';
    case ViewDivisionStockOpname = 'Lihat Stock Opname Divisi';
    case ViewAllStockOpname = 'Lihat Semua Stock Opname';
    case CreateStockOpname = 'Tambah Stock Opname';
    case ProcessStockOpname = 'Proses Stock Opname';
    case FinalizeStockOpname = 'Finalisasi Stock Opname';

    // Monitoring
    case MonitorItemTransaction = 'Monitor Transaksi Barang';
    case MonitorAllItemTransaction = 'Monitor Semua Transaksi Barang';
    case MonitorStock = 'Lihat Stok Divisi';
    case MonitorAllStock = 'Lihat Semua Stok Keseluruhan';
    case IssueStock = 'Pengeluaran Stok Barang';
    case ConvertStock = 'Konversi Stok Barang';

    // Report
    case ViewDivisionReport = 'Lihat Laporan Gudang Divisi';
    case ViewAllReport = 'Lihat Laporan Gudang Semua';

    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
