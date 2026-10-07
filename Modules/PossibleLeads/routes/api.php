<?php

use Illuminate\Support\Facades\Route;
use Modules\PossibleLeads\Http\Controllers\TrackClickController;

// The website reports interest clicks here (no session: cheap even with many visitors). 60 a minute per IP.
Route::post('possible-leads', TrackClickController::class)
    ->middleware('throttle:60,1')
    ->name('possible-leads.track');
