<?php

use Illuminate\Support\Facades\Route;
use Modules\Archieve\Http\Controllers\ArchieveController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('archieves', ArchieveController::class)->names('archieve');
});
