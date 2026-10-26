<?php

namespace Tests\Feature\Website;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\GeneralSettings\Settings\PrivacyPolicySettings;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\GeneralSettings\Settings\TermsOfUseSettings;
use Tests\TestCase;

/**
 * The Privacy Policy and Terms of Use pages: their introduction and numbered sections from Settings, with a contents
 * list and a link to the contact page.
 */
class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();
    }

    private function fill(string $class): void
    {
        $settings = app($class);
        $settings->description_en = "We care about you.\n\nPlease read this <b>carefully</b>.";
        $settings->description_ar = 'نحن نهتم بك.';
        $settings->entries = [
            ['header_en' => 'Information We Collect', 'header_ar' => 'المعلومات التي نجمعها', 'body_en' => "Your name.\nYour phone.", 'body_ar' => 'اسمك.'],
            ['header_en' => '', 'header_ar' => '', 'body_en' => 'Empty section', 'body_ar' => ''],
            ['header_en' => 'Your Rights', 'header_ar' => 'حقوقك', 'body_en' => 'Ask us anytime.', 'body_ar' => 'اسألنا.'],
        ];
        $settings->save();
    }

    public function test_the_privacy_policy_shows_its_settings_as_numbered_sections(): void
    {
        $this->fill(PrivacyPolicySettings::class);

        $this->get(route('privacy'))
            ->assertOk()
            ->assertSee(['<title>Privacy Policy', 'href="#section-1"', 'href="#section-2"', 'id="section-2"'], false)
            ->assertSeeInOrder(['We care about you.', 'Please read this', 'Information We Collect', 'Your name.<br />', 'Your Rights', 'Ask us anytime.'], false)
            ->assertSee('Please read this &lt;b&gt;carefully&lt;/b&gt;.', false) // plain text, never HTML
            ->assertSeeInOrder(['>2</div>', 'Sections'], false)
            ->assertSee(['Questions about your privacy?', 'href="'.route('contact').'"'], false)
            ->assertDontSee('Empty section');
    }

    public function test_the_terms_of_use_page(): void
    {
        $this->fill(TermsOfUseSettings::class);

        $this->get(route('terms'))
            ->assertOk()
            ->assertSee(['Terms of Use', 'Information We Collect', 'Questions about these terms?']);
    }

    public function test_the_footer_links_open_them_and_arabic(): void
    {
        $this->fill(PrivacyPolicySettings::class);

        $this->get(route('home'))->assertSee(['href="'.route('privacy').'"', 'href="'.route('terms').'"'], false);
        $this->withCookie('locale', 'ar')->get(route('privacy'))->assertOk()->assertSee(['سياسة الخصوصية', 'المعلومات التي نجمعها', 'حقوقك', 'في هذه الصفحة']);
    }
}
