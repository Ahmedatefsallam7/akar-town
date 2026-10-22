<?php

namespace Modules\WebsiteUsers\Database\Seeders;

use Database\Seeders\Concerns\SeedsDemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Projects\Models\Project;
use Modules\Units\Database\Seeders\UnitsDatabaseSeeder;
use Modules\Units\Models\Unit;
use Modules\WebsiteUsers\Models\WebsiteUser;

/**
 * Demo: four website accounts (each sign-up way, both languages, one blocked) with every field filled and a few
 * saved properties and projects (wishlist). Password: 123456789. Local only, see SeedsDemoData. With SEED_SCALE, many more accounts (a fifth of it).
 */
class WebsiteUsersDatabaseSeeder extends Seeder
{
    use SeedsDemoData;

    public function run(): void
    {
        if (! $this->demo()) {
            return;
        }

        $this->needs(UnitsDatabaseSeeder::class);

        foreach ([
            ['Hana Mostafa', 'hana@example.com', '01005550001', 'email', 'en', WebsiteUser::ACTIVE],
            ['Tarek Fawzy', 'tarek@example.com', '01005550002', 'phone', 'ar', WebsiteUser::ACTIVE],
            ['Laila Ibrahim', 'laila@example.com', '01005550003', 'google', 'en', WebsiteUser::ACTIVE],
            ['Mahmoud Saad', 'mahmoud@example.com', '01005550004', 'apple', 'ar', WebsiteUser::BLOCKED],
        ] as $i => [$name, $email, $phone, $method, $language, $status]) {
            $user = WebsiteUser::query()->firstOrCreate(['email' => $email], [
                'name' => $name,
                'phone' => $phone,
                'registration_method' => $method,
                'preferred_language' => $language,
                'status' => $status,
                'password' => '123456789',
                'admin_notes' => $status === WebsiteUser::BLOCKED ? 'Blocked after repeated fake requests.' : 'Interested in New Cairo villas.',
            ]);
            $user->forceFill(array_filter([
                'email_verified_at' => $user->email_verified_at ?? now()->subDays(30),
                'phone_verified_at' => $user->phone_verified_at ?? now()->subDays(30),
                'last_login_at' => $user->last_login_at ?? now()->subDays($i),
                'avatar' => $user->avatar ?? $this->avatar('website-users', $i),
            ]))->save();

            if (! $user->favourites()->exists()) {
                $units = Unit::query()->where('slug', 'not like', 'bulk-%')->where('is_published', true)->skip($i * 3)->limit(3)->pluck('id');
                $project = Project::query()->where('slug', 'not like', 'bulk-%')->where('is_published', true)->skip($i)->value('id');
                $user->favourites()->createMany([...$units->map(fn (int $id) => ['unit_id' => $id]), ...($project ? [['project_id' => $project]] : [])]);
            }
        }

        $password = Hash::make('123456789');
        $methods = WebsiteUser::REGISTRATION_METHODS;
        $now = now();
        $this->bulk('website_users', 'email', $this->volume(0.2), fn (int $n) => [
            'name' => "Visitor {$n}",
            'email' => "bulk-visitor-{$n}@example.test",
            'phone' => '0106'.str_pad((string) $n, 7, '0', STR_PAD_LEFT),
            'registration_method' => $methods[$n % count($methods)],
            'email_verified_at' => $now,
            'phone_verified_at' => $now,
            'status' => $n % 30 === 0 ? WebsiteUser::BLOCKED : WebsiteUser::ACTIVE,
            'preferred_language' => $n % 2 ? 'ar' : 'en',
            'password' => $password,
            'last_login_at' => $now->copy()->subMinutes($n),
            'admin_notes' => 'Interested in New Cairo apartments.',
            'created_at' => $now->copy()->subMinutes($n),
            'updated_at' => $now,
        ]);

        $this->stamps('website_users');
    }
}
