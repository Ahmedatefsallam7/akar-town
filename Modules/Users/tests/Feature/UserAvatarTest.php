<?php

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Users\Filament\Auth\EditProfile;
use Modules\Users\Filament\Resources\UserResource\Pages\CreateUser;
use Modules\Users\Filament\Resources\UserResource\Pages\EditUser;
use Modules\Users\Models\User;
use Tests\TestCase;

class UserAvatarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(User::AVATAR_DISK);
        $this->actingAs($this->createAdmin(['username' => 'boss', 'phone' => '+201099999999']));
    }

    private function client(array $attributes = []): User
    {
        return User::factory()->create($attributes + [
            'username' => 'client'.uniqid(), 'phone' => '+2010'.random_int(10000000, 99999999),
            'role_id' => $this->role('user_roles_client')->id,
        ]);
    }

    public function test_admin_can_upload_a_photo_when_creating_a_user(): void
    {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'avatar' => UploadedFile::fake()->image('me.jpg', 256, 256),
                'first_name' => 'Sara', 'last_name' => 'Ali', 'username' => 'sara', 'email' => 'sara@example.com',
                'phone' => '+201012345678', 'role_id' => $this->role('user_roles_client')->id,
                'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'sara@example.com')->sole();
        $this->assertStringStartsWith(User::AVATAR_DIRECTORY.'/', $user->avatar);
        Storage::disk(User::AVATAR_DISK)->assertExists($user->avatar);
        $this->assertSame(Storage::disk(User::AVATAR_DISK)->url($user->avatar), $user->getFilamentAvatarUrl());
    }

    public function test_photo_is_optional(): void
    {
        $this->assertNull($this->client()->getFilamentAvatarUrl());
    }

    public function test_replacing_a_photo_deletes_the_old_file(): void
    {
        Storage::disk(User::AVATAR_DISK)->put('avatars/old.jpg', 'x');
        $user = $this->client(['avatar' => 'avatars/old.jpg']);

        // Like the browser: the old photo is removed from the field, then the new one is uploaded.
        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->set('data.avatar', null)
            ->set('data.avatar', [UploadedFile::fake()->image('new.png', 256, 256)])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNotSame('avatars/old.jpg', $user->fresh()->avatar);
        Storage::disk(User::AVATAR_DISK)->assertMissing('avatars/old.jpg');
        Storage::disk(User::AVATAR_DISK)->assertExists($user->fresh()->avatar);
    }

    public function test_removing_a_photo_deletes_the_file(): void
    {
        Storage::disk(User::AVATAR_DISK)->put('avatars/old.jpg', 'x');
        $user = $this->client(['avatar' => 'avatars/old.jpg']);

        $user->update(['avatar' => null]);

        Storage::disk(User::AVATAR_DISK)->assertMissing('avatars/old.jpg');
    }

    public function test_soft_delete_keeps_the_photo_and_permanent_delete_removes_it(): void
    {
        Storage::disk(User::AVATAR_DISK)->put('avatars/keep.jpg', 'x');
        $user = $this->client(['avatar' => 'avatars/keep.jpg']);

        $user->delete();
        Storage::disk(User::AVATAR_DISK)->assertExists('avatars/keep.jpg');

        $user->forceDelete();
        Storage::disk(User::AVATAR_DISK)->assertMissing('avatars/keep.jpg');
    }

    public function test_only_jpg_png_or_webp_up_to_2_mb(): void
    {
        $user = $this->client();

        foreach ([
            UploadedFile::fake()->create('file.pdf', 10, 'application/pdf'),
            UploadedFile::fake()->create('logo.svg', 1, 'image/svg+xml'),
            UploadedFile::fake()->image('huge.jpg')->size(3000),
        ] as $file) {
            Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
                ->fillForm(['avatar' => $file])
                ->call('save')
                ->assertHasFormErrors(['avatar']);
        }

        $this->assertNull($user->fresh()->avatar);
    }

    public function test_users_can_change_their_own_photo_on_the_profile_page(): void
    {
        $me = $this->client();
        $this->actingAs($me);

        Livewire::test(EditProfile::class)
            ->fillForm(['avatar' => UploadedFile::fake()->image('me.png', 256, 256)])
            ->call('save')
            ->assertHasNoFormErrors();

        Storage::disk(User::AVATAR_DISK)->assertExists($me->fresh()->avatar);
    }

    public function test_a_rolled_back_save_keeps_the_photo(): void
    {
        Storage::disk(User::AVATAR_DISK)->put('avatars/old.jpg', 'x');
        $user = $this->client(['avatar' => 'avatars/old.jpg']);

        try {
            DB::transaction(function () use ($user) {
                $user->update(['avatar' => null]);

                throw new \RuntimeException('save failed');
            });
        } catch (\RuntimeException) {
        }

        $this->assertSame('avatars/old.jpg', $user->fresh()->avatar);
        Storage::disk(User::AVATAR_DISK)->assertExists('avatars/old.jpg');
    }

    public function test_a_file_still_used_by_another_user_is_kept(): void
    {
        Storage::disk(User::AVATAR_DISK)->put('avatars/shared.jpg', 'x');
        $first = $this->client(['avatar' => 'avatars/shared.jpg']);
        $this->client(['avatar' => 'avatars/shared.jpg']);

        $first->update(['avatar' => null]);

        Storage::disk(User::AVATAR_DISK)->assertExists('avatars/shared.jpg');
    }

    public function test_deleting_from_the_table_keeps_the_photo_until_the_permanent_delete(): void
    {
        $disk = Storage::disk(User::AVATAR_DISK);
        $disk->put('avatars/one.jpg', 'x');
        $disk->put('avatars/two.jpg', 'x');
        $one = $this->client(['avatar' => 'avatars/one.jpg']);
        $two = $this->client(['avatar' => 'avatars/two.jpg']);

        // Delete (to the trash): photos kept, so a restore still has them.
        Livewire::test(\Modules\Users\Filament\Resources\UserResource\Pages\ListUsers::class)
            ->callTableBulkAction('delete', [$one, $two]);
        $disk->assertExists(['avatars/one.jpg', 'avatars/two.jpg']);

        // Permanent delete, one row and in bulk: photos removed.
        Livewire::test(\Modules\Users\Filament\Resources\UserResource\Pages\ListUsers::class)
            ->filterTable('trashed', false)
            ->callTableAction('forceDelete', $one);
        $disk->assertMissing('avatars/one.jpg');

        Livewire::test(\Modules\Users\Filament\Resources\UserResource\Pages\ListUsers::class)
            ->filterTable('trashed', false)
            ->callTableBulkAction('forceDelete', [$two]);
        $disk->assertMissing('avatars/two.jpg');
    }

    public function test_the_photo_field_explains_the_allowed_files(): void
    {
        $this->get(\Modules\Users\Filament\Resources\UserResource::getUrl('create'))
            ->assertOk()
            ->assertSee(__('admin.users.avatar_help'));
    }
}
