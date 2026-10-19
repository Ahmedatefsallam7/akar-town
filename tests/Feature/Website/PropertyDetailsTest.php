<?php

namespace Tests\Feature\Website;

use App\Support\Website\Image;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\Lookups\Models\Lookup;
use Modules\Projects\Models\Project;
use Modules\Units\Models\Unit;
use Modules\Users\Models\User;
use Tests\TestCase;

/**
 * The property details page (the design's property-details.html).
 */
class PropertyDetailsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();
    }

    private function lookup(string $type, string $name): Lookup
    {
        $root = Lookup::query()->firstOrCreate(['slug' => $type], ['name_en' => $type]);

        return $root->children()->create(['slug' => str($name)->slug(), 'name_en' => $name]);
    }

    public function test_a_published_property_shows_its_details(): void
    {
        $project = Project::factory()->create(['title_en' => 'Katameya Heights']);
        $unit = Unit::factory()->create([
            'name_en' => 'Serenity Villa',
            'project_id' => $project->id,
            'property_type_id' => $this->lookup('unittype', 'Standalone Villa')->id,
            'finishing_type_id' => $this->lookup('finishingtype', 'Premium')->id,
            'ownership_type_id' => $this->lookup('ownershiptype', 'Freehold')->id,
            'bedrooms' => 5,
            'bathrooms' => 4,
            'floor' => 0,
            'parking_spaces' => 2,
            'building_floors' => 2,
            'building_year' => 2024,
            'area' => 420,
            'price' => 12500000,
            'delivery_date' => '2025-08-01',
            'down_payment' => 20,
            'installment_years_from' => 5,
            'installment_years_to' => 7,
            'latitude' => 30.04,
            'longitude' => 31.4,
            'description_en' => '<p>First.</p><p>Second.</p><p>Hidden third.</p><script>alert(1)</script>',
        ]);
        $unit->lookupsOfType('amenities')->sync([$this->lookup('amenities', 'Private Pool')->id]);
        $unit->lookupsOfType('facility')->sync([$this->lookup('facility', 'Golf Club')->id]);

        $this->get(route('properties.show', $unit->slug))
            ->assertOk()
            ->assertSee(['Serenity Villa', 'Katameya Heights', 'Standalone Villa', 'Premium', 'Q3 2025', 'Freehold', '2024', 'G+2', 'Parking', '12,500,000', '29,762', 'Private Pool', 'Golf Club'])
            ->assertSee(['20%', '80%', 'Over 5 – 7 years', 'Payment Plan', 'x-ref="map"'], false)
            // The description after two paragraphs waits for "Read More"; scripts are removed.
            ->assertSeeInOrder(['First.', 'Second.', 'Read More'])
            ->assertSee('Hidden third.')
            // Ground floor (0) is still shown.
            ->assertSeeInOrder(['Floor', '<span data-pdf-val class="text-[14px] font-semibold text-ink">0</span>'], false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_the_units_own_floor_plan_and_master_plan_show_only_when_it_has_them(): void
    {
        Storage::fake('public');
        $project = Project::factory()->create();
        $project->addMedia(UploadedFile::fake()->image('project-master.png'))->toMediaCollection(Project::MASTER_PLAN);
        $unit = Unit::factory()->create(['project_id' => $project->id]);
        $other = Unit::factory()->create(['project_id' => $project->id]);
        $unit->addMedia(UploadedFile::fake()->image('floor.png'))->toMediaCollection(Unit::FLOOR_PLAN);
        $unit->addMedia(UploadedFile::fake()->image('unit-master.png'))->toMediaCollection(Unit::MASTER_PLAN);

        $this->get(route('properties.show', $unit->slug))
            ->assertOk()
            ->assertSeeInOrder(['Floor Plan', 'floor.png', 'Master Plan', 'unit-master.png'])
            ->assertDontSee('project-master.png');

        // Without plans of its own, neither section shows (not even the project's master plan).
        $this->get(route('properties.show', $other->slug))
            ->assertOk()
            ->assertDontSee(['View floor plan fullscreen', 'View master plan fullscreen', 'project-master.png']);
    }

    public function test_a_property_without_photos_shows_the_placeholder_and_no_photo_counter(): void
    {
        $unit = Unit::factory()->create();

        $this->get(route('properties.show', $unit->slug))
            ->assertOk()
            ->assertSee(Image::placeholder())
            ->assertDontSee('Open gallery fullscreen');
    }

    public function test_unpublished_or_unknown_properties_are_not_found(): void
    {
        $draft = Unit::factory()->create(['is_published' => false]);

        $this->get(route('properties.show', $draft->slug))->assertNotFound();
        $this->get(route('properties.show', 'nothing-here'))->assertNotFound();
    }

    public function test_similar_properties_put_the_same_project_first_and_skip_drafts(): void
    {
        $project = Project::factory()->create();
        $unit = Unit::factory()->create(['name_en' => 'Main Unit', 'project_id' => $project->id]);
        Unit::factory()->create(['name_en' => 'Same Project Unit', 'project_id' => $project->id]);
        Unit::factory()->create(['name_en' => 'Other Unit']);
        Unit::factory()->create(['name_en' => 'Draft Unit', 'project_id' => $project->id, 'is_published' => false]);

        $this->get(route('properties.show', $unit->slug))
            ->assertOk()
            ->assertSee('Similar Properties')
            ->assertSeeInOrder(['Similar Properties', 'Same Project Unit', 'Other Unit'])
            ->assertDontSee('Draft Unit');
    }

    public function test_the_agent_card_shows_the_units_agent_or_its_projects(): void
    {
        $agent = User::factory()->create(['first_name' => 'Ahmed', 'last_name' => 'Hassan', 'phone' => '01001234567', 'job_title_en' => 'Senior Property Consultant', 'whatsapp' => '+20 100 765 4321', 'rating' => 4.8, 'reviews_count' => 48]);
        $advisor = User::factory()->create(['first_name' => 'Mona', 'last_name' => 'Adel']);
        $project = Project::factory()->create(['agent_id' => $advisor->id]);
        $own = Unit::factory()->create(['project_id' => $project->id, 'agent_id' => $agent->id]);
        $inherits = Unit::factory()->create(['project_id' => $project->id]);

        $this->get(route('properties.show', $own->slug))
            ->assertOk()
            ->assertSee(['Ahmed Hassan', 'Senior Property Consultant', '(48 reviews)', 'Call Agent', 'Send Message', 'Rated 4.8 out of 5'])
            ->assertSee(['href="tel:01001234567"', 'href="https://wa.me/201007654321"'], false)
            ->assertDontSee('Mona Adel');
        $this->get(route('properties.show', $inherits->slug))->assertOk()->assertSee('Mona Adel');

        // A suspended agent (left the company) isn't shown; no agent, no card.
        $advisor->update(['is_suspended' => true]);
        $this->get(route('properties.show', $inherits->slug))->assertOk()->assertDontSee(['Mona Adel', 'Call Agent']);
    }

    public function test_the_page_in_arabic(): void
    {
        $unit = Unit::factory()->create(['name_ar' => 'فيلا الهدوء', 'price' => 1000000]);

        $this->withCookie('locale', 'ar')
            ->get(route('properties.show', $unit->slug))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee(['فيلا الهدوء', 'السعر الابتدائي', 'تحميل PDF']);
    }

    public function test_the_page_runs_the_same_queries_for_few_and_many_similar_properties(): void
    {
        $unit = Unit::factory()->create();
        $count = function () use ($unit) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get(route('properties.show', $unit->slug))->assertOk();

            return count(DB::getQueryLog());
        };

        Unit::factory()->create();
        $this->get(route('properties.show', $unit->slug));
        $few = $count();

        Unit::factory()->count(6)->create();

        $this->assertSame($few, $count(), json_encode(array_count_values(array_column(DB::getQueryLog(), 'query'))));
    }
}
