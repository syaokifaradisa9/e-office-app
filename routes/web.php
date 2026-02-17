<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('auth.login');
});

Route::get('/login', function () {
    return redirect()->route('auth.login');
})->name('login');

Route::prefix('auth')->name('auth.')->controller(LoginController::class)->group(function () {
    Route::get('/login', 'index')->name('login');
    Route::post('/verify', 'verify')->name('verify');
    Route::post('/logout', 'logout')->name('logout');
});

Route::middleware(['auth'])->group(function () {
    // Dashboard Routes
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    // Division Routes
    Route::prefix('division')->name('division.')->controller(DivisionController::class)->middleware('division_permission')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/print/{type}', 'printExcel')->name('print');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::prefix('{division}')->group(function () {
            Route::get('/edit', 'edit')->name('edit');
            Route::put('/update', 'update')->name('update');
            Route::delete('/delete', 'delete')->name('delete');
        });
    });

    // Position Routes
    Route::prefix('position')->name('position.')->controller(PositionController::class)->middleware('position_permission')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/print/{type}', 'printExcel')->name('print');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');

        Route::prefix('{position}')->group(function () {
            Route::get('/edit', 'edit')->name('edit');
            Route::put('/update', 'update')->name('update');
            Route::delete('/delete', 'delete')->name('delete');
        });
    });

    // User Routes
    Route::prefix('user')->name('user.')->controller(UserController::class)->middleware('user_permission')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/print/{type}', 'printExcel')->name('print');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');

        Route::prefix('{user}')->group(function () {
            Route::get('/edit', 'edit')->name('edit');
            Route::put('/update', 'update')->name('update');
            Route::delete('/delete', 'delete')->name('delete');
        });
    });

    // Role Routes
    Route::prefix('role')->name('role.')->controller(RoleController::class)->middleware('role_permission')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/print/{type}', 'printExcel')->name('print');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');

        Route::prefix('{role}')->group(function () {
            Route::get('/edit', 'edit')->name('edit');
            Route::put('/update', 'update')->name('update');
            Route::delete('/delete', 'delete')->name('delete');
        });
    });

    // Profile Routes
    Route::prefix('profile')->name('profile.')->controller(ProfileController::class)->group(function () {
        Route::get('/', 'editProfile')->name('edit');
        Route::put('/update', 'updateProfile')->name('update');
        Route::get('/password', 'editPassword')->name('password');
        Route::put('/password/update', 'updatePassword')->name('password.update');
    });
});
