<?php

namespace Tests\Feature\Website;

use App\Support\Website\FooterStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Developers\Models\Developer;
use Modules\GeneralSettings\Settings\BusinessInfoSettings;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\Projects\Models\Project;
use Modules\Units\Models\Unit;
use Tests\TestCase;

/**
 * The website's shared layout (from the design): navbar, mobile drawer and footer, in both languages and themes.
 */
class LayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();
    }

    public function test_every_page_has_the_navbar_and_the_footer_from_the_settings(): void
    {
        $business = app(BusinessInfoSettings::class);
        $business->hotline = '01150000727';
        $business->social_instagram = 'https://www.instagram.com/akar.town.real.estate/';
        $business->social_facebook = null;
        $business->save();

        foreach (['home', 'properties', 'projects', 'developers', 'about', 'contact', 'careers'] as $page) {
            $this->get(route($page))
                ->assertOk()
                ->assertSee('lang="en"', false)
                ->assertSee('dir="ltr"', false)
                ->assertSee('data-theme="light"', false)
                ->assertSee(['Home', 'Properties', 'Projects', 'Developers', 'Submit Property'])
                ->assertSee('href="tel:01150000727"', false)
                ->assertSee('https://www.instagram.com/akar.town.real.estate/', false)
                ->assertDontSee('aria-label="Facebook"', false)
                ->assertSee($business->mini_description_en)
                ->assertSee(['Property Matching', 'Privacy Policy'])
                ->assertSee('href="'.route('locations').'"', false)
                ->assertSee(['href="'.route('wishlist').'"', 'aria-label="Wishlist"'], false);
        }
    }

    public function test_without_a_hotline_no_phone_link_is_shown(): void
    {
        $business = app(BusinessInfoSettings::class);
        $business->hotline = null;
        $business->save();

        $this->get(route('home'))->assertOk()->assertDontSee('href="tel:', false);
    }

    public function test_arabic_pages_are_right_to_left_and_translated(): void
    {
        $this->withCookie('locale', 'ar')
            ->get(route('home'))
            ->assertOk()
            ->assertSee('lang="ar"', false)
            ->assertSee('dir="rtl"', false)
            ->assertSee(['الرئيسية', 'العقارات', 'المشاريع', 'المطورون', 'تقديم عقار', 'مطابقة العقارات'])
            ->assertSee(route('locale.switch', 'en'), false);
    }

    public function test_the_chosen_theme_is_rendered_by_the_server(): void
    {
        $this->withUnencryptedCookie('theme', 'dark')->get(route('home'))->assertSee('data-theme="dark"', false);
        $this->withUnencryptedCookie('theme', 'anything')->get(route('home'))->assertSee('data-theme="light"', false);
    }

    public function test_only_inner_pages_highlight_their_link(): void
    {
        // Home has a see-through navbar over its hero; inner pages have the glass bar and an underlined link.
        $this->get(route('home'))->assertSee('bg-transparent border-transparent', false);
        $this->get(route('projects'))->assertDontSee('bg-transparent border-transparent', false)->assertSee('text-white after:w-full', false);
    }

    public function test_every_footer_link_goes_to_a_page(): void
    {
        $footer = str($this->get(route('home'))->getContent())->after('<footer')->before('</footer>');

        $this->assertStringNotContainsString('href="#"', $footer);
        foreach (['properties', 'projects', 'developers', 'locations', 'about', 'careers', 'contact', 'privacy', 'terms'] as $route) {
            $this->assertStringContainsString('href="'.route($route).'"', $footer);
            $this->get(route($route))->assertOk();
        }
    }

    public function test_footer_numbers_are_counted_once_and_cached(): void
    {
        Unit::factory()->count(2)->create(['is_published' => true]);
        Unit::factory()->create(['is_published' => false]);
        Project::factory()->count(2)->create(); // each with its own published developer
        Project::factory()->create(['is_published' => false]);
        Developer::factory()->create(['is_published' => false]);

        $this->assertSame(['listings' => 2, 'projects' => 2, 'developers' => 3], array_intersect_key(FooterStats::get(), ['listings' => 0, 'projects' => 0, 'developers' => 0]));

        DB::enableQueryLog();
        FooterStats::get();
        $this->assertSame([], DB::getQueryLog());
    }

    public function test_a_page_runs_only_a_few_cache_reads(): void
    {
        // As on the server: database cache, settings and footer numbers read from it on each request (a page with
        // only the layout; the home page's own data is measured in HomeTest).
        config(['cache.default' => 'database']);
        $this->get(route('about'))->assertOk();
        foreach ([SiteSettings::class, BusinessInfoSettings::class] as $settings) {
            app()->forgetInstance($settings);
        }

        DB::enableQueryLog();
        $this->get(route('about'))->assertOk();

        $this->assertLessThanOrEqual(4, count(DB::getQueryLog()), json_encode(array_column(DB::getQueryLog(), 'query')));
    }
}
