<?php

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Modules\Lookups\Filament\Resources\LookupResource;
use Modules\Lookups\Filament\Resources\LookupResource\Pages\ManageLookupValues;
use Modules\Lookups\Models\Lookup;
use Modules\Users\Filament\Resources\RoleResource;
use Modules\Users\Filament\Resources\RoleResource\Pages\ListRoles;
use Modules\Users\Filament\Resources\UserResource;
use Modules\Users\Filament\Resources\UserResource\Pages\ListUsers;
use Modules\Users\Models\User;
use Tests\TestCase;

/**
 * Each action ticked on a role is enforced in the admin pages: pages, row actions, header actions and bulk actions.
 */
class ActionPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private Lookup $type;

    private Lookup $lookup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->type = Lookup::create(['name_en' => 'Views', 'name_ar' => 'الإطلالة', 'slug' => 'views', 'is_deletable' => false]);
        $this->lookup = $this->type->children()->create(['name_en' => 'Sea View', 'name_ar' => 'بحر', 'slug' => 'sea_view']);
    }

    private function actAs(array $actions): void
    {
        $this->actingAs($this->createUserWithPermissions(['lookups' => $actions]));
    }

    private function valuesPage(): Testable
    {
        return Livewire::test(ManageLookupValues::class, ['record' => $this->type->getRouteKey()]);
    }

    public function test_view_only_can_open_the_pages_but_not_change_anything(): void
    {
        $this->actAs(['view']);

        $this->get(LookupResource::getUrl('index'))->assertOk();
        $this->get(LookupResource::getUrl('values', ['record' => $this->type]))->assertOk();

        $this->valuesPage()
            ->assertTableActionHidden('create')
            ->assertTableActionHidden('edit', $this->lookup)
            ->assertTableActionHidden('delete', $this->lookup)
            ->assertTableBulkActionHidden('delete');
        $this->assertFalse(LookupResource::canReorder());
    }

    public function test_without_view_nothing_works_even_with_other_actions(): void
    {
        $this->actAs(['create', 'edit', 'delete', 'bulk']);

        $this->get(LookupResource::getUrl('index'))->assertForbidden();
        $this->get(LookupResource::getUrl('values', ['record' => $this->type]))->assertForbidden();
    }

    public function test_create_allows_only_adding_values(): void
    {
        $this->actAs(['view', 'create']);

        $this->valuesPage()
            ->assertTableActionVisible('create')
            ->assertTableActionHidden('edit', $this->lookup)
            ->callTableAction('create', data: ['name_en' => 'Garden View', 'name_ar' => 'حديقة', 'slug' => 'garden_view'])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('lookups', ['slug' => 'garden_view', 'parent_id' => $this->type->id]);
    }

    public function test_edit_allows_editing_reordering_and_the_active_toggle(): void
    {
        $this->actAs(['view', 'edit']);

        $this->valuesPage()
            ->assertTableActionVisible('edit', $this->lookup)
            ->assertTableActionHidden('delete', $this->lookup)
            ->assertTableActionHidden('create')
            ->call('updateTableColumnState', 'is_active', (string) $this->lookup->getKey(), false);

        $this->assertTrue(LookupResource::canReorder());
        $this->assertFalse($this->lookup->fresh()->is_active);
    }

    public function test_without_edit_the_active_toggle_is_refused(): void
    {
        $this->actAs(['view', 'create', 'delete']);

        $this->valuesPage()->call('updateTableColumnState', 'is_active', (string) $this->lookup->getKey(), false);

        $this->assertTrue($this->lookup->fresh()->is_active);
    }

    public function test_delete_without_bulk_shows_only_the_row_delete(): void
    {
        $this->actAs(['view', 'delete']);

        $this->valuesPage()
            ->assertTableActionVisible('delete', $this->lookup)
            ->assertTableBulkActionHidden('delete');
    }

    public function test_bulk_with_delete_allows_bulk_delete(): void
    {
        $this->actAs(['view', 'delete', 'bulk']);
        $other = $this->type->children()->create(['name_en' => 'Pool View', 'slug' => 'pool_view']);

        $this->valuesPage()
            ->assertTableBulkActionVisible('delete')
            ->callTableBulkAction('delete', [$this->lookup, $other]);

        $this->assertSoftDeleted($this->lookup);
        $this->assertSoftDeleted($other);
    }

    public function test_bulk_delete_skips_protected_lookups(): void
    {
        $this->actingAs($this->createAdmin());
        $protected = $this->type->children()->create(['name_en' => 'Locked', 'slug' => 'locked', 'is_deletable' => false]);

        $this->valuesPage()->callTableBulkAction('delete', [$this->lookup, $protected]);

        $this->assertSoftDeleted($this->lookup);
        $this->assertNotSoftDeleted($protected);
        $this->assertFalse(LookupResource::canDelete($protected));
    }

    public function test_bulk_delete_skips_seeded_roles(): void
    {
        $this->actingAs($this->createAdmin());
        $editor = $this->role('user_roles_editor');

        Livewire::test(ListRoles::class)
            ->tap(fn () => $this->assertFalse(RoleResource::canDelete($editor)))
            ->assertTableActionHidden('delete', $editor)
            ->callTableBulkAction('delete', [$editor]);

        $this->assertNotSoftDeleted($editor);
    }

    public function test_bulk_delete_skips_your_own_account(): void
    {
        $admin = $this->createAdmin();
        $other = User::factory()->create();
        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->tap(fn () => $this->assertFalse(UserResource::canDelete($admin)))
            ->assertTableActionHidden('delete', $admin)
            ->callTableBulkAction('delete', [$admin, $other]);

        $this->assertNotSoftDeleted($admin);
        $this->assertSoftDeleted($other);
    }

    public function test_actions_on_one_module_do_not_leak_to_another(): void
    {
        $this->actingAs($this->createUserWithPermissions(['lookups' => true, 'users' => ['view']]));

        $this->assertTrue(LookupResource::canCreate());
        $this->get(UserResource::getUrl('create'))->assertForbidden();
    }
}
