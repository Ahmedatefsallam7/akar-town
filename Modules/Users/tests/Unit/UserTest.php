<?php

namespace Modules\Users\Tests\Unit;

use Filament\Facades\Filament;
use Modules\Lookups\Models\Lookup;
use Modules\Users\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    private function userWithRole(?array $role): User
    {
        $user = new User;
        $user->setRelation('role', $role ? new Lookup($role) : null);

        return $user;
    }

    public function test_full_name_is_split_into_first_and_last_name(): void
    {
        $user = new User(['name' => 'Ahmed Atef Sallam']);

        $this->assertSame('Ahmed', $user->first_name);
        $this->assertSame('Atef Sallam', $user->last_name);
        $this->assertSame('Ahmed Atef Sallam', $user->name);
    }

    public function test_single_word_name_leaves_last_name_empty(): void
    {
        $user = new User(['name' => 'Ahmed']);

        $this->assertSame('Ahmed', $user->first_name);
        $this->assertSame('', $user->last_name);
    }

    public function test_filament_name_falls_back_to_username_then_email(): void
    {
        $this->assertSame('Ahmed Atef', (new User(['name' => 'Ahmed Atef', 'email' => 'a@b.c']))->getFilamentName());
        $this->assertSame('ahmed', (new User(['username' => 'ahmed', 'email' => 'a@b.c']))->getFilamentName());
        $this->assertSame('a@b.c', (new User(['email' => 'a@b.c']))->getFilamentName());
    }

    public function test_role_color_and_admin_check(): void
    {
        $this->assertSame('danger', $this->userWithRole(['slug' => User::ADMIN_ROLE])->roleColor());
        $this->assertSame('info', $this->userWithRole(['slug' => 'user_roles_editor', 'attributes' => ['color' => 'info']])->roleColor());
        // The color is stored on the role, so it survives a rename (new slug).
        $this->assertSame('success', $this->userWithRole(['slug' => 'user_roles_customers', 'attributes' => ['color' => 'success']])->roleColor());
        $this->assertSame('gray', $this->userWithRole(['slug' => 'user_roles_sales'])->roleColor());
        $this->assertSame('gray', $this->userWithRole(null)->roleColor());

        $this->assertTrue($this->userWithRole(['slug' => User::ADMIN_ROLE])->isAdmin());
        $this->assertFalse($this->userWithRole(['slug' => 'user_roles_editor'])->isAdmin());
        $this->assertFalse($this->userWithRole(null)->isAdmin());
    }

    public function test_suspended_user_cannot_access_the_panel(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertTrue((new User(['is_suspended' => false]))->canAccessPanel($panel));
        $this->assertFalse((new User(['is_suspended' => true]))->canAccessPanel($panel));
    }
}
