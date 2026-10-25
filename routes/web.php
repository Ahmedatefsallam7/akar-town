<?php

use App\Http\Controllers\Website\CareersController;
use App\Http\Controllers\Website\CompareController;
use App\Http\Controllers\Website\DevelopersController;
use App\Http\Controllers\Website\FavouritesController;
use App\Http\Controllers\Website\HomeController;
use App\Http\Controllers\Website\LocationsController;
use App\Http\Controllers\Website\ProjectsController;
use App\Http\Controllers\Website\PropertiesController;
use Illuminate\Support\Facades\Route;
use Modules\WebsiteUsers\Http\Middleware\EnsureWebsiteUserIsActive;
use Modules\WebsiteUsers\Models\WebsiteUser;

// Public website. Pages are built one by one from the design; the ones not built yet show the layout only.
Route::get('/', HomeController::class)->name('home');
Route::get('/properties', [PropertiesController::class, 'index'])->name('properties');
Route::get('/properties/{slug}', [PropertiesController::class, 'show'])->name('properties.show');
Route::view('/projects', 'website.projects')->name('projects');
Route::get('/projects/{slug}', [ProjectsController::class, 'show'])->name('projects.show');
Route::view('/locations', 'website.locations')->name('locations');
Route::get('/locations/{slug}', [LocationsController::class, 'show'])->name('locations.show');
Route::view('/wishlist', 'website.wishlist')->name('wishlist');
Route::view('/developers', 'website.developers')->name('developers');
Route::get('/developers/{slug}', [DevelopersController::class, 'show'])->name('developers.show');
Route::view('/about', 'website.about')->name('about');
Route::view('/contact', 'website.contact')->name('contact');
Route::view('/careers', 'website.careers')->name('careers');
Route::get('/careers/{slug}', [CareersController::class, 'show'])->name('careers.show');
Route::get('/compare', CompareController::class)->name('compare');
Route::view('/privacy-policy', 'website.placeholder')->name('privacy');
Route::view('/terms', 'website.placeholder')->name('terms');

// Website sign out (the window signs in, see App\Livewire\Website\AuthModal). Only the website account: an admin
// signed in on the same browser stays signed in; the session id and the form token are renewed.
Route::post('/account/logout', function () {
    auth('website')->logout();
    session()->regenerate(true);
    session()->regenerateToken();

    // Back to the same page, only within this site (as the language switch).
    $previous = url()->previous();

    return redirect()->to(parse_url($previous, PHP_URL_HOST) === request()->getHost() ? $previous : url('/'));
})->name('account.logout');

// A signed-in user's heart clicks, saved to the account ($store.favourites).
Route::post('/account/favourites', FavouritesController::class)->middleware(['auth:website', 'throttle:60,1'])->name('account.favourites');

// The "reset your password" e-mail's link: a website page with the new password form (App\Livewire\Website\ResetPassword).
Route::view('/account/reset-password/{token}', 'website.account.reset-password')->name('account.password.reset');

// The "verify your e-mail" link: signed (can't be changed or made up) and valid 60 minutes. No database id in the
// URL: the account is found by its e-mail (as the reset link), so a changed e-mail also ends an old link.
Route::get('/account/verify', function () {
    $user = request()->hasValidSignature() ? WebsiteUser::query()->where('email', (string) request()->query('email'))->first() : null;

    if ($user && ! $user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
    }

    return redirect()->route('home')->with(EnsureWebsiteUserIsActive::NOTICE, $user ? __('Your e-mail is verified. Thank you!') : __('This link is invalid or has expired.'));
})->middleware('throttle:6,1')->name('account.verify');

Route::get('/locale/{locale}', function (string $locale) {
    abort_unless(array_key_exists($locale, config('app.available_locales')), 404);

    // Go back only within this site: the Referer header can point anywhere (open redirect).
    $previous = url()->previous();
    $target = parse_url($previous, PHP_URL_HOST) === request()->getHost() ? $previous : url('/');

    return redirect()->to($target)->withCookie(cookie()->forever('locale', $locale));
})->name('locale.switch');
