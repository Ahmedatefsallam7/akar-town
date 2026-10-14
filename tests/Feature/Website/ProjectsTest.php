<?php

namespace Tests\Feature\Website;

use App\Livewire\Website\Projects;
use App\Support\Website\ProjectCards;
use App\Support\Website\ProjectSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Developers\Models\Developer;
use Modules\GeneralSettings\Settings\BusinessInfoSettings;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\Locations\Models\Location;
use Modules\Lookups\Models\Lookup;
use Modules\Projects\Models\Project;
use Modules\Units\Models\Unit;
use Tests\TestCase;

/**
 * The projects listing (the design's projects.html) and its search.
 */
class ProjectsTest extends TestCase
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
        return ProjectSearch::query($filters)->pluck('title_en')->all();
    }

    /**
     * @return array<string, Lookup>
     */
    private function lookups(string $type, array $slugs): array
    {
        $root = Lookup::create(['slug' => $type, 'name_en' => $type]);

        return collect($slugs)->mapWithKeys(fn ($slug) => [$slug => $root->children()->create(['slug' => $slug, 'name_en' => ucwords(str_replace('-', ' ', $slug))])])->all();
    }

    public function test_the_page_lists_published_projects_with_their_count_and_the_cta_banner(): void
    {
        $settings = app(BusinessInfoSettings::class);
        $settings->hotline = '01150000727';
        $settings->working_days_from = 'monday';
        $settings->working_days_to = 'saturday';
        $settings->working_hours_from = '09:00';
        $settings->working_hours_to = '18:00';
        $settings->save();

        Project::factory()->create(['title_en' => 'Lumiere Heights']);
        Project::factory()->create(['title_en' => 'Hidden Draft', 'is_published' => false]);

        $this->get(route('projects'))
            ->assertOk()
            ->assertSee(['Explore <em>Exceptional</em> Projects', 'Active Projects', 'Lumiere Heights'], false)
            ->assertDontSee('Hidden Draft')
            ->assertSee(['Can’t Find Your', 'tel:01150000727', 'Monday – Saturday, 9 AM – 6 PM', 'href="#property-matching"'], false);
    }

    public function test_the_card_shows_status_developer_price_units_delivery_and_amenities(): void
    {
        $statuses = $this->lookups('projectstatus', ['off-plan', 'under-construction', 'ready-to-move']);
        $amenities = $this->lookups('amenities', ['pool', 'gym', 'spa', 'golf']);
        $project = Project::factory()->create([
            'title_en' => 'Al Noor',
            'developer_id' => Developer::factory()->create(['name_en' => 'Palm Hills'])->id,
            'price_from' => 4200000,
            'delivery_date' => '2027-11-01',
            'is_featured' => true,
        ]);
        $project->lookupsOfType('projectstatus')->sync([$statuses['under-construction']->id]);
        $project->lookupsOfType('amenities')->sync(collect($amenities)->pluck('id'));
        Unit::factory()->count(2)->create(['project_id' => $project->id]);
        Unit::factory()->create(['project_id' => $project->id, 'is_published' => false]);

        $card = ProjectCards::from(ProjectCards::eagerLoad(Project::query())->get())[0];

        $this->assertSame(['Under Construction', 'construction', 'Palm Hills', 'EGP 4.2M', 2, 'Q4 2027', true, 2], [
            $card['status'], $card['statusStyle'], $card['developer'], $card['price'], $card['units'], $card['delivery'], $card['featured'], $card['more'],
        ]);
        $this->assertCount(2, $card['tags']);
        $this->assertSame(route('projects.show', $project->slug), $card['url']);
        $this->assertSame(route('properties', ['project' => $project->id]), $card['unitsUrl']);
    }

    public function test_search_location_type_status_developer_and_sort(): void
    {
        $types = $this->lookups('unittype', ['villa', 'apartment']);
        $statuses = $this->lookups('projectstatus', ['off-plan', 'ready-to-move']);
        $locations = Lookup::create(['slug' => 'locations', 'name_en' => 'Locations']);
        $egypt = $locations->children()->create(['slug' => 'egypt', 'name_en' => 'Egypt']);
        $cairo = $egypt->children()->create(['slug' => 'cairo', 'name_en' => 'Cairo']);
        $coast = $egypt->children()->create(['slug' => 'north-coast', 'name_en' => 'North Coast']);
        Lookup::fixTree();
        $sodic = Developer::factory()->create(['name_en' => 'Sodic']);

        $villas = Project::factory()->create(['title_en' => 'Garden Villas', 'price_from' => 9000000, 'delivery_date' => '2028-01-01', 'country_id' => $egypt->id, 'city_id' => $cairo->id, 'developer_id' => $sodic->id]);
        $tower = Project::factory()->create(['title_en' => 'Sky Tower', 'price_from' => 3000000, 'delivery_date' => '2026-01-01', 'country_id' => $egypt->id, 'city_id' => $coast->id, 'is_featured' => true]);
        $villas->lookupsOfType('unittype')->sync([$types['villa']->id]);
        $villas->lookupsOfType('projectstatus')->sync([$statuses['off-plan']->id]);
        $tower->lookupsOfType('projectstatus')->sync([$statuses['ready-to-move']->id]);

        $this->assertSame(['Garden Villas'], $this->names(['q' => 'garden']));
        $this->assertSame(['Garden Villas'], $this->names(['q' => 'sod'])); // the developer
        $this->assertSame(['Sky Tower'], $this->names(['q' => 'north'])); // the place
        $this->assertSame(['Garden Villas'], $this->names(['type' => (string) $types['villa']->id]));
        $this->assertSame(['Garden Villas'], $this->names(['type' => [(string) $types['villa']->id]])); // the quick search's list
        $this->assertSame(['Sky Tower'], $this->names(['status' => (string) $statuses['ready-to-move']->id]));
        $this->assertSame(['Garden Villas'], $this->names(['developer' => (string) $sodic->id]));

        Location::factory()->create(['slug' => 'north-coast-page', 'country_id' => $egypt->id, 'city_id' => $coast->id]);
        $this->assertSame(['Sky Tower'], $this->names(['location' => 'north-coast-page']));

        $this->assertSame(['Sky Tower', 'Garden Villas'], $this->names([])); // featured first
        $this->assertSame(['Sky Tower', 'Garden Villas'], $this->names(['sort' => 'price-asc']));
        $this->assertSame(['Garden Villas', 'Sky Tower'], $this->names(['sort' => 'price-desc']));
        $this->assertSame(['Sky Tower', 'Garden Villas'], $this->names(['sort' => 'completion']));
        // Junk is ignored, not an error.
        $this->assertCount(2, $this->names(['type' => 'x', 'status' => ['1;drop'], 'developer' => 'abc', 'sort' => 'nope']));
    }

    public function test_the_quick_search_off_plan_tab_lands_on_off_plan_projects(): void
    {
        $statuses = $this->lookups('projectstatus', ['off-plan', 'ready-to-move']);
        Project::factory()->create(['title_en' => 'Plan Project'])->lookupsOfType('projectstatus')->sync([$statuses['off-plan']->id]);
        Project::factory()->create(['title_en' => 'Ready Project'])->lookupsOfType('projectstatus')->sync([$statuses['ready-to-move']->id]);

        Livewire::withQueryParams(['intent' => 'new', 'category' => 'off-plan', 'type' => ['']])
            ->test(Projects::class)
            ->assertSet('status', (string) $statuses['off-plan']->id)
            ->assertSee('Plan Project')
            ->assertDontSee('Ready Project');
    }

    public function test_filters_apply_at_once_load_more_and_reset(): void
    {
        $statuses = $this->lookups('projectstatus', ['ready-to-move']);
        Project::factory()->count(7)->create();
        Project::factory()->create(['title_en' => 'The Ready One'])->lookupsOfType('projectstatus')->sync([$statuses['ready-to-move']->id]);

        Livewire::test(Projects::class)
            ->assertViewHas('total', 8)
            ->assertViewHas('cards', fn ($cards) => count($cards) === 6)
            ->call('loadMore')
            ->assertViewHas('cards', fn ($cards) => count($cards) === 8)
            ->set('status', (string) $statuses['ready-to-move']->id)
            ->assertViewHas('total', 1)
            ->assertSee('The Ready One')
            ->call('clear')
            ->assertViewHas('total', 8)
            ->assertViewHas('cards', fn ($cards) => count($cards) === 6)
            ->assertSet('status', '');
    }

    public function test_no_results_show_the_empty_message(): void
    {
        Livewire::test(Projects::class)->set('q', 'nothing like this')->assertSee('No projects found');
    }

    public function test_the_listing_runs_the_same_queries_for_few_and_many_cards(): void
    {
        $count = function () {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get(route('projects'))->assertOk();

            return count(DB::getQueryLog());
        };

        Project::factory()->create();
        $this->get(route('projects'));
        $few = $count();

        Project::factory()->count(5)->create();

        $this->assertSame($few, $count(), json_encode(array_count_values(array_column(DB::getQueryLog(), 'query'))));
    }
}
