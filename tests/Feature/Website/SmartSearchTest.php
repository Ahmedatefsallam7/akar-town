<?php

namespace Tests\Feature\Website;

use App\Livewire\Website\GlobalSearch;
use App\Livewire\Website\Search;
use App\Support\SmartSearch;
use App\Support\Website\GlobalSearch as Results;
use App\Support\Website\PropertySearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Developers\Filament\Resources\DeveloperResource\Pages\ListDevelopers;
use Modules\Developers\Models\Developer;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\Locations\Models\Location;
use Modules\Lookups\Models\Lookup;
use Modules\Lookups\Support\SearchText;
use Modules\Projects\Models\Project;
use Modules\Units\Filament\Resources\UnitResource\Pages\ListUnits;
use Modules\Units\Models\Unit;
use Tests\TestCase;

/**
 * Smart search (AKAR-48): results while typing, the best matches first, any part of a name, Arabic spellings and
 * small typos forgiven, a full results page, published only on the website and unpublished too in the admin.
 */
class SmartSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();
    }

    public function test_typos_are_measured_letter_by_letter_and_forgiven_a_little(): void
    {
        $this->assertSame(1, SearchText::distance('madinaty', 'madinsty'));
        $this->assertSame(1, SearchText::distance('مدينتي', 'مدينتب'));
        // Counted in letters, not bytes (an Arabic letter is two bytes), mixed texts too.
        $this->assertSame(2, SearchText::distance('مدينتي', 'مدين'));
        $this->assertSame(3, SearchText::distance('abc', 'مدي'));
        $this->assertSame(1, SearchText::distance('mivida مدينتي', 'mivida مدينت'));
        $this->assertSame(0, SearchText::distance('', ''));
        $this->assertSame(4, SearchText::distance('', 'مدين'));
        $this->assertTrue(SearchText::nearly('Madinaty', 'madinsty'));        // one letter off
        $this->assertTrue(SearchText::nearly('Palm Hills October', 'palm hils')); // a letter missed, two words
        $this->assertTrue(SearchText::nearly('Emaar Misr', 'emar'));           // the start of a word
        $this->assertFalse(SearchText::nearly('Madinaty', 'mivida'));
        $this->assertFalse(SearchText::nearly('Madinaty', 'mad'));             // under four letters: never
    }

    public function test_records_keep_their_names_as_searches_compare_them(): void
    {
        $unit = Unit::factory()->create(['name_en' => 'Sky VILLA', 'name_ar' => 'فيلا السماء الكبيرة']);
        $project = Project::factory()->create(['title_en' => 'Al Noor', 'title_ar' => 'مدينة النور']);

        $this->assertSame("sky villa\nفيلا السماء الكبيره", $unit->search_name);
        $this->assertSame("al noor\nمدينه النور", $project->search_name);

        $unit->update(['name_en' => 'Garden House']);
        $this->assertStringStartsWith('garden house', $unit->fresh()->search_name);
    }

    public function test_any_part_of_a_name_matches_and_names_starting_with_the_text_come_first(): void
    {
        Unit::factory()->create(['name_en' => 'Royal Garden Villa', 'is_featured' => true]);
        Unit::factory()->create(['name_en' => 'Gardenia Flat']);
        Unit::factory()->create(['name_en' => 'Sky Penthouse']);
        Unit::factory()->create(['name_en' => 'Big Garden Studio']);

        $ranked = SmartSearch::rank(SmartSearch::match(Unit::query(), 'GARDEN'), 'GARDEN')->orderBy('id')->pluck('name_en')->all();

        // Starts with the text first, then a word starting with it, then the rest.
        $this->assertSame(['Gardenia Flat', 'Royal Garden Villa', 'Big Garden Studio'], $ranked);
        $this->assertSame(['Royal Garden Villa', 'Big Garden Studio'], SmartSearch::match(Unit::query(), 'den v')->orWhere(fn ($query) => SmartSearch::match($query, 'den s'))->orderBy('id')->pluck('name_en')->all());
    }

    public function test_arabic_spellings_and_a_typed_percent_sign(): void
    {
        Unit::factory()->create(['name_en' => 'Lake View', 'name_ar' => 'إطلالة البحيرة']);
        Unit::factory()->create(['name_en' => '100% Sea', 'name_ar' => 'بحر']);

        $this->assertSame(['Lake View'], PropertySearch::query(['q' => 'اطلاله'])->pluck('name_en')->all());
        $this->assertSame(['100% Sea'], PropertySearch::query(['q' => '0%'])->pluck('name_en')->all());
        $this->assertSame([], PropertySearch::query(['q' => '1_0'])->pluck('name_en')->all());
    }

    public function test_the_window_suggests_published_items_by_type_with_the_type_on_each(): void
    {
        Unit::factory()->create(['name_en' => 'Royal Garden Villa']);
        Unit::factory()->create(['name_en' => 'Garden Draft', 'is_published' => false]);
        Developer::factory()->create(['name_en' => 'Garden Homes', 'is_published' => true]);

        Livewire::test(GlobalSearch::class)->set('q', 'garden')
            ->assertSeeText(['Royal Garden Villa', 'Garden Homes', 'Property', 'Developer']) // the text, "garden" is marked
            ->assertDontSeeText('Garden Draft')
            ->assertSee(route('search', ['q' => 'garden']), false);
    }

    public function test_a_small_typo_shows_close_matches(): void
    {
        $root = Lookup::create(['slug' => 'locations', 'name_en' => 'Locations']);
        $area = $root->children()->create(['slug' => 'egypt', 'name_en' => 'Egypt'])
            ->children()->create(['slug' => 'cairo', 'name_en' => 'Cairo'])
            ->children()->create(['slug' => 'new-cairo', 'name_en' => 'New Cairo'])
            ->children()->create(['slug' => 'fifth', 'name_en' => 'Fifth Settlement']);
        $madinaty = $area->children()->create(['slug' => 'madinaty', 'name_en' => 'Madinaty', 'name_ar' => 'مدينتي']);
        Lookup::fixTree();
        Unit::factory()->create(['name_en' => 'Sunny Flat', 'sub_area_id' => $madinaty->id]);
        $developer = Developer::factory()->create(['name_en' => 'Emaar Misr', 'is_published' => true]);
        Project::factory()->create(['title_en' => 'Uptown Cairo', 'developer_id' => $developer->id, 'is_published' => true]);

        $typo = Results::search('madinsty');
        $this->assertTrue($typo['near']);
        $this->assertSame(['Madinaty'], array_column($typo['groups']['compounds'], 'title'));
        $this->assertSame(['Sunny Flat'], array_column($typo['groups']['properties'], 'title'));

        $developerTypo = Results::search('emar misr');
        $this->assertSame(['Emaar Misr'], array_column($developerTypo['groups']['developers'], 'title'));
        $this->assertSame(['Uptown Cairo'], array_column($developerTypo['groups']['projects'], 'title')); // its projects

        $this->assertFalse(Results::search('madinaty')['near']); // a real match isn't "close"
        Livewire::test(GlobalSearch::class)->set('q', 'madinsty')->assertSee('showing close matches');
    }

    public function test_the_search_page_shows_every_result_with_links_to_the_listings(): void
    {
        Unit::factory()->count(7)->sequence(fn ($sequence) => ['name_en' => 'Garden Unit '.$sequence->index])->create();
        Location::factory()->create(['name_en' => 'Garden City', 'is_active' => true]);

        $this->get(route('search', ['q' => 'garden']))
            ->assertOk()
            ->assertSeeLivewire(Search::class)
            ->assertSeeText(['Garden Unit 0', 'Garden Unit 6', 'Garden City', 'See all'])  // more than the window's five; "garden" is marked
            ->assertSee(route('properties', ['q' => 'garden']), false)
            ->assertSee(route('locations', ['q' => 'garden']), false);

        $this->get(route('search', ['q' => 'zzzz']))->assertOk()->assertSee('No results for “zzzz”');
        $this->get(route('search'))->assertOk()->assertSee('What are you looking for?');
    }

    public function test_the_admin_finds_any_part_of_a_name_in_any_spelling_unpublished_too(): void
    {
        $this->actingAs($this->createAdmin());
        $draft = Unit::factory()->create(['name_en' => 'Hidden Lake House', 'name_ar' => 'بيت البحيرة', 'is_published' => false]);
        $other = Unit::factory()->create(['name_en' => 'Sky Flat']);
        $developer = Developer::factory()->create(['name_en' => 'Al Ahly Sabbour', 'name_ar' => 'الأهلي صبور', 'is_published' => false]);

        Livewire::test(ListUnits::class)->searchTable('lake')->assertCanSeeTableRecords([$draft])->assertCanNotSeeTableRecords([$other]);
        Livewire::test(ListUnits::class)->searchTable('البحيره')->assertCanSeeTableRecords([$draft]);
        Livewire::test(ListDevelopers::class)->searchTable('الاهلي')->assertCanSeeTableRecords([$developer]);
    }
}
