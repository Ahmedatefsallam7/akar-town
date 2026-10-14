<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Developers\Models\Developer;
use Modules\Galleries\Models\Gallery;
use Modules\Locations\Models\Location;
use Modules\Lookups\Models\Lookup;
use Modules\Projects\Models\Project;
use Modules\Sliders\Models\Slider;
use Modules\Units\Models\Unit;

/**
 * Demo content from the website design (titles, texts and Unsplash photos of the design files), to see and show
 * the website before the real data is entered. Local / demo only: `php artisan db:seed --class=WebsiteDemoSeeder`.
 * Safe to run again (finds what it made by title). Needs internet for the photos.
 */
class WebsiteDemoSeeder extends Seeder
{
    /** The design's home hero slides (index.html). */
    private const HERO_PROJECTS = [
        ['The Pinnacle Residences', 'مساكن القمة', 'Where Sky Meets Luxury', 'حيث تلتقي السماء بالفخامة', 'Cairo', 'photo-1545324418-cc1a3fa10c00'],
        ['Al Noor Villas', 'فلل النور', 'Timeless Living, Reimagined', 'معيشة خالدة بصياغة جديدة', 'New Cairo', 'photo-1512917774080-9991f1c4c750'],
        ['Maaya Heights', 'مرتفعات مايا', 'Elevated Beyond Expectation', 'أعلى من كل توقع', '6th of October', 'photo-1600596542815-ffad4c1539a9'],
        ['Sky Gardens', 'حدائق السماء', 'Nature Meets Architecture', 'حيث تلتقي الطبيعة بالعمارة', 'North Coast', 'photo-1600607687939-ce8a6c25118c'],
        ['The Boulevard', 'البوليفار', 'Live at the Center of Everything', 'عِش في قلب كل شيء', 'Sheikh Zayed', 'photo-1600585154340-be6161a56a0c'],
        ['Azure Coast Estates', 'عقارات الساحل الأزرق', 'Where the Sea Becomes Home', 'حيث يصبح البحر وطناً', 'Ain Sokhna', 'photo-1499793983690-e29da59ef1c2'],
    ];

    /** The design's featured properties (index.html): title, description, place, beds, baths, area, price, status, photos. */
    private const FEATURED_UNITS = [
        ['The Pinnacle Penthouse', "Soaring above New Cairo's skyline, this ultra-luxury penthouse redefines elevated living with panoramic city views, a private rooftop pool terrace, and bespoke handcrafted finishes throughout.", 'New Cairo', 4, 5, 650, 45000000, 'Ready to Move', ['photo-1545324418-cc1a3fa10c00', 'photo-1600566753190-17f0baa2a6c3', 'photo-1600210492493-0946911123ea', 'photo-1600585154526-990dced4db0d', 'photo-1613490493576-7fde63acd811']],
        ['Al Noor Garden Villas', 'Timeless architecture meets lush private landscaping in this exclusive gated community. Each villa offers a private pool, garden atrium, and fully bespoke interior by award-winning designers.', 'Sheikh Zayed', 5, 6, 820, 62500000, 'Ready to Move', ['photo-1512917774080-9991f1c4c750', 'photo-1600596542815-ffad4c1539a9', 'photo-1580587771525-78b9dba3b914', 'photo-1613490493576-7fde63acd811', 'photo-1600210492493-0946911123ea']],
        ['Maaya Heights Tower', 'An architectural statement rising from the heart of 6th October City. Elevated beyond expectation with resort-style amenities, private concierge, and investment-grade returns.', '6th of October', 3, 4, 420, 28000000, 'Off-Plan', ['photo-1600596542815-ffad4c1539a9', 'photo-1545324418-cc1a3fa10c00', 'photo-1499793983690-e29da59ef1c2', 'photo-1580587771525-78b9dba3b914', 'photo-1512917774080-9991f1c4c750']],
        ['Azure Coast Estates', "Where the Mediterranean breeze meets private beach access. Luxury seafront living on Egypt's most coveted coastline, complete with private dock, beach club, and panoramic sea views.", 'Ain Sokhna', 6, 7, 1200, 95000000, 'Ready to Move', ['photo-1499793983690-e29da59ef1c2', 'photo-1613490493576-7fde63acd811', 'photo-1512917774080-9991f1c4c750', 'photo-1600596542815-ffad4c1539a9', 'photo-1545324418-cc1a3fa10c00']],
        ['Sky Gardens Residences', "Suspended greenery and sky-high living converge at Egypt's first vertical garden tower. Each residence features floor-to-ceiling glazing and a private planted sky terrace.", 'North Coast', 3, 3, 380, 22500000, 'Off-Plan', ['photo-1580587771525-78b9dba3b914', 'photo-1545324418-cc1a3fa10c00', 'photo-1600596542815-ffad4c1539a9', 'photo-1499793983690-e29da59ef1c2', 'photo-1600210492493-0946911123ea']],
    ];

    /** The design's "Our Projects" cards (index.html): title, place, starting price, photos (main first). */
    private const GRID_PROJECTS = [
        ['Lumière Penthouse', 'New Cairo', 85000000, ['photo-1613490493576-7fde63acd811', 'photo-1600566753190-17f0baa2a6c3', 'photo-1600210492493-0946911123ea', 'photo-1600585154526-990dced4db0d']],
        ['The Crown Tower', 'Cairo', 62000000, ['photo-1512917774080-9991f1c4c750', 'photo-1600596542815-ffad4c1539a9', 'photo-1580587771525-78b9dba3b914']],
        ['Meridian Residence', '6th of October', 28000000, ['photo-1600596542815-ffad4c1539a9', 'photo-1545324418-cc1a3fa10c00', 'photo-1499793983690-e29da59ef1c2']],
        ['Aqua Terrace', 'North Coast', 35000000, ['photo-1600585154526-990dced4db0d', 'photo-1512917774080-9991f1c4c750', 'photo-1613490493576-7fde63acd811']],
    ];

    /** The design's project cards (projects.html): status slug, delivery date, description. */
    private const PROJECT_DETAILS = [
        ['off-plan', '2027-10-01', 'A gated sanctuary of 320 garden villas set across 180 acres of landscaped parkland. Private pools, tennis courts, and a dedicated commercial strip.'],
        ['under-construction', '2026-04-01', 'A landmark 38-floor tower redefining vertical luxury in Sheikh Zayed. Sky lounge, panoramic pool deck, and concierge services on demand.'],
        ['off-plan', '2028-07-01', 'Six kilometres of private Mediterranean coastline, 450 chalets and villas, two beach clubs, and a world-class marina.'],
        ['ready-to-move', '2025-06-01', 'A mixed-use urban village anchored by two residential towers, a boutique hotel, retail promenade, and rooftop garden terraces.'],
        ['under-construction', '2027-01-01', 'A crystalline lagoon resort on the Red Sea coast. Floating cabanas, aqua park, private beach strips, and a curated dining village.'],
        ['ready-to-move', '2025-03-01', '500 twin houses and standalone villas set within 240 acres of English-style gardens. Fully operational community with schools, clinics and retail.'],
    ];

    /** The design's "Prime Destinations" cards (index.html): name, description, photo. */
    private const LOCATIONS = [
        ['New Cairo', "Egypt's fastest-growing urban hub — world-class amenities, gated communities.", 'photo-1512917774080-9991f1c4c750'],
        ['North Coast', "Sun-kissed shores along Egypt's Mediterranean edge, steps from the sea.", 'photo-1499793983690-e29da59ef1c2'],
        ['Sheikh Zayed', 'An elegant enclave of spacious living and luxury west of Cairo.', 'photo-1580587771525-78b9dba3b914'],
        ['Ain Sokhna', 'Private Red Sea escapes just 90 minutes from the capital.', 'photo-1600210492493-0946911123ea'],
        ['6th of October', 'Modern infrastructure with room to breathe, grow, and invest.', 'photo-1600596542815-ffad4c1539a9'],
        ['Cairo', 'The heart of Egypt — iconic, historic, and always in demand.', 'photo-1545324418-cc1a3fa10c00'],
    ];

    /** The design's property description (property-details.html). */
    private const DESCRIPTION = '<p>Welcome to an exceptional residence within one of Egypt\'s most prestigious gated communities. Positioned on a generous plot, it offers a rare combination of privacy, elegance, and natural beauty.</p>'
        .'<p>Finished to an exceptional premium standard, it features soaring double-height ceilings, Italian marble flooring throughout the ground floor, and a bespoke open-plan kitchen. Floor-to-ceiling glazing in the main living area opens directly onto a landscaped private garden and sparkling pool.</p>'
        .'<p>Each bedroom is en-suite, with the master suite occupying the entire upper floor west wing, complete with a walk-in dressing room and private terrace overlooking the garden.</p>'
        .'<p>Residents enjoy a 24/7 guarded perimeter, manicured streetscapes, immediate access to premium schools and malls, and direct ring-road connectivity to all major Cairo districts.</p>';

    public function run(): void
    {
        $developer = Developer::query()->firstOrCreate(['name_en' => 'Akar Town Demo Developer'], ['name_ar' => 'مطور تجريبي', 'is_published' => true]);

        $hero = Slider::query()->firstOrCreate(
            ['placement' => Slider::HOME_HERO],
            ['title_en' => 'Home Hero', 'title_ar' => 'الواجهة الرئيسية', 'content_type' => 'projects', 'order' => 1, 'is_active' => true],
        );
        $hero->update(['is_active' => true]);

        foreach (self::HERO_PROJECTS as $order => [$titleEn, $titleAr, $taglineEn, $taglineAr, $place, $photo]) {
            $project = Project::query()->firstOrCreate(['title_en' => $titleEn], [
                'title_ar' => $titleAr,
                'short_description_en' => $taglineEn,
                'short_description_ar' => $taglineAr,
                'developer_id' => $developer->id,
                'is_published' => true,
                'is_featured' => true,
                ...$this->location($place),
            ]);

            if (! $project->hasMedia(Project::FEATURED_IMAGE)) {
                $this->photo($project, $photo, Project::FEATURED_IMAGE);
            }

            $hero->items()->firstOrCreate(['project_id' => $project->id], ['order' => $order + 1, 'is_active' => true]);
        }

        // Newest featured projects fill the home "Our Projects" grid: made last, in reverse, so the first is newest.
        foreach (array_reverse(self::GRID_PROJECTS) as [$title, $place, $price, $photos]) {
            $project = Project::query()->firstOrCreate(['title_en' => $title], [
                'title_ar' => $title,
                'developer_id' => $developer->id,
                'is_published' => true,
                'is_featured' => true,
                'price_from' => $price,
                ...$this->location($place),
            ]);

            if (! $project->hasMedia(Project::FEATURED_IMAGE)) {
                $this->photo($project, $photos[0], Project::FEATURED_IMAGE);
                foreach ($photos as $photo) {
                    $this->photo($project, $photo, Project::GALLERY);
                }
            }
        }

        $titles = [...array_column(self::HERO_PROJECTS, 0), ...array_column(self::GRID_PROJECTS, 0)];
        foreach (Project::query()->whereIn('title_en', $titles)->orderBy('id')->get() as $i => $project) {
            $this->projectDetails($project, self::PROJECT_DETAILS[$i % count(self::PROJECT_DETAILS)]);
        }

        // The design's destinations first: other featured locations move after them.
        Location::query()->whereNotIn('name_en', array_column(self::LOCATIONS, 0))->where('order', '<', 100)->toBase()->increment('order', 100);
        foreach (self::LOCATIONS as $order => [$name, $description, $photo]) {
            $location = Location::query()->firstOrCreate(['name_en' => $name], ['name_ar' => $name, ...$this->location($name)]);
            $location->update(['is_active' => true, 'is_featured' => true, 'order' => $order + 1, 'description_en' => $location->description_en ?: $description]);
            if (! $location->hasMedia(Location::FEATURED_IMAGE)) {
                $this->photo($location, $photo, Location::FEATURED_IMAGE);
            }
        }

        // The design's order of the home blocks.
        foreach (['properties' => 1, 'projects' => 2, 'locations' => 3, 'cta_ribbon' => 4] as $type => $order) {
            Slider::query()->where('placement', Slider::HOME_BODY)->where('content_type', $type)->toBase()->update(['order' => $order]);
        }

        $project = Project::query()->where('title_en', 'The Pinnacle Residences')->first();
        $primary = $this->lookup('offeringtype', 'Primary');
        if ($project && ! $project->hasMedia(Project::MASTER_PLAN)) {
            $this->photo($project, 'photo-1503387762-592deb58ef4e', Project::MASTER_PLAN);
        }

        foreach (self::FEATURED_UNITS as $order => [$name, $description, $place, $beds, $baths, $area, $price, $status, $photos]) {
            $unit = Unit::query()->firstOrCreate(['name_en' => $name], [
                'name_ar' => $name,
                'short_description_en' => $description,
                'is_published' => true,
                'is_featured' => true,
                'bedrooms' => $beds,
                'bathrooms' => $baths,
                'area' => $area,
                'price' => $price,
                'offering_type_id' => $primary,
                'unit_status_id' => $this->lookup('unitstatus', $status),
                ...$this->location($place),
            ]);
            $unit->update(['order' => $order + 1]);

            if (! $unit->hasMedia(Unit::FEATURED_IMAGE)) {
                $this->photo($unit, $photos[0], Unit::FEATURED_IMAGE);
            }

            if (! $unit->gallery_id) {
                $gallery = Gallery::query()->create(['name_en' => "{$name} Gallery", 'name_ar' => $name, 'project_id' => $project?->id]);
                foreach (array_slice($photos, 1) as $photo) {
                    $this->photo($gallery, $photo, Gallery::PHOTOS);
                }
                $unit->update(['gallery_id' => $gallery->id]);
            }

            $this->details($unit, $project);
        }
    }

    /**
     * The design's property details page content (Serenity Villa) on a demo unit, where it's still empty.
     */
    private function details(Unit $unit, ?Project $project): void
    {
        foreach ([Unit::FLOOR_PLAN, Unit::MASTER_PLAN] as $plan) {
            if (! $unit->hasMedia($plan)) {
                $this->photo($unit, 'photo-1503387762-592deb58ef4e', $plan);
            }
        }

        if ($unit->description_en) {
            return;
        }

        $unit->update([
            'project_id' => $project?->id,
            'property_type_id' => $this->lookup('unittype', 'Standalone Villa'),
            'finishing_type_id' => $this->lookup('finishingtype', 'Ultra Super Lux'),
            'furnishing_status_id' => $this->lookup('furnishingstatus', 'Partly Furnished'),
            'floor' => 2,
            'parking_spaces' => 2,
            'building_floors' => 2,
            'building_year' => 2024,
            'ownership_type_id' => $this->lookup('ownershiptype', 'Freehold'),
            'delivery_date' => '2027-08-01',
            'latitude' => 30.0131,
            'longitude' => 31.4089,
            'down_payment' => 20,
            'installment_years_from' => 5,
            'installment_years_to' => 7,
            'description_en' => self::DESCRIPTION,
        ]);

        foreach (['amenities' => 6, 'views' => 1, 'facility' => 4] as $type => $count) {
            $unit->lookupsOfType($type)->sync(Lookup::query()
                ->whereIn('parent_id', Lookup::query()->select('id')->whereNull('parent_id')->where('slug', $type))
                ->orderBy('id')->limit($count)->pluck('id'));
        }
    }

    /**
     * The design's project card details (status, delivery, description, amenities) on a demo project still without
     * a status. A hero project keeps its tagline as its short description.
     *
     * @param  array{0: string, 1: string, 2: string}  $details
     */
    private function projectDetails(Project $project, array $details): void
    {
        if ($project->lookupsOfType('projectstatus')->exists()) {
            return;
        }

        [$status, $delivery, $description] = $details;
        $project->update([
            'delivery_date' => $delivery,
            'short_description_en' => $project->short_description_en ?: $description,
        ]);
        $project->lookupsOfType('projectstatus')->sync(array_filter([$this->lookupBySlug('projectstatus', $status)]));
        $project->lookupsOfType('amenities')->sync(Lookup::query()
            ->whereIn('parent_id', Lookup::query()->select('id')->whereNull('parent_id')->where('slug', 'amenities'))
            ->orderBy('order')->limit(4)->pluck('id'));
    }

    private function lookupBySlug(string $type, string $slug): ?int
    {
        return Lookup::query()
            ->whereIn('parent_id', Lookup::query()->select('id')->whereNull('parent_id')->where('slug', $type))
            ->where('slug', $slug)
            ->value('id');
    }

    private function photo($model, string $photo, string $collection): void
    {
        $model->addMediaFromUrl("https://images.unsplash.com/{$photo}?w=1920&auto=format&fit=crop&q=90&fm=jpg")
            ->usingFileName("{$photo}.jpg")
            ->toMediaCollection($collection);
    }

    private function lookup(string $type, string $name): ?int
    {
        return Lookup::query()
            ->whereIn('parent_id', Lookup::query()->select('id')->whereNull('parent_id')->where('slug', $type))
            ->where('name_en', $name)
            ->value('id');
    }

    /**
     * The location chain (country_id …) of a place name in the locations tree; empty when it isn't there.
     *
     * @return array<string, int>
     */
    private function location(string $name): array
    {
        $node = Lookup::query()->where('name_en', $name)->first();
        $chain = [];
        while ($node && $node->parent_id) {
            array_unshift($chain, $node->id);
            $node = Lookup::query()->find($node->parent_id);
        }
        // The first id is the "Locations" root's child (the country) once the root itself is dropped.
        $columns = ['country_id', 'city_id', 'region_id', 'area_id', 'sub_area_id'];

        return array_combine(array_slice($columns, 0, count($chain)), $chain) ?: [];
    }
}
