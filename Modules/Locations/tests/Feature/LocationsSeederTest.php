<?php

namespace Modules\Locations\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Locations\Database\Seeders\LocationsDatabaseSeeder;
use Modules\Locations\Models\Location;
use Modules\Lookups\Database\Seeders\LookupsDatabaseSeeder;
use Modules\Lookups\Models\Lookup;
use Tests\TestCase;

class LocationsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_are_tied_to_their_place_with_every_level_above_it(): void
    {
        $this->seed([LookupsDatabaseSeeder::class, LocationsDatabaseSeeder::class]);

        $page = Location::where('slug', 'fifth-settlement')->sole();
        $names = Lookup::whereIn('id', [$page->country_id, $page->city_id, $page->region_id, $page->area_id])->orderBy('_lft')->pluck('name_en')->all();

        $this->assertSame(['Egypt', 'Cairo', 'New Cairo', 'Fifth Settlement'], $names);
        $this->assertNull($page->sub_area_id);
        $this->assertTrue($page->is_featured);
        $this->assertTrue($page->is_active);
    }

    public function test_running_again_creates_nothing_new_and_keeps_admin_edits(): void
    {
        $this->seed([LookupsDatabaseSeeder::class, LocationsDatabaseSeeder::class]);
        $count = Location::count();
        Location::where('slug', 'cairo')->update(['name_en' => 'Cairo (edited)']);

        $this->seed(LocationsDatabaseSeeder::class);

        $this->assertSame($count, Location::count());
        $this->assertSame('Cairo (edited)', Location::where('slug', 'cairo')->value('name_en'));
    }

    public function test_pages_whose_place_is_not_in_the_tree_are_skipped(): void
    {
        $this->seed([LookupsDatabaseSeeder::class, LocationsDatabaseSeeder::class]);

        $this->assertFalse(Location::where('slug', 'mohandessin')->exists());
    }

    public function test_nothing_is_seeded_without_the_locations_tree(): void
    {
        $this->seed(LocationsDatabaseSeeder::class);

        $this->assertSame(0, Location::count());
    }
}
