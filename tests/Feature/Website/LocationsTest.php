<?php

namespace Tests\Feature\Website;

use App\Livewire\Website\Locations;
use App\Livewire\Website\Properties;
use App\Support\Website\FeaturedLocations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\Locations\Models\Location;
use Modules\Lookups\Models\Lookup;
use Modules\Units\Models\Unit;
use Tests\TestCase;

/**
 * The locations page (the design's locations.html) and one location's page (its properties).
 */
class LocationsTest extends TestCase
{
    use RefreshDatabase;

    private Lookup $egypt;

    private Lookup $cairo;

    private Lookup $newCairo;

    protected function setUp(): void
    {
        parent::setUp();

        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();

        // The location tree is read once per request, so it is made first.
        $root = Lookup::create(['slug' => 'locations', 'name_en' => 'Locations']);
        $this->egypt = $root->children()->create(['slug' => 'egypt', 'name_en' => 'Egypt']);
        $this->cairo = $this->egypt->children()->create(['slug' => 'cairo', 'name_en' => 'Cairo']);
        $this->newCairo = $this->cairo->children()->create(['slug' => 'new-cairo-area', 'name_en' => 'New Cairo']);
        Lookup::fixTree();
    }

    private function newCairo(array $attributes = []): Location
    {
        return Location::factory()->create($attributes + [
            'name_en' => 'New Cairo',
            'slug' => 'new-cairo',
            'country_id' => $this->egypt->id,
            'city_id' => $this->cairo->id,
            'region_id' => $this->newCairo->id,
        ]);
    }

    public function test_the_page_lists_active_locations_featured_first_then_in_the_admins_order(): void
    {
        $this->newCairo(['order' => 2]);
        Location::factory()->create(['name_en' => 'Maadi', 'order' => 1]);
        Location::factory()->create(['name_en' => 'Zamalek', 'order' => 5, 'is_featured' => true]);
        Location::factory()->create(['name_en' => 'Hidden Place', 'is_active' => false]);
        Unit::factory()->count(2)->create(['country_id' => $this->egypt->id, 'city_id' => $this->cairo->id, 'region_id' => $this->newCairo->id]);
        Unit::factory()->create(['region_id' => $this->newCairo->id, 'is_published' => false]);

        $this->get(route('locations'))
            ->assertOk()
            ->assertSeeLivewire(Locations::class)
            ->assertSee(['Explore <em>Prime</em> Locations', '>3</div>'], false)
            ->assertSeeInOrder(['Zamalek', 'Featured', 'Maadi', 'Cairo', 'New Cairo', '2 Properties'])
            ->assertSee('href="'.route('locations.show', 'new-cairo').'"', false)
            ->assertDontSee('Hidden Place');
    }

    public function test_load_more_shows_six_more_cards_and_cant_be_changed_by_the_browser(): void
    {
        Location::factory()->count(20)->create();

        Livewire::test(Locations::class)
            ->assertViewHas('cards', fn (array $cards) => count($cards) === 9)
            ->assertSee('Load More Locations')
            ->call('loadMore')
            ->assertViewHas('cards', fn (array $cards) => count($cards) === 15)
            ->call('loadMore')
            ->assertViewHas('cards', fn (array $cards) => count($cards) === 20)
            ->assertDontSee('Load More Locations');

        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(Locations::class)->set('shown', 5000);
    }

    public function test_no_locations_shows_the_empty_message(): void
    {
        $this->get(route('locations'))->assertOk()->assertSee('No locations found');
    }

    public function test_the_page_keeps_the_same_queries_with_more_locations(): void
    {
        Location::factory()->count(3)->create();
        $this->get(route('locations'));
        DB::enableQueryLog();
        $this->get(route('locations'))->assertOk();
        $few = count(DB::getQueryLog());

        Location::factory()->count(9)->create(['region_id' => $this->newCairo->id]);
        DB::flushQueryLog();
        $this->get(route('locations'))->assertOk();

        $this->assertLessThanOrEqual($few + 1, count(DB::getQueryLog())); // + the count for the new chain level
    }

    public function test_a_locations_page_shows_its_header_and_only_its_properties(): void
    {
        $this->newCairo([
            'description_en' => 'Gated compounds and top schools.',
            'meta_title_en' => 'New Cairo Homes for Sale',
            'meta_description_en' => 'Find villas and flats in New Cairo.',
        ]);
        Unit::factory()->create(['name_en' => 'Inside Villa', 'country_id' => $this->egypt->id, 'city_id' => $this->cairo->id, 'region_id' => $this->newCairo->id]);
        Unit::factory()->create(['name_en' => 'Elsewhere Flat']);

        $this->get(route('locations.show', 'new-cairo'))
            ->assertOk()
            ->assertSee(['<title>New Cairo Homes for Sale', 'content="Find villas and flats in New Cairo."', 'Gated compounds and top schools.', 'href="'.route('locations').'"'], false)
            ->assertSee('Inside Villa')
            ->assertDontSee('Elsewhere Flat');
    }

    public function test_without_seo_texts_the_name_and_description_are_used(): void
    {
        $this->newCairo(['description_en' => '<p>Gated compounds.</p>']);

        $this->get(route('locations.show', 'new-cairo'))
            ->assertOk()
            ->assertSee(['<title>New Cairo — Akar Town</title>', 'content="Gated compounds."'], false);
    }

    public function test_the_listing_stays_in_the_location_even_after_reset(): void
    {
        $this->newCairo();
        Unit::factory()->create(['name_en' => 'Inside Villa', 'region_id' => $this->newCairo->id]);
        Unit::factory()->create(['name_en' => 'Elsewhere Flat']);

        Livewire::test(Properties::class, ['place' => 'new-cairo'])
            ->assertSee('Inside Villa')
            ->assertDontSee('Elsewhere Flat')
            ->call('clear')
            ->assertSet('place', 'new-cairo')
            ->assertDontSee('Elsewhere Flat');

        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(Properties::class, ['place' => 'new-cairo'])->set('place', '');
    }

    public function test_inactive_or_unknown_locations_are_not_found(): void
    {
        $this->newCairo(['is_active' => false]);

        $this->get(route('locations.show', 'new-cairo'))->assertNotFound();
        $this->get(route('locations.show', 'nowhere'))->assertNotFound();
    }

    public function test_a_location_name_is_shown_as_text_not_html(): void
    {
        $this->newCairo(['name_en' => '<script>alert(1)</script>']);

        $this->get(route('locations.show', 'new-cairo'))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_the_home_destinations_open_the_location_pages(): void
    {
        $this->newCairo(['is_featured' => true]);

        $this->assertSame(route('locations.show', 'new-cairo'), FeaturedLocations::get()[0]['url']);
    }

    public function test_arabic(): void
    {
        $this->newCairo(['name_ar' => 'القاهرة الجديدة']);

        $this->withCookie('locale', 'ar')
            ->get(route('locations'))
            ->assertOk()
            ->assertSee(['اكتشف', 'القاهرة الجديدة']);
    }
}
