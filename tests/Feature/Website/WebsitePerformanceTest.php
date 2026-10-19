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
        $media->generated_conversions = ['thumb' => true, 'large' => true, 'card' => false];
        $this->assertStringEndsWith('-large.webp', Image::url($media, 'card'));
        $media->generated_conversions = ['thumb' => true, 'large' => false, 'card' => false];
        $this->assertStringEndsWith('villa.jpg', Image::url($media, 'card'));
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

        $this->assertSame(1, substr_count($html, 'data-bg class="absolute inset-x-0 -top-[5%] h-[110%] w-full bg-cover bg-center will-change-transform"  style="background-image'));
        $this->assertSame(2, substr_count($html, 'data-bg-src="'));
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

        $this->assertMatchesRegularExpression('/src="[^"]+"\s+data-pdf-hero\s+data-photo="0"/', $html);
        $this->assertMatchesRegularExpression('/data-src="[^"]+"\s+data-photo="1"/', $html);
        // The map (Leaflet + tiles) loads when it comes near the screen.
        $this->assertStringContainsString('x-intersect.once.margin.300px="map()"', $html);
    }
}
