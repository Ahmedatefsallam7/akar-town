<?php

namespace Tests\Feature\Website;

use App\Livewire\Website\Properties;
use App\Support\Website\Image;
use App\Support\Website\PropertyCards;
use App\Support\Website\PropertySearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\Locations\Models\Location;
use Modules\Lookups\Models\Lookup;
use Modules\Projects\Models\Project;
use Modules\Units\Models\Unit;
use Tests\TestCase;

/**
 * The properties listing (the design's listings.html) and its search.
 */
class PropertiesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();
    }

    private function names(array $filters): array
    {
        return PropertySearch::query($filters)->pluck('name_en')->sort()->values()->all();
    }

    private function lookups(string $type, array $slugs): array
    {
        $root = Lookup::create(['slug' => $type, 'name_en' => $type]);

        return collect($slugs)->mapWithKeys(fn ($slug) => [$slug => $root->children()->create(['slug' => $slug, 'name_en' => ucfirst($slug)])->id])->all();
    }

    public function test_the_page_lists_published_properties_with_the_available_count(): void
    {
        Unit::factory()->create(['name_en' => 'Sky Penthouse']);
        Unit::factory()->create(['name_en' => 'Hidden Draft', 'is_published' => false]);

        $this->get(route('properties'))
            ->assertOk()
            ->assertSee('Sky Penthouse')
            ->assertDontSee('Hidden Draft')
            ->assertSee(['Discover <em>Exceptional</em> Properties', 'Available Properties', 'Apply Filters'], false);
    }

    public function test_without_filters_the_count_comes_from_the_cache(): void
    {
        Unit::factory()->count(2)->create(['name_en' => 'Garden Villa']);
        Unit::factory()->create(['name_en' => 'Sea Chalet']);
        $countsUnits = fn () => collect(DB::getQueryLog())->contains(fn (array $query) => str_contains($query['query'], 'count(*)') && str_contains($query['query'], '"units"'));

        $component = Livewire::test(Properties::class); // warms the footer's cached count
        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::test(Properties::class)->assertViewHas('total', 3);
        $this->assertFalse($countsUnits());

        // A filter counts its own matches.
        DB::flushQueryLog();
        $component->set('q', 'Garden')->call('apply')->assertViewHas('total', 2);
        $this->assertTrue($countsUnits());
    }

    public function test_search_by_name_counts_price_and_area(): void
    {
        Unit::factory()->create(['name_en' => 'Sky Villa', 'bedrooms' => 3, 'bathrooms' => 2, 'price' => 3000000, 'area' => 200]);
        Unit::factory()->create(['name_en' => 'Sea Studio', 'bedrooms' => 0, 'bathrooms' => 1, 'price' => 900000, 'area' => 60]);
        Unit::factory()->create(['name_en' => 'Big House', 'bedrooms' => 7, 'bathrooms' => 6, 'price' => 25000000, 'area' => 900]);

        $this->assertSame(['Sky Villa'], $this->names(['q' => 'sky']));
        $this->assertSame(['Sky Villa'], $this->names(['beds' => ['3']]));
        $this->assertSame(['Big House'], $this->names(['beds' => ['6+']]));
        $this->assertSame(['Sea Studio'], $this->names(['beds' => ['0']]));
        $this->assertSame(['Big House', 'Sea Studio'], $this->names(['baths' => ['1', '5+']]));
        $this->assertSame(['Sea Studio', 'Sky Villa'], $this->names(['price' => ['0-2000000', '2000000-5000000']]));
        $this->assertSame(['Big House'], $this->names(['price' => ['20000000-']]));
        $this->assertSame(['Sky Villa'], $this->names(['area_min' => '100', 'area_max' => '300']));
        // Junk is ignored, not an error.
        $this->assertCount(3, $this->names(['beds' => ['abc'], 'price' => ['1;drop'], 'type' => ['x']]));
    }

    public function test_status_intent_type_amenities_location_and_sort(): void
    {
        $offering = $this->lookups('offeringtype', ['primary', 'resale', 'rent']);
        $status = $this->lookups('unitstatus', ['us-off-plan', 'us-ready-to-move']);
        $types = $this->lookups('unittype', ['villa', 'apartment']);
        $amenities = $this->lookups('amenities', ['pool', 'gym']);
        $locations = Lookup::create(['slug' => 'locations', 'name_en' => 'Locations']);
        $egypt = $locations->children()->create(['slug' => 'egypt', 'name_en' => 'Egypt']);
        $cairo = $egypt->children()->create(['slug' => 'cairo', 'name_en' => 'Cairo']);
        $coast = $egypt->children()->create(['slug' => 'north-coast', 'name_en' => 'North Coast']);
        Lookup::fixTree();

        $sale = Unit::factory()->create(['name_en' => 'Sale Villa', 'offering_type_id' => $offering['primary'], 'property_type_id' => $types['villa'], 'price' => 5000000, 'country_id' => $egypt->id, 'city_id' => $cairo->id]);
        Unit::factory()->create(['name_en' => 'Rent Flat', 'offering_type_id' => $offering['rent'], 'property_type_id' => $types['apartment'], 'price' => 30000, 'country_id' => $egypt->id, 'city_id' => $coast->id]);
        Unit::factory()->create(['name_en' => 'Plan Tower', 'offering_type_id' => $offering['resale'], 'unit_status_id' => $status['us-off-plan'], 'price' => 9000000]);
        $sale->lookupsOfType('amenities')->sync([$amenities['pool'], $amenities['gym']]);

        $this->assertSame(['Rent Flat'], $this->names(['status' => ['for-rent']]));
        $this->assertSame(['Plan Tower', 'Rent Flat'], $this->names(['status' => ['for-rent', 'off-plan']]));
        $this->assertSame(['Plan Tower', 'Sale Villa'], $this->names(['intent' => 'buy']));
        $this->assertSame(['Sale Villa'], $this->names(['intent' => 'buy', 'category' => 'primary']));
        $this->assertSame(['Rent Flat'], $this->names(['intent' => 'rent']));
        $this->assertSame(['Sale Villa'], $this->names(['type' => ['villa']]));
        // A project card's "units" link filters by the project's slug (an unknown one is ignored).
        $project = Project::factory()->create();
        $sale->update(['project_id' => $project->id]);
        $this->assertSame(['Sale Villa'], $this->names(['project' => $project->slug]));
        $this->assertCount(3, $this->names(['project' => (string) $project->id]));
        // Filters name values by slug: a database id matches nothing.
        $this->assertCount(3, $this->names(['type' => [(string) $types['villa']]]));
        $this->assertSame(['Sale Villa'], $this->names(['amenities' => ['pool', 'gym']]));
        // A place's name finds the properties there.
        $this->assertSame(['Rent Flat'], $this->names(['q' => 'north']));

        Location::factory()->create(['name_en' => 'Cairo Page', 'slug' => 'cairo-page', 'country_id' => $egypt->id, 'city_id' => $cairo->id]);
        $this->assertSame(['Sale Villa'], $this->names(['location' => 'cairo-page']));

        $this->assertSame(['Rent Flat', 'Sale Villa', 'Plan Tower'], PropertySearch::query(['sort' => 'price-asc'])->pluck('name_en')->all());
        $this->assertSame(['Plan Tower', 'Sale Villa', 'Rent Flat'], PropertySearch::query(['sort' => 'price-desc'])->pluck('name_en')->all());
    }

    public function test_filters_apply_load_more_and_clear(): void
    {
        Unit::factory()->count(11)->create(['bedrooms' => 2]);
        Unit::factory()->create(['name_en' => 'Three Bed Home', 'bedrooms' => 3]);

        $component = Livewire::test(Properties::class)
            ->assertViewHas('total', 12)
            ->assertViewHas('cards', fn ($cards) => count($cards) === 6)
            ->call('loadMore')
            ->assertViewHas('cards', fn ($cards) => count($cards) === 12);

        $component->set('beds', ['3'])->call('apply')
            ->assertViewHas('total', 1)
            ->assertSee('Three Bed Home')
            ->call('clear')
            ->assertViewHas('total', 12)
            ->assertSet('beds', []);
    }

    public function test_the_home_quick_search_lands_on_the_filtered_listing(): void
    {
        Unit::factory()->create(['name_en' => 'Two Beds', 'bedrooms' => 2]);
        Unit::factory()->create(['name_en' => 'Four Beds', 'bedrooms' => 4]);

        Livewire::withQueryParams(['beds' => ['4'], 'q' => 'Four'])
            ->test(Properties::class)
            ->assertSee('Four Beds')
            ->assertDontSee('Two Beds');
    }

    public function test_a_card_without_photos_shows_the_placeholder_and_no_photos_button(): void
    {
        Unit::factory()->create();

        $card = PropertyCards::from(PropertyCards::eagerLoad(Unit::query())->get())[0];

        $this->assertSame([Image::placeholder(), []], [$card['image'], $card['images']]);
    }

    public function test_the_listing_runs_the_same_queries_for_few_and_many_cards(): void
    {
        $count = function () {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get(route('properties'))->assertOk();

            return count(DB::getQueryLog());
        };

        Unit::factory()->create();
        $this->get(route('properties'));
        $few = $count();

        Unit::factory()->count(8)->create();

        $this->assertSame($few, $count());
    }
}
