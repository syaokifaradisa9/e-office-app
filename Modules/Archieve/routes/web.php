<?php

use Illuminate\Support\Facades\Route;
use Modules\Archieve\Http\Controllers\ArchieveController;
use Modules\Archieve\Http\Controllers\CategoryController;
use Modules\Archieve\Http\Controllers\CategoryContextController;
use Modules\Archieve\Http\Controllers\DocumentClassificationController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('archieve')->name('archieve.')->group(function () {
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::get('categories/datatable', [CategoryController::class, 'datatable'])->name('categories.datatable');
        Route::get('categories/print-excel', [CategoryController::class, 'printExcel'])->name('categories.print-excel');

        Route::resource('contexts', CategoryContextController::class)->except(['show']);
        Route::get('contexts/datatable', [CategoryContextController::class, 'datatable'])->name('contexts.datatable');
        Route::get('contexts/print-excel', [CategoryContextController::class, 'printExcel'])->name('contexts.print-excel');

        Route::resource('classifications', DocumentClassificationController::class)->except(['show']);
        Route::get('classifications/datatable', [DocumentClassificationController::class, 'datatable'])->name('classifications.datatable');
        Route::get('classifications/print-excel', [DocumentClassificationController::class, 'printExcel'])->name('classifications.print-excel');
    });

    Route::resource('archieves', ArchieveController::class)->names('archieve');
});
