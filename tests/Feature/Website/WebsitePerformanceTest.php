<?php

namespace Tests\Feature\Website;

use App\Support\Website\Image;
use App\Support\Website\SearchOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Developers\Models\Developer;
use Modules\Galleries\Models\Gallery;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\Locations\Models\Location;
use Modules\Projects\Models\Project;
use Modules\Sliders\Models\Slider;
use Modules\Sliders\Models\SliderItem;
use Modules\Units\Models\Unit;
use Tests\TestCase;

/**
 * What keeps the website light: image sizes, plain-row option lists and photos that load only when needed.
 */
class WebsitePerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();
    }

    public function test_an_image_url_takes_the_asked_size_then_the_large_one_then_the_original(): void
    {
        Storage::fake('public');
        $unit = Unit::factory()->create();
        $media = $unit->addMedia(UploadedFile::fake()->image('villa.jpg', 2400, 1600))->toMediaCollection(Unit::FEATURED_IMAGE);

        // Sizes are made in the background after an upload: until then, the next best one.
        $media->generated_conversions = ['thumb' => true, 'large' => true, 'card' => true];
        $this->assertStringEndsWith('-card.webp', Image::url($media, 'card'));
        $this->assertStringEndsWith('-large.webp', Image::url($media));
        // Both sizes made: the browser picks (phones the card one).
        $this->assertMatchesRegularExpression('/-card\.webp 900w, .+-large\.webp 1920w$/', Image::srcset($media));
        $media->generated_conversions = ['thumb' => true, 'large' => true, 'card' => false];
        $this->assertStringEndsWith('-large.webp', Image::url($media, 'card'));
        $media->generated_conversions = ['thumb' => true, 'large' => false, 'card' => false];
        $this->assertStringEndsWith('villa.jpg', Image::url($media, 'card'));
        $this->assertNull(Image::srcset($media)); // nothing to choose from yet
        $this->assertNull(Image::url(null));
    }

    public function test_cards_use_the_card_size(): void
    {
        Storage::fake('public');
        $unit = Unit::factory()->create(['is_featured' => true]);
        $unit->addMedia(UploadedFile::fake()->image('villa.jpg', 2400, 1600))->toMediaCollection(Unit::FEATURED_IMAGE);

        $this->get(route('properties'))->assertOk()->assertSee('-card.webp', false);
    }

    public function test_filter_lists_are_read_as_plain_rows_in_the_current_language(): void
    {
        Location::factory()->create(['name_en' => 'New Cairo', 'name_ar' => 'القاهرة الجديدة', 'slug' => 'new-cairo', 'is_active' => true, 'order' => 1]);
        Location::factory()->create(['name_en' => 'Sahel', 'name_ar' => '', 'slug' => 'sahel', 'is_active' => true, 'order' => 2]);
        Location::factory()->create(['name_en' => 'Hidden', 'slug' => 'hidden', 'is_active' => false]);
        $developer = Developer::factory()->create(['name_en' => 'Emaar', 'name_ar' => 'إعمار']);

        $this->assertSame(['new-cairo' => 'New Cairo', 'sahel' => 'Sahel'], SearchOptions::locations());

        app()->setLocale('ar');
        $this->assertSame(['new-cairo' => 'القاهرة الجديدة', 'sahel' => 'Sahel'], SearchOptions::locations());
        $this->assertSame('إعمار', SearchOptions::developers()[$developer->slug]);
    }

    public function test_only_the_first_hero_photo_loads_with_the_home_page(): void
    {
        $hero = Slider::factory()->hero()->create(['is_active' => true]);
        foreach ([1, 2, 3] as $order) {
            SliderItem::factory()->create(['slider_id' => $hero->id, 'project_id' => Project::factory()->create()->id, 'order' => $order, 'is_active' => true]);
        }

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'style="--bg: url('));
        $this->assertSame(2, substr_count($html, 'data-bg-src="'));
        // Phones take the card size, and the first photo is asked for early (in the <head>).
        $this->assertSame(2, substr_count($html, 'data-bg-src-small="'));
        $this->assertStringContainsString('<link rel="preload" as="image"', str($html)->before('</head>'));
    }

    public function test_only_the_first_property_photo_loads_with_the_page(): void
    {
        Storage::fake('public');
        $gallery = Gallery::query()->create(['name_en' => 'Villa Gallery', 'name_ar' => 'معرض']);
        foreach (['a.jpg', 'b.jpg'] as $name) {
            $gallery->addMedia(UploadedFile::fake()->image($name))->toMediaCollection(Gallery::PHOTOS);
        }
        $unit = Unit::factory()->create(['gallery_id' => $gallery->id, 'latitude' => 30.04, 'longitude' => 31.4]);

        $html = $this->get(route('properties.show', $unit->slug))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/src="[^"]+" srcset="[^"]*" fetchpriority="high"/', $html);
        $this->assertMatchesRegularExpression('/data-src="[^"]+" data-srcset="[^"]*"/', $html);
        // The map (Leaflet + tiles) loads when it comes near the screen.
        $this->assertStringContainsString('x-intersect.once.margin.300px="map()"', $html);
    }

    public function test_livewire_comes_in_the_site_bundle_instead_of_its_blocking_script(): void
    {
        $html = $this->get(route('about'))->assertOk()->getContent();

        $this->assertStringContainsString('window.livewireScriptConfig', $html);
        $this->assertDoesNotMatchRegularExpression('/<script src="[^"]*livewire[^"]*\.js/', $html);
    }

    public function test_english_pages_draw_the_language_switch_letters_without_the_whole_arabic_font(): void
    {
        $this->get(route('about'))->assertOk()
            ->assertSee('font-label-ar text-[11px]', false)
            ->assertDontSee('font-arabic text-[11px]', false);

        $this->withCookie('locale', 'ar')->get(route('about'))->assertOk()
            ->assertSee('font-arabic text-[11px]', false);
    }

    public function test_the_listing_header_photo_is_asked_for_early_and_phones_take_the_small_copy(): void
    {
        $html = $this->get(route('properties'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<link rel="preload" as="image" href="[^"]+" media="\(max-width: 768px\)" fetchpriority="high"/', $html);
        $this->assertStringContainsString('bg-(image:--bg-small) min-[769px]:bg-(image:--bg)', $html);

        Storage::fake('public');
        $location = Location::factory()->create(['slug' => 'new-cairo', 'is_active' => true]);
        $location->addMedia(UploadedFile::fake()->image('cairo.jpg', 1600, 900))->toMediaCollection(Location::FEATURED_IMAGE);

        $html = $this->get(route('locations.show', 'new-cairo'))->assertOk()->getContent();
        $small = Image::url($location->getFirstMedia(Location::FEATURED_IMAGE), 'card');
        $this->assertStringContainsString('href="'.$small.'" media="(max-width: 768px)"', $html);
    }

    public function test_the_wishlist_keeps_a_screen_of_room_so_the_footer_does_not_jump(): void
    {
        $this->get(route('wishlist'))->assertOk()->assertSee('min-h-dvh bg-page', false);
    }

    public function test_the_home_splash_is_css_only_and_short(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertSee(['animate-splash-out', 'animate-splash-column', 'animate-splash-logo', 'motion-reduce:hidden'], false)
            ->assertDontSee('x-data="splashScreen"', false);
    }

    public function test_the_hero_entry_is_played_by_css_and_hidden_parts_are_set_up_later(): void
    {
        // A hero with slides (without them the home page has no slider to play in).
        $hero = Slider::factory()->hero()->create(['is_active' => true]);
        SliderItem::factory()->create(['slider_id' => $hero->id, 'project_id' => Project::factory()->create()->id, 'order' => 1, 'is_active' => true]);

        $home = $this->get(route('home'))->assertOk();
        // The navbar and the first slide come in as the splash parts, without waiting for the scripts.
        $home->assertSee(['motion-safe:animate-hero-nav', 'motion-safe:animate-hero-text motion-safe:[animation-delay:0.64s]', 'motion-safe:animate-hero-zoom'], false);

        // Windows, lightboxes and the footer are set up by Alpine after the start (website/boot.js).
        $this->get(route('about'))->assertOk()
            ->assertSee(['x-data x-ignore data-alpine-later="touch"', 'x-data x-ignore data-alpine-later="near"', 'x-data x-ignore data-alpine-later="soon"'], false)
            ->assertDontSee('motion-safe:animate-hero-nav', false);
    }

    public function test_parts_under_the_first_screen_are_set_up_when_they_come_near(): void
    {
        // The phone menu waits for the first touch; it's closed in the markup until then.
        $this->get(route('about'))->assertOk()
            ->assertSee('<div x-ignore data-alpine-later="touch" class="contents">', false);

        $unit = Unit::factory()->create(['slug' => 'garden-villa']);
        $this->get(route('properties.show', 'garden-villa'))->assertOk()
            ->assertSee(['<div x-ignore data-alpine-later="near" class="contents">', 'class="bg-page px-12 pt-12'], false);

        // The splash's sketches are drawn once and reused.
        $html = $this->get(route('home'))->assertOk()->getContent();
        $this->assertSame(10, substr_count($html, '<symbol id="splash-sketch-'));
        $this->assertGreaterThan(10, substr_count($html, '<use href="#splash-sketch-'));
    }
}

