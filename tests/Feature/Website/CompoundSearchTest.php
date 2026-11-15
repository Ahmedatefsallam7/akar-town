<?php

namespace Tests\Feature\Website;

use App\Livewire\Website\Developers;
use App\Livewire\Website\GlobalSearch;
use App\Livewire\Website\Locations;
use App\Livewire\Website\Projects;
use App\Livewire\Website\Properties;
use App\Support\Website\GlobalSearch as Search;
use App\Support\Website\PropertySearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Developers\Filament\Resources\DeveloperResource\Pages\ListDevelopers;
use Modules\Developers\Models\Developer;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\Locations\Filament\Resources\LocationResource\Pages\ListLocations;
use Modules\Locations\Models\Location;
use Modules\Lookups\Models\Lookup;
use Modules\Lookups\Support\SearchText;
use Modules\Projects\Filament\Resources\ProjectResource\Pages\ListProjects;
use Modules\Projects\Models\Project;
use Modules\Units\Filament\Resources\UnitResource\Pages\ListUnits;
use Modules\Units\Models\Unit;
use Tests\TestCase;

/**
 * Search by compound (AKAR-47): a compound's name, any part, any case, Arabic letters written either way, finds the
 * compound, its properties and the projects, developers and locations around it, on the website (published only)
 * and in the admin lists (unpublished too).
 */
class CompoundSearchTest extends TestCase
{
    use RefreshDatabase;

    private Lookup $egypt;

    private Lookup $fifth;

    private Lookup $madinaty;

    private Lookup $empty;

    private Unit $villa;

    private Unit $draft;

    private Project $project;

    private Developer $developer;

    protected function setUp(): void
    {
        parent::setUp();

        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();

        // The location tree is read once per request, so it is made first.
        $root = Lookup::create(['slug' => 'locations', 'name_en' => 'Locations']);
        $this->egypt = $root->children()->create(['slug' => 'egypt', 'name_en' => 'Egypt', 'name_ar' => 'مصر']);
        $cairo = $this->egypt->children()->create(['slug' => 'cairo', 'name_en' => 'Cairo', 'name_ar' => 'القاهرة']);
        $newCairo = $cairo->children()->create(['slug' => 'new-cairo', 'name_en' => 'New Cairo', 'name_ar' => 'القاهرة الجديدة']);
        $this->fifth = $newCairo->children()->create(['slug' => 'fifth-settlement', 'name_en' => 'Fifth Settlement', 'name_ar' => 'التجمع الخامس']);
        $this->madinaty = $this->fifth->children()->create(['slug' => 'madinaty', 'name_en' => 'Madinaty', 'name_ar' => 'مدينتي']);
        $this->empty = $this->fifth->children()->create(['slug' => 'quiet-gardens', 'name_en' => 'Quiet Gardens', 'name_ar' => 'حدائق هادئة']);
        Lookup::fixTree();

        $place = ['country_id' => $this->egypt->id, 'city_id' => $cairo->id, 'region_id' => $newCairo->id, 'area_id' => $this->fifth->id];
        $this->developer = Developer::factory()->create(['name_en' => 'Talaat Group', 'is_published' => true]);
        $this->project = Project::factory()->create(['title_en' => 'Garden Towers', 'developer_id' => $this->developer->id, 'is_published' => true] + $place);
        Project::factory()->create(['title_en' => 'Far Away Project', 'is_published' => true]);
        Developer::factory()->create(['name_en' => 'Other Developer', 'is_published' => true]);

        $this->villa = Unit::factory()->create(['name_en' => 'Sunny Villa', 'project_id' => $this->project->id, 'sub_area_id' => $this->madinaty->id] + $place);
        $this->draft = Unit::factory()->create(['name_en' => 'Draft Flat', 'is_published' => false, 'sub_area_id' => $this->empty->id] + $place);
        Unit::factory()->create(['name_en' => 'Elsewhere Flat']);

        Location::factory()->create(['name_en' => 'Fifth Settlement', 'slug' => 'fifth-settlement', 'is_active' => true, 'area_id' => $this->fifth->id] + $place);
        Location::factory()->create(['name_en' => 'Maadi', 'slug' => 'maadi', 'is_active' => true]);
    }

    public function test_text_is_compared_without_case_diacritics_or_arabic_letter_variants(): void
    {
        $this->assertSame('madinaty', SearchText::normalize('  MADINATY '));
        $this->assertSame(SearchText::normalize('مدينتي'), SearchText::normalize('مدينتى'));
        $this->assertSame(SearchText::normalize('ساحة'), SearchText::normalize('ساحه'));
        $this->assertSame(SearchText::normalize('اكتوبر'), SearchText::normalize('أُكتوبر'));
        $this->assertSame(SearchText::normalize('اسكندرية'), SearchText::normalize('إسكندرية'));
        $this->assertTrue(SearchText::contains('Madinaty', 'dina'));
        $this->assertFalse(SearchText::contains('Madinaty', ''));
    }

    public function test_the_global_search_finds_the_compound_and_everything_around_it(): void
    {
        foreach (['madinaty', 'DINA', 'مدينتى', 'مدينتي'] as $text) {
            $results = Search::for($text);

            $this->assertSame(['Madinaty'], array_column($results['compounds'], 'title'), $text);
            $this->assertSame('Fifth Settlement', $results['compounds'][0]['sub']);
            $this->assertSame('1 property', $results['compounds'][0]['badge']);
            $this->assertSame(route('properties', ['compound' => ['madinaty']]), $results['compounds'][0]['url']);
            $this->assertSame(['Sunny Villa'], array_column($results['properties'], 'title'));
            $this->assertSame('Madinaty, Egypt', $results['properties'][0]['sub']); // its compound, as the task asks
            $this->assertSame(['Garden Towers'], array_column($results['projects'], 'title'));
            $this->assertSame(['Talaat Group'], array_column($results['developers'], 'title'));
            $this->assertSame(['Fifth Settlement'], array_column($results['locations'], 'title'));
            $this->assertSame('New Cairo, Egypt', $results['locations'][0]['sub']); // not its own name again
        }
    }

    public function test_a_compound_without_published_properties_is_not_offered(): void
    {
        $results = Search::for('Quiet');

        $this->assertSame([], $results['compounds']);
        $this->assertSame([], $results['properties']);
    }

    public function test_the_search_window_shows_groups_a_hint_and_no_results(): void
    {
        Livewire::test(GlobalSearch::class)
            ->assertSee(['Type at least two letters', 'Browse'])
            ->set('q', 'madinaty')
            ->assertSee(['Compounds', 'Madinaty', 'Sunny Villa', 'Garden Towers', 'Talaat Group', 'Fifth Settlement'])
            ->assertDontSee(['Elsewhere Flat', 'Far Away Project', 'Other Developer', 'Maadi'])
            ->assertSee(['1 project', 'See all properties', route('properties', ['q' => 'madinaty'])], false)
            ->set('q', 'zzzz nothing')
            ->assertSee('No results for “zzzz nothing”');
    }

    public function test_the_navbar_opens_the_search_window(): void
    {
        $this->get(route('home'))->assertOk()->assertSee("\$dispatch('open-search')", false)->assertSeeLivewire(GlobalSearch::class);
    }

    public function test_the_listing_pages_find_by_a_compound_name(): void
    {
        $this->assertSame(['Sunny Villa'], PropertySearch::query(['q' => 'madinaty'])->pluck('name_en')->all());

        Livewire::test(Properties::class)->set('q', 'Madinaty')->assertSee('Sunny Villa')->assertDontSee('Elsewhere Flat');
        Livewire::test(Projects::class)->set('q', 'مدينتى')->assertSee('Garden Towers')->assertDontSee('Far Away Project');
        Livewire::test(Developers::class)->set('q', 'madinaty')->assertSee('Talaat Group')->assertDontSee('Other Developer');
        Livewire::test(Locations::class)->set('q', 'MADINATY')->assertSee('Fifth Settlement')->assertDontSee('Maadi');
        Livewire::test(Developers::class)->set('q', 'zzzz')->assertSee('Try another name or compound.');
    }

    public function test_the_admin_lists_find_by_a_compound_name_unpublished_too(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(ListUnits::class)->searchTable('madinaty')->assertCanSeeTableRecords([$this->villa])->assertCanNotSeeTableRecords([$this->draft]);
        Livewire::test(ListUnits::class)->searchTable('quiet')->assertCanSeeTableRecords([$this->draft])->assertCanNotSeeTableRecords([$this->villa]);
        Livewire::test(ListProjects::class)->searchTable('مدينتى')->assertCanSeeTableRecords([$this->project])->assertCountTableRecords(1);
        Livewire::test(ListDevelopers::class)->searchTable('MADINATY')->assertCanSeeTableRecords([$this->developer])->assertCountTableRecords(1);
        Livewire::test(ListLocations::class)->searchTable('madinaty')->assertCanSeeTableRecords([Location::where('slug', 'fifth-settlement')->first()])->assertCountTableRecords(1);
    }

    public function test_the_window_offers_the_popular_compounds_only_once_opened(): void
    {
        Livewire::test(GlobalSearch::class)
            ->assertDontSee('Popular compounds')
            ->set('opened', true)
            ->assertSee(['Popular compounds', 'Madinaty'])
            ->assertDontSee('Quiet Gardens'); // no published property
    }

    public function test_the_typed_text_is_marked_and_escaped(): void
    {
        $this->villa->update(['name_en' => 'Sunny <b>Villa</b>']);

        Livewire::test(GlobalSearch::class)->set('q', 'sunny')
            ->assertSeeHtml('>Sunny</mark>')
            ->assertSeeHtml('&lt;b&gt;Villa&lt;/b&gt;');
    }
}
