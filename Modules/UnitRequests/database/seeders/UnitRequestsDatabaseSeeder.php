<?php

namespace Modules\UnitRequests\Database\Seeders;

use Database\Seeders\Concerns\SeedsDemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Lookups\Database\Seeders\LookupsDatabaseSeeder;
use Modules\UnitRequests\Models\UnitRequest;
use Modules\Units\Database\Seeders\UnitsDatabaseSeeder;
use Modules\Units\Models\Unit;
use Modules\WebsiteUsers\Database\Seeders\WebsiteUsersDatabaseSeeder;
use Modules\WebsiteUsers\Models\WebsiteUser;

/**
 * Demo: one "Sell Property" request per status with every field filled (the closed one turned into a property). Saved
 * quietly: NOT sent to the CRM. Local only, see SeedsDemoData. With SEED_SCALE, many more requests (5% of it).
 */
class UnitRequestsDatabaseSeeder extends Seeder
{
    use SeedsDemoData;

    private const PEOPLE = [['Sara Ali', 'sara.seller@example.com', '01001234561'], ['Ahmed Nabil', 'ahmed.seller@example.com', '01001234562'], ['Mariam Hany', 'mariam.seller@example.com', '01001234563'], ['Hassan Zaki', 'hassan.seller@example.com', '01001234564']];

    public function run(): void
    {
        if (! $this->demo()) {
            return;
        }

        $this->needs(LookupsDatabaseSeeder::class, WebsiteUsersDatabaseSeeder::class, UnitsDatabaseSeeder::class);

        $users = WebsiteUser::query()->where('email', 'not like', 'bulk-%')->pluck('id')->all();
        $place = $this->location('Sheikh Zayed');
        foreach (UnitRequest::STATUSES as $i => $status) {
            [$name, $email, $phone] = self::PEOPLE[$i % 4];
            $email = str_replace('@', "+{$status}@", $email);
            if (UnitRequest::query()->where('email', $email)->exists()) {
                continue;
            }
            $request = UnitRequest::query()->createQuietly([
                'website_user_id' => $users ? $users[$i % count($users)] : null,
                'visitor_id' => (string) Str::uuid(),
                'seller_name' => $name,
                'email' => $email,
                'phone' => $phone,
                'name_en' => 'Garden Villa for Sale '.($i + 1),
                'name_ar' => 'فيلا بحديقة للبيع '.($i + 1),
                ...$place,
                'address' => 'Beverly Hills, Sheikh Zayed',
                'latitude' => 30.0444,
                'longitude' => 30.9833,
                'price' => 9500000 + $i * 500000,
                'area' => 300 + $i * 20,
                'bedrooms' => 4,
                'bathrooms' => 3,
                'down_payment' => 30,
                'installment_years_from' => 2,
                'installment_years_to' => 4,
                'short_description_en' => 'Corner villa with a private garden and pool.',
                'short_description_ar' => 'فيلا ناصية بحديقة وحمام سباحة خاص.',
                'description_en' => '<p>Fully finished villa, ready to move, with a private garden, pool and two parking spaces.</p>',
                'description_ar' => '<p>فيلا متشطبة بالكامل جاهزة للسكن، بحديقة وحمام سباحة خاص ومكانين للسيارات.</p>',
                'status' => $status,
                'internal_notes' => 'Owner is flexible on the price.',
                'unit_id' => $status === UnitRequest::CLOSED ? Unit::query()->where('slug', 'not like', 'bulk-%')->orderByDesc('id')->value('id') : null,
                'crm_lead_id' => $i % 2 ? (string) (92000 + $i) : null,
                'crm_synced_at' => $i % 2 ? now()->subDay() : null,
            ]);
            $this->fullChain($request);
            foreach (UnitRequest::LOOKUPS as $type) {
                $this->lookupsIfEmpty($request, $type, 2, $i);
            }
            $this->mediaIfEmpty($request, UnitRequest::FEATURED_IMAGE, [self::PHOTOS[$i % count(self::PHOTOS)]]);
        }

        $chains = $this->chains();
        $now = now();
        $this->bulk('unit_requests', 'email', $this->volume(0.05), fn (int $n) => $chains[$n % count($chains)] + [
            'visitor_id' => $this->bulkUuid($n),
            'seller_name' => "Seller {$n}",
            'email' => "bulk-seller-{$n}@example.test",
            'phone' => '0103'.str_pad((string) $n, 7, '0', STR_PAD_LEFT),
            'name_en' => "Apartment for Sale {$n}",
            'name_ar' => "شقة للبيع {$n}",
            'address' => "Building {$n}, 90th Street",
            'latitude' => 30 + ($n % 100) / 100,
            'longitude' => 31 + ($n % 100) / 100,
            'price' => 2_000_000 + ($n % 300) * 50_000,
            'area' => 90 + $n % 250,
            'bedrooms' => 1 + $n % 5,
            'bathrooms' => 1 + $n % 3,
            'down_payment' => 10 + $n % 40,
            'installment_years_from' => 1 + $n % 3,
            'installment_years_to' => 4 + $n % 4,
            'short_description_en' => 'Bright apartment close to the main roads.',
            'short_description_ar' => 'شقة مضيئة قريبة من الطرق الرئيسية.',
            'description_en' => '<p>Fully finished, ready to move.</p>',
            'description_ar' => '<p>متشطبة بالكامل وجاهزة للسكن.</p>',
            'status' => UnitRequest::STATUSES[$n % count(UnitRequest::STATUSES)],
            'internal_notes' => 'Owner is flexible on the price.',
            'crm_lead_id' => $n % 2 ? (string) (300000 + $n) : null,
            'crm_synced_at' => $n % 2 ? $now : null,
            'created_at' => $now->copy()->subMinutes($n * 11),
            'updated_at' => $now,
        ]);

        // Requests made before (or from the website) get their place too.
        foreach (UnitRequest::query()->where('email', 'not like', 'bulk-%')->whereNull('country_id')->get() as $i => $request) {
            $request->updateQuietly($this->place('Sheikh Zayed', $i));
            $this->fullChain($request);
        }

        $this->stamps('unit_requests');
    }
}
