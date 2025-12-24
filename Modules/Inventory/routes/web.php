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
        Route::middleware('permission:lihat_barang|kelola_barang|keluarkan_stok|konversi_barang')->group(function () {
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

        Route::middleware('permission:konversi_barang')->group(function () {
            Route::get('/{item}/convert', 'convert')->name('convert');
            Route::post('/{item}/convert', 'processConversion')->name('process-conversion');
        });

        Route::middleware('permission:keluarkan_stok')->group(function () {
            Route::get('/{item}/issue', 'issueForm')->name('issue.form');
            Route::post('/{item}/issue', 'issue')->name('issue');
        });
    });


    // Stock Monitoring
    Route::prefix('stock-monitoring')->name('stock-monitoring.')->controller(StockMonitoringController::class)->group(function () {
        Route::middleware('permission:monitor_stok|monitor_semua_stok')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/datatable', 'datatable')->name('datatable');
            Route::get('/print-excel', 'printExcel')->name('print-excel');
        });

        Route::middleware('permission:konversi_barang')->group(function () {
            Route::get('/{item}/convert', 'convert')->name('convert');
            Route::post('/{item}/convert', 'processConversion')->name('process-conversion');
        });

        Route::middleware('permission:keluarkan_stok')->group(function () {
            Route::get('/{item}/issue', 'issueForm')->name('issue.form');
            Route::post('/{item}/issue', 'issue')->name('issue');
        });
    });

});
