<?php

namespace Tests\Feature\Website;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\GeneralSettings\Settings\AboutUsSettings;
use Modules\GeneralSettings\Settings\SiteSettings;
use Tests\TestCase;

/**
 * The about page (the design's about.html, plus the About Us numbers and core values): everything from
 * Settings → About Us.
 */
class AboutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();

        $about = app(AboutUsSettings::class);
        $about->hero_header_en = 'Redefining Real Estate';
        $about->hero_body_en = 'Discover the passion behind us.';
        $about->story_header_en = 'Built on Trust and Vision';
        $about->story_body_en = "First paragraph.\n\nSecond <b>paragraph</b>.";
        $about->years_of_excellence = 20;
        $about->stats_happy_clients = 5000;
        $about->stats_properties_sold = 1200;
        $about->stats_awards_won = 15;
        $about->stats_commitment = 100;
        $about->core_values = [
            ['icon' => null, 'header_en' => 'Integrity', 'header_ar' => 'النزاهة', 'body_en' => 'We are honest.', 'body_ar' => 'نحن صادقون.'],
            ['icon' => null, 'header_en' => '', 'header_ar' => '', 'body_en' => 'Empty value', 'body_ar' => ''],
        ];
        $about->save();
    }

    public function test_the_page_shows_the_about_us_settings(): void
    {
        $this->get(route('about'))
            ->assertOk()
            ->assertSee(['Redefining <em>Real Estate</em>', 'Built on Trust and <em>Vision</em>', '<p', 'First paragraph.</p>'], false)
            ->assertSee(['Discover the passion behind us.', 'Second <b>paragraph</b>.', '20+', 'Years of Excellence', 'Explore Properties'])
            ->assertDontSee('Second <b>paragraph</b>.', false)
            ->assertSee(['5000+', 'Happy Clients', '1200+', 'Properties Sold', '15+', 'Awards Won', '100%', 'Commitment'])
            ->assertSee(['Our Core <em>Values</em>'], false)
            ->assertSee(['Integrity', 'We are honest.', 'Browse All Properties'])
            ->assertDontSee('Empty value');
    }

    public function test_without_core_values_their_section_is_hidden(): void
    {
        $about = app(AboutUsSettings::class);
        $about->core_values = [];
        $about->save();

        $this->get(route('about'))->assertOk()->assertDontSee('Our Core');
    }

    public function test_the_story_photo_is_the_uploaded_one_else_the_designs(): void
    {
        $this->get(route('about'))->assertSee(asset('images/website/about-story.webp'), false);

        $about = app(AboutUsSettings::class);
        $about->story_image = 'settings/images/story.jpg';
        $about->save();

        $this->get(route('about'))
            ->assertSee(Storage::disk('public')->url('settings/images/story.jpg'), false)
            ->assertDontSee('about-story.webp');
    }

    public function test_arabic(): void
    {
        $about = app(AboutUsSettings::class);
        $about->hero_header_ar = 'إعادة تعريف العقارات';
        $about->story_header_ar = 'قائمة على الثقة والرؤية';
        $about->save();

        $this->withCookie('locale', 'ar')->get(route('about'))
            ->assertOk()
            ->assertSee(['إعادة <em>تعريف العقارات</em>', 'قائمة على الثقة <em>والرؤية</em>'], false)
            ->assertSee(['النزاهة', 'نحن صادقون.', 'عميل سعيد']);
    }
}
