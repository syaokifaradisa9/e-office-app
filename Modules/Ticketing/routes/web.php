<?php

use Illuminate\Support\Facades\Route;
use Modules\Ticketing\Http\Controllers\TicketingController;

use Modules\Ticketing\Http\Controllers\AssetCategoryController;
use Modules\Ticketing\Http\Controllers\ChecklistController;
use Modules\Ticketing\Http\Controllers\AssetItemController;
use Modules\Ticketing\Http\Middleware\TicketingRoutePermissionCheck;

Route::middleware(['auth', TicketingRoutePermissionCheck::class])->group(function () {
    Route::prefix('ticketing')->name('ticketing.')->group(function () {
        Route::get('/', [TicketingController::class, 'index'])->name('index');

        // Asset Category Management
        Route::prefix('asset-categories')->name('asset-categories.')->controller(AssetCategoryController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/datatable', 'datatable')->name('datatable');
            Route::get('/print/excel', 'printExcel')->name('print-excel');
            Route::get('/create', 'create')->name('create');
            Route::post('/store', 'store')->name('store');

            Route::prefix('{assetCategory}')->group(function () {
                Route::get('/edit', 'edit')->name('edit');
                Route::put('/update', 'update')->name('update');
                Route::delete('/delete', 'delete')->name('delete');

                // Checklist Management (sub-resource)
                Route::prefix('checklists')->name('checklists.')->controller(ChecklistController::class)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/datatable', 'datatable')->name('datatable');
                    Route::get('/print/excel', 'printExcel')->name('print-excel');
                    Route::get('/create', 'create')->name('create');
                    Route::post('/store', 'store')->name('store');

                    Route::prefix('{checklist}')->group(function () {
                        Route::get('/edit', 'edit')->name('edit');
                        Route::put('/update', 'update')->name('update');
                        Route::delete('/delete', 'delete')->name('delete');
                    });
                });
            });
        });

        // Asset Management
        Route::prefix('assets')->name('assets.')->controller(AssetItemController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/datatable', 'datatable')->name('datatable');
            Route::get('/print/excel', 'printExcel')->name('print-excel');
            Route::get('/create', 'create')->name('create');
            Route::post('/store', 'store')->name('store');
            Route::get('/users-by-division/{division}', 'getUsersByDivision')->name('users-by-division');

            Route::prefix('{asset}')->group(function () {
                Route::get('/edit', 'edit')->name('edit');
                Route::put('/update', 'update')->name('update');
                Route::delete('/delete', 'delete')->name('delete');
            });
        });

        // Maintenance Management
        Route::prefix('maintenances')->name('maintenances.')->controller(\Modules\Ticketing\Http\Controllers\MaintenanceController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/datatable', 'datatable')->name('datatable');
            Route::get('/print/excel', 'printExcel')->name('print-excel');
            Route::get('/{maintenance}/detail', 'detail')->name('detail');
            Route::get('/{maintenance}/process', 'process')->name('process');
            Route::post('/{maintenance}/store-checklist', 'storeChecklist')->name('store-checklist');
            Route::post('/{maintenance}/cancel', 'cancel')->name('cancel');
            Route::post('/{maintenance}/confirm', 'confirm')->name('confirm');
            Route::get('/{id}/refinement', [\Modules\Ticketing\Http\Controllers\RefinementController::class, 'index'])->name('refinement.index');
            Route::get('/{id}/refinement/create', [\Modules\Ticketing\Http\Controllers\RefinementController::class, 'create'])->name('refinement.create');
            Route::post('/{id}/refinement', [\Modules\Ticketing\Http\Controllers\RefinementController::class, 'store'])->name('refinement.store');
            Route::get('/{id}/refinement/datatable', [\Modules\Ticketing\Http\Controllers\RefinementController::class, 'datatable'])->name('refinement.datatable');
            Route::post('/{id}/refinement/finish', [\Modules\Ticketing\Http\Controllers\RefinementController::class, 'finish'])->name('refinement.finish');
        });

        Route::prefix('refinement')->name('refinement.')->controller(\Modules\Ticketing\Http\Controllers\RefinementController::class)->group(function () {
            Route::delete('/{id}/delete', 'delete')->name('delete');
            Route::get('/{id}/edit', 'edit')->name('edit');
            Route::put('/{id}/update', 'update')->name('update');
        });
    });
});
