<?php

namespace Tests\Feature\Performance;

use App\Livewire\Website\Properties;
use App\Support\Images\ProtectedImages;
use App\Support\Website\GlobalSearch;
use App\Support\Website\Image;
use App\Support\Website\PropertySearch;
use App\Support\Website\SearchOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Once;
use Livewire\Livewire;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\Locations\Models\Location;
use Modules\Lookups\Models\Lookup;
use Modules\Lookups\Support\LocationTree;
use Modules\Units\Models\Unit;
use Tests\TestCase;

/**
 * Performance review (2026-11-18): what every page used to read again is kept, and cleared when it changes.
 */
class WebsiteCachesTest extends TestCase
{
    use RefreshDatabase;

    private Lookup $compound;

    protected function setUp(): void
    {
        parent::setUp();
        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();

        $root = Lookup::create(['slug' => LocationTree::ROOT_SLUG, 'name_en' => 'Locations']);
        $egypt = $root->children()->create(['slug' => 'egypt', 'name_en' => 'Egypt']);
        $cairo = $egypt->children()->create(['slug' => 'cairo', 'name_en' => 'Cairo']);
        $region = $cairo->children()->create(['slug' => 'new-cairo', 'name_en' => 'New Cairo']);
        $area = $region->children()->create(['slug' => 'fifth', 'name_en' => 'Fifth']);
        $this->compound = $area->children()->create(['slug' => 'mivida', 'name_en' => 'Mivida']);
        Lookup::fixTree();
    }

    public function test_the_compound_filter_is_kept_for_every_request_and_cleared_when_a_unit_changes(): void
    {
        $unit = Unit::factory()->create(['is_published' => false, 'sub_area_id' => $this->compound->id]);
        $this->assertSame([], SearchOptions::compounds('units'));

        // Kept: the next request reads no table for it.
        Once::flush();
        $this->assertSame(0, $this->countQueries(fn () => SearchOptions::compounds('units')));

        // Publishing the unit clears it.
        $unit->update(['is_published' => true]);
        Once::flush();
        $this->assertSame(['mivida' => 'Mivida'], SearchOptions::compounds('units'));

        $unit->delete();
        Once::flush();
        $this->assertSame([], SearchOptions::compounds('units'));
    }

    public function test_the_search_finds_the_compounds_with_properties_without_reading_a_table(): void
    {
        $empty = $this->compound->parent->children()->create(['slug' => 'mivida-east', 'name_en' => 'Mivida East']);
        Lookup::fixTree();
        Unit::factory()->create(['is_published' => true, 'sub_area_id' => $this->compound->id]);
        Unit::factory()->create(['is_published' => false, 'sub_area_id' => $empty->id]);
        $this->assertSame([$this->compound->id], PropertySearch::compoundsWithProperties('mivida'));

        Once::flush();
        $this->assertSame(0, $this->countQueries(fn () => PropertySearch::compoundsWithProperties('mivida')));
        $this->assertSame([], PropertySearch::compoundsWithProperties('nothing like it'));
    }

    public function test_a_location_page_counts_its_properties_once(): void
    {
        $location = Location::factory()->create(['slug' => 'mivida-page', 'is_active' => true, 'sub_area_id' => $this->compound->id]);
        Unit::factory()->count(2)->create(['is_published' => true, 'sub_area_id' => $this->compound->id]);
        Unit::factory()->create(['is_published' => true]);

        $sql = [];
        DB::listen(function ($query) use (&$sql) {
            $sql[] = $query->sql;
        });
        $this->get(route('locations.show', $location->slug))->assertOk()->assertSee('2');

        $this->assertCount(1, array_filter($sql, fn (string $q) => str_contains($q, 'count(*)') && str_contains($q, 'from "units"') && str_contains($q, 'sub_area_id')));
        $this->assertCount(1, array_filter($sql, fn (string $q) => str_starts_with($q, 'select * from "locations"')));
    }

    public function test_the_website_search_keeps_its_order_when_it_finds_ids_first(): void
    {
        // Best name match first, then featured, then newest; unpublished never.
        Unit::factory()->create(['name_en' => 'Garden Villa', 'is_published' => true, 'is_featured' => false]);
        Unit::factory()->create(['name_en' => 'Villa Old', 'is_published' => true, 'is_featured' => false]);
        Unit::factory()->create(['name_en' => 'Villa Star', 'is_published' => true, 'is_featured' => true]);
        Unit::factory()->create(['name_en' => 'Villa New', 'is_published' => true, 'is_featured' => false]);
        Unit::factory()->create(['name_en' => 'Villa Hidden', 'is_published' => false]);

        $titles = array_column(GlobalSearch::for('villa')['properties'], 'title');

        $this->assertSame(['Villa Star', 'Villa New', 'Villa Old', 'Garden Villa'], $titles);
        $this->assertSame(['Villa Star', 'Villa New'], array_column(GlobalSearch::for('villa', 2)['properties'], 'title'));
    }

    public function test_the_listing_without_a_project_filter_doesnt_look_for_a_project(): void
    {
        $sql = [];
        DB::listen(function ($query) use (&$sql) {
            $sql[] = $query->sql;
        });

        PropertySearch::query(['project' => ''])->toSql();

        $this->assertSame([], array_filter($sql, fn (string $q) => str_contains($q, 'from "projects"')));
    }

    public function test_the_lowest_price_first_keeps_the_units_without_a_price_last(): void
    {
        foreach (['No Price' => null, 'Dear' => 9_000_000, 'Cheap' => 1_000_000] as $name => $price) {
            Unit::factory()->create(['name_en' => $name, 'price' => $price, 'is_published' => true]);
        }

        $this->assertSame(['Cheap', 'Dear', 'No Price'], PropertySearch::query(['sort' => 'price-asc'])->pluck('name_en')->all());
    }

    public function test_the_locations_tree_is_kept_and_cleared_when_a_location_changes_or_moves(): void
    {
        $this->assertSame('Mivida', LocationTree::path([$this->compound->id]));

        Once::flush();
        $this->assertSame(0, $this->countQueries(fn () => LocationTree::path([$this->compound->id])));

        $this->compound->update(['name_en' => 'Mivida Gardens']);
        $this->assertSame('Mivida Gardens', LocationTree::path([$this->compound->id]));

        // fixTree() rebuilds positions with a query (no model events): it clears the tree too.
        $other = Lookup::query()->create(['parent_id' => $this->compound->parent_id, 'slug' => 'palm', 'name_en' => 'Palm Hills']);
        Lookup::fixTree();
        $this->assertSame(['Palm Hills', 5], [LocationTree::path([$other->id]), LocationTree::levelOf($other->id)]);
    }

    public function test_only_a_change_to_a_place_reads_the_tree_again_and_other_kept_values_stay(): void
    {
        LocationTree::path([$this->compound->id]);
        $other = fn () => once(fn () => random_int(1, PHP_INT_MAX)); // any other value kept for this request
        $kept = $other();

        // An amenity (not a place) leaves the tree as it is.
        $amenities = Lookup::create(['slug' => 'amenities', 'name_en' => 'Amenities']);
        $amenities->children()->create(['slug' => 'gym', 'name_en' => 'Gym']);
        $this->assertSame(0, $this->countQueries(fn () => LocationTree::path([$this->compound->id])));

        // A new compound under a place is read at once; the request's other kept values aren't thrown away.
        $new = $this->compound->parent->children()->create(['slug' => 'hyde-park', 'name_en' => 'Hyde Park']);
        $this->assertSame('Hyde Park', LocationTree::path([$new->id]));
        $this->assertSame($kept, $other());
    }

    public function test_the_caches_hold_plain_values_only(): void
    {
        // The cache never unserializes objects (config cache.serializable_classes = false): a cached model would come
        // back broken on the server (the tests' array cache keeps objects as they are, so this checks it).
        Unit::factory()->create(['is_published' => true, 'sub_area_id' => $this->compound->id]);
        LocationTree::path([$this->compound->id]);
        SearchOptions::compounds('units');

        foreach (['lookups.location_tree', 'website.compounds.units'] as $key) {
            $cached = cache()->get($key);
            $this->assertNotNull($cached, $key);
            array_walk_recursive($cached, fn ($value) => $this->assertFalse(is_object($value), "{$key} holds an object"));
        }
    }

    public function test_an_image_is_sent_without_a_session_or_a_query_and_the_admin_flag_cant_be_forged(): void
    {
        Storage::fake(ProtectedImages::DISK);
        $media = Unit::factory()->create()->addMedia(UploadedFile::fake()->image('villa.jpg', 1600, 1200))->toMediaCollection(Unit::FEATURED_IMAGE)->refresh();
        $visitor = $media->getUrl('card');
        $inPage = ['Sec-Fetch-Dest' => 'image', 'Sec-Fetch-Site' => 'same-origin'];

        $response = null;
        $queries = $this->countQueries(function () use (&$response, $visitor, $inPage) {
            $response = $this->withHeaders($inPage)->get($visitor);
        });
        $response->assertOk();
        $this->assertSame(0, $queries);
        $this->assertSame([], $response->headers->getCookies());

        // "a=1" only counts inside the signature.
        $this->withHeaders($inPage)->get($visitor.'&a=1')->assertForbidden();

        // An admin's links are signed as such; a share link never is, even made on an admin's page.
        $this->actingAs($this->createAdmin());
        $this->assertStringContainsString('a=1', ProtectedImages::url($media->getPathRelativeToRoot()));
        $this->assertStringNotContainsString('a=1', (string) Image::shareUrl($media));
    }

    public function test_a_text_search_lists_the_same_units_in_the_same_order(): void
    {
        foreach (['Garden Villa Old', 'Sea Villa', 'Garden Villa New'] as $i => $name) {
            Unit::factory()->create(['name_en' => $name, 'is_published' => true, 'created_at' => now()->addMinutes($i)]);
        }
        Unit::factory()->create(['name_en' => 'Hidden Villa', 'is_published' => false]);

        Livewire::test(Properties::class, ['q' => 'garden villa'])
            ->assertSeeInOrder(['Garden Villa New', 'Garden Villa Old'])
            ->assertDontSee(['Sea Villa', 'Hidden Villa']);
    }

    public function test_the_listing_indexes_exist(): void
    {
        $indexes = fn (string $table) => array_column(Schema::getIndexes($table), 'name');

        $this->assertContains('units_published_compound_index', $indexes('units'));
        $this->assertContains('units_published_search_index', $indexes('units'));
        $this->assertContains('units_published_price_sort_index', $indexes('units'));
        $this->assertContains('units_published_type_index', $indexes('units'));
        $this->assertContains('projects_published_compound_index', $indexes('projects'));
        $this->assertContains('projects_published_search_index', $indexes('projects'));
        $this->assertContains('projects_developer_published_index', $indexes('projects'));
    }
}
