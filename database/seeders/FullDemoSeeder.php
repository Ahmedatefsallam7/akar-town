<?php

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Careers\Models\Applicant;
use Modules\Careers\Models\Career;
use Modules\Developers\Models\Developer;
use Modules\Galleries\Models\Gallery;
use Modules\GeneralSettings\Settings\AboutUsSettings;
use Modules\GeneralSettings\Settings\BusinessInfoSettings;
use Modules\GeneralSettings\Settings\HomeSettings;
use Modules\Inquiry\Models\Inquiry;
use Modules\Inquiry\Models\Meeting;
use Modules\Locations\Models\Location;
use Modules\Lookups\Models\Lookup;
use Modules\PossibleLeads\Models\PossibleLead;
use Modules\Projects\Models\Project;
use Modules\UnitRequests\Models\UnitRequest;
use Modules\Units\Models\Unit;
use Modules\Units\Models\UnitPriceHistory;
use Modules\Users\Models\User;
use Modules\WebsiteUsers\Models\WebsiteUser;

/**
 * Every admin field filled, to test the admin and the website by hand: runs WebsiteDemoSeeder, then fills each empty
 * field of the existing records (settings, developers, locations, lookups, projects, units, galleries, users) and
 * adds sample records where a module is empty (careers, applicants, leads, website users, possible leads). Only empty
 * values are written, so it is safe to run again. Local / demo only:
 * `php artisan db:seed --class=FullDemoSeeder`. Needs internet the first time (photos are kept in storage/app/demo).
 *
 * Leads are saved without model events: they are NOT sent to the CRM and don't convert real Possible Leads.
 */
class FullDemoSeeder extends Seeder
{
    /** Unsplash photos of the design, used for every image field. */
    private const PHOTOS = [
        'photo-1545324418-cc1a3fa10c00', 'photo-1512917774080-9991f1c4c750', 'photo-1600596542815-ffad4c1539a9',
        'photo-1600607687939-ce8a6c25118c', 'photo-1600585154340-be6161a56a0c', 'photo-1499793983690-e29da59ef1c2',
        'photo-1613490493576-7fde63acd811', 'photo-1600566753190-17f0baa2a6c3', 'photo-1600210492493-0946911123ea',
        'photo-1580587771525-78b9dba3b914', 'photo-1600585154526-990dced4db0d', 'photo-1522708323590-d24dbb6b0267',
    ];

    /** Portraits for the user and agent photos (the first one is the design's agent). */
    private const PORTRAITS = ['photo-1507003211169-0a1dd7228f2d', 'photo-1494790108377-be9c29b29330', 'photo-1500648767791-00dcc994a43e', 'photo-1438761681033-6461ffad8d80'];

    private const PLAN = 'photo-1503387762-592deb58ef4e';

    private const VIDEO = 'https://www.youtube.com/watch?v=ScMzIvxBSi4';

    private const EMBED = '<iframe src="https://www.youtube.com/embed/ScMzIvxBSi4" title="Project video" allowfullscreen></iframe>';

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

    /** Extra units made per project: type name, bedrooms, bathrooms, area, price. */
    private const UNIT_TYPES = [
        ['Apartment', 3, 2, 165, 7800000],
        ['Duplex', 4, 3, 260, 13500000],
        ['Penthouse', 4, 4, 320, 21000000],
        ['Townhouse', 4, 3, 280, 16000000],
        ['Studio', 1, 1, 75, 3200000],
        ['Chalet', 2, 2, 120, 6500000],
    ];

    private ?User $admin = null;

    /** @var array<string, list<int>> lookup type => child ids */
    private array $ids = [];

    public function run(): void
    {
        $this->call(WebsiteDemoSeeder::class);
        $this->admin = User::query()->orderBy('id')->first();

        $this->settings();
        $this->users();
        $this->lookups();
        $this->locations();
        $this->developers();
        $this->projects();
        $this->galleries();
        $this->units();
        $this->careers();
        $this->websiteUsers();
        $this->leads();
        $this->possibleLeads();
        $this->fullChains();

        UnitPriceHistory::query()->whereNull('changed_by')->update(['changed_by' => $this->admin?->id]);
    }

    private function settings(): void
    {
        $business = app(BusinessInfoSettings::class);
        foreach ([
            'social_facebook' => 'https://www.facebook.com/AkarTownEG',
            'social_instagram' => 'https://www.instagram.com/akar.town.real.estate/',
            'social_linkedin' => 'https://www.linkedin.com/company/akar-town/',
            'social_youtube' => 'https://youtube.com/@mohamedelhemaly.akrealestate',
            'social_tiktok' => 'https://www.tiktok.com/@akartown',
            'headquarters_en' => '90th Street, Fifth Settlement, New Cairo, Egypt',
            'headquarters_ar' => 'شارع التسعين، التجمع الخامس، القاهرة الجديدة، مصر',
            'branch_1_en' => 'Mall of Arabia, 6th of October', 'branch_1_ar' => 'مول العرب، السادس من أكتوبر',
            'branch_2_en' => 'Arkan Plaza, Sheikh Zayed', 'branch_2_ar' => 'أركان بلازا، الشيخ زايد',
            'branch_3_en' => 'Marassi, North Coast', 'branch_3_ar' => 'مراسي، الساحل الشمالي',
            'branch_4_en' => 'Porto Sokhna, Ain Sokhna', 'branch_4_ar' => 'بورتو السخنة، العين السخنة',
            'hotline' => '19000',
            'phone_primary' => '01001234567', 'phone_secondary' => '01112345678', 'phone_tertiary' => '01223456789',
            'whatsapp' => '201001234567',
            'email' => 'info@akartown.com',
            'tax_number' => '123-456-789',
        ] as $key => $value) {
            if (blank($business->{$key})) {
                $business->{$key} = $value;
            }
        }
        $business->save();

        // Numbers left at 0 (e.g. the footer's "Years").
        $about = app(AboutUsSettings::class);
        foreach (['years_of_excellence' => 20, 'stats_happy_clients' => 5000, 'stats_properties_sold' => 1200, 'stats_awards_won' => 15, 'stats_commitment' => 100] as $key => $value) {
            $about->{$key} = $about->{$key} ?: $value;
        }
        $about->save();

        $home = app(HomeSettings::class);
        if (blank($home->why_choose_cta_url)) {
            $home->why_choose_cta_url = '/projects';
            $home->save();
        }
    }

    private function users(): void
    {
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

        foreach (User::query()->get() as $i => $user) {
            $this->fillEmpty($user, [
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
    }

    /**
     * Each lookup gets a short description in both languages.
     */
    private function lookups(): void
    {
        foreach (Lookup::query()->whereNotNull('parent_id')->whereNull('description_en')->get() as $lookup) {
            $lookup->update([
                'description_en' => "{$lookup->name_en} — shown in the website filters and on the property pages.",
                'description_ar' => ($lookup->name_ar ?: $lookup->name_en).' — يظهر في فلاتر الموقع وصفحات العقارات.',
            ]);
        }
    }

    private function locations(): void
    {
        foreach (Location::query()->get() as $location) {
            [$lat, $lng, $code, $areas] = self::PLACES[$location->name_en] ?? [30.0444 + $location->id / 100, 31.2357 + $location->id / 100, strtoupper(Str::substr(Str::slug($location->name_en, ''), 0, 3)), ['Downtown', 'Riverside']];
            $this->fillEmpty($location, [
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
    }

    private function developers(): void
    {
        $place = $this->location('New Cairo');
        foreach (Developer::query()->get() as $i => $developer) {
            $ar = $developer->name_ar ?: $developer->name_en;
            $this->fillEmpty($developer, [
                'tagline_en' => 'Building communities that last',
                'tagline_ar' => 'نبني مجتمعات تدوم',
                'short_description_en' => "{$developer->name_en} delivers premium residential and commercial communities across Egypt.",
                'short_description_ar' => "{$ar} تقدم مجتمعات سكنية وتجارية راقية في أنحاء مصر.",
                'description_en' => "<p>{$developer->name_en} has delivered more than 20 projects since 2004, known for on-time handover and high finishing standards.</p><p>Its portfolio covers apartments, villas and mixed-use towns in New Cairo, Sheikh Zayed and the North Coast.</p>",
                'description_ar' => "<p>سلمت {$ar} أكثر من 20 مشروعاً منذ 2004، وتُعرف بالالتزام بمواعيد التسليم وجودة التشطيب.</p><p>تضم مشروعاتها شققاً وفللاً ومدناً متكاملة في القاهرة الجديدة والشيخ زايد والساحل الشمالي.</p>",
                'email' => 'contact@'.(Str::slug($developer->name_en) ?: 'developer'.$developer->id).'.com',
                'phone' => '0120'.str_pad((string) (1000000 + $developer->id), 7, '0', STR_PAD_LEFT),
                'neighbourhood' => 'Fifth Settlement',
                ...($developer->country_id ? [] : $place),
                ...$this->meta($developer->name_en, $ar),
            ]);
            $this->mediaIfEmpty($developer, Developer::LOGO, [], $this->logo($developer->name_en));
            $this->mediaIfEmpty($developer, Developer::FEATURED_IMAGE, [self::PHOTOS[$i % count(self::PHOTOS)]]);
        }
    }

    private function projects(): void
    {
        $agents = $this->agents();
        $egp = $this->children('currencies')['Egyptian Pound'] ?? null;
        foreach (Project::query()->get() as $i => $project) {
            $ar = $project->title_ar ?: $project->title_en;
            $place = $project->country_id ? [] : $this->location(array_keys(self::PLACES)[2 + $i % 5]);
            [$lat, $lng] = array_values(self::PLACES)[2 + $i % 5];
            $this->fillEmpty($project, [
                'project_number' => 'PRJ-'.str_pad((string) $project->id, 4, '0', STR_PAD_LEFT),
                'agent_id' => $agents[$i % count($agents)] ?? null,
                'short_description_en' => 'A gated community of villas and apartments set around landscaped gardens.',
                'short_description_ar' => 'كمبوند مسور من الفلل والشقق وسط حدائق منسقة.',
                'description_en' => "<p>{$project->title_en} is a landmark development set across 150 acres of landscaped parkland, with only 40% of the land built.</p><p>Residents enjoy a clubhouse, pools, a sports club, a commercial strip and 24/7 security, minutes from the main roads.</p><p>Units range from studios to standalone villas, with flexible payment plans and delivery in phases.</p>",
                'description_ar' => "<p>{$ar} مشروع مميز على مساحة 150 فداناً من الحدائق، والمباني 40% فقط من المساحة.</p><p>يتمتع السكان بنادٍ اجتماعي وحمامات سباحة ونادٍ رياضي ومنطقة تجارية وأمن على مدار الساعة، على بعد دقائق من الطرق الرئيسية.</p><p>الوحدات من الاستوديو حتى الفيلا المستقلة، بخطط سداد مرنة وتسليم على مراحل.</p>",
                ...$place,
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

    /**
     * One full gallery per project (photos, floor plans, 360° images, a PDF, a video), plus the existing ones filled.
     */
    private function galleries(): void
    {
        foreach (Project::query()->get() as $project) {
            Gallery::query()->firstOrCreate(['project_id' => $project->id, 'name_en' => "{$project->title_en} Gallery"], ['name_ar' => 'معرض '.($project->title_ar ?: $project->title_en)]);
        }

        foreach (Gallery::query()->get() as $i => $gallery) {
            $this->fillEmpty($gallery, ['video_url' => self::VIDEO]);
            $photos = $this->rotate($i, 4);
            $this->mediaIfEmpty($gallery, Gallery::FEATURED_IMAGE, [$photos[0]]);
            $this->mediaIfEmpty($gallery, Gallery::PHOTOS, $photos);
            $this->mediaIfEmpty($gallery, Gallery::FLOOR_PLANS, [self::PLAN]);
            $this->mediaIfEmpty($gallery, Gallery::IMAGES_360, [$photos[2]]);
            $this->mediaIfEmpty($gallery, Gallery::ATTACHMENTS, [], $this->pdf());
        }
    }

    private function units(): void
    {
        // Every project gets three more units of different types, so the unit tabs, filters and lists have data.
        foreach (Project::query()->get() as $i => $project) {
            foreach ([0, 1, 2] as $n) {
                [$type, $beds, $baths, $area, $price] = self::UNIT_TYPES[($i + $n * 2) % count(self::UNIT_TYPES)];
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

        $agents = $this->agents();
        $first = fn (string $type, int $i) => ($ids = array_values($this->children($type))) ? $ids[$i % count($ids)] : null;
        foreach (Unit::query()->with('project')->get() as $i => $unit) {
            $project = $unit->project;
            $ar = $unit->name_ar ?: $unit->name_en;
            $this->fillEmpty($unit, [
                // Every other unit has its own agent; the rest show their project's.
                'agent_id' => $i % 2 ? ($agents[($i + 1) % max(1, count($agents))] ?? null) : null,
                'offering_type_id' => $first('offeringtype', $i),
                'property_type_id' => $first('unittype', $i),
                'property_usage_id' => $first('propertyusage', 0),
                'finishing_type_id' => $first('finishingtype', $i),
                'furnishing_status_id' => $first('furnishingstatus', $i),
                'unit_status_id' => $first('unitstatus', $i),
                'ownership_type_id' => $first('ownershiptype', $i),
                ...($unit->country_id || ! $project ? [] : array_filter($project->only(['country_id', 'city_id', 'region_id', 'area_id', 'sub_area_id']))),
                'neighbourhood' => 'Fifth Settlement',
                'address' => "Building {$unit->id}, {$project?->address_en}",
                'latitude' => $project?->latitude,
                'longitude' => $project?->longitude,
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
                'gallery_id' => $project ? Gallery::query()->where('project_id', $project->id)->value('id') : null,
                'video_url' => self::VIDEO,
                ...$this->meta($unit->name_en, $ar),
            ]);

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

    private function careers(): void
    {
        $departments = array_values($this->children('department'));
        $place = $this->location('New Cairo');
        foreach ([
            ['Senior Sales Consultant', 'مستشار مبيعات أول', 'full_time', 3],
            ['Marketing Specialist', 'أخصائي تسويق', 'hybrid', 1],
            ['Customer Support Agent', 'موظف خدمة عملاء', 'part_time', 2],
            ['Frontend Developer', 'مطور واجهات أمامية', 'online', 1],
        ] as $i => [$en, $ar, $type, $vacancies]) {
            $career = Career::query()->firstOrCreate(['title_en' => $en], [
                'title_ar' => $ar,
                'type' => $type,
                'vacancies' => $vacancies,
                'department_id' => $departments[$i % max(1, count($departments))] ?? null,
                'closing_date' => now()->addMonths(2)->toDateString(),
                'is_active' => true,
                ...$place,
                'description_en' => "<p>We are looking for a {$en} to join our growing team in New Cairo.</p><p>You will work with a friendly team and a strong portfolio of projects.</p>",
                'description_ar' => "<p>نبحث عن {$ar} للانضمام إلى فريقنا في القاهرة الجديدة.</p><p>ستعمل مع فريق متعاون ومحفظة قوية من المشروعات.</p>",
                'responsibilities' => [['en' => 'Meet clients and understand their needs', 'ar' => 'مقابلة العملاء وفهم احتياجاتهم'], ['en' => 'Follow up leads until closing', 'ar' => 'متابعة العملاء المحتملين حتى إتمام البيع']],
                'requirements' => [['en' => '2+ years of experience', 'ar' => 'خبرة سنتين على الأقل'], ['en' => 'Good English and Arabic', 'ar' => 'إجادة الإنجليزية والعربية']],
                'show_salary' => true,
                'salary_from' => 15000 + $i * 5000,
                'salary_to' => 25000 + $i * 5000,
                'salary_currency' => 'EGP',
                ...$this->meta($en, $ar),
            ]);
            $this->lookupsIfEmpty($career, 'benefits', 3, $i);

            foreach (array_slice(Applicant::STATUSES, $i % 2, 2) as $n => $status) {
                $email = Str::slug($en)."-{$n}@example.com";
                if (Applicant::query()->where('email', $email)->exists()) {
                    continue;
                }
                $applicant = Applicant::query()->createQuietly([
                    'career_id' => $career->id,
                    'name' => ['Sara Ali', 'Youssef Adel', 'Nour Mahmoud', 'Karim Samir'][($i + $n) % 4],
                    'email' => $email,
                    'phone' => '0101'.str_pad((string) ($i * 10 + $n), 7, '0', STR_PAD_LEFT),
                    'status' => $status,
                    'gender' => Applicant::GENDERS[$n % 2],
                    'linkedin_profile' => 'https://www.linkedin.com/in/demo-applicant-'.($i * 10 + $n),
                    'cover_letter' => "I'd love to join Akar Town as a {$en}. I have relevant experience and I'm ready to start soon.",
                ]);
                $applicant->addMedia($this->pdf())->preservingOriginal()->usingFileName('resume.pdf')->toMediaCollection(Applicant::RESUME, Applicant::RESUME_DISK);
            }
        }
    }

    private function websiteUsers(): void
    {
        foreach ([
            ['Hana Mostafa', 'hana@example.com', '01005550001', 'email', 'en', WebsiteUser::ACTIVE],
            ['Tarek Fawzy', 'tarek@example.com', '01005550002', 'phone', 'ar', WebsiteUser::ACTIVE],
            ['Laila Ibrahim', 'laila@example.com', '01005550003', 'google', 'en', WebsiteUser::ACTIVE],
            ['Mahmoud Saad', 'mahmoud@example.com', '01005550004', 'apple', 'ar', WebsiteUser::BLOCKED],
        ] as $i => [$name, $email, $phone, $method, $language, $status]) {
            $user = WebsiteUser::query()->firstOrCreate(['email' => $email], [
                'name' => $name,
                'phone' => $phone,
                'registration_method' => $method,
                'preferred_language' => $language,
                'status' => $status,
                'password' => '123456789',
                'admin_notes' => $status === WebsiteUser::BLOCKED ? 'Blocked after repeated fake requests.' : 'Interested in New Cairo villas.',
            ]);
            $user->forceFill(array_filter([
                'email_verified_at' => $user->email_verified_at ?? now()->subDays(30),
                'phone_verified_at' => $user->phone_verified_at ?? now()->subDays(30),
                'last_login_at' => $user->last_login_at ?? now()->subDays($i),
                'avatar' => $user->avatar ?? $this->avatar('website-users', $i),
            ]))->save();
        }
    }

    /**
     * Sample leads of every kind (saved quietly: not sent to the CRM).
     */
    private function leads(): void
    {
        $users = WebsiteUser::query()->pluck('id')->all();
        $people = [['Sara Ali', 'sara.lead@example.com', '01001234561'], ['Ahmed Nabil', 'ahmed.lead@example.com', '01001234562'], ['Mariam Hany', 'mariam.lead@example.com', '01001234563'], ['Hassan Zaki', 'hassan.lead@example.com', '01001234564']];

        if (! Inquiry::query()->exists()) {
            foreach (Inquiry::FORMS as $i => $form) {
                foreach ([0, 1] as $n) {
                    [$name, $email, $phone] = $people[($i + $n) % 4];
                    Inquiry::query()->createQuietly([
                        'website_user_id' => $n ? ($users[$i % max(1, count($users))] ?? null) : null,
                        'visitor_id' => (string) Str::uuid(),
                        'full_name' => $name,
                        'email' => str_replace('@', "+{$form}{$n}@", $email),
                        'phone' => $phone,
                        'message' => "Hello, I'd like more details and the payment plan, please.",
                        'form' => $form,
                        'source' => 'Website',
                        'url' => url('/'),
                    ]);
                }
            }
        }

        if (! Meeting::query()->exists()) {
            foreach (Meeting::STATUSES as $i => $status) {
                [$name, $email, $phone] = $people[$i % 4];
                Meeting::query()->createQuietly([
                    'website_user_id' => $users[$i % max(1, count($users))] ?? null,
                    'visitor_id' => (string) Str::uuid(),
                    'name' => $name,
                    'phone' => $phone,
                    'email' => $email,
                    'meeting_at' => now()->addDays($i - 2)->setTime(11 + $i, 0),
                    'type' => Meeting::TYPES[$i % count(Meeting::TYPES)],
                    'status' => $status,
                    'source' => 'Property page',
                    'url' => url('/properties'),
                    'notes_en' => 'Prefers a morning visit.',
                    'notes_ar' => 'يفضل الزيارة صباحاً.',
                    'admin_notes_en' => 'Call one day before to confirm.',
                    'admin_notes_ar' => 'الاتصال قبلها بيوم للتأكيد.',
                ]);
            }
        }

        if (! UnitRequest::query()->exists()) {
            $place = $this->location('Sheikh Zayed');
            foreach (UnitRequest::STATUSES as $i => $status) {
                [$name, $email, $phone] = $people[$i % 4];
                $request = UnitRequest::query()->createQuietly([
                    'website_user_id' => $users[$i % max(1, count($users))] ?? null,
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
                ]);
                foreach (UnitRequest::LOOKUPS as $type) {
                    $this->lookupsIfEmpty($request, $type, 2, $i);
                }
                $this->mediaIfEmpty($request, UnitRequest::FEATURED_IMAGE, [self::PHOTOS[$i % count(self::PHOTOS)]]);
            }
        }
    }

    private function possibleLeads(): void
    {
        if (PossibleLead::query()->exists()) {
            return;
        }

        $units = Unit::query()->limit(4)->get(['id', 'slug']);
        $projects = Project::query()->limit(4)->get(['id', 'slug']);
        $inquiries = Inquiry::query()->limit(3)->get();
        foreach (range(0, 9) as $i) {
            $unit = $i % 2 ? $units[$i % max(1, $units->count())] ?? null : null;
            $project = $i % 2 ? null : $projects[$i % max(1, $projects->count())] ?? null;
            $converted = $i < 3 ? ($inquiries[$i] ?? null) : null;
            $lead = new PossibleLead([
                'click_type' => PossibleLead::TYPES[$i % 3],
                'page_url' => $unit ? route('properties.show', $unit->slug) : ($project ? route('projects.show', $project->slug) : url('/')),
                'unit_id' => $unit?->id,
                'project_id' => $project?->id,
                'visitor_id' => $converted?->visitor_id ?? (string) Str::uuid(),
                'website_user_id' => $converted?->website_user_id,
            ]);
            $lead->forceFill([
                'converted_lead_type' => $converted?->getMorphClass(),
                'converted_lead_id' => $converted?->id,
                'converted_at' => $converted ? now()->subHours($i) : null,
                'created_at' => now()->subDays($i)->subHours(2),
            ])->saveQuietly();
        }
    }

    /**
     * Records placed only down to a city or region get the next levels too (the first child each time), so the area
     * and sub-area fields have data. The Locations records stay as they are: each one is the place itself.
     */
    private function fullChains(): void
    {
        $columns = ['country_id', 'city_id', 'region_id', 'area_id', 'sub_area_id'];
        foreach ([Project::class, Unit::class, Developer::class, Career::class, UnitRequest::class] as $class) {
            foreach ($class::query()->whereNull('sub_area_id')->whereNotNull('country_id')->get() as $record) {
                $changes = [];
                $parent = collect($columns)->map(fn (string $column) => $record->{$column})->filter()->last();
                foreach ($columns as $column) {
                    if ($record->{$column} !== null) {
                        continue;
                    }
                    $parent = Lookup::query()->where('parent_id', $parent)->orderBy('order')->orderBy('id')->value('id');
                    if (! $parent) {
                        break;
                    }
                    $changes[$column] = $parent;
                }
                $changes && $record->updateQuietly($changes);
            }
        }
    }

    /**
     * @return list<int> ids of the admin users that can be agents (not suspended)
     */
    private function agents(): array
    {
        return User::query()->where('is_suspended', false)->orderBy('id')->pluck('id')->all();
    }

    /**
     * Sets the given values only where the record's field is still empty, then saves.
     *
     * @param  array<string, mixed>  $values
     */
    private function fillEmpty(Model $model, array $values): void
    {
        foreach ($values as $key => $value) {
            $current = $model->getAttribute($key);
            if (($current === null || $current === '' || $current === []) && $value !== null) {
                $model->setAttribute($key, $value);
            }
        }
        $model->save();
    }

    private function lookupsIfEmpty(Model $model, string $type, int $count, int $offset): void
    {
        if ($model->lookupsOfType($type)->exists() || ! ($ids = array_values($this->children($type)))) {
            return;
        }

        $picked = [];
        for ($n = 0; $n < min($count, count($ids)); $n++) {
            $picked[] = $ids[($offset + $n) % count($ids)];
        }
        $model->lookupsOfType($type)->sync($picked);
    }

    /**
     * @param  list<string>  $photos  Unsplash ids
     */
    private function mediaIfEmpty(Model $model, string $collection, array $photos, ?string $file = null): void
    {
        if ($model->hasMedia($collection)) {
            return;
        }

        foreach ($file ? [$file] : array_map(fn (string $photo) => $this->photo($photo), $photos) as $path) {
            $model->addMedia($path)->preservingOriginal()->toMediaCollection($collection);
        }
    }

    /**
     * A photo kept in storage/app/demo after its first download, so runs after the first are offline and fast.
     */
    private function photo(string $id): string
    {
        $path = storage_path("app/demo/{$id}.jpg");
        if (! is_file($path)) {
            @mkdir(dirname($path), 0775, true);
            file_put_contents($path, Http::timeout(60)->get("https://images.unsplash.com/{$id}?w=1600&auto=format&fit=crop&q=85&fm=jpg")->throw()->body());
        }

        return $path;
    }

    /**
     * A square logo with the name's initials (white on the brand red).
     */
    private function logo(string $name): string
    {
        $letters = collect(preg_split('/\s+/', trim($name)))->filter()->take(2)->map(fn (string $word) => strtoupper($word[0]))->implode('');
        $path = storage_path("app/demo/logo-{$letters}.png");
        if (! is_file($path)) {
            @mkdir(dirname($path), 0775, true);
            $image = imagecreatetruecolor(400, 400);
            imagefill($image, 0, 0, imagecolorallocate($image, 200, 16, 46));
            $tile = imagecreatetruecolor(20, 16);
            imagefill($tile, 0, 0, imagecolorallocate($tile, 200, 16, 46));
            imagestring($tile, 5, 1, 0, $letters, imagecolorallocate($tile, 255, 255, 255));
            imagecopyresized($image, $tile, 50, 90, 0, 0, 300, 240, 20, 16);
            imagepng($image, $path);
        }

        return $path;
    }

    /**
     * A one-page PDF (resumes and gallery attachments).
     */
    private function pdf(): string
    {
        $path = storage_path('app/demo/demo.pdf');
        if (! is_file($path)) {
            @mkdir(dirname($path), 0775, true);
            $text = 'BT /F1 24 Tf 72 720 Td (Akar Town demo document) Tj ET';
            $objects = ['<< /Type /Catalog /Pages 2 0 R >>', '<< /Type /Pages /Kids [3 0 R] /Count 1 >>', '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>', '<< /Length '.strlen($text)." >>\nstream\n{$text}\nendstream", '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>'];
            $pdf = "%PDF-1.4\n";
            $offsets = [];
            foreach ($objects as $n => $object) {
                $offsets[] = strlen($pdf);
                $pdf .= ($n + 1)." 0 obj\n{$object}\nendobj\n";
            }
            $xref = strlen($pdf);
            $pdf .= 'xref
0 '.(count($objects) + 1)."\n0000000000 65535 f \n".implode('', array_map(fn ($offset) => sprintf("%010d 00000 n \n", $offset), $offsets));
            $pdf .= 'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
            file_put_contents($path, $pdf);
        }

        return $path;
    }

    /**
     * A portrait copied to the public disk; returns its path there.
     */
    private function avatar(string $directory, int $i): string
    {
        $id = self::PORTRAITS[$i % count(self::PORTRAITS)];
        $target = "{$directory}/demo-{$id}.jpg";
        if (! Storage::disk('public')->exists($target)) {
            // A 512×512 square, as the admin's avatar field saves it.
            $photo = imagecreatefromjpeg($this->photo($id));
            $side = min(imagesx($photo), imagesy($photo));
            $square = imagecreatetruecolor(512, 512);
            imagecopyresampled($square, $photo, 0, 0, intdiv(imagesx($photo) - $side, 2), intdiv(imagesy($photo) - $side, 2), 512, 512, $side, $side);
            ob_start();
            imagejpeg($square, null, 82);
            Storage::disk('public')->put($target, ob_get_clean());
        }

        return $target;
    }

    /**
     * @return array<string, mixed> SEO fields in both languages
     */
    private function meta(string $en, string $ar): array
    {
        return [
            'meta_title_en' => "{$en} | Akar Town",
            'meta_title_ar' => "{$ar} | عقار تاون",
            'meta_description_en' => "Discover {$en} with Akar Town: prices, photos, payment plans and location.",
            'meta_description_ar' => "اكتشف {$ar} مع عقار تاون: الأسعار والصور وخطط السداد والموقع.",
            'meta_keywords_en' => ['real estate', 'Egypt', $en],
            'meta_keywords_ar' => ['عقارات', 'مصر', $ar],
        ];
    }

    /**
     * @return list<string> $count photos starting at a different one for each record
     */
    private function rotate(int $offset, int $count): array
    {
        return array_map(fn (int $n) => self::PHOTOS[($offset + $n) % count(self::PHOTOS)], range(0, $count - 1));
    }

    /**
     * @return array<string, int> child name_en => id of a lookup type
     */
    private function children(string $type): array
    {
        return $this->ids[$type] ??= Lookup::query()
            ->whereIn('parent_id', Lookup::query()->select('id')->whereNull('parent_id')->where('slug', $type))
            ->orderBy('order')->orderBy('id')
            ->pluck('id', 'name_en')
            ->all();
    }

    /**
     * The location chain (country_id …) of a place name; empty when it isn't in the tree.
     *
     * @return array<string, int>
     */
    private function location(string $name): array
    {
        $node = Lookup::query()->where('name_en', $name)->whereNotNull('parent_id')->first();
        $chain = [];
        while ($node && $node->parent_id) {
            array_unshift($chain, $node->id);
            $node = Lookup::query()->find($node->parent_id);
        }
        $columns = ['country_id', 'city_id', 'region_id', 'area_id', 'sub_area_id'];

        return array_combine(array_slice($columns, 0, count($chain)), $chain) ?: [];
    }
}
