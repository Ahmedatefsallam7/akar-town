<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/locale/{locale}', function (string $locale) {
    abort_unless(array_key_exists($locale, config('app.available_locales')), 404);

    // Go back only within this site: the Referer header can point anywhere (open redirect).
    $previous = url()->previous();
    $target = parse_url($previous, PHP_URL_HOST) === request()->getHost() ? $previous : url('/');

    return redirect()->to($target)->withCookie(cookie()->forever('locale', $locale));
})->name('locale.switch');
