<?php

namespace Tests\Feature;

use App\Filament\Widgets\QuickStatsWidget;
use App\Filament\Widgets\WelcomeWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Lookups\Filament\Resources\LookupResource;
use Modules\Users\Filament\Resources\UserResource;
use Modules\Users\Models\User;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_greets_the_user_with_their_role(): void
    {
        $this->actingAs($this->createAdmin(['name' => 'Sara Ali']));

        $this->get('/admin')
            ->assertOk()
            ->assertSee(__('admin.dashboard.welcome', ['name' => 'Sara Ali']))
            ->assertDontSee('filament-info-widget');
    }

    public function test_shortcuts_and_numbers_follow_the_users_permissions(): void
    {
        $this->actingAs($this->createUserWithPermissions(['lookups' => ['view', 'create'], 'users' => ['view']]));

        $shortcuts = collect(Livewire::test(WelcomeWidget::class)->instance()->getShortcuts())->pluck('url');
        $this->assertSame([LookupResource::getUrl('create')], $shortcuts->all());

        Livewire::test(QuickStatsWidget::class)
            ->assertSee(LookupResource::getPluralModelLabel())
            ->assertSee(UserResource::getPluralModelLabel())
            ->assertSee(LookupResource::getUrl('index'), false)
            ->assertDontSee(__('admin.roles.plural'));
    }

    public function test_user_without_access_sees_a_helpful_message(): void
    {
        $this->seedRoles();
        $this->actingAs(User::factory()->create());

        $this->get('/admin')->assertOk()->assertSee(__('admin.dashboard.no_access'));
    }
}
