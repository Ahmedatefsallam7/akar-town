<?php

namespace Modules\Locations\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Locations\Filament\Resources\LocationResource;
use Modules\Locations\Filament\Resources\LocationResource\Pages\CreateLocation;
use Modules\Locations\Filament\Resources\LocationResource\Pages\EditLocation;
use Modules\Locations\Filament\Resources\LocationResource\Pages\ListLocations;
use Modules\Locations\Models\Location;
use Modules\Lookups\Filament\Resources\LookupResource;
use Modules\Lookups\Filament\Resources\LookupResource\Pages\ManageLookupValues;
use Modules\Lookups\Models\Lookup;
use Modules\Lookups\Support\LocationTree;
use Modules\Users\Support\PermissionModules;
use Tests\TestCase;

class LocationResourceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * locations › Egypt › Cairo › New Cairo › Fifth Settlement › Golden Square, plus Giza (another city) and UAE.
     *
     * @return array<string, Lookup>
     */
    private function chain(): array
    {
        $root = Lookup::create(['slug' => LocationTree::ROOT_SLUG, 'name_en' => 'Locations', 'name_ar' => 'المواقع']);
        $add = fn (Lookup $parent, string $name, array $extra = []) => $parent->children()->create($extra + ['slug' => str($name)->slug('_'), 'name_en' => $name, 'name_ar' => "ع {$name}"]);

        $nodes['egypt'] = $add($root, 'Egypt');
        $nodes['uae'] = $add($root, 'UAE');
        $nodes['cairo'] = $add($nodes['egypt'], 'Cairo');
        $nodes['giza'] = $add($nodes['egypt'], 'Giza');
        $nodes['dubai'] = $add($nodes['uae'], 'Dubai');
        $nodes['new_cairo'] = $add($nodes['cairo'], 'New Cairo');
        $nodes['fifth'] = $add($nodes['new_cairo'], 'Fifth Settlement');
        $nodes['golden'] = $add($nodes['fifth'], 'Golden Square');

        return $nodes;
    }

    private function validData(array $overrides = []): array
    {
        return $overrides + ['name_en' => 'New Cairo', 'name_ar' => 'القاهرة الجديدة'];
    }

    public function test_users_without_the_locations_permission_are_forbidden(): void
    {
        $this->actingAs($this->createUserWithPermissions(['lookups' => true]));

        $this->get(LocationResource::getUrl('index'))->assertForbidden();
        $this->get(LocationResource::getUrl('create'))->assertForbidden();
    }

    public function test_admin_can_open_every_page(): void
    {
        $this->actingAs($this->createAdmin());
        $location = Location::factory()->create();

        $this->get(LocationResource::getUrl('index'))->assertOk();
        $this->get(LocationResource::getUrl('create'))->assertOk();
        $this->get(LocationResource::getUrl('view', ['record' => $location]))->assertOk();
        $this->get(LocationResource::getUrl('edit', ['record' => $location]))->assertOk();
    }

    public function test_locations_is_a_property_management_permission_module(): void
    {
        $this->assertSame('property_management', PermissionModules::all()['locations']['group'] ?? null);
    }

    public function test_list_shows_name_neighbourhoods_featured_active_and_created_at(): void
    {
        $this->actingAs($this->createAdmin());
        Location::factory()->create(['name_en' => 'Zayed', 'neighbourhoods' => ['Beverly Hills', 'Al Rabwa']]);

        $this->get(LocationResource::getUrl('index'))
            ->assertSee('Zayed')
            ->assertSee('Beverly Hills')
            ->assertSee(__('admin.table.featured_image'))
            ->assertSee(__('admin.table.featured'))
            ->assertSee(__('admin.locations.active'))
            ->assertSee(__('admin.table.created_at'));
    }

    public function test_admin_can_create_a_location_with_every_field(): void
    {
        $this->actingAs($this->createAdmin());
        $tree = $this->chain();

        Livewire::test(CreateLocation::class)
            ->fillForm($this->validData([
                'country_id' => $tree['egypt']->id,
                'city_id' => $tree['cairo']->id,
                'region_id' => $tree['new_cairo']->id,
                'neighbourhoods' => ['Banafseg', 'Narges'],
                'code' => 'NC',
                'latitude' => '30.0074',
                'longitude' => '31.4913',
                'order' => 2,
                'is_featured' => true,
                'description_en' => 'East of Cairo.',
                'meta_title_en' => 'Homes in New Cairo',
                'meta_keywords_en' => ['new cairo', 'villas'],
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $location = Location::sole();
        $this->assertSame([$tree['egypt']->id, $tree['cairo']->id, $tree['new_cairo']->id, null, null], [$location->country_id, $location->city_id, $location->region_id, $location->area_id, $location->sub_area_id]);
        $this->assertSame(['Banafseg', 'Narges'], $location->neighbourhoods);
        $this->assertSame([30.0074, 31.4913], [$location->latitude, $location->longitude]);
        $this->assertTrue($location->is_featured);
        $this->assertTrue($location->is_active);
        $this->assertSame('new-cairo', $location->slug);
        $this->assertSame(['new cairo', 'villas'], $location->meta_keywords_en);
    }

    public function test_names_are_required(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateLocation::class)
            ->fillForm(['name_en' => '', 'name_ar' => ''])
            ->call('create')
            ->assertHasFormErrors(['name_en' => 'required', 'name_ar' => 'required']);
    }

    public function test_each_level_must_belong_to_the_one_above(): void
    {
        $this->actingAs($this->createAdmin());
        $tree = $this->chain();

        // Dubai is not a city of Egypt, and a city needs a country.
        Livewire::test(CreateLocation::class)
            ->fillForm($this->validData(['country_id' => $tree['egypt']->id, 'city_id' => $tree['dubai']->id]))
            ->call('create')
            ->assertHasFormErrors(['city_id']);

        Livewire::test(CreateLocation::class)
            ->fillForm($this->validData(['city_id' => $tree['cairo']->id]))
            ->call('create')
            ->assertHasFormErrors(['city_id']);

        $this->assertSame(0, Location::count());
    }

    public function test_changing_a_level_clears_the_levels_below_it(): void
    {
        $this->actingAs($this->createAdmin());
        $tree = $this->chain();

        Livewire::test(CreateLocation::class)
            ->fillForm(['country_id' => $tree['egypt']->id, 'city_id' => $tree['cairo']->id, 'region_id' => $tree['new_cairo']->id])
            ->fillForm(['country_id' => $tree['uae']->id])
            ->assertSchemaStateSet(['city_id' => null, 'region_id' => null]);
    }

    public function test_each_select_lists_only_active_values_under_the_one_above(): void
    {
        $tree = $this->chain();
        $tree['giza']->update(['is_active' => false]);

        $this->assertSame([$tree['egypt']->id, $tree['uae']->id], array_keys(LocationTree::options(null)));
        $this->assertSame([$tree['cairo']->id], array_keys(LocationTree::options($tree['egypt']->id)));
        // A saved value that was switched off later still shows.
        $this->assertSame([$tree['cairo']->id, $tree['giza']->id], array_keys(LocationTree::options($tree['egypt']->id, $tree['giza']->id)));
        $this->assertSame([], LocationTree::options($tree['golden']->id));
    }

    public function test_latitude_and_longitude_are_in_range_and_go_together(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateLocation::class)
            ->fillForm($this->validData(['latitude' => '91', 'longitude' => '-181']))
            ->call('create')
            ->assertHasFormErrors(['latitude', 'longitude']);

        Livewire::test(CreateLocation::class)
            ->fillForm($this->validData(['latitude' => '30.1']))
            ->call('create')
            ->assertHasFormErrors(['longitude' => 'required_with']);
    }

    public function test_the_slug_is_unique_even_against_trashed_locations(): void
    {
        $this->actingAs($this->createAdmin());
        Location::factory()->create(['name_en' => 'Maadi'])->delete();

        $this->assertSame('maadi-2', Location::factory()->create(['name_en' => 'Maadi'])->slug);

        Livewire::test(CreateLocation::class)
            ->fillForm($this->validData(['slug' => 'maadi']))
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);
    }

    public function test_typing_the_english_name_fills_a_free_slug(): void
    {
        $this->actingAs($this->createAdmin());
        Location::factory()->create(['name_en' => 'Maadi']);

        Livewire::test(CreateLocation::class)
            ->fillForm(['name_en' => 'Maadi'])
            ->assertSchemaStateSet(['slug' => 'maadi-2']);
    }

    public function test_a_slug_written_by_hand_is_kept_when_the_name_changes(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateLocation::class)
            ->fillForm(['name_en' => 'Maadi'])
            ->fillForm(['slug' => 'maadi-homes'])
            ->fillForm(['name_en' => 'Maadi Sarayat'])
            ->assertSchemaStateSet(['slug' => 'maadi-homes'])
            ->fillForm(['slug' => ''])
            ->fillForm(['name_en' => 'Degla'])
            ->assertSchemaStateSet(['slug' => 'degla']);
    }

    public function test_list_filters_by_each_level_of_the_chain(): void
    {
        $this->actingAs($this->createAdmin());
        $tree = $this->chain();
        $cairo = Location::factory()->create(['country_id' => $tree['egypt']->id, 'city_id' => $tree['cairo']->id]);
        $giza = Location::factory()->create(['country_id' => $tree['egypt']->id, 'city_id' => $tree['giza']->id]);
        $dubai = Location::factory()->create(['country_id' => $tree['uae']->id, 'city_id' => $tree['dubai']->id]);

        Livewire::test(ListLocations::class)
            ->filterTable('country_id', $tree['egypt']->id)
            ->assertCanSeeTableRecords([$cairo, $giza])
            ->assertCanNotSeeTableRecords([$dubai])
            ->filterTable('city_id', $tree['giza']->id)
            ->assertCanSeeTableRecords([$giza])
            ->assertCanNotSeeTableRecords([$cairo, $dubai]);
    }

    public function test_level_filters_name_the_parent_when_names_can_repeat(): void
    {
        $tree = $this->chain();

        $this->assertSame('Cairo — Egypt', LocationTree::levelOptions(2)[$tree['cairo']->id]);
        $this->assertSame('Egypt', LocationTree::levelOptions(1)[$tree['egypt']->id]);
        $this->assertSame([$tree['golden']->id], array_keys(LocationTree::levelOptions(5)));
    }

    public function test_deleting_is_soft_and_the_trashed_filter_shows_it_again(): void
    {
        $this->actingAs($this->createAdmin());
        $location = Location::factory()->create();

        Livewire::test(ListLocations::class)
            ->callTableAction('delete', $location)
            ->assertCanNotSeeTableRecords([$location])
            ->filterTable('trashed', true)
            ->assertCanSeeTableRecords([$location])
            ->callTableAction('restore', $location);

        $this->assertFalse($location->fresh()->trashed());
    }

    public function test_bulk_activate_deactivate_feature_and_unfeature(): void
    {
        $this->actingAs($this->createAdmin());
        $locations = Location::factory()->count(2)->create(['is_active' => false, 'is_featured' => false]);

        $list = Livewire::test(ListLocations::class);

        $list->callTableBulkAction('activate', $locations);
        $this->assertSame([true, true], $locations->map(fn ($location) => $location->fresh()->is_active)->all());

        $list->callTableBulkAction('feature', $locations);
        $this->assertSame([true, true], $locations->map(fn ($location) => $location->fresh()->is_featured)->all());

        $list->callTableBulkAction('unfeature', $locations);
        $this->assertSame([false, false], $locations->map(fn ($location) => $location->fresh()->is_featured)->all());

        $list->callTableBulkAction('deactivate', $locations);
        $this->assertSame([false, false], $locations->map(fn ($location) => $location->fresh()->is_active)->all());
    }

    public function test_bulk_status_actions_need_the_edit_and_bulk_permissions(): void
    {
        $this->actingAs($this->createUserWithPermissions(['locations' => ['view', 'delete', 'bulk']]));
        Location::factory()->create();

        Livewire::test(ListLocations::class)
            ->assertTableBulkActionHidden('feature')
            ->assertTableBulkActionHidden('activate');
    }

    public function test_the_featured_image_is_saved_with_a_small_webp_for_the_list(): void
    {
        Storage::fake('public');
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateLocation::class)
            ->fillForm($this->validData(['featured_image' => [UploadedFile::fake()->image('cover.jpg', 1200, 900)]]))
            ->call('create')
            ->assertHasNoFormErrors();

        $media = Location::sole()->getFirstMedia(Location::FEATURED_IMAGE);
        $this->assertNotNull($media);
        Storage::disk('public')->assertExists($media->getPathRelativeToRoot());
        Storage::disk('public')->assertExists($media->getPathRelativeToRoot('thumb'));
        $this->assertStringEndsWith('.webp', $media->getPathRelativeToRoot('thumb'));
    }

    public function test_only_images_up_to_5_mb_are_accepted(): void
    {
        Storage::fake('public');
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateLocation::class)
            ->fillForm($this->validData(['featured_image' => [UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf')]]))
            ->call('create')
            ->assertHasFormErrors(['featured_image']);

        Livewire::test(CreateLocation::class)
            ->fillForm($this->validData(['featured_image' => [UploadedFile::fake()->image('huge.jpg')->size(6000)]]))
            ->call('create')
            ->assertHasFormErrors(['featured_image']);
    }

    public function test_the_image_stays_in_the_trash_and_goes_with_a_permanent_delete(): void
    {
        Storage::fake('public');
        $location = Location::factory()->create();
        $media = $location->addMedia(UploadedFile::fake()->image('cover.jpg'))->toMediaCollection(Location::FEATURED_IMAGE);
        $path = $media->getPathRelativeToRoot();

        $location->delete();
        Storage::disk('public')->assertExists($path);

        $location->forceDelete();
        Storage::disk('public')->assertMissing($path);
    }

    public function test_a_lookup_value_a_location_uses_cannot_be_deleted_nor_anything_above_it(): void
    {
        $this->actingAs($this->createAdmin());
        $tree = $this->chain();
        Location::factory()->create(['country_id' => $tree['egypt']->id, 'city_id' => $tree['cairo']->id]);

        foreach (['cairo', 'egypt'] as $used) {
            $this->assertFalse(LookupResource::canDelete($tree[$used]->fresh()), "{$used} should be protected");
            $this->assertFalse(LookupResource::canForceDelete($tree[$used]->fresh()));
        }

        $this->assertTrue(LookupResource::canDelete($tree['giza']->fresh()));
        $this->assertTrue(LookupResource::canDelete($tree['new_cairo']->fresh()), 'a value below the used one is free');
        $this->assertSame(__('admin.lookups.cannot_delete_used'), LookupResource::getAuthorizationResponse('delete', $tree['cairo']->fresh())->message());
    }

    public function test_a_trashed_location_still_protects_its_lookup_values(): void
    {
        $tree = $this->chain();
        Location::factory()->create(['country_id' => $tree['uae']->id, 'city_id' => $tree['dubai']->id])->delete();
        $this->actingAs($this->createAdmin());

        $this->assertFalse(LookupResource::canDelete($tree['dubai']->fresh()));
    }

    public function test_the_values_page_shows_delete_disabled_with_the_reason_for_a_used_value(): void
    {
        $tree = $this->chain();
        Location::factory()->create(['country_id' => $tree['egypt']->id, 'city_id' => $tree['cairo']->id]);

        $this->actingAs($this->createAdmin());
        Livewire::test(ManageLookupValues::class, ['record' => $tree['egypt']->getRouteKey()])
            ->assertTableActionVisible('delete', $tree['cairo'])
            ->assertTableActionDisabled('delete', $tree['cairo'])
            ->assertTableActionEnabled('delete', $tree['giza'])
            ->assertSee('In use (e.g. by a location)');

        // Without the delete permission it stays hidden, as before.
        $this->actingAs($this->createUserWithPermissions(['lookups' => ['view', 'edit']]));
        Livewire::test(ManageLookupValues::class, ['record' => $tree['egypt']->getRouteKey()])
            ->assertTableActionHidden('delete', $tree['giza']);
    }

    public function test_bulk_delete_skips_used_values(): void
    {
        $this->actingAs($this->createAdmin());
        $tree = $this->chain();
        Location::factory()->create(['country_id' => $tree['egypt']->id, 'city_id' => $tree['cairo']->id]);

        Livewire::test(ManageLookupValues::class, ['record' => $tree['egypt']->getRouteKey()])
            ->callTableBulkAction('delete', [$tree['cairo'], $tree['giza']]);

        $this->assertFalse($tree['cairo']->fresh()->trashed());
        $this->assertTrue(Lookup::withTrashed()->find($tree['giza']->id)->trashed());
    }

    public function test_editing_keeps_the_saved_chain_and_updates_fields(): void
    {
        $this->actingAs($this->createAdmin());
        $tree = $this->chain();
        $location = Location::factory()->create(['country_id' => $tree['egypt']->id, 'city_id' => $tree['cairo']->id]);

        Livewire::test(EditLocation::class, ['record' => $location->getRouteKey()])
            ->assertSchemaStateSet(['country_id' => $tree['egypt']->id, 'city_id' => $tree['cairo']->id])
            ->fillForm(['name_en' => 'Cairo East', 'region_id' => $tree['new_cairo']->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $location->refresh();
        $this->assertSame('Cairo East', $location->name_en);
        $this->assertSame($tree['new_cairo']->id, $location->region_id);
    }
}
