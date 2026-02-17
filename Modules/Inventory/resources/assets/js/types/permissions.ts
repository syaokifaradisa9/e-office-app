export enum InventoryPermission {
    // Dashboard
    ViewMainWarehouseDashboard = 'Lihat Dashboard Gudang Utama',
    ViewDivisionWarehouseDashboard = 'Lihat Dashboard Gudang Divisi',
    ViewAllWarehouseDashboard = 'Lihat Dashboard Gudang Keseluruhan',

    // Category Item
    ViewCategory = 'Lihat Data Kategori',
    ManageCategory = 'Kelola Data Kategori',

    // Item (Gudang Utama)
    ViewItem = 'Lihat Data Barang Gudang',
    ManageItem = 'Kelola Data Barang Gudang',
    IssueItemGudang = 'Pengeluaran Barang Gudang',
    ConvertItemGudang = 'Konversi Barang Gudang',

    // Warehouse Order
    ViewWarehouseOrderDivisi = 'Lihat Permintaan Barang Divisi',
    ViewAllWarehouseOrder = 'Lihat Semua Permintaan Barang',
    CreateWarehouseOrder = 'Buat Permintaan Barang',
    ConfirmWarehouseOrder = 'Konfirmasi Permintaan Barang',
    HandoverItem = 'Serah Terima Barang',
    ReceiveItem = 'Terima Barang',

    // Stock Opname
    ViewWarehouseStockOpname = 'Lihat Stock Opname Gudang',
    ViewDivisionStockOpname = 'Lihat Stock Opname Divisi',
    ViewAllStockOpname = 'Lihat Semua Stock Opname',
    CreateStockOpname = 'Tambah Stock Opname',
    ProcessStockOpname = 'Proses Stock Opname',
    FinalizeStockOpname = 'Finalisasi Stock Opname',

    // Monitoring
    MonitorItemTransaction = 'Monitor Transaksi Barang',
    MonitorAllItemTransaction = 'Monitor Semua Transaksi Barang',
    MonitorStock = 'Lihat Stok Divisi',
    MonitorAllStock = 'Lihat Semua Stok Keseluruhan',
    IssueStock = 'Pengeluaran Stok Barang',
    ConvertStock = 'Konversi Stok Barang',

    // Report
    ViewDivisionReport = 'Lihat Laporan Gudang Divisi',
    ViewAllReport = 'Lihat Laporan Gudang Semua',
}
