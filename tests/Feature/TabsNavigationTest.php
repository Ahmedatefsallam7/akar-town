<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Careers\Filament\Resources\CareerResource;
use Modules\Developers\Filament\Resources\DeveloperResource;
use Modules\Locations\Filament\Resources\LocationResource;
use Modules\Projects\Filament\Resources\ProjectResource;
use Modules\UnitRequests\Filament\Resources\UnitRequestResource;
use Modules\Units\Filament\Resources\UnitResource;
use Modules\Users\Filament\Resources\UserResource;
use Tests\TestCase;

/**
 * Every tabbed form ends each tab with Previous / Next buttons (App\Filament\Schemas\Tab).
 */
class TabsNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_tabbed_create_form_has_previous_and_next_buttons(): void
    {
        $this->actingAs($this->createAdmin());

        foreach ([ProjectResource::class, UnitResource::class, DeveloperResource::class, LocationResource::class, CareerResource::class, UnitRequestResource::class, UserResource::class] as $resource) {
            $this->get($resource::getUrl('create'))
                ->assertOk()
                ->assertSee(__('admin.actions.next'))
                ->assertSee(__('admin.actions.previous'));
        }
    }

    public function test_no_form_uses_the_plain_filament_tab(): void
    {
        $files = array_merge(glob(base_path('Modules/*/app/Filament/**/*.php')) ?: [], glob(base_path('Modules/*/app/Filament/*/*.php')) ?: [], glob(base_path('app/Filament/**/*.php')) ?: []);

        foreach ($files as $file) {
            $this->assertStringNotContainsString('use Filament\Schemas\Components\Tabs\Tab;', file_get_contents($file), basename($file).' uses Filament\'s Tab: use App\Filament\Schemas\Tab so its tabs get Previous / Next.');
        }
    }
}
