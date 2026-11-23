<?php

use App\Http\Controllers\ProtectedImageController;
use App\Http\Controllers\Website\CareersController;
use App\Http\Controllers\Website\CompareController;
use App\Http\Controllers\Website\DevelopersController;
use App\Http\Controllers\Website\FavouritesController;
use App\Http\Controllers\Website\HomeController;
use App\Http\Controllers\Website\LocationsController;
use App\Http\Controllers\Website\ProjectsController;
use App\Http\Controllers\Website\PropertiesController;
use App\Http\Middleware\SetLocale;
use App\Support\Website\Datasheet;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Modules\WebsiteUsers\Http\Middleware\EnsureWebsiteUserIsActive;
use Modules\WebsiteUsers\Models\WebsiteUser;

// The website's images (AKAR-51): private files shown through short-lived signed links, see ProtectedImages.
Route::get('/images/{path}', ProtectedImageController::class)
    ->where('path', '.*')
    // No session, CSRF or locale: an image needs none of them (the admin is known from its signed link), so a page
    // with a dozen photos doesn't read and write the sessions table a dozen times.
    ->withoutMiddleware([StartSession::class, ShareErrorsFromSession::class, PreventRequestForgery::class, SetLocale::class, EnsureWebsiteUserIsActive::class])
    ->name('images.show');

// Public website. Pages are built one by one from the design; the ones not built yet show the layout only.
Route::get('/', HomeController::class)->name('home');
Route::get('/properties', [PropertiesController::class, 'index'])->name('properties');
Route::get('/properties/{slug}', [PropertiesController::class, 'show'])->name('properties.show');
// Built on the server (mPDF, a few hundred ms): limited per visitor.
Route::get('/properties/{slug}/pdf/{locale}', [PropertiesController::class, 'pdf'])
    ->whereIn('locale', Datasheet::LOCALES)
    ->middleware('throttle:20,1')
    ->name('properties.pdf');
// The property's files (its gallery's brochure...): downloaded, not shown in a page like the protected photos.
Route::get('/properties/{slug}/files/{file}', [PropertiesController::class, 'file'])
    ->whereUuid('file')
    ->middleware('throttle:30,1')
    ->name('properties.file');
Route::view('/projects', 'website.projects')->name('projects');
Route::get('/projects/{slug}', [ProjectsController::class, 'show'])->name('projects.show');
Route::get('/projects/{slug}/pdf/{locale}', [ProjectsController::class, 'pdf'])
    ->whereIn('locale', Datasheet::LOCALES)
    ->middleware('throttle:20,1')
    ->name('projects.pdf');
Route::view('/locations', 'website.locations')->name('locations');
Route::get('/locations/{slug}', [LocationsController::class, 'show'])->name('locations.show');
Route::view('/wishlist', 'website.wishlist')->name('wishlist');
Route::view('/search', 'website.search')->name('search');
Route::view('/developers', 'website.developers')->name('developers');
Route::get('/developers/{slug}', [DevelopersController::class, 'show'])->name('developers.show');
Route::view('/about', 'website.about')->name('about');
Route::view('/contact', 'website.contact')->name('contact');
Route::view('/careers', 'website.careers')->name('careers');
Route::get('/careers/{slug}', [CareersController::class, 'show'])->name('careers.show');
Route::get('/compare', CompareController::class)->name('compare');
Route::view('/privacy-policy', 'website.privacy')->name('privacy');
Route::view('/terms', 'website.terms')->name('terms');

// Website sign out
Route::post('/account/logout', function () {
    auth('website')->logout();
    session()->regenerate(true);
    session()->regenerateToken();

    return redirect()->to(url('/'))->with(EnsureWebsiteUserIsActive::NOTICE, __('You are signed out.'));
})->name('account.logout');

// The signed-in visitor's account page; a guest goes home with a note to sign in (the website has no sign-in page).
Route::get('/account', fn () => auth('website')->check()
    ? view('website.account')
    : redirect()->route('home')->with(EnsureWebsiteUserIsActive::NOTICE, __('Please sign in to see your account.')))->name('account');

// A signed-in user's heart clicks, saved to the account ($store.favourites).
Route::post('/account/favourites', FavouritesController::class)->middleware(['auth:website', 'throttle:60,1'])->name('account.favourites');

// The "reset your password" e-mail's link: a website page with the new password form (App\Livewire\Website\ResetPassword).
Route::view('/account/reset-password/{token}', 'website.account.reset-password')->name('account.password.reset');

// The "verify your e-mail" link: signed (can't be changed or made up) and valid 60 minutes
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
