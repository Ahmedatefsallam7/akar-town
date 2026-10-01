<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Lookups\Database\Seeders\LookupsDatabaseSeeder;
use Modules\Users\Database\Seeders\UsersDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database. Module seeders are safe to run again.
     */
    public function run(): void
    {
        $this->call([
            LookupsDatabaseSeeder::class,
            UsersDatabaseSeeder::class,
        ]);
    }
}
