<?php

namespace Modules\Users\Database\Seeders;

use Database\Seeders\Concerns\SeedsDemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Users\Models\User;

/**
 * Demo: two more admin users, and every user's empty fields filled (photo, phone, agent card details) — local only,
 * see SeedsDemoData. Password of the demo users: 123456789. With SEED_SCALE, many more users (a tenth of it).
 */
class UsersDemoSeeder extends Seeder
{
    use SeedsDemoData;

    public function run(): void
    {
        if (! $this->demo()) {
            return;
        }

        $roles = $this->children('user_roles');
        foreach ([
            ['admin@akartown.com', 'Mona', 'Hassan', 'mona.hassan', '01001112223', 'Editor'],
            ['sales@akartown.com', 'Omar', 'Khaled', 'omar.khaled', '01002223334', 'Editor'],
        ] as [$email, $first, $last, $username, $phone, $role]) {
            User::query()->firstOrCreate(['email' => $email], [
                'first_name' => $first,
                'last_name' => $last,
                'username' => $username,
                'phone' => $phone,
                'password' => '123456789',
                'is_suspended' => false,
                'role_id' => $roles[$role] ?? null,
            ]);
        }

        foreach (User::query()->where('email', 'not like', 'bulk-%')->get() as $i => $user) {
            $this->fillEmpty($user, [
                'role_id' => $roles['Editor'] ?? null,
                'username' => Str::slug($user->first_name.' '.$user->last_name, '.') ?: 'user'.$user->id,
                'phone' => '0100'.str_pad((string) (2000000 + $user->id), 7, '0', STR_PAD_LEFT),
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
                'avatar' => $this->avatar(User::AVATAR_DIRECTORY, $i),
                // The agent card on the website.
                'job_title_en' => ['Senior Property Consultant', 'Sales Manager', 'Property Consultant'][$i % 3],
                'job_title_ar' => ['مستشار عقاري أول', 'مدير المبيعات', 'مستشار عقاري'][$i % 3],
                'whatsapp' => '20100'.str_pad((string) (3000000 + $user->id), 7, '0', STR_PAD_LEFT),
                'rating' => [4.9, 4.7, 4.5][$i % 3],
                'reviews_count' => [48, 31, 17][$i % 3],
            ]);
        }

        $this->bulkUsers(array_values(array_diff_key($roles, ['Admin' => true])));
        $this->stamps('users');
    }

    /**
     * @param  list<int>  $roles  the non-admin roles
     */
    private function bulkUsers(array $roles): void
    {
        $password = Hash::make('123456789');
        $now = now();
        $this->bulk('users', 'email', $this->volume(0.1), fn (int $n) => [
            'first_name' => "User{$n}",
            'last_name' => 'Bulk',
            'username' => "bulk-user-{$n}",
            'email' => "bulk-user-{$n}@example.test",
            'phone' => '0109'.str_pad((string) $n, 7, '0', STR_PAD_LEFT),
            'job_title_en' => 'Property Consultant',
            'job_title_ar' => 'مستشار عقاري',
            'whatsapp' => '20109'.str_pad((string) $n, 7, '0', STR_PAD_LEFT),
            'rating' => 4 + ($n % 10) / 10,
            'reviews_count' => $n % 60,
            'email_verified_at' => $now,
            'phone_verified_at' => $now,
            'password' => $password,
            'is_suspended' => $n % 25 === 0,
            'role_id' => $roles ? $roles[$n % count($roles)] : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
