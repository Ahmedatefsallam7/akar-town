<?php

namespace Modules\Users\Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * The roles and the local admin account; locally also the demo users (UsersDemoSeeder).
 */
class UsersDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesTableSeeder::class,
            AdminSeeder::class,
        ]);

        if (app()->isLocal()) {
            $this->call(UsersDemoSeeder::class);
        }
    }
}
