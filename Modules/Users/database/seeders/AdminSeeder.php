<?php

namespace Modules\Users\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Lookups\Models\Lookup;
use Modules\Users\Models\User;

class AdminSeeder extends Seeder
{
    /**
     * Local login account with the Admin role. Skipped outside local, because the password is public.
     */
    public function run(): void
    {
        if (! app()->isLocal()) {
            $this->command?->warn('AdminSeeder skipped: it only runs in the local environment.');

            return;
        }

        User::updateOrCreate(
            ['email' => 'ahmed@gmail.com'],
            [
                'name' => 'Ahmed Atef',
                'password' => '123456789',
                'is_suspended' => false,
                'role_id' => Lookup::where('slug', User::ADMIN_ROLE)->value('id'),
            ]
        );
    }
}
