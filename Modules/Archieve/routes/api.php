<?php

use Illuminate\Support\Facades\Route;
use Modules\Archieve\Http\Controllers\ArchieveController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('archieves', ArchieveController::class)->names('archieve');
});
