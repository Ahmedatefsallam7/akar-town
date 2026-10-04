<?php

namespace Tests\Feature;

use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', SetLocale::class])
            ->get('/_test/locale', fn () => app()->getLocale());
    }

    public function test_switch_route_stores_the_locale_in_a_cookie_and_redirects_back(): void
    {
        $this->from('/admin')
            ->get(route('locale.switch', 'ar'))
            ->assertRedirect('/admin')
            ->assertCookie('locale', 'ar');
    }

    public function test_switch_route_rejects_an_unknown_locale(): void
    {
        $this->get(route('locale.switch', 'fr'))->assertNotFound();
    }

    public function test_middleware_uses_the_locale_cookie(): void
    {
        $this->withCookie('locale', 'ar')->get('/_test/locale')->assertSee('ar');
    }

    public function test_middleware_falls_back_to_the_browser_language(): void
    {
        $this->withHeader('Accept-Language', 'ar,en;q=0.8')->get('/_test/locale')->assertSee('ar');
    }

    public function test_middleware_ignores_an_unsupported_locale_cookie(): void
    {
        $this->withCookie('locale', 'fr')->get('/_test/locale')->assertSee(config('app.locale'));
    }
}
