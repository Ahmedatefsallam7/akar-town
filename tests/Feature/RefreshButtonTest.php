<?php

namespace Tests\Feature;

use App\Livewire\RefreshButton;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\Locations\Models\Location;
use Modules\Users\Filament\Resources\UserResource;
use Tests\TestCase;

/**
 * One "Refresh" button in the top bar (it replaced the Utilities page and the list Refresh button).
 */
class RefreshButtonTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sits_in_the_top_bar_between_global_search_and_go_to_website(): void
    {
        $this->actingAs($this->createAdmin());

        $html = $this->get(UserResource::getUrl('index'))->assertOk()->getContent();

        $search = strpos($html, 'fi-global-search');
        $refresh = strpos($html, __('admin.refresh.tooltip'));
        $website = strpos($html, __('admin.navigation.go_to_website'));

        $this->assertNotFalse($refresh, 'Refresh button not found.');
        $this->assertTrue($search < $refresh && $refresh < $website, 'Refresh must be after global search and before Go to website.');
    }

    public function test_every_logged_in_user_gets_it(): void
    {
        $this->actingAs($this->createUserWithPermissions(['users' => ['view']]));

        $this->get(UserResource::getUrl('index'))->assertSee(__('admin.refresh.tooltip'));
    }

    public function test_it_rebuilds_the_cached_counts_and_reloads_the_page(): void
    {
        $this->actingAs($this->createAdmin());
        Cache::put('navigation_badges_all', ['stale' => 99]);
        Cache::put('navigation_badges_non_admin', ['stale' => 99]);
        Cache::put('login-throttle', 3);

        Livewire::test(RefreshButton::class)
            ->call('refresh')
            ->assertNotified(__('admin.refresh.done'));

        $this->assertFalse(Cache::has('navigation_badges_all'));
        $this->assertFalse(Cache::has('navigation_badges_non_admin'));
        // Only our counts: other cached things (e.g. login attempt limits) are left alone.
        $this->assertTrue(Cache::has('login-throttle'));
    }

    public function test_it_also_clears_the_websites_cached_lists_and_numbers(): void
    {
        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();
        Location::factory()->create(['is_active' => true]);
        $this->get(route('locations'))->assertOk(); // builds the location cards' counts
        foreach (['website.compounds.units', 'website.compounds.projects', 'lookups.location_tree', 'website.footer_counts', 'website.search.popular_compounds'] as $key) {
            Cache::put($key, ['stale']);
        }
        $counts = Cache::get('website.location_counts.keys');
        $this->assertNotEmpty($counts);
        Cache::put('login-throttle', 3);

        $this->actingAs($this->createAdmin());
        Livewire::test(RefreshButton::class)->call('refresh')->assertNotified(__('admin.refresh.done'));

        foreach (['website.compounds.units', 'website.compounds.projects', 'lookups.location_tree', 'website.footer_counts', 'website.search.popular_compounds', 'website.location_counts.keys', ...$counts] as $key) {
            $this->assertFalse(Cache::has($key), $key);
        }
        $this->assertTrue(Cache::has('login-throttle')); // nothing else
    }

    public function test_the_utilities_page_is_gone(): void
    {
        $this->actingAs($this->createAdmin());

        $this->get('/admin/utilities')->assertNotFound();
    }
}
