<?php

namespace Tests\Feature;

use App\Livewire\RefreshButton;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
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

    public function test_the_utilities_page_is_gone(): void
    {
        $this->actingAs($this->createAdmin());

        $this->get('/admin/utilities')->assertNotFound();
    }
}
