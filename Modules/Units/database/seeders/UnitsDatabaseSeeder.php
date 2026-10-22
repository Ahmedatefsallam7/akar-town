<?php

namespace Modules\Units\Database\Seeders;

use Database\Seeders\Concerns\SeedsDemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Galleries\Database\Seeders\GalleriesDatabaseSeeder;
use Modules\Galleries\Models\Gallery;
use Modules\Projects\Models\Project;
use Modules\Units\Models\Unit;
use Modules\Units\Models\UnitPriceHistory;
use Modules\Users\Models\User;

/**
 * Demo: the design's featured properties (home and property page), three more units of different types per project,
 * then every unit's empty fields filled (agent, lookups, place, map point, numbers, payment plan, texts, gallery,
 * video, SEO, picked lookups, photos, plans). Local only, see SeedsDemoData. With SEED_SCALE, that many more units.
 */
class UnitsDatabaseSeeder extends Seeder
{
    use SeedsDemoData;

    /** The design's featured properties (index.html): title, description, place, beds, baths, area, price, status, photos. */
    private const FEATURED = [
        ['The Pinnacle Penthouse', "Soaring above New Cairo's skyline, this ultra-luxury penthouse redefines elevated living with panoramic city views, a private rooftop pool terrace, and bespoke handcrafted finishes throughout.", 'New Cairo', 4, 5, 650, 45000000, 'Ready to Move', ['photo-1545324418-cc1a3fa10c00', 'photo-1600566753190-17f0baa2a6c3', 'photo-1600210492493-0946911123ea', 'photo-1600585154526-990dced4db0d', 'photo-1613490493576-7fde63acd811']],
        ['Al Noor Garden Villas', 'Timeless architecture meets lush private landscaping in this exclusive gated community. Each villa offers a private pool, garden atrium, and fully bespoke interior by award-winning designers.', 'Sheikh Zayed', 5, 6, 820, 62500000, 'Ready to Move', ['photo-1512917774080-9991f1c4c750', 'photo-1600596542815-ffad4c1539a9', 'photo-1580587771525-78b9dba3b914', 'photo-1613490493576-7fde63acd811', 'photo-1600210492493-0946911123ea']],
        ['Maaya Heights Tower', 'An architectural statement rising from the heart of 6th October City. Elevated beyond expectation with resort-style amenities, private concierge, and investment-grade returns.', '6th of October', 3, 4, 420, 28000000, 'Off-Plan', ['photo-1600596542815-ffad4c1539a9', 'photo-1545324418-cc1a3fa10c00', 'photo-1499793983690-e29da59ef1c2', 'photo-1580587771525-78b9dba3b914', 'photo-1512917774080-9991f1c4c750']],
        ['Azure Coast Estates', "Where the Mediterranean breeze meets private beach access. Luxury seafront living on Egypt's most coveted coastline, complete with private dock, beach club, and panoramic sea views.", 'Ain Sokhna', 6, 7, 1200, 95000000, 'Ready to Move', ['photo-1499793983690-e29da59ef1c2', 'photo-1613490493576-7fde63acd811', 'photo-1512917774080-9991f1c4c750', 'photo-1600596542815-ffad4c1539a9', 'photo-1545324418-cc1a3fa10c00']],
        ['Sky Gardens Residences', "Suspended greenery and sky-high living converge at Egypt's first vertical garden tower. Each residence features floor-to-ceiling glazing and a private planted sky terrace.", 'North Coast', 3, 3, 380, 22500000, 'Off-Plan', ['photo-1580587771525-78b9dba3b914', 'photo-1545324418-cc1a3fa10c00', 'photo-1600596542815-ffad4c1539a9', 'photo-1499793983690-e29da59ef1c2', 'photo-1600210492493-0946911123ea']],
    ];

    /** The design's property description (property-details.html). */
    private const DESCRIPTION = '<p>Welcome to an exceptional residence within one of Egypt\'s most prestigious gated communities. Positioned on a generous plot, it offers a rare combination of privacy, elegance, and natural beauty.</p>'
        .'<p>Finished to an exceptional premium standard, it features soaring double-height ceilings, Italian marble flooring throughout the ground floor, and a bespoke open-plan kitchen. Floor-to-ceiling glazing in the main living area opens directly onto a landscaped private garden and sparkling pool.</p>'
        .'<p>Each bedroom is en-suite, with the master suite occupying the entire upper floor west wing, complete with a walk-in dressing room and private terrace overlooking the garden.</p>'
        .'<p>Residents enjoy a 24/7 guarded perimeter, manicured streetscapes, immediate access to premium schools and malls, and direct ring-road connectivity to all major Cairo districts.</p>';

    /** Extra units made per project: type name, bedrooms, bathrooms, area, price. */
    private const TYPES = [
        ['Apartment', 3, 2, 165, 7800000],
        ['Duplex', 4, 3, 260, 13500000],
        ['Penthouse', 4, 4, 320, 21000000],
        ['Townhouse', 4, 3, 280, 16000000],
        ['Studio', 1, 1, 75, 3200000],
        ['Chalet', 2, 2, 120, 6500000],
    ];

    public function run(): void
    {
        if (! $this->demo()) {
            return;
        }

        $this->needs(GalleriesDatabaseSeeder::class);

        $this->designUnits();
        $this->perProject();
        $this->fill();
        $this->bulkUnits();

        UnitPriceHistory::query()->whereNull('changed_by')->update(['changed_by' => User::query()->orderBy('id')->value('id')]);
        $this->stamps('units');
    }

    /**
     * The design's featured properties, each with its own gallery, in the design's order.
     */
    private function designUnits(): void
    {
        $project = Project::query()->where('title_en', 'The Pinnacle Residences')->first();

        foreach (self::FEATURED as $order => [$name, $description, $place, $beds, $baths, $area, $price, $status, $photos]) {
            $unit = Unit::query()->firstOrCreate(['name_en' => $name], [
                'name_ar' => $name,
                'project_id' => $project?->id,
                'short_description_en' => $description,
                'description_en' => self::DESCRIPTION,
                'is_published' => true,
                'is_featured' => true,
                'bedrooms' => $beds,
                'bathrooms' => $baths,
                'area' => $area,
                'price' => $price,
                'offering_type_id' => $this->lookup('offeringtype', 'Primary'),
                'unit_status_id' => $this->lookup('unitstatus', $status),
                ...$this->location($place),
            ]);
            $unit->update(['order' => $order + 1]);
            $this->mediaIfEmpty($unit, Unit::FEATURED_IMAGE, [$photos[0]]);

            if (! $unit->gallery_id) {
                $gallery = Gallery::query()->create(['name_en' => "{$name} Gallery", 'name_ar' => "معرض {$name}", 'project_id' => $project?->id, 'video_url' => self::VIDEO]);
                $this->mediaIfEmpty($gallery, Gallery::FEATURED_IMAGE, [$photos[0]]);
                $this->mediaIfEmpty($gallery, Gallery::PHOTOS, array_slice($photos, 1));
                $this->mediaIfEmpty($gallery, Gallery::FLOOR_PLANS, [self::PLAN]);
                $this->mediaIfEmpty($gallery, Gallery::IMAGES_360, [$photos[2]]);
                $this->mediaIfEmpty($gallery, Gallery::ATTACHMENTS, [], $this->pdf());
                $unit->update(['gallery_id' => $gallery->id]);
            }
        }
    }

    /**
     * Every project gets three more units of different types, so the unit tabs, filters and lists have data.
     */
    private function perProject(): void
    {
        foreach (Project::query()->where('slug', 'not like', 'bulk-%')->get() as $i => $project) {
            foreach ([0, 1, 2] as $n) {
                [$type, $beds, $baths, $area, $price] = self::TYPES[($i + $n * 2) % count(self::TYPES)];
                Unit::query()->firstOrCreate(['name_en' => "{$project->title_en} {$type} ".chr(65 + $n).'-'.($n + 1) * 10], [
                    'name_ar' => ($project->title_ar ?: $project->title_en).' '.$type,
                    'project_id' => $project->id,
                    'property_type_id' => $this->children('unittype')[$type] ?? null,
                    'is_published' => true,
                    'is_featured' => $n === 0,
                    'bedrooms' => $beds,
                    'bathrooms' => $baths,
                    'area' => $area + $n * 5,
                    'price' => $price + $i * 150000,
                ]);
            }
        }
    }

    /**
     * SEED_SCALE: that many more units, every field filled; 4 in 5 inside a project (the bulk ones first).
     */
    private function bulkUnits(): void
    {
        $chains = $this->chains();
        $projects = DB::table('projects')->orderByRaw("slug like 'bulk-%' desc")->orderBy('id')->limit(20_000)->pluck('id')->all() ?: [null];
        $galleries = Gallery::query()->orderBy('id')->pluck('id')->all() ?: [null];
        $agents = $this->agents() ?: [null];
        $pick = fn (string $type) => array_values($this->children($type)) ?: [null];
        [$offering, $types, $usage, $finishing, $furnishing, $status, $ownership] = array_map($pick, ['offeringtype', 'unittype', 'propertyusage', 'finishingtype', 'furnishingstatus', 'unitstatus', 'ownershiptype']);
        $now = now();

        $this->bulk('units', 'slug', $this->volume(1), fn (int $n) => $chains[$n % count($chains)] + [
            'name_en' => "Bulk Unit {$n}",
            'name_ar' => "وحدة {$n}",
            'slug' => "bulk-unit-{$n}",
            'project_id' => $n % 5 === 0 ? null : $projects[$n % count($projects)],
            'agent_id' => $agents[$n % count($agents)],
            'offering_type_id' => $offering[$n % count($offering)],
            'property_type_id' => $types[$n % count($types)],
            'property_usage_id' => $usage[$n % count($usage)],
            'finishing_type_id' => $finishing[$n % count($finishing)],
            'furnishing_status_id' => $furnishing[$n % count($furnishing)],
            'unit_status_id' => $status[$n % count($status)],
            'ownership_type_id' => $ownership[$n % count($ownership)],
            'neighbourhood' => 'Fifth Settlement',
            'address' => "Building {$n}, 90th Street",
            'latitude' => 30 + ($n % 100) / 100,
            'longitude' => 31 + ($n % 100) / 100,
            'is_published' => $n % 10 !== 0,
            'is_featured' => $n % 25 === 0,
            'order' => 1000 + $n,
            'price' => 800_000 + ($n % 400) * 25_000,
            'area' => 50 + $n % 350,
            'bedrooms' => 1 + $n % 6,
            'bathrooms' => 1 + $n % 4,
            'floor' => $n % 12,
            'parking_spaces' => $n % 3,
            'building_floors' => 4 + $n % 10,
            'building_year' => 2015 + $n % 12,
            'down_payment' => 5 + $n % 20,
            'installment_years_from' => 3 + $n % 4,
            'installment_years_to' => 7 + $n % 4,
            'delivery_date' => $now->copy()->addDays($n % 1500)->toDateString(),
            'short_description_en' => 'Bright unit with a wide terrace, open-plan living and garden views.',
            'short_description_ar' => 'وحدة مضيئة بتراس واسع ومعيشة مفتوحة وإطلالة على الحديقة.',
            'description_en' => "<p>Bulk Unit {$n}: premium finishing, a fitted kitchen and en-suite bedrooms.</p>",
            'description_ar' => "<p>وحدة {$n}: تشطيب فاخر ومطبخ مجهز وغرف نوم بحمامات خاصة.</p>",
            'gallery_id' => $galleries[$n % count($galleries)],
            'video_url' => self::VIDEO,
            ...$this->bulkMeta("Bulk Unit {$n}", "وحدة {$n}"),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->bulkLookups(Unit::class, 'units', 'slug', ['amenities' => 3, 'views' => 1, 'facility' => 1, 'paymentmethod' => 1]);
    }

    /**
     * Every unit's empty fields.
     */
    private function fill(): void
    {
        $agents = $this->agents();
        $pick = fn (string $type, int $i) => ($ids = array_values($this->children($type))) ? $ids[$i % count($ids)] : null;

        foreach (Unit::query()->where('slug', 'not like', 'bulk-%')->with('project')->get() as $i => $unit) {
            $project = $unit->project;
            $ar = $unit->name_ar ?: $unit->name_en;
            $this->fillEmpty($unit, [
                'agent_id' => $agents ? $agents[($i + 1) % count($agents)] : null,
                'offering_type_id' => $pick('offeringtype', $i),
                'property_type_id' => $pick('unittype', $i),
                'property_usage_id' => $pick('propertyusage', 0),
                'finishing_type_id' => $pick('finishingtype', $i),
                'furnishing_status_id' => $pick('furnishingstatus', $i),
                'unit_status_id' => $pick('unitstatus', $i),
                'ownership_type_id' => $pick('ownershiptype', $i),
                ...($unit->country_id ? [] : (($project ? array_filter($project->only(['country_id', 'city_id', 'region_id', 'area_id', 'sub_area_id'])) : []) ?: $this->place('New Cairo', $i))),
                'neighbourhood' => 'Fifth Settlement',
                'address' => "Building {$unit->id}, ".($project?->address_en ?: '90th Street'),
                'latitude' => $project?->latitude ?? 30.0131,
                'longitude' => $project?->longitude ?? 31.4089,
                'order' => $i + 1,
                'floor' => $i % 8,
                'parking_spaces' => 1 + $i % 2,
                'building_floors' => 5 + $i % 6,
                'building_year' => 2024,
                'down_payment' => 10,
                'installment_years_from' => 6,
                'installment_years_to' => 8,
                'delivery_date' => now()->addYears(1 + $i % 3)->startOfQuarter()->toDateString(),
                'short_description_en' => 'Bright corner unit with a wide terrace, open-plan living and garden views.',
                'short_description_ar' => 'وحدة ناصية مضيئة بتراس واسع ومعيشة مفتوحة وإطلالة على الحديقة.',
                'description_en' => '<p>A bright corner unit with floor-to-ceiling windows, an open-plan living area and a wide terrace overlooking the gardens.</p><p>Premium finishing, a fitted kitchen and en-suite bedrooms, minutes from the clubhouse and the main gate.</p><p>Flexible payment plan with a 10% down payment over up to 8 years.</p>',
                'description_ar' => '<p>وحدة ناصية مضيئة بنوافذ من الأرض للسقف ومعيشة مفتوحة وتراس واسع على الحدائق.</p><p>تشطيب فاخر ومطبخ مجهز وغرف نوم بحمامات خاصة، على بعد دقائق من النادي والبوابة الرئيسية.</p><p>خطة سداد مرنة بمقدم 10% على 8 سنوات.</p>',
                'gallery_id' => Gallery::query()->when($project, fn ($query) => $query->where('project_id', $project->id))->orderBy('id')->value('id'),
                'video_url' => self::VIDEO,
                ...$this->meta($unit->name_en, $ar),
            ]);
            $this->fullChain($unit);

            foreach (['amenities' => 6, 'views' => 1, 'facility' => 3, 'services' => 4, 'paymentmethod' => 2] as $type => $count) {
                $this->lookupsIfEmpty($unit, $type, $count, $i);
            }

            $photos = $this->rotate($i, 3);
            $this->mediaIfEmpty($unit, Unit::FEATURED_IMAGE, [$photos[0]]);
            $this->mediaIfEmpty($unit, Unit::FLOOR_PLAN, [self::PLAN]);
            $this->mediaIfEmpty($unit, Unit::MASTER_PLAN, [self::PLAN]);
            $this->mediaIfEmpty($unit, Unit::IMAGES_360, [$photos[1]]);
        }
    }
}
