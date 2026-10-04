<?php

namespace Modules\Lookups\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Lookups\Filament\Resources\LookupResource;
use Modules\Lookups\Filament\Resources\LookupResource\Pages\CreateLookup;
use Modules\Lookups\Filament\Resources\LookupResource\Pages\EditLookup;
use Modules\Lookups\Filament\Resources\LookupResource\Pages\ListLookups;
use Modules\Lookups\Models\Lookup;
use Tests\TestCase;

class LookupResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_the_lookups_permission_is_forbidden(): void
    {
        $this->actingAs($this->createUserWithPermissions(['users' => true]))
            ->get(LookupResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_user_with_the_lookups_permission_can_open_every_page(): void
    {
        $lookup = Lookup::create(['name_en' => 'Views', 'slug' => 'views']);
        $this->actingAs($this->createUserWithPermissions(['lookups' => true]));

        $this->get(LookupResource::getUrl('index'))->assertOk();
        $this->get(LookupResource::getUrl('create'))->assertOk();
        $this->get(LookupResource::getUrl('view', ['record' => $lookup]))->assertOk();
        $this->get(LookupResource::getUrl('edit', ['record' => $lookup]))->assertOk();
    }

    public function test_list_hides_roles(): void
    {
        $this->actingAs($this->createAdmin());
        $views = Lookup::create(['name_en' => 'Views', 'slug' => 'views']);

        Livewire::test(ListLookups::class)
            ->assertCanSeeTableRecords([$views])
            ->assertCanNotSeeTableRecords(Lookup::roles()->get());
    }

    public function test_admin_can_create_a_lookup_inside_a_category(): void
    {
        $this->actingAs($this->createAdmin());
        $views = Lookup::create(['name_en' => 'Views', 'slug' => 'views']);

        Livewire::test(CreateLookup::class)
            ->fillForm([
                'name_en' => 'Sea View',
                'name_ar' => 'إطلالة على البحر',
                'slug' => 'sea_view',
                'parent_id' => $views->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('lookups', ['slug' => 'sea_view', 'parent_id' => $views->id, 'name_ar' => 'إطلالة على البحر']);
    }

    public function test_required_fields_are_validated(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateLookup::class)
            ->fillForm(['name_en' => '', 'name_ar' => '', 'slug' => ''])
            ->call('create')
            ->assertHasFormErrors(['name_en' => 'required', 'name_ar' => 'required', 'slug' => 'required']);
    }

    public function test_slug_is_unique_inside_a_category_but_can_repeat_across_categories(): void
    {
        $this->actingAs($this->createAdmin());
        $views = Lookup::create(['name_en' => 'Views', 'slug' => 'views']);
        $types = Lookup::create(['name_en' => 'Types', 'slug' => 'types']);
        $views->children()->create(['name_en' => 'Studio', 'slug' => 'studio']);

        Livewire::test(CreateLookup::class)
            ->fillForm(['name_en' => 'Studio', 'name_ar' => 'ستوديو', 'slug' => 'studio', 'parent_id' => $views->id])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);

        Livewire::test(CreateLookup::class)
            ->fillForm(['name_en' => 'Studio', 'name_ar' => 'ستوديو', 'slug' => 'studio', 'parent_id' => $types->id])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_admin_can_edit_a_lookup(): void
    {
        $this->actingAs($this->createAdmin());
        $lookup = Lookup::create(['name_en' => 'Views', 'name_ar' => 'الإطلالة', 'slug' => 'views']);

        Livewire::test(EditLookup::class, ['record' => $lookup->getRouteKey()])
            ->fillForm(['name_en' => 'Nice Views'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Nice Views', $lookup->fresh()->name_en);
    }

    public function test_delete_is_hidden_for_a_protected_lookup(): void
    {
        $this->actingAs($this->createAdmin());
        $protected = Lookup::create(['name_en' => 'Views', 'slug' => 'views', 'is_deletable' => false]);
        $deletable = Lookup::create(['name_en' => 'Types', 'slug' => 'types', 'is_deletable' => true]);

        Livewire::test(ListLookups::class)
            ->assertTableActionHidden('delete', $protected)
            ->assertTableActionVisible('delete', $deletable);

        // The rule itself (the table helper can resolve the wrong row inside action groups).
        $this->assertFalse(LookupResource::canDelete($protected));
        $this->assertTrue(LookupResource::canDelete($deletable));
    }

    public function test_a_lookup_cannot_be_moved_inside_its_own_items(): void
    {
        $this->actingAs($this->createAdmin());
        $category = Lookup::create(['name_en' => 'A', 'name_ar' => 'ا', 'slug' => 'a']);
        $child = $category->children()->create(['name_en' => 'B', 'name_ar' => 'ب', 'slug' => 'b']);
        $grandchild = $child->children()->create(['name_en' => 'C', 'name_ar' => 'ج', 'slug' => 'c']);

        Livewire::test(EditLookup::class, ['record' => $category->getRouteKey()])
            ->fillForm(['parent_id' => $grandchild->id])
            ->call('save')
            ->assertHasFormErrors(['parent_id']);

        $this->assertNull($category->fresh()->parent_id);
    }

    public function test_a_category_with_protected_items_cannot_be_deleted(): void
    {
        $this->actingAs($this->createAdmin());
        $category = Lookup::create(['name_en' => 'A', 'slug' => 'a']);
        $category->children()->create(['name_en' => 'B', 'slug' => 'b', 'is_deletable' => false]);
        $free = Lookup::create(['name_en' => 'C', 'slug' => 'c']);
        $free->children()->create(['name_en' => 'D', 'slug' => 'd']);

        $this->assertFalse(LookupResource::canDelete($category->fresh()));
        $this->assertFalse(LookupResource::canForceDelete($category->fresh()));
        $this->assertTrue(LookupResource::canDelete($free->fresh()));

        Livewire::test(ListLookups::class)->callTableBulkAction('delete', [$category]);
        $this->assertNotSoftDeleted($category);
    }
}
