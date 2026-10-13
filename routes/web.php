<?php

use App\Http\Controllers\Website\HomeController;
use App\Http\Controllers\Website\PropertiesController;
use Illuminate\Support\Facades\Route;

// Public website. Pages are built one by one from the design; the ones not built yet show the layout only.
Route::get('/', HomeController::class)->name('home');
Route::get('/properties', [PropertiesController::class, 'index'])->name('properties');
Route::view('/projects', 'website.placeholder')->name('projects');
Route::view('/developers', 'website.placeholder')->name('developers');
Route::view('/about', 'website.placeholder')->name('about');
Route::view('/contact', 'website.placeholder')->name('contact');
Route::view('/careers', 'website.placeholder')->name('careers');
Route::view('/compare', 'website.placeholder')->name('compare');

Route::get('/locale/{locale}', function (string $locale) {
    abort_unless(array_key_exists($locale, config('app.available_locales')), 404);

    // Go back only within this site: the Referer header can point anywhere (open redirect).
    $previous = url()->previous();
    $target = parse_url($previous, PHP_URL_HOST) === request()->getHost() ? $previous : url('/');

    return redirect()->to($target)->withCookie(cookie()->forever('locale', $locale));
})->name('locale.switch');
