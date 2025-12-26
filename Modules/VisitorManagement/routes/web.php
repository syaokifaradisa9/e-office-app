<?php

use Illuminate\Support\Facades\Route;
use Modules\VisitorManagement\Http\Controllers\VisitorManagementController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('visitormanagements', VisitorManagementController::class)->names('visitormanagement');
});
