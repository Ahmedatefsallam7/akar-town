<?php

namespace Modules\Users\Tests\Feature;

use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Events\NoPendingMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Lookups\Models\Lookup;
use Modules\Users\Filament\Resources\RoleResource\Pages\EditRole;
use Modules\Users\Models\User;
use Modules\Users\Support\PermissionModules;
use Tests\TestCase;

/**
 * A module added in a new release is given to the Admin role once, after migrate; the admin can switch it off.
 */
class AdminNewModulesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Lookup
    {
        return $this->role(User::ADMIN_ROLE)->fresh();
    }

    /**
     * The Admin role as it was before the "developers" module existed.
     */
    private function adminWithoutDevelopers(): void
    {
        $this->seedRoles();
        $attributes = $this->admin()->getAttribute('attributes');
        unset($attributes['permissions']['developers']);
        $attributes['offered_modules'] = array_values(array_diff($attributes['offered_modules'], ['developers']));
        $this->admin()->update(['attributes' => $attributes]);
    }

    public function test_a_new_admin_role_gets_every_module_and_remembers_them_as_offered(): void
    {
        $this->seedRoles();

        $this->assertEqualsCanonicalizing(array_keys(PermissionModules::all()), $this->admin()->getAttribute('attributes')['offered_modules']);
    }

    public function test_a_new_module_is_given_to_the_admin_role_after_migrate(): void
    {
        $this->adminWithoutDevelopers();

        event(new MigrationsEnded('up'));

        $this->assertSame(PermissionModules::actionsFor('developers'), $this->admin()->getAttribute('attributes')['permissions']['developers']);
        $this->assertContains('developers', $this->admin()->getAttribute('attributes')['offered_modules']);
    }

    public function test_it_also_runs_when_the_release_has_no_new_migrations(): void
    {
        $this->adminWithoutDevelopers();

        event(new NoPendingMigrations('up'));

        $this->assertArrayHasKey('developers', $this->admin()->getAttribute('attributes')['permissions']);
    }

    public function test_a_module_the_admin_switched_off_is_not_given_back(): void
    {
        $this->seedRoles();
        $attributes = $this->admin()->getAttribute('attributes');
        $attributes['permissions']['developers'] = [];
        $this->admin()->update(['attributes' => $attributes]);

        event(new MigrationsEnded('up'));

        $this->assertSame([], $this->admin()->getAttribute('attributes')['permissions']['developers']);
    }

    public function test_switching_a_module_off_on_the_roles_page_keeps_it_off_after_the_next_release(): void
    {
        $this->actingAs($this->createAdmin());
        $matrix = PermissionModules::everything();
        $matrix['developers'] = [];

        Livewire::test(EditRole::class, ['record' => $this->admin()->getRouteKey()])
            ->fillForm(['permission_matrix' => $matrix])
            ->call('save')
            ->assertHasNoFormErrors();

        event(new MigrationsEnded('up'));

        $this->assertSame([], $this->admin()->getAttribute('attributes')['permissions']['developers'] ?? []);
        $this->assertContains('developers', $this->admin()->getAttribute('attributes')['offered_modules']);
    }

    public function test_an_admin_role_from_before_the_offered_list_gets_only_the_modules_it_lacks(): void
    {
        $this->adminWithoutDevelopers();
        $attributes = $this->admin()->getAttribute('attributes');
        unset($attributes['offered_modules']);
        $attributes['color'] = 'danger';
        $this->admin()->update(['attributes' => $attributes]);

        PermissionModules::grantNewModulesToAdmin();

        $after = $this->admin()->getAttribute('attributes');
        $this->assertArrayHasKey('developers', $after['permissions']);
        $this->assertEqualsCanonicalizing(array_keys(PermissionModules::all()), $after['offered_modules']);
        $this->assertSame('danger', $after['color']);
    }

    public function test_rolling_back_does_not_grant_anything(): void
    {
        $this->adminWithoutDevelopers();

        event(new MigrationsEnded('down'));

        $this->assertArrayNotHasKey('developers', $this->admin()->getAttribute('attributes')['permissions']);
    }

    public function test_nothing_happens_without_an_admin_role(): void
    {
        PermissionModules::grantNewModulesToAdmin();

        $this->assertSame(0, Lookup::count());
    }
}
