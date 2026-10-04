<?php

namespace Tests\Feature;

use App\Filament\Pages\Utilities;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class UtilitiesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_the_utilities_permission_is_forbidden(): void
    {
        $this->actingAs($this->createUserWithPermissions(['lookups' => true]))
            ->get(Utilities::getUrl())
            ->assertForbidden();
    }

    public function test_admin_can_open_the_page(): void
    {
        $this->actingAs($this->createAdmin())->get(Utilities::getUrl())->assertOk();
    }

    public function test_refresh_lists_clears_the_cache(): void
    {
        $this->actingAs($this->createAdmin());
        Cache::put('badge_count_lookups', 99);

        Livewire::test(Utilities::class)->callAction('refreshAppLists');

        $this->assertFalse(Cache::has('badge_count_lookups'));
    }

    public function test_refresh_permissions_clears_the_role_counters(): void
    {
        $this->actingAs($this->createAdmin());
        Cache::put('navigation_badges_all', ['x' => 99]);
        Cache::put('navigation_badges_non_admin', ['x' => 99]);
        Cache::put('badge_count_lookups', 5);

        Livewire::test(Utilities::class)->callAction('refreshPermissions');

        $this->assertFalse(Cache::has('navigation_badges_all'));
        $this->assertFalse(Cache::has('navigation_badges_non_admin'));
        $this->assertTrue(Cache::has('badge_count_lookups'));
    }
}
