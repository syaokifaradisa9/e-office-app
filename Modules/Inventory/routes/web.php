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
use Modules\Inventory\Http\Controllers\InventoryController;
use Modules\Inventory\Http\Middleware\InventoryItemRoutePermissionCheck;
use Modules\Inventory\Http\Middleware\CheckActiveStockOpname;

Route::prefix('inventory')->name('inventory.')->middleware(['auth', InventoryItemRoutePermissionCheck::class, CheckActiveStockOpname::class])->group(function () {
    Route::get('/', [InventoryController::class, 'index'])->name('index');

    // Dashboard
    Route::prefix('dashboard')->name('dashboard.')->controller(DashboardController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/main-warehouse', 'mainWarehouse')->name('main-warehouse');
        Route::get('/division-warehouse', 'divisionWarehouse')->name('division-warehouse');
    });

    // Item Transactions
    Route::prefix('transactions')->name('transactions.')->controller(ItemTransactionController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/print-excel', 'printExcel')->name('print-excel');
    });

    // Stock Opname
    Route::prefix('stock-opname')->name('stock-opname.')->controller(StockOpnameController::class)->group(function () {
        Route::get('/datatable/{type}', 'datatable')->name('datatable');
        Route::get('/print-excel/{type}', 'printExcel')->name('print-excel');
        Route::get('/{type}/{stockOpname}/show', 'show')->name('show');
        Route::get('/{type}/{stockOpname}/detail', 'show')->name('detail');
        Route::get('/{type?}', 'index')->name('index');

        Route::get('/{type}/create', 'create')->name('create');
        Route::post('/{type}/store', 'store')->name('store');
        Route::get('/{type}/{stockOpname}/edit', 'edit')->name('edit');
        Route::put('/{type}/{stockOpname}/update', 'update')->name('update');
        Route::delete('/{type}/{stockOpname}/delete', 'delete')->name('delete');

        Route::get('/{type}/{stockOpname}/process', 'process')->name('process');
        Route::post('/{type}/{stockOpname}/process', 'storeProcess')->name('store-process');

        Route::get('/{type}/{stockOpname}/finalize', 'finalize')->name('finalize');
        Route::post('/{type}/{stockOpname}/finalize', 'storeFinalize')->name('store-finalize');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->controller(ReportController::class)->group(function () {
        Route::get('/division', 'division')->name('division');
        Route::get('/all', 'all')->name('all');
        Route::get('/print-excel', 'printExcel')->name('print-excel');
    });

    // Categories
    Route::prefix('categories')->name('categories.')->controller(CategoryItemController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/print-excel', 'printExcel')->name('print-excel');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/{categoryItem}/edit', 'edit')->name('edit');
        Route::put('/{categoryItem}/update', 'update')->name('update');
        Route::delete('/{categoryItem}/delete', 'delete')->name('delete');
    });

    // Items
    Route::prefix('items')->name('items.')->controller(ItemController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/print-excel', 'printExcel')->name('print-excel');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/{item}/edit', 'edit')->name('edit');
        Route::put('/{item}/update', 'update')->name('update');
        Route::delete('/{item}/delete', 'delete')->name('delete');
        Route::get('/{item}/convert', 'convert')->name('convert');
        Route::post('/{item}/convert', 'processConversion')->name('process-conversion');
        Route::get('/{item}/issue', 'issueForm')->name('issue.form');
        Route::post('/{item}/issue', 'issue')->name('issue');
    });

    // Warehouse Orders
    Route::prefix('warehouse-orders')->name('warehouse-orders.')->controller(WarehouseOrderController::class)->group(function () {
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/{warehouseOrder}/edit', 'edit')->name('edit');
        Route::put('/{warehouseOrder}/update', 'update')->name('update');
        Route::delete('/{warehouseOrder}/delete', 'delete')->name('delete');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/print-excel', 'printExcel')->name('print-excel');
        Route::get('/', 'index')->name('index');
        Route::get('/{warehouseOrder}', 'show')->name('show');
        Route::patch('/{warehouseOrder}/confirm', 'confirm')->name('confirm');
        Route::post('/{warehouseOrder}/reject', 'reject')->name('reject');
        Route::get('/{warehouseOrder}/delivery', 'delivery')->name('delivery');
        Route::post('/{warehouseOrder}/delivery', 'deliver')->name('deliver');
        Route::get('/{warehouseOrder}/receive', 'received')->name('received');
        Route::post('/{warehouseOrder}/receive', 'receive')->name('receive');
    });

    // Stock Monitoring
    Route::prefix('stock-monitoring')->name('stock-monitoring.')->controller(StockMonitoringController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/print-excel', 'printExcel')->name('print-excel');
        Route::get('/{item}/convert', 'convert')->name('convert');
        Route::post('/{item}/convert', 'processConversion')->name('process-conversion');
        Route::get('/{item}/issue', 'issueForm')->name('issue.form');
        Route::post('/{item}/issue', 'issue')->name('issue');
    });

});
