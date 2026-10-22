<?php

namespace Modules\Locations\Database\Seeders;

use Database\Seeders\Concerns\SeedsDemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Locations\Models\Location;
use Modules\Lookups\Database\Seeders\LookupsDatabaseSeeder;

/**
 * Demo: the design's "Prime Destinations" first (featured, with their photos and texts), then every location's empty
 * fields filled (place chain, code, neighbourhoods, map point, descriptions, SEO, photo). Local only, see SeedsDemoData.
 * With SEED_SCALE, many more locations (4% of it).
 */
class LocationsDemoSeeder extends Seeder
{
    use SeedsDemoData;

    /** The design's destinations (index.html): name, description, photo. */
    private const DESTINATIONS = [
        ['New Cairo', "Egypt's fastest-growing urban hub — world-class amenities, gated communities.", 'photo-1512917774080-9991f1c4c750'],
        ['North Coast', "Sun-kissed shores along Egypt's Mediterranean edge, steps from the sea.", 'photo-1499793983690-e29da59ef1c2'],
        ['Sheikh Zayed', 'An elegant enclave of spacious living and luxury west of Cairo.', 'photo-1580587771525-78b9dba3b914'],
        ['Ain Sokhna', 'Private Red Sea escapes just 90 minutes from the capital.', 'photo-1600210492493-0946911123ea'],
        ['6th of October', 'Modern infrastructure with room to breathe, grow, and invest.', 'photo-1600596542815-ffad4c1539a9'],
        ['Cairo', 'The heart of Egypt — iconic, historic, and always in demand.', 'photo-1545324418-cc1a3fa10c00'],
    ];

    /** Place name => [latitude, longitude, code, neighbourhoods]. */
    private const PLACES = [
        'Egypt' => [26.8206, 30.8025, 'EG', ['Cairo', 'Giza', 'Alexandria']],
        'Cairo' => [30.0444, 31.2357, 'CAI', ['Zamalek', 'Maadi', 'Heliopolis']],
        'New Cairo' => [30.0300, 31.4700, 'NCA', ['Fifth Settlement', 'First Settlement', 'Katameya']],
        'Sheikh Zayed' => [30.0444, 30.9833, 'SZY', ['Beverly Hills', 'Al Rabwa', 'Zayed 2000']],
        '6th of October' => [29.9285, 30.9188, 'OCT', ['Al Motamayez', 'Dreamland', 'West Somid']],
        'North Coast' => [30.9500, 28.8000, 'NCO', ['Sidi Abdel Rahman', 'Ras El Hekma', 'El Alamein']],
        'Ain Sokhna' => [29.6000, 32.3167, 'SOK', ['Galala', 'Zafarana', 'Porto Sokhna']],
    ];

    public function run(): void
    {
        if (! $this->demo()) {
            return;
        }

        $this->needs(LookupsDatabaseSeeder::class);

        // The design's destinations first: other featured locations move after them.
        Location::query()->whereNotIn('name_en', array_column(self::DESTINATIONS, 0))->where('order', '<', 100)->toBase()->increment('order', 100);
        foreach (self::DESTINATIONS as $order => [$name, $description, $photo]) {
            $location = Location::query()->firstOrCreate(['name_en' => $name], ['name_ar' => $name, ...$this->location($name)]);
            $location->update(['is_active' => true, 'is_featured' => true, 'order' => $order + 1, 'description_en' => $location->description_en ?: $description]);
            $this->mediaIfEmpty($location, Location::FEATURED_IMAGE, [$photo]);
        }

        foreach (Location::query()->where('slug', 'not like', 'bulk-%')->get() as $location) {
            [$lat, $lng, $code, $areas] = self::PLACES[$location->name_en] ?? [30.0444 + $location->id / 100, 31.2357 + $location->id / 100, strtoupper(Str::substr(Str::slug($location->name_en, ''), 0, 3)), ['Downtown', 'Riverside']];
            $this->fillEmpty($location, [
                // The place in the tree with its slug or name (the page lists the properties there).
                ...($location->country_id ? [] : ($this->location($location->slug) ?: $this->location($location->name_en))),
                'code' => $code.'-'.$location->id,
                'neighbourhoods' => $areas,
                'latitude' => $lat,
                'longitude' => $lng,
                'description_en' => "{$location->name_en} is one of Egypt's most sought-after addresses, with gated communities, schools and easy access to the main roads.",
                'description_ar' => ($location->name_ar ?: $location->name_en).' من أكثر المناطق طلباً في مصر، بها كمبوندات مسورة ومدارس وسهولة الوصول للطرق الرئيسية.',
                ...$this->meta($location->name_en, $location->name_ar ?: $location->name_en),
            ]);
            $this->mediaIfEmpty($location, Location::FEATURED_IMAGE, [self::PHOTOS[$location->id % count(self::PHOTOS)]]);
        }

        $chains = $this->chains();
        $now = now();
        $this->bulk('locations', 'slug', $this->volume(0.04), fn (int $n) => $chains[$n % count($chains)] + [
            'name_en' => "Bulk Location {$n}",
            'name_ar' => "موقع {$n}",
            'slug' => "bulk-location-{$n}",
            'code' => "BLK-{$n}",
            'neighbourhoods' => json_encode(['North', 'South', 'Centre']),
            'latitude' => 30 + ($n % 100) / 100,
            'longitude' => 31 + ($n % 100) / 100,
            'order' => 1000 + $n,
            'is_featured' => false,
            'is_active' => $n % 10 !== 0,
            'description_en' => "Bulk Location {$n}: gated communities, schools and easy access to the main roads.",
            'description_ar' => "موقع {$n}: كمبوندات مسورة ومدارس وسهولة الوصول للطرق الرئيسية.",
            ...$this->bulkMeta("Bulk Location {$n}", "موقع {$n}"),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->stamps('locations');
    }
}
