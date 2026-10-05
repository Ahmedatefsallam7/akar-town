<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Users\Models\User;
use Tests\TestCase;

/**
 * Jobs that keep the database small as the site runs for months.
 */
class MaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_cache_rows_are_pruned_and_valid_ones_kept(): void
    {
        DB::table('cache')->insert([
            ['key' => 'old', 'value' => 's:1:"x";', 'expiration' => now()->subMinute()->getTimestamp()],
            ['key' => 'fresh', 'value' => 's:1:"x";', 'expiration' => now()->addHour()->getTimestamp()],
        ]);
        DB::table('cache_locks')->insert(['key' => 'old-lock', 'owner' => 'x', 'expiration' => now()->subMinute()->getTimestamp()]);

        $this->artisan('cache:prune-expired')->expectsOutput('Removed 2 expired cache entries.')->assertSuccessful();

        $this->assertSame(['fresh'], DB::table('cache')->pluck('key')->all());
        $this->assertSame(0, DB::table('cache_locks')->count());
    }

    public function test_pruning_is_scheduled_daily(): void
    {
        $event = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains($event->command ?? '', 'cache:prune-expired'));

        $this->assertNotNull($event, 'cache:prune-expired is not scheduled.');
        $this->assertSame('0 0 * * *', $event->expression);
    }

    public function test_unused_avatar_files_are_pruned_after_a_day(): void
    {
        Storage::fake(User::AVATAR_DISK);
        $disk = Storage::disk(User::AVATAR_DISK);
        $this->seedRoles();

        foreach (['avatars/used.jpg', 'avatars/trashed-user.jpg', 'avatars/orphan-old.jpg', 'avatars/orphan-new.jpg'] as $path) {
            $disk->put($path, 'x');
        }
        touch($disk->path('avatars/used.jpg'), now()->subDays(3)->getTimestamp());
        touch($disk->path('avatars/trashed-user.jpg'), now()->subDays(3)->getTimestamp());
        touch($disk->path('avatars/orphan-old.jpg'), now()->subDays(3)->getTimestamp());

        User::factory()->create(['avatar' => 'avatars/used.jpg']);
        User::factory()->create(['avatar' => 'avatars/trashed-user.jpg'])->delete();

        $this->artisan('users:prune-orphan-avatars')->expectsOutput('Removed 1 unused avatar files.')->assertSuccessful();

        $disk->assertExists(['avatars/used.jpg', 'avatars/trashed-user.jpg', 'avatars/orphan-new.jpg']);
        $disk->assertMissing('avatars/orphan-old.jpg');
    }

    public function test_avatar_pruning_is_scheduled_daily(): void
    {
        $event = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains($event->command ?? '', 'users:prune-orphan-avatars'));

        $this->assertNotNull($event);
        $this->assertSame('0 0 * * *', $event->expression);
    }
}
