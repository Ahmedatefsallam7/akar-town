<?php

namespace Tests\Feature\Website;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\GeneralSettings\Settings\BusinessInfoSettings;
use Tests\TestCase;

/**
 * The page visitors see while the site is Offline: the logo, the note, a way to reach the office and the language
 * switch.
 */
class SiteOfflinePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitors_see_the_logo_the_note_and_the_language_switch(): void
    {
        $this->get('/')->assertStatus(503)
            ->assertSee('viewBox="0 0 2185 380"', false) // the logo
            ->assertSee(['Under maintenance', "We'll be back soon", __('admin.site_status.offline_message')])
            ->assertSee(route('locale.switch', 'ar'), false)
            ->assertDontSee('Need help in the meantime?'); // nothing to offer without a hotline or an e-mail

        $this->withCookie('locale', 'ar')->get('/')->assertStatus(503)
            ->assertSee(['تحت الصيانة', 'سنعود قريباً'])
            ->assertSee(route('locale.switch', 'en'), false);
    }

    public function test_the_hotline_and_email_from_business_info_are_offered(): void
    {
        $business = app(BusinessInfoSettings::class);
        $business->hotline = '19 555';
        $business->email = 'info@example.test';
        $business->save();

        $this->get('/')->assertStatus(503)
            ->assertSee(['Need help in the meantime?', '19 555'])
            ->assertSee('href="tel:19555"', false)
            ->assertSee('href="mailto:info@example.test"', false);
    }
}
