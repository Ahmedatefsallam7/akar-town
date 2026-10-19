<?php

namespace Tests\Feature\Website;

use App\Livewire\Website\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\Projects\Models\Project;
use Modules\Units\Models\Unit;
use Tests\TestCase;

/**
 * The wishlist page (the design's wishlist.html): the browser's saved ids, shown with the current data.
 */
class WishlistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();
    }

    public function test_the_page_loads_the_list_from_the_browser(): void
    {
        $this->get(route('wishlist'))
            ->assertOk()
            ->assertSeeLivewire(Wishlist::class)
            ->assertSee(['Your Favourites', '$wire.load([...$store.favourites.ids])'], false);
    }

    public function test_saved_published_items_show_newest_saved_first(): void
    {
        $older = Unit::factory()->create(['name_en' => 'Older Villa', 'bedrooms' => 4, 'bathrooms' => 3, 'area' => 320, 'price' => 9000000]);
        $newer = Unit::factory()->create(['name_en' => 'Newer Flat']);
        $draft = Unit::factory()->create(['name_en' => 'Draft Unit', 'is_published' => false]);
        $project = Project::factory()->create(['title_en' => 'Azure Heights']);
        $hidden = Project::factory()->create(['title_en' => 'Hidden Project', 'is_published' => false]);

        Livewire::test(Wishlist::class)
            ->assertDontSee('Older Villa')
            ->call('load', [$older->id, "project-{$project->id}", $newer->id, $draft->id, "project-{$hidden->id}", 999999, 'project-999999', 'junk', ['x']])
            ->assertSet('loaded', true)
            ->assertSeeInOrder(['Newer Flat', 'Older Villa', 'Azure Heights'])
            ->assertSee(['EGP 9,000,000', '320', '2 properties, 1 project saved', 'Remove from wishlist'])
            ->assertDontSee(['Draft Unit', 'Hidden Project'])
            // The empty state waits hidden until every card is removed.
            ->assertSeeHtml('style="display: none"  class="flex flex-col items-center px-8')
            // Removing happens in the browser: each card follows the saved ids.
            ->assertSeeHtml('$store.favourites.has('.$older->id.')')
            ->assertSeeHtml('$store.favourites.has(&#039;project-'.$project->id.'&#039;)');
    }

    public function test_nothing_saved_shows_the_empty_state(): void
    {
        Livewire::test(Wishlist::class)
            ->call('load', [])
            ->assertSee(['Your wishlist is empty', 'Explore Properties', '0 items saved']);
    }

    public function test_the_page_in_arabic(): void
    {
        app()->setLocale('ar');
        $unit = Unit::factory()->create();

        Livewire::test(Wishlist::class)
            ->call('load', [$unit->id])
            ->assertSee(['مفضلتك', 'محفوظ: عقار واحد', 'إزالة من المفضلة']);
    }

    public function test_the_list_runs_the_same_queries_for_few_and_many_items(): void
    {
        $count = function (array $ids) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            Livewire::test(Wishlist::class)->call('load', $ids);

            return array_column(DB::getQueryLog(), 'query');
        };

        $ids = [Unit::factory()->create()->id, 'project-'.Project::factory()->create()->id];
        $count($ids); // the locations tree is read once per process
        $few = $count($ids);
        $many = $count([...Unit::factory()->count(8)->create()->pluck('id'), ...Project::factory()->count(5)->create()->map(fn (Project $project) => "project-{$project->id}")]);

        $this->assertSame(count($few), count($many), json_encode([array_values(array_diff($many, $few)), array_values(array_diff($few, $many))]));
    }
}
