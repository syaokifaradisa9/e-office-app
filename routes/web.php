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
    Route::prefix('division')->name('division.')->controller(DivisionController::class)->group(function () {
        Route::middleware('role_or_permission:lihat_divisi|kelola_divisi')->group(function () {
            Route::get('/', 'index')->name('index');
        });

        Route::middleware('permission:lihat_divisi')->group(function () {
            Route::get('/datatable', 'datatable')->name('datatable');
            Route::get('/print-excel', 'printExcel')->name('print-excel');
        });

        Route::middleware('permission:kelola_divisi')->group(function () {
            Route::get('/create', 'create')->name('create');
            Route::post('/store', 'store')->name('store');
            Route::get('/{division}/edit', 'edit')->name('edit');
            Route::put('/{division}/update', 'update')->name('update');
            Route::delete('/{division}/delete', 'delete')->name('delete');
        });
    });

    // Position Routes
    Route::prefix('position')->name('position.')->controller(PositionController::class)->group(function () {
        Route::middleware('role_or_permission:lihat_jabatan|kelola_jabatan')->group(function () {
            Route::get('/', 'index')->name('index');
        });

        Route::middleware('permission:lihat_jabatan')->group(function () {
            Route::get('/datatable', 'datatable')->name('datatable');
            Route::get('/print-excel', 'printExcel')->name('print-excel');
        });

        Route::middleware('permission:kelola_jabatan')->group(function () {
            Route::get('/create', 'create')->name('create');
            Route::post('/store', 'store')->name('store');
            Route::get('/{position}/edit', 'edit')->name('edit');
            Route::put('/{position}/update', 'update')->name('update');
            Route::delete('/{position}/delete', 'delete')->name('delete');
        });
    });

    // User Routes
    Route::prefix('user')->name('user.')->controller(UserController::class)->group(function () {
        Route::middleware('role_or_permission:lihat_pengguna|kelola_pengguna')->group(function () {
            Route::get('/', 'index')->name('index');
        });

        Route::middleware('permission:lihat_pengguna')->group(function () {
            Route::get('/datatable', 'datatable')->name('datatable');
            Route::get('/print-excel', 'printExcel')->name('print-excel');
        });

        Route::middleware('permission:kelola_pengguna')->group(function () {
            Route::get('/create', 'create')->name('create');
            Route::post('/store', 'store')->name('store');
            Route::get('/{user}/edit', 'edit')->name('edit');
            Route::put('/{user}/update', 'update')->name('update');
            Route::delete('/{user}/delete', 'delete')->name('delete');
        });
    });

    // Role Routes
    Route::prefix('role')->name('role.')->controller(RoleController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/{role}/edit', 'edit')->name('edit');
        Route::put('/{role}/update', 'update')->name('update');
        Route::delete('/{role}/delete', 'delete')->name('delete');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/print-excel', 'printExcel')->name('print-excel');
    });

    // Profile Routes
    Route::prefix('profile')->name('profile.')->controller(ProfileController::class)->group(function () {
        Route::get('/', 'editProfile')->name('edit');
        Route::put('/update', 'updateProfile')->name('update');
        Route::get('/password', 'editPassword')->name('password');
        Route::put('/password/update', 'updatePassword')->name('password.update');
    });
});
