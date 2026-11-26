<?php

namespace Tests\Feature\Website;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\Units\Models\Unit;
use Tests\TestCase;

class NotFoundPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();
    }

    public function test_an_unknown_address_shows_the_websites_page_not_found(): void
    {
        $this->get('/no-such-page')
            ->assertNotFound()
            ->assertSee(['Page not found', 'Back to Home', '<meta name="robots" content="noindex" />'], false)
            ->assertSee(['href="'.route('properties').'"', 'href="'.route('projects').'"'], false);
    }

    public function test_it_follows_the_visitors_language(): void
    {
        $this->withCookie('locale', 'ar')->get('/no-such-page')
            ->assertNotFound()
            ->assertSee(['الصفحة غير موجودة', 'العودة إلى الرئيسية']);
    }

    public function test_a_server_error_shows_its_own_page_without_the_database(): void
    {
        $html = view('errors.500')->render();

        $this->assertStringContainsString('Something went wrong', $html);
        $this->assertStringContainsString('noindex', $html);
    }

    public function test_a_hidden_property_shows_it_too(): void
    {
        $unit = Unit::factory()->create(['is_published' => false]);

        $this->get(route('properties.show', $unit->slug))->assertNotFound()->assertSee('Page not found');
    }
}
