<?php

use Illuminate\Support\Facades\Route;
use Modules\Archieve\Http\Controllers\ArchieveController;
use Modules\Archieve\Http\Controllers\CategoryController;
use Modules\Archieve\Http\Controllers\CategoryContextController;
use Modules\Archieve\Http\Controllers\DocumentClassificationController;
use Modules\Archieve\Http\Controllers\DivisionStorageController;
use Modules\Archieve\Http\Controllers\DocumentController;
use Modules\Archieve\Http\Controllers\DashboardController;
use Modules\Archieve\Http\Controllers\ReportController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('archieve')->name('archieve.')->group(function () {
        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Reports
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/all', [ReportController::class, 'all'])->name('reports.all');

        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::get('categories/datatable', [CategoryController::class, 'datatable'])->name('categories.datatable');
        Route::get('categories/print/{type}', [CategoryController::class, 'printExcel'])->name('categories.print');

        Route::resource('contexts', CategoryContextController::class)->except(['show']);
        Route::get('contexts/datatable', [CategoryContextController::class, 'datatable'])->name('contexts.datatable');
        Route::get('contexts/print/{type}', [CategoryContextController::class, 'printExcel'])->name('contexts.print');

        Route::resource('classifications', DocumentClassificationController::class)->except(['show']);
        Route::get('classifications/datatable', [DocumentClassificationController::class, 'datatable'])->name('classifications.datatable');
        Route::get('classifications/print/{type}', [DocumentClassificationController::class, 'printExcel'])->name('classifications.print');

        Route::get('division-storages', [DivisionStorageController::class, 'index'])->name('division-storages.index');
        Route::post('division-storages', [DivisionStorageController::class, 'store'])->name('division-storages.store');
        Route::put('division-storages/{divisionStorage}', [DivisionStorageController::class, 'update'])->name('division-storages.update');
        Route::delete('division-storages/{divisionStorage}', [DivisionStorageController::class, 'destroy'])->name('division-storages.destroy');

        // Document routes
        Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('documents/create', [DocumentController::class, 'create'])->name('documents.create');
        Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::get('documents/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
        Route::put('documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
        Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
        Route::get('documents/datatable', [DocumentController::class, 'datatable'])->name('documents.datatable');
        Route::get('documents/print/{type}', [DocumentController::class, 'printExcel'])->name('documents.print');
        Route::get('documents/classification-children/{parentId}', [DocumentController::class, 'getClassificationChildren'])->name('documents.classification-children');
        Route::get('documents/users-by-division/{divisionId}', [DocumentController::class, 'getUsersByDivision'])->name('documents.users-by-division');
        
        // Document Search
        Route::get('documents/search', [DocumentController::class, 'search'])->name('documents.search');
        Route::get('documents/search/results', [DocumentController::class, 'searchResults'])->name('documents.search.results');
        Route::get('documents/search/classifications', [DocumentController::class, 'filteredClassifications'])->name('documents.search.classifications');
    });

    Route::resource('archieves', ArchieveController::class)->names('archieve');
});
