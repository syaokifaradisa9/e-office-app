<?php

use Illuminate\Support\Facades\Route;
use Modules\Ticketing\Http\Controllers\TicketingController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('ticketings', TicketingController::class)->names('ticketing');
});
