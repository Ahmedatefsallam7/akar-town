<?php

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Lookups\Filament\Resources\LookupResource;
use Modules\Lookups\Filament\Resources\LookupResource\Pages\ListLookups;
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

    private Lookup $lookup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lookup = Lookup::create(['name_en' => 'Views', 'name_ar' => 'الإطلالة', 'slug' => 'views']);
    }

    private function actAs(array $actions): void
    {
        $this->actingAs($this->createUserWithPermissions(['lookups' => $actions]));
    }

    public function test_view_only_can_list_and_view_but_not_change_anything(): void
    {
        $this->actAs(['view']);

        $this->get(LookupResource::getUrl('index'))->assertOk();
        $this->get(LookupResource::getUrl('view', ['record' => $this->lookup]))->assertOk();
        $this->get(LookupResource::getUrl('create'))->assertForbidden();
        $this->get(LookupResource::getUrl('edit', ['record' => $this->lookup]))->assertForbidden();

        Livewire::test(ListLookups::class)
            ->assertActionHidden('create')
            ->assertTableActionVisible('view', $this->lookup)
            ->assertTableActionHidden('edit', $this->lookup)
            ->assertTableActionHidden('delete', $this->lookup)
            ->assertTableBulkActionHidden('delete')
            ->assertTableBulkActionHidden('activate');
    }

    public function test_without_view_nothing_works_even_with_other_actions(): void
    {
        $this->actAs(['create', 'edit', 'delete', 'bulk']);

        $this->get(LookupResource::getUrl('index'))->assertForbidden();
        $this->get(LookupResource::getUrl('create'))->assertForbidden();
        $this->get(LookupResource::getUrl('edit', ['record' => $this->lookup]))->assertForbidden();
    }

    public function test_create_allows_only_creating(): void
    {
        $this->actAs(['view', 'create']);

        $this->get(LookupResource::getUrl('create'))->assertOk();
        $this->get(LookupResource::getUrl('edit', ['record' => $this->lookup]))->assertForbidden();
        Livewire::test(ListLookups::class)->assertActionVisible('create');
    }

    public function test_edit_allows_only_editing(): void
    {
        $this->actAs(['view', 'edit']);

        $this->get(LookupResource::getUrl('edit', ['record' => $this->lookup]))->assertOk();
        $this->get(LookupResource::getUrl('create'))->assertForbidden();

        Livewire::test(ListLookups::class)
            ->assertTableActionVisible('edit', $this->lookup)
            ->assertTableActionHidden('delete', $this->lookup)
            ->assertTableBulkActionHidden('activate');
    }

    public function test_delete_without_bulk_shows_only_the_row_delete(): void
    {
        $this->actAs(['view', 'delete']);

        Livewire::test(ListLookups::class)
            ->assertTableActionVisible('delete', $this->lookup)
            ->assertTableBulkActionHidden('delete');
    }

    public function test_bulk_with_delete_allows_bulk_delete(): void
    {
        $this->actAs(['view', 'delete', 'bulk']);
        $other = Lookup::create(['name_en' => 'Types', 'slug' => 'types']);

        Livewire::test(ListLookups::class)
            ->assertTableBulkActionVisible('delete')
            ->assertTableBulkActionHidden('activate')
            ->callTableBulkAction('delete', [$this->lookup, $other]);

        $this->assertSoftDeleted($this->lookup);
        $this->assertSoftDeleted($other);
    }

    public function test_bulk_with_edit_allows_custom_bulk_actions(): void
    {
        $this->actAs(['view', 'edit', 'bulk']);
        $this->lookup->update(['is_active' => false]);

        Livewire::test(ListLookups::class)
            ->assertTableBulkActionVisible('activate')
            ->assertTableBulkActionHidden('delete')
            ->callTableBulkAction('activate', [$this->lookup]);

        $this->assertTrue($this->lookup->fresh()->is_active);
    }

    public function test_bulk_delete_skips_protected_lookups(): void
    {
        $this->actingAs($this->createAdmin());
        $protected = Lookup::create(['name_en' => 'Types', 'slug' => 'types', 'is_deletable' => false]);

        Livewire::test(ListLookups::class)->callTableBulkAction('delete', [$this->lookup, $protected]);

        $this->assertSoftDeleted($this->lookup);
        $this->assertNotSoftDeleted($protected);
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

        $this->get(LookupResource::getUrl('create'))->assertOk();
        $this->get(UserResource::getUrl('create'))->assertForbidden();
    }
}
