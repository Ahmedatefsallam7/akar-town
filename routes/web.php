<?php

use App\Http\Controllers\Website\CompareController;
use App\Http\Controllers\Website\HomeController;
use App\Http\Controllers\Website\ProjectsController;
use App\Http\Controllers\Website\PropertiesController;
use Illuminate\Support\Facades\Route;

// Public website. Pages are built one by one from the design; the ones not built yet show the layout only.
Route::get('/', HomeController::class)->name('home');
Route::get('/properties', [PropertiesController::class, 'index'])->name('properties');
Route::get('/properties/{slug}', [PropertiesController::class, 'show'])->name('properties.show');
Route::view('/projects', 'website.projects')->name('projects');
Route::get('/projects/{slug}', [ProjectsController::class, 'show'])->name('projects.show');
Route::view('/locations', 'website.placeholder')->name('locations');
Route::view('/wishlist', 'website.wishlist')->name('wishlist');
Route::view('/developers', 'website.placeholder')->name('developers');
Route::view('/about', 'website.placeholder')->name('about');
Route::view('/contact', 'website.placeholder')->name('contact');
Route::view('/careers', 'website.placeholder')->name('careers');
Route::get('/compare', CompareController::class)->name('compare');
Route::view('/privacy-policy', 'website.placeholder')->name('privacy');
Route::view('/terms', 'website.placeholder')->name('terms');

Route::get('/locale/{locale}', function (string $locale) {
    abort_unless(array_key_exists($locale, config('app.available_locales')), 404);

    // Go back only within this site: the Referer header can point anywhere (open redirect).
    $previous = url()->previous();
    $target = parse_url($previous, PHP_URL_HOST) === request()->getHost() ? $previous : url('/');

    return redirect()->to($target)->withCookie(cookie()->forever('locale', $locale));
})->name('locale.switch');
