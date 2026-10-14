<?php

namespace Tests\Feature\Website;

use App\Support\Website\FeaturedLocations;
use App\Support\Website\FeaturedProjects;
use App\Support\Website\FeaturedUnits;
use App\Support\Website\HomeHero;
use App\Support\Website\HomeSections;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\Locations\Models\Location;
use Modules\Lookups\Models\Lookup;
use Modules\Projects\Models\Project;
use Modules\Sliders\Models\Slider;
use Modules\Sliders\Models\SliderItem;
use Modules\Units\Models\Unit;
use Tests\TestCase;

/**
 * The home page (the design's index.html): hero, quick search and the blocks the admin's home sliders turn on.
 */
class HomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();
    }

    public function test_the_hero_shows_the_hero_sliders_published_projects_in_order(): void
    {
        $hero = Slider::factory()->hero()->create(['is_active' => true]);
        $second = Project::factory()->create(['title_en' => 'Second Tower']);
        $first = Project::factory()->create(['title_en' => 'First Villas']);
        $hidden = Project::factory()->create(['title_en' => 'Draft Project', 'is_published' => false]);
        SliderItem::factory()->create(['slider_id' => $hero->id, 'project_id' => $second->id, 'order' => 2, 'is_active' => true]);
        SliderItem::factory()->create(['slider_id' => $hero->id, 'project_id' => $first->id, 'order' => 1, 'is_active' => true]);
        SliderItem::factory()->create(['slider_id' => $hero->id, 'project_id' => $hidden->id, 'order' => 3, 'is_active' => true]);

        $this->assertSame(['First Villas', 'Second Tower'], array_column(HomeHero::slides(), 'title'));

        $this->get(route('home'))->assertOk()->assertSeeInOrder(['First Villas', 'Second Tower'])->assertDontSee('Draft Project')
            // "Explore Projects" opens the projects list, "View Project Details" the slide's own project.
            ->assertSee(['href="'.route('projects').'"', 'href="'.route('projects.show', $first->slug).'"', 'Explore Projects', 'View Project Details'], false);
    }

    public function test_without_hero_slides_the_featured_projects_are_shown(): void
    {
        Project::factory()->create(['title_en' => 'Featured One', 'is_featured' => true]);
        Project::factory()->create(['title_en' => 'Plain One']);

        $this->assertSame(['Featured One'], array_column(HomeHero::slides(), 'title'));
    }

    public function test_the_blocks_follow_the_admins_home_sliders(): void
    {
        Slider::factory()->create(['content_type' => 'projects', 'title_en' => 'Our Projects', 'order' => 2, 'is_active' => true]);
        Slider::factory()->create(['content_type' => 'properties', 'title_en' => 'Featured Properties', 'order' => 1, 'is_active' => true]);
        Slider::factory()->create(['content_type' => 'locations', 'title_en' => 'Prime Destinations', 'order' => 3, 'is_active' => false]);
        Unit::factory()->create(['name_en' => 'Sky Penthouse', 'is_featured' => true]);
        Project::factory()->create(['title_en' => 'Crown Tower', 'is_featured' => true]);
        Location::factory()->create(['name_en' => 'Hidden Coast', 'is_featured' => true]);

        $this->assertSame(['properties', 'projects'], array_column(HomeSections::get(), 'type'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSeeInOrder(['Featured <em>Properties</em>', 'Sky Penthouse', 'id="why-us"', 'Our <em>Projects</em>', 'Crown Tower'], false)
            ->assertDontSee('Hidden Coast');
    }

    public function test_featured_properties_show_only_published_featured_units_with_their_details(): void
    {
        $types = Lookup::create(['slug' => 'offeringtype', 'name_en' => 'Offering Type']);
        $primary = $types->children()->create(['slug' => 'primary', 'name_en' => 'Primary', 'name_ar' => 'أولي']);
        Unit::factory()->create(['name_en' => 'Garden Villa', 'is_featured' => true, 'price' => 45000000, 'area' => 650, 'bedrooms' => 4, 'bathrooms' => 5, 'offering_type_id' => $primary->id]);
        Unit::factory()->create(['name_en' => 'Not Featured', 'is_featured' => false]);
        Unit::factory()->create(['name_en' => 'Unpublished', 'is_featured' => true, 'is_published' => false]);

        $units = FeaturedUnits::get();

        $this->assertSame(['Garden Villa'], array_column($units, 'title'));
        $this->assertSame(['EGP 45,000,000', '650', 4, 5], [$units[0]['price'], $units[0]['area'], $units[0]['beds'], $units[0]['baths']]);
        $this->assertSame(['Featured', 'Primary'], array_column($units[0]['badges'], 'label'));
    }

    public function test_our_projects_and_destinations_show_featured_ones_with_prices_and_counts(): void
    {
        // The location tree is read once per request, so it is made first.
        $root = Lookup::create(['slug' => 'locations', 'name_en' => 'Locations']);
        $egypt = $root->children()->create(['slug' => 'egypt', 'name_en' => 'Egypt']);
        $cairo = $egypt->children()->create(['slug' => 'cairo', 'name_en' => 'Cairo']);
        Lookup::fixTree();
        Project::factory()->create(['title_en' => 'Lumiere', 'is_featured' => true, 'price_from' => 85000000]);
        Project::factory()->create(['title_en' => 'Hidden', 'is_featured' => true, 'is_published' => false]);
        $this->assertSame(['Lumiere'], array_column(FeaturedProjects::get(), 'title'));
        $this->assertSame('Starting from EGP 85,000,000', FeaturedProjects::get()[0]['price']);

        Location::factory()->create(['name_en' => 'Cairo', 'is_featured' => true, 'country_id' => $egypt->id, 'city_id' => $cairo->id]);
        Location::factory()->create(['name_en' => 'Inactive', 'is_featured' => true, 'is_active' => false]);
        Unit::factory()->count(2)->create(['country_id' => $egypt->id, 'city_id' => $cairo->id]);
        Unit::factory()->create(['country_id' => $egypt->id, 'city_id' => $cairo->id, 'is_published' => false]);

        $locations = FeaturedLocations::get();
        $this->assertSame(['Cairo'], array_column($locations, 'name'));
        $this->assertSame(['Egypt', 2], [$locations[0]['country'], $locations[0]['count']]);
    }

    public function test_the_quick_search_lists_the_admins_property_types_and_sends_to_the_listings(): void
    {
        $types = Lookup::create(['slug' => 'unittype', 'name_en' => 'Unit Type']);
        $types->children()->create(['slug' => 'villa', 'name_en' => 'Villa', 'is_active' => true]);
        $types->children()->create(['slug' => 'bank', 'name_en' => 'Old Bank Type', 'is_active' => false]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(['Buy', 'Rent', 'New Projects', 'Find Properties', 'Villa'])
            ->assertDontSee('Old Bank Type')
            ->assertSee(route('properties'), false);
    }

    public function test_the_home_page_in_arabic(): void
    {
        Slider::factory()->create(['content_type' => 'properties', 'title_en' => 'Featured Properties', 'title_ar' => 'عقارات مميزة', 'is_active' => true]);
        Unit::factory()->create(['is_featured' => true]);

        $this->withCookie('locale', 'ar')
            ->get(route('home'))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee(['عقارات <em>مميزة</em>', 'شراء', 'ابحث عن عقارات', 'السعر الابتدائي'], false);
    }

    public function test_the_home_page_runs_a_small_fixed_number_of_queries(): void
    {
        foreach (['properties', 'projects', 'locations'] as $order => $type) {
            Slider::factory()->create(['content_type' => $type, 'order' => $order + 1, 'is_active' => true]);
        }
        $hero = Slider::factory()->hero()->create(['is_active' => true]);
        $count = function () {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get(route('home'))->assertOk();

            return count(DB::getQueryLog());
        };

        SliderItem::factory()->create(['slider_id' => $hero->id, 'is_active' => true]);
        $this->get(route('home'));
        Unit::factory()->create(['is_featured' => true]);
        Project::factory()->create(['is_featured' => true]);
        Location::factory()->create(['is_featured' => true]);
        $few = $count();

        SliderItem::factory()->count(4)->create(['slider_id' => $hero->id, 'is_active' => true]);
        Unit::factory()->count(5)->create(['is_featured' => true]);
        Project::factory()->count(3)->create(['is_featured' => true]);
        Location::factory()->count(5)->create(['is_featured' => true]);

        // More rows, same queries (no N+1).
        $many = $count();
        $this->assertSame($few, $many, json_encode(array_count_values(array_column(DB::getQueryLog(), 'query'))));
    }
}
