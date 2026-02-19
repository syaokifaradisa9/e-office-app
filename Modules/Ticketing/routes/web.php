<?php

use Illuminate\Support\Facades\Route;
use Modules\Ticketing\Http\Controllers\TicketingController;
use Modules\Ticketing\Http\Controllers\AssetModelController;
use Modules\Ticketing\Http\Middleware\TicketingRoutePermissionCheck;

Route::middleware(['auth', TicketingRoutePermissionCheck::class])->group(function () {
    Route::prefix('ticketing')->name('ticketing.')->group(function () {
        Route::get('/', [TicketingController::class, 'index'])->name('index');

        // Asset Model Management
        Route::prefix('asset-models')->name('asset-models.')->controller(AssetModelController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/datatable', 'datatable')->name('datatable');
            Route::get('/print/excel', 'printExcel')->name('print-excel');
            Route::get('/create', 'create')->name('create');
            Route::post('/store', 'store')->name('store');

            Route::prefix('{assetModel}')->group(function () {
                Route::get('/edit', 'edit')->name('edit');
                Route::put('/update', 'update')->name('update');
                Route::delete('/delete', 'delete')->name('delete');
            });
        });
    });
});
