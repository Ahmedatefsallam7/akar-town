<?php

namespace Tests\Feature\Website;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\Lookups\Models\Lookup;
use Modules\Units\Models\Unit;
use Modules\Users\Models\User;
use Tests\TestCase;

/**
 * The compare page (the design's compare.html): the compare bar's "?units=" (slugs) side by side.
 */
class CompareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();
    }

    private function amenity(string $name): Lookup
    {
        $root = Lookup::query()->firstOrCreate(['slug' => 'amenities'], ['name_en' => 'Amenities']);

        return $root->children()->create(['slug' => str($name)->slug(), 'name_en' => $name]);
    }

    public function test_the_picked_units_show_side_by_side_with_the_best_values(): void
    {
        $agent = User::factory()->create(['first_name' => 'Sara', 'last_name' => 'Hassan']);
        $cheap = Unit::factory()->create(['name_en' => 'Cheap Flat', 'price' => 3000000, 'bedrooms' => 2, 'bathrooms' => 2, 'area' => 120, 'floor' => 3]);
        $big = Unit::factory()->create(['name_en' => 'Big Villa', 'price' => 9000000, 'bedrooms' => 5, 'bathrooms' => 2, 'area' => 450, 'floor' => 1, 'agent_id' => $agent->id]);
        $draft = Unit::factory()->create(['name_en' => 'Draft Unit', 'is_published' => false]);
        $big->lookupsOfType('amenities')->sync([$this->amenity('Private Pool')->id]);
        $cheap->lookupsOfType('amenities')->sync([$this->amenity('Gym')->id]);

        $response = $this->get(route('compare', ['units' => "{$big->slug},{$cheap->slug},{$draft->slug},no-such-unit"]));
        $response->assertOk()
            ->assertSeeInOrder(['Big Villa', 'Cheap Flat'])
            ->assertSee(['Compare <em>Properties</em>', '9,000,000', '3,000,000', 'Best Value', 'Most', 'Largest', 'Highest', 'Sara Hassan', 'Private Pool', 'Gym', 'Ready to <em>Decide?</em>'], false)
            ->assertDontSee('Draft Unit');
        // Bedrooms has a winner; both have 2 bathrooms, so nobody wins that row.
        $this->assertSame(1, substr_count($response->getContent(), '>Most</span>'));
    }

    public function test_at_most_five_units_are_compared(): void
    {
        $slugs = Unit::factory()->count(6)->create()->pluck('slug');

        $this->get(route('compare', ['units' => $slugs->implode(',')]))
            ->assertOk()
            ->assertSee('>5</div>', false)
            ->assertDontSee(Unit::firstWhere('slug', $slugs->last())->name_en)
            ->assertDontSee('Add More');
    }

    public function test_nothing_to_compare(): void
    {
        $this->get(route('compare'))->assertOk()->assertSee(['Nothing to Compare', 'Browse Properties'])
            // The compare bar takes its size from the server.
            ->assertSee('<meta name="compare-max" content="5" />', false);
        $this->get(route('compare', ['units' => ['a', 'b']]))->assertOk()->assertSee('Nothing to Compare');
        // Database ids aren't accepted: the URL names units by slug only.
        $this->get(route('compare', ['units' => (string) Unit::factory()->create()->id]))->assertOk()->assertSee('Nothing to Compare');
    }

    public function test_the_page_in_arabic(): void
    {
        $unit = Unit::factory()->create(['name_ar' => 'فيلا الهدوء', 'price' => 1000000]);

        $this->withCookie('locale', 'ar')
            ->get(route('compare', ['units' => $unit->slug]))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee(['فيلا الهدوء', 'مقارنة', 'وحدات مختارة', 'عرض التفاصيل']);
    }

    public function test_the_page_runs_the_same_queries_for_one_and_five_units(): void
    {
        $count = function (string $slugs) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get(route('compare', ['units' => $slugs]))->assertOk();

            return count(DB::getQueryLog());
        };

        $one = Unit::factory()->create()->slug;
        $count($one);
        $few = $count($one);
        $many = $count(Unit::factory()->count(5)->create()->pluck('slug')->implode(','));

        $this->assertSame($few, $many);
    }
}
