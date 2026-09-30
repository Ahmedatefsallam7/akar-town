<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/locale/{locale}', function (string $locale) {
    abort_unless(array_key_exists($locale, config('app.available_locales')), 404);

    return redirect()->back()->withCookie(cookie()->forever('locale', $locale));
})->name('locale.switch');
