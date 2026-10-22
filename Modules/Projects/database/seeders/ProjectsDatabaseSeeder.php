<?php

namespace Modules\Projects\Database\Seeders;

use Database\Seeders\Concerns\SeedsDemoData;
use Illuminate\Database\Seeder;
use Modules\Developers\Database\Seeders\DevelopersDatabaseSeeder;
use Modules\Developers\Models\Developer;
use Modules\Lookups\Database\Seeders\LookupsDatabaseSeeder;
use Modules\Projects\Models\Project;
use Modules\Users\Database\Seeders\UsersDatabaseSeeder;

/**
 * Demo: the design's projects (the home hero slides and the "Our Projects" cards, with their photos and texts), then
 * every project's empty fields filled (number, agent, texts, place, prices, payment plan, video, SEO, picked lookups,
 * photos, logo, master plan). Local only, see SeedsDemoData. With SEED_SCALE, many more projects (a fifth of it).
 */
class ProjectsDatabaseSeeder extends Seeder
{
    use SeedsDemoData;

    /** The design's home hero slides (index.html): title, Arabic title, tagline, Arabic tagline, place, photo. */
    public const HERO = [
        ['The Pinnacle Residences', 'مساكن القمة', 'Where Sky Meets Luxury', 'حيث تلتقي السماء بالفخامة', 'Cairo', 'photo-1545324418-cc1a3fa10c00'],
        ['Al Noor Villas', 'فلل النور', 'Timeless Living, Reimagined', 'معيشة خالدة بصياغة جديدة', 'New Cairo', 'photo-1512917774080-9991f1c4c750'],
        ['Maaya Heights', 'مرتفعات مايا', 'Elevated Beyond Expectation', 'أعلى من كل توقع', '6th of October', 'photo-1600596542815-ffad4c1539a9'],
        ['Sky Gardens', 'حدائق السماء', 'Nature Meets Architecture', 'حيث تلتقي الطبيعة بالعمارة', 'North Coast', 'photo-1600607687939-ce8a6c25118c'],
        ['The Boulevard', 'البوليفار', 'Live at the Center of Everything', 'عِش في قلب كل شيء', 'Sheikh Zayed', 'photo-1600585154340-be6161a56a0c'],
        ['Azure Coast Estates', 'عقارات الساحل الأزرق', 'Where the Sea Becomes Home', 'حيث يصبح البحر وطناً', 'Ain Sokhna', 'photo-1499793983690-e29da59ef1c2'],
    ];

    /** The design's "Our Projects" cards (index.html): title, place, starting price, photos (main first). */
    private const GRID = [
        ['Lumière Penthouse', 'New Cairo', 85000000, ['photo-1613490493576-7fde63acd811', 'photo-1600566753190-17f0baa2a6c3', 'photo-1600210492493-0946911123ea', 'photo-1600585154526-990dced4db0d']],
        ['The Crown Tower', 'Cairo', 62000000, ['photo-1512917774080-9991f1c4c750', 'photo-1600596542815-ffad4c1539a9', 'photo-1580587771525-78b9dba3b914']],
        ['Meridian Residence', '6th of October', 28000000, ['photo-1600596542815-ffad4c1539a9', 'photo-1545324418-cc1a3fa10c00', 'photo-1499793983690-e29da59ef1c2']],
        ['Aqua Terrace', 'North Coast', 35000000, ['photo-1600585154526-990dced4db0d', 'photo-1512917774080-9991f1c4c750', 'photo-1613490493576-7fde63acd811']],
    ];

    /** The design's project cards (projects.html): status slug, delivery date, description. */
    private const DETAILS = [
        ['off-plan', '2027-10-01', 'A gated sanctuary of 320 garden villas set across 180 acres of landscaped parkland. Private pools, tennis courts, and a dedicated commercial strip.'],
        ['under-construction', '2026-04-01', 'A landmark 38-floor tower redefining vertical luxury in Sheikh Zayed. Sky lounge, panoramic pool deck, and concierge services on demand.'],
        ['off-plan', '2028-07-01', 'Six kilometres of private Mediterranean coastline, 450 chalets and villas, two beach clubs, and a world-class marina.'],
        ['ready-to-move', '2025-06-01', 'A mixed-use urban village anchored by two residential towers, a boutique hotel, retail promenade, and rooftop garden terraces.'],
        ['under-construction', '2027-01-01', 'A crystalline lagoon resort on the Red Sea coast. Floating cabanas, aqua park, private beach strips, and a curated dining village.'],
        ['ready-to-move', '2025-03-01', '500 twin houses and standalone villas set within 240 acres of English-style gardens. Fully operational community with schools, clinics and retail.'],
    ];

    /** Map points of the places the projects are spread over: [latitude, longitude]. */
    private const POINTS = [
        'New Cairo' => [30.0300, 31.4700],
        'Sheikh Zayed' => [30.0444, 30.9833],
        '6th of October' => [29.9285, 30.9188],
        'North Coast' => [30.9500, 28.8000],
        'Ain Sokhna' => [29.6000, 32.3167],
    ];

    private const EMBED = '<iframe src="https://www.youtube.com/embed/ScMzIvxBSi4" title="Project video" allowfullscreen></iframe>';

    public function run(): void
    {
        if (! $this->demo()) {
            return;
        }

        $this->needs(LookupsDatabaseSeeder::class, UsersDatabaseSeeder::class, DevelopersDatabaseSeeder::class);

        $this->designProjects();
        $this->fill();
        $this->bulkProjects();
        $this->stamps('projects');
    }

    /**
     * SEED_SCALE: many more projects, every field filled, spread over the places, developers and agents.
     */
    private function bulkProjects(): void
    {
        $chains = $this->chains();
        $developers = Developer::query()->orderBy('id')->pluck('id')->all() ?: [null];
        $agents = $this->agents() ?: [null];
        $currency = $this->children('currencies')['Egyptian Pound'] ?? null;
        $now = now();

        $this->bulk('projects', 'slug', $this->volume(0.2), fn (int $n) => $chains[$n % count($chains)] + [
            'title_en' => "Bulk Project {$n}",
            'title_ar' => "مشروع {$n}",
            'slug' => "bulk-project-{$n}",
            'project_number' => "BLK-{$n}",
            'developer_id' => $developers[$n % count($developers)],
            'agent_id' => $agents[$n % count($agents)],
            'short_description_en' => 'A gated community of villas and apartments set around landscaped gardens.',
            'short_description_ar' => 'كمبوند مسور من الفلل والشقق وسط حدائق منسقة.',
            'description_en' => "<p>Bulk Project {$n} is a gated development with a clubhouse, pools and 24/7 security.</p>",
            'description_ar' => "<p>مشروع {$n} كمبوند مسور به نادٍ اجتماعي وحمامات سباحة وأمن على مدار الساعة.</p>",
            'neighbourhood' => 'Fifth Settlement',
            'latitude' => 30 + ($n % 100) / 100,
            'longitude' => 31 + ($n % 100) / 100,
            'address_en' => "Plot {$n}, 90th Street",
            'address_ar' => "قطعة {$n}، شارع التسعين",
            'currency_id' => $currency,
            'price_from' => 1_000_000 + ($n % 200) * 50_000,
            'price_to' => 3_000_000 + ($n % 200) * 80_000,
            'area_from' => 60 + $n % 300,
            'area_to' => 200 + $n % 400,
            'down_payment' => 5 + $n % 20,
            'installment_years_from' => 3 + $n % 4,
            'installment_years_to' => 7 + $n % 4,
            'delivery_date' => $now->copy()->addDays($n % 1500)->toDateString(),
            'is_published' => $n % 10 !== 0,
            'is_featured' => $n % 20 === 0,
            'video_embed' => self::EMBED,
            ...$this->bulkMeta("Bulk Project {$n}", "مشروع {$n}"),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->bulkLookups(Project::class, 'projects', 'slug', ['amenities' => 4, 'views' => 1, 'projectstatus' => 1, 'unittype' => 2, 'paymentmethod' => 1]);
    }

    /**
     * The design's hero and "Our Projects" projects, featured, with their photos and card details.
     */
    private function designProjects(): void
    {
        $developer = Developer::query()->where('is_published', true)->orderBy('id')->value('id');

        foreach (self::HERO as [$titleEn, $titleAr, $taglineEn, $taglineAr, $place, $photo]) {
            $project = Project::query()->firstOrCreate(['title_en' => $titleEn], [
                'title_ar' => $titleAr,
                'short_description_en' => $taglineEn,
                'short_description_ar' => $taglineAr,
                'developer_id' => $developer,
                'is_published' => true,
                'is_featured' => true,
                ...$this->location($place),
            ]);
            $this->mediaIfEmpty($project, Project::FEATURED_IMAGE, [$photo]);
        }

        // Newest featured projects fill the home "Our Projects" grid: made last, in reverse, so the first is newest.
        foreach (array_reverse(self::GRID) as [$title, $place, $price, $photos]) {
            $project = Project::query()->firstOrCreate(['title_en' => $title], [
                'title_ar' => $title,
                'developer_id' => $developer,
                'is_published' => true,
                'is_featured' => true,
                'price_from' => $price,
                ...$this->location($place),
            ]);
            $this->mediaIfEmpty($project, Project::FEATURED_IMAGE, [$photos[0]]);
            $this->mediaIfEmpty($project, Project::GALLERY, $photos);
        }

        // The design's card details (status, delivery, description) on the projects still without a status.
        $titles = [...array_column(self::HERO, 0), ...array_column(self::GRID, 0)];
        foreach (Project::query()->whereIn('title_en', $titles)->orderBy('id')->get() as $i => $project) {
            if ($project->lookupsOfType('projectstatus')->exists()) {
                continue;
            }
            [$status, $delivery, $description] = self::DETAILS[$i % count(self::DETAILS)];
            $project->update(['delivery_date' => $delivery, 'short_description_en' => $project->short_description_en ?: $description]);
            $project->lookupsOfType('projectstatus')->sync(array_filter([$this->lookup('projectstatus', $status)]));
        }
    }

    /**
     * Every project's empty fields.
     */
    private function fill(): void
    {
        $agents = $this->agents();
        $developers = Developer::query()->where('slug', 'not like', 'bulk-%')->orderBy('id')->pluck('id')->all();
        $egp = $this->children('currencies')['Egyptian Pound'] ?? null;

        foreach (Project::query()->where('slug', 'not like', 'bulk-%')->get() as $i => $project) {
            $ar = $project->title_ar ?: $project->title_en;
            $placeName = array_keys(self::POINTS)[$i % count(self::POINTS)];
            [$lat, $lng] = self::POINTS[$placeName];
            $this->fillEmpty($project, [
                'project_number' => 'PRJ-'.str_pad((string) $project->id, 4, '0', STR_PAD_LEFT),
                'developer_id' => $developers ? $developers[$i % count($developers)] : null,
                'agent_id' => $agents ? $agents[$i % count($agents)] : null,
                'short_description_en' => 'A gated community of villas and apartments set around landscaped gardens.',
                'short_description_ar' => 'كمبوند مسور من الفلل والشقق وسط حدائق منسقة.',
                'description_en' => "<p>{$project->title_en} is a landmark development set across 150 acres of landscaped parkland, with only 40% of the land built.</p><p>Residents enjoy a clubhouse, pools, a sports club, a commercial strip and 24/7 security, minutes from the main roads.</p><p>Units range from studios to standalone villas, with flexible payment plans and delivery in phases.</p>",
                'description_ar' => "<p>{$ar} مشروع مميز على مساحة 150 فداناً من الحدائق، والمباني 40% فقط من المساحة.</p><p>يتمتع السكان بنادٍ اجتماعي وحمامات سباحة ونادٍ رياضي ومنطقة تجارية وأمن على مدار الساعة، على بعد دقائق من الطرق الرئيسية.</p><p>الوحدات من الاستوديو حتى الفيلا المستقلة، بخطط سداد مرنة وتسليم على مراحل.</p>",
                ...($project->country_id ? [] : $this->place($placeName, $i)),
                'neighbourhood' => 'Fifth Settlement',
                'latitude' => $lat + $i / 200,
                'longitude' => $lng + $i / 200,
                'address_en' => "Plot {$project->id}, 90th Street",
                'address_ar' => "قطعة {$project->id}، شارع التسعين",
                'currency_id' => $egp,
                'price_from' => 3200000,
                'price_to' => 21000000,
                'area_from' => 75,
                'area_to' => 320,
                'down_payment' => 10,
                'installment_years_from' => 6,
                'installment_years_to' => 8,
                'delivery_date' => now()->addYears(2)->startOfQuarter()->toDateString(),
                'video_embed' => self::EMBED,
                ...$this->meta($project->title_en, $ar),
            ]);
            $this->fullChain($project);

            foreach (['amenities' => 8, 'facility' => 3, 'unittype' => 4, 'propertyusage' => 1, 'services' => 4, 'projectstatus' => 1, 'views' => 2, 'finishingtype' => 2, 'paymentmethod' => 2, 'offeringtype' => 1] as $type => $count) {
                $this->lookupsIfEmpty($project, $type, $count, $i);
            }

            $photos = $this->rotate($i, 5);
            $this->mediaIfEmpty($project, Project::FEATURED_IMAGE, [$photos[0]]);
            $this->mediaIfEmpty($project, Project::GALLERY, $photos);
            $this->mediaIfEmpty($project, Project::ABOUT_IMAGE, [$photos[1]]);
            $this->mediaIfEmpty($project, Project::MASTER_PLAN, [self::PLAN]);
            $this->mediaIfEmpty($project, Project::LOGO, [], $this->logo($project->title_en));
        }
    }
}
