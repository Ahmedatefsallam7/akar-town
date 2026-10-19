<?php

use App\Http\Middleware\EnsureSiteIsLive;
use Illuminate\Support\Facades\Route;
use Modules\PossibleLeads\Http\Controllers\TrackClickController;

// The website reports interest clicks here (no session: cheap even with many visitors). Per IP: 60 a minute and 300
// a day, so nobody can fill the table. Closed with the website while it's offline.
Route::post('possible-leads', TrackClickController::class)
    ->middleware(['throttle:60,1', 'throttle:300,1440,possible-leads-day', EnsureSiteIsLive::class])
    ->name('possible-leads.track');
