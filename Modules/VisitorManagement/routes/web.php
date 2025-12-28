<?php

use Illuminate\Support\Facades\Route;

use Modules\VisitorManagement\Http\Controllers\VisitorCheckInController;
use Modules\VisitorManagement\Http\Controllers\VisitorCheckOutController;
use Modules\VisitorManagement\Http\Controllers\VisitorController;
use Modules\VisitorManagement\Http\Controllers\VisitorDashboardController;
use Modules\VisitorManagement\Http\Controllers\PurposeController;
use Modules\VisitorManagement\Http\Controllers\FeedbackQuestionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public Routes (No Auth)
Route::prefix('visitor')->group(function () {
    // Check-in Flow
    Route::controller(VisitorCheckInController::class)->prefix('check-in')->name('visitor.check-in.')->group(function() {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/list', 'list')->name('list');
        Route::prefix('{visitor}')->group(function() {
             Route::get('/success', 'success')->name('success');
             Route::get('/', 'edit')->name('edit');
             Route::post('/', 'update')->name('update');
        });
    });

    // Checkout flow
    Route::controller(VisitorCheckOutController::class)->prefix('check-out')->name('visitor.check-out.')->group(function() {
        Route::get('/', 'index')->name('index');
        Route::get('/search', 'search')->name('search');
        Route::prefix('{visitor}')->group(function() {
             Route::get('/', 'show')->name('show');
             Route::post('/', 'store')->name('store');
             Route::post('/cancel', 'cancel')->name('cancel');
             Route::get('/success', 'success')->name('success');
        });
    });
});

// Authenticated Routes
Route::middleware(['auth', 'verified'])->prefix('visitor')->name('visitor.')->group(function () {
    Route::get('/dashboard', [VisitorDashboardController::class, 'index'])->name('dashboard');

    // Purpose (Master Data)
    Route::controller(PurposeController::class)->prefix('purposes')->name('purposes.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/{purpose}/edit', 'edit')->name('edit');
        Route::put('/{purpose}/update', 'update')->name('update');
        Route::delete('/{purpose}/delete', 'destroy')->name('destroy');
        Route::post('/{purpose}/toggle', 'toggleStatus')->name('toggle');
    });

    // Feedback Questions (Master Data)
    Route::controller(FeedbackQuestionController::class)->prefix('feedback-questions')->name('feedback-questions.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/{question}/edit', 'edit')->name('edit');
        Route::put('/{question}/update', 'update')->name('update');
        Route::delete('/{question}/delete', 'destroy')->name('destroy');
        Route::post('/{question}/toggle', 'toggleStatus')->name('toggle');
    });

    Route::controller(VisitorController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/export', 'export')->name('export');
        Route::post('/store-invitation', 'storeInvitation')->name('store-invitation');
        Route::get('/{visitor}', 'show')->name('show');
        Route::post('/{visitor}/confirm', 'confirm')->name('confirm');
    });
});
