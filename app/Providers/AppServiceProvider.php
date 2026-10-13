<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Mattiverse\Userstamps\Userstamps;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // created_by / updated_by / deleted_by point at admin users: take them from the admin login only. A logged-in
        // website user (the "website" guard) leaves them empty; their leads record who they are in website_user_id.
        Userstamps::resolveUsing(fn () => Auth::guard('web')->id());
    }
}
