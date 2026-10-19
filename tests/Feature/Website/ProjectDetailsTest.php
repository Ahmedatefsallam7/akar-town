<?php

namespace Tests\Feature\Website;

use App\Livewire\Website\ProjectEnquiry;
use App\Support\Website\Image;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Developers\Models\Developer;
use Modules\GeneralSettings\Settings\BusinessInfoSettings;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\Lookups\Models\Lookup;
use Modules\Projects\Models\Project;
use Modules\Units\Models\Unit;
use Modules\Users\Models\User;
use Tests\TestCase;

/**
 * The project details page (the design's project-details.html).
 */
class ProjectDetailsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();
    }

    private function lookup(string $type, string $name, int $order = 0): Lookup
    {
        $root = Lookup::query()->firstOrCreate(['slug' => $type], ['name_en' => $type]);

        return $root->children()->create(['slug' => str($name)->slug(), 'name_en' => $name, 'order' => $order]);
    }

    public function test_a_published_project_shows_its_details(): void
    {
        $project = Project::factory()->create([
            'title_en' => 'Azure Heights',
            'short_description_en' => 'A curated collection of luxury residences.',
            'description_en' => '<p>Landmark development.</p><script>alert(1)</script>',
            'developer_id' => Developer::factory()->create(['name_en' => 'Emaar Properties'])->id,
            'area_from' => 65,
            'area_to' => 450,
            'down_payment' => 10,
            'installment_years_to' => 8,
            'delivery_date' => '2027-11-01',
            'address_en' => 'Plot 42',
        ]);
        $project->lookupsOfType('projectstatus')->sync([$this->lookup('projectstatus', 'Off-Plan')->id]);
        $project->lookupsOfType('propertyusage')->sync([$this->lookup('propertyusage', 'Residential')->id]);
        $project->lookupsOfType('amenities')->sync([$this->lookup('amenities', 'Olympic Pool')->id]);
        $project->lookupsOfType('facility')->sync([$this->lookup('facility', 'Cairo Airport')->id]);

        $villa = $this->lookup('unittype', 'Villa', 2);
        $studio = $this->lookup('unittype', 'Studio', 1);
        Unit::factory()->create(['project_id' => $project->id, 'property_type_id' => $villa->id, 'area' => 320, 'price' => 9000000, 'bathrooms' => 4, 'floor' => 1]);
        Unit::factory()->create(['project_id' => $project->id, 'property_type_id' => $villa->id, 'area' => 450, 'price' => 12000000, 'bathrooms' => 5, 'floor' => 2]);
        $sample = Unit::factory()->create(['project_id' => $project->id, 'property_type_id' => $studio->id, 'area' => 65, 'price' => 2100000]);
        $sample->lookupsOfType('amenities')->sync([$this->lookup('amenities', 'Balcony')->id]);
        // Drafts and units without a type: not in the tabs (a typeless published one still counts in the total).
        Unit::factory()->create(['project_id' => $project->id, 'property_type_id' => $villa->id, 'is_published' => false]);
        Unit::factory()->create(['project_id' => $project->id]);

        $this->get(route('projects.show', $project->slug))
            ->assertOk()
            ->assertSee(['Azure Heights', 'Off-Plan', 'Residential', 'By Emaar Properties', 'A curated collection of luxury residences.', 'Landmark development.'])
            // The starting price follows the cheapest unit (kept up to date by the Units module).
            ->assertSee(['Total Units', 'Q4 <em>2027</em>', '<em>2.1</em>M EGP', '65–450', '10<em>%</em>', '8 <em>Yrs</em>'], false)
            // Unit types in the admin's Lookups order, with their ranges, prices and counts.
            ->assertSeeInOrder(['Studio', 'Villa'])
            ->assertSee(['2 configurations · 4 total units', '320–450', '9,000,000 EGP', '4–5', 'Floors 1–2', 'Balcony', '2 units currently available', '1 unit currently available'])
            ->assertSee(['Olympic Pool', '1 Amenity', 'Cairo Airport', 'Plot 42'])
            ->assertSeeLivewire(ProjectEnquiry::class)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_parts_without_data_are_not_shown(): void
    {
        $project = Project::factory()->create(['title_en' => 'Bare Project']);

        $this->get(route('projects.show', $project->slug))
            ->assertOk()
            ->assertSee(['Bare Project', Image::placeholder(), 'Send an Enquiry'])
            ->assertDontSee(['Select Your', 'Designed for the Life', 'Total Units', 'Starting Price', 'Down Payment', 'Installment Plan', 'Talk to Our Sales Team'], false);
    }

    public function test_the_sales_advisor_is_the_projects_agent_with_the_company_numbers_as_fallback(): void
    {
        $agent = User::factory()->create(['first_name' => 'Ahmed', 'last_name' => 'Hassan', 'phone' => '01001234567', 'email' => 'ahmed@akartown.com', 'job_title_en' => 'Senior Sales Consultant']);
        $project = Project::factory()->create(['agent_id' => $agent->id]);

        $this->get(route('projects.show', $project->slug))
            ->assertOk()
            ->assertSee(['Your Sales Advisor', 'Ahmed Hassan', 'AH', 'Senior Sales Consultant', 'ahmed@akartown.com'])
            ->assertSee('href="tel:01001234567"', false);

        $business = app(BusinessInfoSettings::class);
        $business->hotline = '19000';
        $business->save();
        $this->get(route('projects.show', Project::factory()->create()->slug))
            ->assertOk()
            ->assertSee('Talk to Our Sales Team')
            ->assertSee('href="tel:19000"', false)
            ->assertDontSee('Your Sales Advisor');
    }

    public function test_unpublished_or_unknown_projects_are_not_found(): void
    {
        $draft = Project::factory()->create(['is_published' => false]);

        $this->get(route('projects.show', $draft->slug))->assertNotFound();
        $this->get(route('projects.show', 'no-such-project'))->assertNotFound();
    }

    public function test_the_page_in_arabic(): void
    {
        $project = Project::factory()->create(['title_ar' => 'مساكن القمة', 'price_from' => 1000000]);

        $this->withCookie('locale', 'ar')
            ->get(route('projects.show', $project->slug))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee(['مساكن القمة', 'السعر الابتدائي', 'أرسل استفساراً', 'احجز وحدتك']);
    }

    public function test_the_page_runs_the_same_queries_for_few_and_many_units_and_types(): void
    {
        $project = Project::factory()->create();
        $count = function () use ($project) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get(route('projects.show', $project->slug))->assertOk();

            return count(DB::getQueryLog());
        };

        Unit::factory()->create(['project_id' => $project->id, 'property_type_id' => $this->lookup('unittype', 'Villa')->id]);
        $this->get(route('projects.show', $project->slug));
        $few = $count();

        foreach (['Studio', 'Duplex', 'Chalet'] as $name) {
            $type = $this->lookup('unittype', $name);
            Unit::factory()->count(3)->create(['project_id' => $project->id, 'property_type_id' => $type->id]);
        }
        $project->lookupsOfType('amenities')->sync([$this->lookup('amenities', 'Gym')->id, $this->lookup('amenities', 'Spa')->id]);

        $this->assertSame($few, $count(), json_encode(array_count_values(array_column(DB::getQueryLog(), 'query'))));
    }
}
