<?php

namespace Modules\Lookups\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Lookups\Filament\Resources\LookupResource;
use Modules\Lookups\Filament\Resources\LookupResource\Pages\ListLookupTypes;
use Modules\Lookups\Filament\Resources\LookupResource\Pages\ManageLookupValues;
use Modules\Lookups\Models\Lookup;
use Modules\Users\Filament\Resources\RoleResource;
use Tests\TestCase;

class LookupResourceTest extends TestCase
{
    use RefreshDatabase;

    private function type(string $slug, string $name, array $values = []): Lookup
    {
        $type = Lookup::create(['slug' => $slug, 'name_en' => $name, 'name_ar' => "ع {$name}", 'is_deletable' => false]);

        foreach ($values as $index => $value) {
            $type->children()->create(['slug' => str($value)->snake(), 'name_en' => $value, 'name_ar' => "ع {$value}", 'order' => $index + 1]);
        }

        return $type;
    }

    /**
     * Locations tree: locations › Egypt › Cairo › New Cairo › Fifth Settlement › Golden Square.
     *
     * @return array<string, Lookup>
     */
    private function locations(): array
    {
        $nodes = ['root' => $this->type('locations', 'Locations')];
        $parent = $nodes['root'];

        foreach (['country' => 'Egypt', 'city' => 'Cairo', 'region' => 'New Cairo', 'area' => 'Fifth Settlement', 'sub_area' => 'Golden Square'] as $level => $name) {
            $parent = $nodes[$level] = $parent->children()->create(['slug' => str($name)->snake(), 'name_en' => $name, 'name_ar' => "ع {$name}"]);
        }

        return $nodes;
    }

    public function test_types_page_shows_a_card_per_type_with_its_number_of_values(): void
    {
        $this->actingAs($this->createAdmin());
        $amenities = $this->type('amenities', 'Amenities', ['Pool', 'Gym', 'Garden']);
        $this->type('unittype', 'Unit Type');

        $this->get(LookupResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Amenities')
            ->assertSee(trans_choice('admin.lookups.values_count', 3, ['count' => 3]))
            ->assertSee(trans_choice('admin.lookups.values_count', 0, ['count' => 0]))
            ->assertSee(LookupResource::getUrl('values', ['record' => $amenities]), false);
    }

    public function test_user_roles_card_opens_the_roles_page_only_for_users_who_can_manage_roles(): void
    {
        $this->actingAs($this->createAdmin());
        $this->get(LookupResource::getUrl('index'))
            ->assertSee(__('admin.lookups.roles_note'))
            ->assertSee(RoleResource::getUrl('index'), false);

        $this->actingAs($this->createUserWithPermissions(['lookups' => true]));
        $this->get(LookupResource::getUrl('index'))->assertDontSee(__('admin.lookups.roles_note'));
    }

    public function test_roles_can_never_be_managed_as_lookup_values(): void
    {
        $this->actingAs($this->createAdmin());

        $this->get(LookupResource::getUrl('values', ['record' => $this->role(Lookup::ROLES_SLUG)]))->assertNotFound();
    }

    public function test_users_without_the_lookups_permission_are_forbidden(): void
    {
        $type = $this->type('amenities', 'Amenities');
        $this->actingAs($this->createUserWithPermissions(['users' => true]));

        $this->get(LookupResource::getUrl('index'))->assertForbidden();
        $this->get(LookupResource::getUrl('values', ['record' => $type]))->assertForbidden();
    }

    public function test_super_search_finds_values_in_every_type_by_english_or_arabic_name(): void
    {
        $this->actingAs($this->createAdmin());
        $amenities = $this->type('amenities', 'Amenities', ['Sea Pool']);
        $views = $this->type('views', 'Views', ['Sea View', 'Garden View']);

        $results = Livewire::test(ListLookupTypes::class)->set('superSearch', 'sea')->instance()->getSuperSearchResults();
        $this->assertEqualsCanonicalizing(['Sea Pool', 'Sea View'], $results->pluck('name')->all());
        $this->assertContains(LookupResource::getUrl('values', ['record' => $views]), $results->pluck('url')->all());
        $this->assertContains(LookupResource::getUrl('values', ['record' => $amenities]), $results->pluck('url')->all());

        app()->setLocale('ar');
        $this->assertSame(['ع Garden View'], Livewire::test(ListLookupTypes::class)->set('superSearch', 'Garden')->instance()->getSuperSearchResults()->pluck('name')->all());
    }

    public function test_super_search_needs_two_letters_never_returns_types_or_roles_and_treats_wildcards_literally(): void
    {
        $this->actingAs($this->createAdmin());
        $this->type('amenities', 'Amenities', ['Pool']);

        $page = Livewire::test(ListLookupTypes::class);
        $this->assertEmpty($page->set('superSearch', 'p')->instance()->getSuperSearchResults());
        $this->assertEmpty($page->set('superSearch', 'Amenities')->instance()->getSuperSearchResults());
        $this->assertEmpty($page->set('superSearch', 'Admin')->instance()->getSuperSearchResults());
        $this->assertEmpty($page->set('superSearch', '%%')->instance()->getSuperSearchResults());
    }

    public function test_admin_can_add_a_value_to_a_type_with_an_automatic_slug(): void
    {
        $this->actingAs($this->createAdmin());
        $type = $this->type('views', 'Views');

        Livewire::test(ManageLookupValues::class, ['record' => $type->getRouteKey()])
            ->callTableAction('create', data: ['name_en' => 'Sea View', 'name_ar' => 'إطلالة بحرية', 'slug' => 'sea_view'])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('lookups', ['parent_id' => $type->id, 'slug' => 'sea_view', 'name_ar' => 'إطلالة بحرية']);
    }

    public function test_names_and_slug_are_required(): void
    {
        $this->actingAs($this->createAdmin());
        $type = $this->type('views', 'Views');

        Livewire::test(ManageLookupValues::class, ['record' => $type->getRouteKey()])
            ->callTableAction('create', data: ['name_en' => '', 'name_ar' => '', 'slug' => ''])
            ->assertHasTableActionErrors(['name_en' => 'required', 'name_ar' => 'required', 'slug' => 'required']);
    }

    public function test_a_slug_is_unique_in_its_type_but_can_repeat_in_another_type(): void
    {
        $this->actingAs($this->createAdmin());
        $unitTypes = $this->type('unittype', 'Unit Type', ['Studio']);
        $facilities = $this->type('facility', 'Facility');

        Livewire::test(ManageLookupValues::class, ['record' => $unitTypes->getRouteKey()])
            ->callTableAction('create', data: ['name_en' => 'Studio', 'name_ar' => 'ستوديو', 'slug' => 'studio'])
            ->assertHasTableActionErrors(['slug' => 'unique']);

        Livewire::test(ManageLookupValues::class, ['record' => $facilities->getRouteKey()])
            ->callTableAction('create', data: ['name_en' => 'Studio', 'name_ar' => 'ستوديو', 'slug' => 'studio'])
            ->assertHasNoTableActionErrors();
    }

    public function test_admin_can_edit_a_value(): void
    {
        $this->actingAs($this->createAdmin());
        $type = $this->type('views', 'Views', ['Sea View']);
        $value = $type->children()->first();

        Livewire::test(ManageLookupValues::class, ['record' => $type->getRouteKey()])
            ->callTableAction('edit', $value, data: ['name_en' => 'Ocean View'])
            ->assertHasNoTableActionErrors();

        $this->assertSame('Ocean View', $value->fresh()->name_en);
    }

    public function test_values_are_reordered_by_drag_and_drop(): void
    {
        $this->actingAs($this->createAdmin());
        $type = $this->type('views', 'Views', ['First', 'Second', 'Third']);
        [$first, $second, $third] = $type->children()->orderBy('order')->get()->all();

        Livewire::test(ManageLookupValues::class, ['record' => $type->getRouteKey()])
            ->call('reorderTable', [(string) $third->id, (string) $first->id, (string) $second->id]);

        $this->assertSame([$third->id, $first->id, $second->id], $type->children()->orderBy('order')->pluck('id')->all());
    }

    public function test_types_cannot_be_deleted_and_neither_can_a_value_holding_a_protected_item(): void
    {
        $this->actingAs($this->createAdmin());
        $type = $this->type('views', 'Views', ['Sea View']);
        $holder = $type->children()->first();
        $holder->children()->create(['slug' => 'locked', 'name_en' => 'Locked', 'is_deletable' => false]);

        $this->assertFalse(LookupResource::canDelete($type->fresh()));
        $this->assertFalse(LookupResource::canDelete($holder->fresh()));
    }

    public function test_deleting_a_value_with_items_inside_warns_how_many_go_with_it(): void
    {
        $this->actingAs($this->createAdmin());
        $tree = $this->locations();

        Livewire::test(ManageLookupValues::class, ['record' => $tree['country']->getRouteKey()])
            ->mountTableAction('delete', $tree['city'])
            ->assertMountedActionModalSee(trans_choice('admin.lookups.delete_with_items', 3, ['count' => 3]));
    }

    public function test_deleting_a_value_without_items_shows_the_normal_confirmation(): void
    {
        $this->actingAs($this->createAdmin());
        $type = $this->type('views', 'Views', ['Sea View']);

        Livewire::test(ManageLookupValues::class, ['record' => $type->getRouteKey()])
            ->mountTableAction('delete', $type->children()->first())
            ->assertMountedActionModalSee(__('filament-actions::modal.confirmation'));
    }

    public function test_unit_type_holds_the_unit_kinds_and_there_is_no_separate_property_type(): void
    {
        $types = collect(require base_path('Modules/Lookups/database/seeders/data/lookups.php'))->keyBy('slug');

        $this->assertArrayNotHasKey('propertytype', $types);
        $this->assertContains('apartment', array_column($types['unittype']['items'], 'slug'));
    }

    public function test_every_seeded_lookup_has_an_arabic_name(): void
    {
        $walk = function (array $nodes) use (&$walk): void {
            foreach ($nodes as $node) {
                $this->assertMatchesRegularExpression('/\p{Arabic}/u', $node['name_ar'], "{$node['slug']} has no Arabic name.");
                $walk($node['items'] ?? []);
            }
        };

        $walk(require base_path('Modules/Lookups/database/seeders/data/lookups.php'));
    }

    public function test_locations_open_level_by_level_down_to_sub_areas(): void
    {
        $this->actingAs($this->createAdmin());
        $tree = $this->locations();

        // Cities listed under Egypt open their regions; sub-areas (the last level) open nothing.
        $this->get(LookupResource::getUrl('values', ['record' => $tree['country']]))
            ->assertOk()
            ->assertSee(LookupResource::getUrl('values', ['record' => $tree['city']]), false);

        $this->assertTrue(Livewire::test(ManageLookupValues::class, ['record' => $tree['region']->getRouteKey()])->instance()->valuesHaveALevelBelow());
        $this->assertFalse(Livewire::test(ManageLookupValues::class, ['record' => $tree['area']->getRouteKey()])->instance()->valuesHaveALevelBelow());

        // Flat types never open a level.
        $views = $this->type('views', 'Views', ['Sea View']);
        $this->assertFalse(Livewire::test(ManageLookupValues::class, ['record' => $views->getRouteKey()])->instance()->valuesHaveALevelBelow());
    }

    public function test_tree_pages_show_the_breadcrumb_trail(): void
    {
        $this->actingAs($this->createAdmin());
        $tree = $this->locations();

        $crumbs = Livewire::test(ManageLookupValues::class, ['record' => $tree['city']->getRouteKey()])->instance()->getBreadcrumbs();

        $this->assertSame([__('admin.lookups.title'), 'Locations', 'Egypt', 'Cairo'], array_values($crumbs));
    }
}
