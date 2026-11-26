<?php

namespace Tests\Feature\Website;

use App\Support\Images\ProtectedImages;
use App\Support\Website\Image;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Galleries\Models\Gallery;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\Lookups\Models\Lookup;
use Modules\Projects\Models\Project;
use Modules\Units\Filament\Resources\UnitResource\Pages\EditUnit;
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
            'land_area' => 420,
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
            ->assertSeeInOrder(['Floor', '<span class="text-[14px] font-semibold text-ink">0</span>'], false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_a_rent_price_shows_its_period_on_the_page_and_the_listing(): void
    {
        // Old payment values saved before it became rent aren't shown: rent has no payment plan or handover.
        $unit = Unit::factory()->create(['is_published' => true, 'price' => 25000, 'rent_period' => 'monthly',
            'down_payment' => 10, 'installment_years_from' => 6, 'delivery_date' => '2028-06-01']);

        $this->get(route('properties.show', $unit->slug))->assertOk()->assertSee('25,000 / month')
            ->assertDontSee(__('Down Payment'))->assertDontSee(__('Handover'))->assertDontSee('Q2 2028');
        $this->get(route('properties'))->assertOk()->assertSee('25,000 / month');
        $this->withCookie('locale', 'ar')->get(route('properties.show', $unit->slug))->assertOk()->assertSee('25,000 / شهريًا');

        $unit->update(['rent_period' => null, 'price' => 3000000]);
        $this->get(route('properties.show', $unit->slug))->assertSee('3,000,000')->assertDontSee('/ month');
    }

    public function test_the_page_has_one_main_heading(): void
    {
        $unit = Unit::factory()->create(['name_en' => 'Sky Penthouse']);

        $html = $this->get(route('properties.show', $unit->slug))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertMatchesRegularExpression('#<h1[^>]*>Sky Penthouse</h1>#', $html);
    }

    public function test_the_pdf_language_picker_links_to_both_download_versions(): void
    {
        $unit = Unit::factory()->create();

        $this->get(route('properties.show', $unit->slug))
            ->assertOk()
            ->assertSee(__('Choose PDF language'))
            ->assertSee(route('properties.pdf', ['slug' => $unit->slug, 'locale' => 'en']), false)
            ->assertSee(route('properties.pdf', ['slug' => $unit->slug, 'locale' => 'ar']), false);
    }

    public function test_the_pdf_downloads_in_english_and_arabic_only_for_a_published_property(): void
    {
        $unit = Unit::factory()->create(['name_en' => 'Sky Penthouse', 'slug' => 'sky-penthouse']);

        foreach (['en', 'ar'] as $locale) {
            $response = $this->get(route('properties.pdf', ['slug' => 'sky-penthouse', 'locale' => $locale]))
                ->assertOk()
                ->assertHeader('Content-Type', 'application/pdf')
                ->assertHeader('Content-Disposition', 'attachment; filename="sky-penthouse-'.$locale.'.pdf"');
            $this->assertStringStartsWith('%PDF-', $response->getContent());
        }

        // The page's language stays as it was.
        $this->assertSame('en', app()->getLocale());

        $this->get('/properties/sky-penthouse/pdf/fr')->assertNotFound();

        $unit->update(['is_published' => false]);
        $this->get(route('properties.pdf', ['slug' => 'sky-penthouse', 'locale' => 'en']))->assertNotFound();
    }

    public function test_the_units_own_floor_plan_and_master_plan_show_only_when_it_has_them(): void
    {
        Storage::fake(ProtectedImages::DISK);
        $project = Project::factory()->create();
        $project->addMedia(UploadedFile::fake()->image('project-master.png'))->toMediaCollection(Project::MASTER_PLAN);
        $unit = Unit::factory()->create(['project_id' => $project->id]);
        $other = Unit::factory()->create(['project_id' => $project->id]);
        $unit->addMedia(UploadedFile::fake()->image('floor.png'))->toMediaCollection(Unit::FLOOR_PLAN);
        $unit->addMedia(UploadedFile::fake()->image('unit-master.png'))->toMediaCollection(Unit::MASTER_PLAN);

        $this->get(route('properties.show', $unit->slug))
            ->assertOk()
            ->assertSeeInOrder(['Floor Plan', 'floor-large.webp', 'Master Plan', 'unit-master-large.webp'])
            ->assertDontSee('project-master');

        // Without plans of its own, neither section shows (not even the project's master plan).
        $this->get(route('properties.show', $other->slug))
            ->assertOk()
            ->assertDontSee(['View floor plan fullscreen', 'View master plan fullscreen', 'project-master']);
    }

    public function test_the_gallery_picked_in_the_admin_shows_on_the_page_even_from_another_project(): void
    {
        // AKAR-54: the property shows the gallery the admin picked, whichever project it belongs to.
        Storage::fake(ProtectedImages::DISK);
        $unit = Unit::factory()->create(['project_id' => Project::factory()->create()->id]);
        $crown = Gallery::factory()->create(['project_id' => Project::factory()->create()->id, 'name_en' => 'The Crown Tower Gallery']);
        $crown->addMedia(UploadedFile::fake()->image('crown-photo.png'))->toMediaCollection(Gallery::PHOTOS);
        $other = Gallery::factory()->create();
        $other->addMedia(UploadedFile::fake()->image('garden-photo.png'))->toMediaCollection(Gallery::PHOTOS);

        $this->actingAs($this->createAdmin());
        Livewire::test(EditUnit::class, ['record' => $unit->getRouteKey()])
            ->fillForm(['gallery_id' => $crown->id])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->get(route('properties.show', $unit->slug))->assertOk()->assertSee('crown-photo')->assertDontSee('garden-photo');

        // Removed and another one picked: the page follows.
        Livewire::test(EditUnit::class, ['record' => $unit->getRouteKey()])
            ->fillForm(['gallery_id' => null])
            ->fillForm(['gallery_id' => $other->id])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->get(route('properties.show', $unit->slug))->assertOk()->assertSee('garden-photo')->assertDontSee('crown-photo');
    }

    public function test_the_gallerys_images_take_the_place_of_the_propertys_own_and_its_own_are_the_fallback(): void
    {
        // User, 2026-11-22: units of one model share their gallery's images; a unit's own upload shows only when the
        // gallery has none of that kind.
        Storage::fake(ProtectedImages::DISK);
        $gallery = Gallery::factory()->create();
        $gallery->addMedia(UploadedFile::fake()->image('gallery-cover.jpg', 1600, 1200))->toMediaCollection(Gallery::FEATURED_IMAGE);
        $gallery->addMedia(UploadedFile::fake()->image('gallery-plan.png', 1600, 1200))->toMediaCollection(Gallery::FLOOR_PLANS);
        $gallery->addMedia(UploadedFile::fake()->image('gallery-tour.jpg', 2000, 1000))->toMediaCollection(Gallery::IMAGES_360);
        $gallery->addMedia(UploadedFile::fake()->create('gallery-brochure.pdf', 10, 'application/pdf'))->toMediaCollection(Gallery::ATTACHMENTS);
        $unit = Unit::factory()->create(['name_en' => 'Twin House 7', 'gallery_id' => $gallery->id]);
        $unit->addMedia(UploadedFile::fake()->image('own-cover.jpg', 1600, 1200))->toMediaCollection(Unit::FEATURED_IMAGE);
        $unit->addMedia(UploadedFile::fake()->image('own-plan.png', 1600, 1200))->toMediaCollection(Unit::FLOOR_PLAN);

        $fresh = fn () => Unit::query()->with(['media', 'gallery.media'])->find($unit->id);
        $this->assertSame('gallery-cover', $fresh()->featuredMedia()->name);
        $this->assertSame(['gallery-plan'], $fresh()->floorPlans()->pluck('name')->all());
        $this->assertSame(['gallery-tour'], $fresh()->images360()->pluck('name')->all());
        $this->assertSame(['gallery-brochure'], $fresh()->attachments()->pluck('name')->all());

        // The page, a card (the listing) and the search show the gallery's cover and plan, not the unit's own.
        $this->get(route('properties.show', $unit->slug))->assertOk()
            ->assertSee(['gallery-cover', 'gallery-plan'])->assertDontSee(['own-cover', 'own-plan']);
        $this->get(route('properties'))->assertOk()->assertSee('gallery-cover')->assertDontSee('own-cover');
        $this->get(route('search', ['q' => 'Twin House']))->assertOk()->assertSee('gallery-cover')->assertDontSee('own-cover');

        // The 360° tour (the viewer's size, a visitor may load it in the page) and the brochure, downloaded.
        $media360 = $fresh()->images360()->first();
        $brochure = $fresh()->attachments()->first();
        $this->get(route('properties.show', $unit->slug))->assertOk()
            ->assertSee(['360° Tour', 'Start the 360° tour', 'Brochure & Files', 'gallery-brochure'])
            ->assertSee(route('properties.file', ['slug' => $unit->slug, 'file' => $brochure->uuid]), false);
        $this->assertTrue($media360->hasGeneratedConversion('pano'));
        $this->withHeaders(['Sec-Fetch-Dest' => 'image', 'Sec-Fetch-Site' => 'same-origin'])->get($media360->getUrl('pano'))->assertOk();
        $this->get(route('properties.file', ['slug' => $unit->slug, 'file' => $brochure->uuid]))->assertOk()->assertDownload('gallery-brochure.pdf');
        // Only this property's files, and only a published property.
        $other = Unit::factory()->create();
        $this->get(route('properties.file', ['slug' => $other->slug, 'file' => $brochure->uuid]))->assertNotFound();
        $unit->update(['is_published' => false]);
        $this->get(route('properties.file', ['slug' => $unit->slug, 'file' => $brochure->uuid]))->assertNotFound();
        $unit->update(['is_published' => true]);

        // A gallery without a cover or a plan: the unit's own show.
        $gallery->clearMediaCollection(Gallery::FEATURED_IMAGE);
        $gallery->clearMediaCollection(Gallery::FLOOR_PLANS);
        $this->get(route('properties.show', $unit->slug))->assertOk()->assertSee(['own-cover', 'own-plan'])->assertDontSee('gallery-cover');
        $this->assertSame('own-cover', $fresh()->featuredMedia()->name);
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
            ->assertSee(['href="tel:+201001234567"', 'href="https://wa.me/201007654321"'], false)
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

    public function test_the_project_and_the_compound_are_separate_rows(): void
    {
        $compound = Lookup::create(['slug' => 'locations', 'name_en' => 'Locations'])
            ->children()->create(['slug' => 'egypt', 'name_en' => 'Egypt'])
            ->children()->create(['slug' => 'cairo', 'name_en' => 'Cairo'])
            ->children()->create(['slug' => 'new-cairo', 'name_en' => 'New Cairo'])
            ->children()->create(['slug' => 'fifth-settlement', 'name_en' => 'Fifth Settlement'])
            ->children()->create(['slug' => 'mivida', 'name_en' => 'Mivida', 'name_ar' => 'ميفيدا']);
        Lookup::fixTree();
        // The project is in the compound; its unit takes the project's place (a unit without a country of its own does).
        $project = Project::factory()->create(['title_en' => 'Mivida Gardens', 'is_published' => true, 'sub_area_id' => $compound->id]);
        $unit = Unit::factory()->create(['project_id' => $project->id]);

        // AKAR-46: "Compound" is the place's last level; the project has its own row.
        $this->get(route('properties.show', $unit->slug))->assertOk()
            ->assertSeeInOrder(['Project', 'Mivida Gardens'])
            ->assertSeeInOrder(['Compound', 'Mivida']);
        $this->withCookie('locale', 'ar')->get(route('properties.show', $unit->slug))->assertOk()
            ->assertSee(['المشروع', 'الكمبوند', 'ميفيدا']);
    }
}
