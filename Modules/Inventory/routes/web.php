<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\CategoryItemController;
use Modules\Inventory\Http\Controllers\DashboardController;
use Modules\Inventory\Http\Controllers\ItemController;
use Modules\Inventory\Http\Controllers\ItemTransactionController;
use Modules\Inventory\Http\Controllers\ReportController;
use Modules\Inventory\Http\Controllers\StockMonitoringController;
use Modules\Inventory\Http\Controllers\StockOpnameController;
use Modules\Inventory\Http\Controllers\WarehouseOrderController;

Route::prefix('inventory')->name('inventory.')->middleware(['auth'])->group(function () {
    // Dashboard
    Route::prefix('dashboard')->name('dashboard.')->controller(DashboardController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/main-warehouse', 'mainWarehouse')->name('main-warehouse');
        Route::get('/division-warehouse', 'divisionWarehouse')->name('division-warehouse');
    });

    // Item Transactions
    Route::prefix('transactions')->name('transactions.')->controller(ItemTransactionController::class)->group(function () {
        Route::middleware('permission:monitor_transaksi_barang|monitor_semua_transaksi_barang')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/datatable', 'datatable')->name('datatable');
            Route::get('/print-excel', 'printExcel')->name('print-excel');
        });
    });

    // Stock Opname
    Route::prefix('stock-opname')->name('stock-opname.')->controller(StockOpnameController::class)->group(function () {
        Route::middleware('permission:lihat_stock_opname_gudang|lihat_stock_opname_divisi|lihat_semua_stock_opname')->group(function () {
            Route::get('/datatable/{type}', 'datatable')->name('datatable');
            Route::get('/print-excel/{type}', 'printExcel')->name('print-excel');
            Route::get('/{type}/{stockOpname}/show', 'show')->name('show');
            Route::get('/{type}/{stockOpname}/detail', 'show')->name('detail'); // Alias for detail link in frontend
        });

        Route::middleware('permission:kelola_stock_opname_gudang|kelola_stock_opname_divisi')->group(function () {
            Route::get('/{type}/create', 'create')->name('create');
            Route::post('/{type}/store', 'store')->name('store');
            Route::get('/{type}/{stockOpname}/edit', 'edit')->name('edit');
            Route::put('/{type}/{stockOpname}/update', 'update')->name('update');
            Route::delete('/{type}/{stockOpname}/delete', 'delete')->name('delete');
        });

        Route::middleware('permission:konfirmasi_stock_opname')->group(function () {
            Route::post('/{stockOpname}/confirm', 'confirm')->name('confirm'); // Frontend uses POST (router.post) baris 194
        });

        // Index route at the bottom
        Route::get('/{type?}', 'index')->name('index');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->controller(ReportController::class)->group(function () {
        Route::middleware('permission:lihat_laporan_gudang_divisi|lihat_laporan_gudang_semua')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/division', 'division')->name('division');
            Route::get('/all', 'all')->name('all')->middleware('permission:lihat_laporan_gudang_semua');
            Route::get('/print-excel', 'printExcel')->name('print-excel');
        });
    });

    // Categories
    Route::prefix('categories')->name('categories.')->controller(CategoryItemController::class)->group(function () {
        // Index - can be accessed with lihat OR kelola
        Route::middleware('role_or_permission:lihat_kategori|kelola_kategori')->group(function () {
            Route::get('/', 'index')->name('index');
        });

        // Datatable and Print Excel - ONLY with lihat permission
        Route::middleware('permission:lihat_kategori')->group(function () {
            Route::get('/datatable', 'datatable')->name('datatable');
            Route::get('/print-excel', 'printExcel')->name('print-excel');
        });

        // CRUD operations - with kelola permission
        Route::middleware('permission:kelola_kategori')->group(function () {
            Route::get('/create', 'create')->name('create');
            Route::post('/store', 'store')->name('store');
            Route::get('/{categoryItem}/edit', 'edit')->name('edit');
            Route::put('/{categoryItem}/update', 'update')->name('update');
            Route::delete('/{categoryItem}/delete', 'delete')->name('delete');
        });
    });

    // Items
    Route::prefix('items')->name('items.')->controller(ItemController::class)->group(function () {
        Route::middleware('permission:lihat_barang|kelola_barang|pengeluaran_barang_gudang|konversi_barang_gudang')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/datatable', 'datatable')->name('datatable');
            Route::get('/print-excel', 'printExcel')->name('print-excel');
        });

        Route::middleware('permission:kelola_barang')->group(function () {
            Route::get('/create', 'create')->name('create');
            Route::post('/store', 'store')->name('store');
            Route::get('/{item}/edit', 'edit')->name('edit');
            Route::put('/{item}/update', 'update')->name('update');
            Route::delete('/{item}/delete', 'delete')->name('delete');
        });

        Route::middleware('permission:konversi_barang_gudang')->group(function () {
            Route::get('/{item}/convert', 'convert')->name('convert');
            Route::post('/{item}/convert', 'processConversion')->name('process-conversion');
        });

        Route::middleware('permission:pengeluaran_barang_gudang')->group(function () {
            Route::get('/{item}/issue', 'issueForm')->name('issue.form');
            Route::post('/{item}/issue', 'issue')->name('issue');
        });
    });
    // Warehouse Orders
    Route::prefix('warehouse-orders')->name('warehouse-orders.')->controller(WarehouseOrderController::class)->group(function () {
        Route::middleware('permission:buat_permintaan_barang')->group(function () {
            Route::get('/create', 'create')->name('create');
            Route::post('/store', 'store')->name('store');
            Route::get('/{warehouseOrder}/edit', 'edit')->name('edit');
            Route::put('/{warehouseOrder}/update', 'update')->name('update');
            Route::delete('/{warehouseOrder}/delete', 'delete')->name('delete');
        });

        Route::middleware('permission:lihat_permintaan_barang_divisi|lihat_semua_permintaan_barang')->group(function () {
            Route::get('/datatable', 'datatable')->name('datatable');
            Route::get('/print-excel', 'printExcel')->name('print-excel');
        });

        Route::middleware('permission:lihat_permintaan_barang_divisi|lihat_semua_permintaan_barang|buat_permintaan_barang|konfirmasi_permintaan_barang|serah_terima_barang|terima_barang')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{warehouseOrder}', 'show')->name('show');
        });

        Route::middleware('permission:konfirmasi_permintaan_barang')->group(function () {
            Route::patch('/{warehouseOrder}/confirm', 'confirm')->name('confirm');
            Route::post('/{warehouseOrder}/reject', 'reject')->name('reject');
        });

        Route::middleware('permission:serah_terima_barang')->group(function () {
            Route::get('/{warehouseOrder}/delivery', 'delivery')->name('delivery');
            Route::post('/{warehouseOrder}/delivery', 'deliver')->name('deliver');
        });

        Route::middleware('permission:terima_barang')->group(function () {
            Route::get('/{warehouseOrder}/receive', 'received')->name('received');
            Route::post('/{warehouseOrder}/receive', 'receive')->name('receive');
        });
    });


    // Stock Monitoring
    Route::prefix('stock-monitoring')->name('stock-monitoring.')->controller(StockMonitoringController::class)->group(function () {
        Route::middleware('permission:lihat_stok_divisi|lihat_semua_stok')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/datatable', 'datatable')->name('datatable');
            Route::get('/print-excel', 'printExcel')->name('print-excel');
        });

        Route::middleware('permission:konversi_stok_barang')->group(function () {
            Route::get('/{item}/convert', 'convert')->name('convert');
            Route::post('/{item}/convert', 'processConversion')->name('process-conversion');
        });

        Route::middleware('permission:pengeluaran_stok_barang')->group(function () {
            Route::get('/{item}/issue', 'issueForm')->name('issue.form');
            Route::post('/{item}/issue', 'issue')->name('issue');
        });
    });

});
