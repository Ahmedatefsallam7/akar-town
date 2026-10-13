<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Locations\Filament\Resources\LocationResource;
use Modules\Projects\Filament\Resources\ProjectResource;
use Modules\UnitRequests\Filament\Resources\UnitRequestResource;
use Modules\Units\Filament\Resources\UnitResource;
use Tests\TestCase;

/**
 * "Set location on map" on every form with latitude / longitude (projects, properties, sell requests, locations).
 */
class MapPickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_form_with_coordinates_has_the_map_picker(): void
    {
        $this->actingAs($this->createAdmin());

        foreach ([ProjectResource::class, UnitResource::class, UnitRequestResource::class, LocationResource::class] as $resource) {
            $this->get($resource::getUrl('create'))
                ->assertOk()
                ->assertSee(__('admin.map.set_location'))
                ->assertSee('x-ref="map"', false)
                ->assertSee("\$entangle('data.latitude')", false)
                ->assertSee('map-picker', false);
        }
    }
}
