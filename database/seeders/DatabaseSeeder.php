<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsDemoData;
use Illuminate\Database\Seeder;
use Modules\Careers\Database\Seeders\CareersDatabaseSeeder;
use Modules\Developers\Database\Seeders\DevelopersDatabaseSeeder;
use Modules\Galleries\Database\Seeders\GalleriesDatabaseSeeder;
use Modules\GeneralSettings\Database\Seeders\GeneralSettingsDatabaseSeeder;
use Modules\Inquiry\Database\Seeders\InquiryDatabaseSeeder;
use Modules\Locations\Database\Seeders\LocationsDatabaseSeeder;
use Modules\Lookups\Database\Seeders\LookupsDatabaseSeeder;
use Modules\PossibleLeads\Database\Seeders\PossibleLeadsDatabaseSeeder;
use Modules\Projects\Database\Seeders\ProjectsDatabaseSeeder;
use Modules\Sliders\Database\Seeders\SlidersDatabaseSeeder;
use Modules\UnitRequests\Database\Seeders\UnitRequestsDatabaseSeeder;
use Modules\Units\Database\Seeders\UnitsDatabaseSeeder;
use Modules\Users\Database\Seeders\UsersDatabaseSeeder;
use Modules\WebsiteUsers\Database\Seeders\WebsiteUsersDatabaseSeeder;

/**
 * Every module's seeder, in order. Everywhere: the fixed data (lookup lists, roles, location pages, developers'
 * names, home blocks). Locally also the demo data in every admin field, and with SEED_SCALE in .env thousands more
 * rows for speed tests (see Database\Seeders\Concerns\SeedsDemoData). Safe to run again.
 *
 * One module alone: `php artisan module:seed Projects` (it runs what it needs first).
 */
class DatabaseSeeder extends Seeder
{
    use SeedsDemoData;

    public function run(): void
    {
        $this->needs(
            LookupsDatabaseSeeder::class,
            UsersDatabaseSeeder::class,
            LocationsDatabaseSeeder::class,
            DevelopersDatabaseSeeder::class,
            SlidersDatabaseSeeder::class,
        );

        if (! app()->isLocal()) {
            return;
        }

        $this->needs(
            GeneralSettingsDatabaseSeeder::class,
            ProjectsDatabaseSeeder::class,
            GalleriesDatabaseSeeder::class,
            UnitsDatabaseSeeder::class,
            CareersDatabaseSeeder::class,
            WebsiteUsersDatabaseSeeder::class,
            InquiryDatabaseSeeder::class,
            UnitRequestsDatabaseSeeder::class,
            PossibleLeadsDatabaseSeeder::class,
        );
    }
}
