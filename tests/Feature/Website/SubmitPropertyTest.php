<?php

namespace Tests\Feature\Website;

use App\Jobs\SendLeadToCrm;
use App\Livewire\Website\SubmitProperty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\GeneralSettings\Settings\SubmitPropertySettings;
use Modules\Lookups\Models\Lookup;
use Modules\UnitRequests\Models\UnitRequest;
use Modules\WebsiteUsers\Models\WebsiteUser;
use Tests\TestCase;

/**
 * The navbar's "Submit Property" window (the design's submit-modal.js): saved as a sell request with its photos and
 * sent to the CRM; the side panel shows Settings → Submit Property.
 */
class SubmitPropertyTest extends TestCase
{
    use RefreshDatabase;

    private Lookup $villa;

    private Lookup $egypt;

    private Lookup $newCairo;

    private Lookup $fifth;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Storage::fake('public');
        RateLimiter::clear('submit-property:127.0.0.1');

        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();

        $this->villa = Lookup::create(['slug' => 'unittype', 'name_en' => 'Unit Type'])->children()->create(['slug' => 'villa', 'name_en' => 'Villa', 'name_ar' => 'فيلا']);
        $offering = Lookup::create(['slug' => 'offeringtype', 'name_en' => 'Offering Type']);
        $offering->children()->create(['slug' => 'resale', 'name_en' => 'Resale']);
        $offering->children()->create(['slug' => 'rent', 'name_en' => 'Rent']);
        $places = Lookup::create(['slug' => 'locations', 'name_en' => 'Locations']);
        $this->egypt = $places->children()->create(['slug' => 'egypt', 'name_en' => 'Egypt', 'name_ar' => 'مصر']);
        $this->newCairo = $this->egypt->children()->create(['slug' => 'new-cairo', 'name_en' => 'New Cairo', 'name_ar' => 'القاهرة الجديدة']);
        $this->fifth = $this->newCairo->children()->create(['slug' => 'fifth-settlement', 'name_en' => 'Fifth Settlement', 'name_ar' => 'التجمع الخامس']);
        Lookup::fixTree();
    }

    private function filled(): mixed
    {
        return Livewire::test(SubmitProperty::class)
            ->call('open')
            ->set([
                'fullName' => 'Sara Ali',
                'phone' => '+20 100 123 4567',
                'type' => 'villa',
                'listing' => 'rent',
                'compound' => 'Katameya Heights',
                'place.0' => $this->egypt->id,
                'place.1' => $this->newCairo->id,
                'price' => '4500000',
                'bedrooms' => '3',
                'bathrooms' => '2',
                'unitArea' => '180',
            ]);
    }

    public function test_every_page_has_the_window_and_the_buttons_open_it(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSeeLivewire(SubmitProperty::class)
            ->assertSee("\$dispatch('open-submit-property')", false);
    }

    public function test_the_form_is_only_built_when_the_window_opens(): void
    {
        Livewire::test(SubmitProperty::class)
            ->assertDontSee('Egypt')
            ->call('open')
            ->assertSee(['Egypt', 'Villa', 'For Sale', 'For Rent']);
    }

    public function test_each_level_of_the_tree_opens_under_the_chosen_one_and_a_change_clears_the_ones_below(): void
    {
        $form = Livewire::test(SubmitProperty::class)->call('open')
            ->assertSee('Egypt')->assertDontSee('New Cairo')
            ->set('place.0', $this->egypt->id)->assertSee('New Cairo')->assertDontSee('Fifth Settlement')
            ->set('place.1', $this->newCairo->id)->assertSee('Fifth Settlement')
            ->set('place.2', $this->fifth->id);

        $form->set('place.0', $this->egypt->id)->assertSet('place.1', null)->assertSet('place.2', null);
    }

    public function test_the_deepest_place_names_the_request(): void
    {
        $this->filled()->set('place.2', $this->fifth->id)->call('send')->assertHasNoErrors();

        $request = UnitRequest::sole();
        $this->assertSame(['Villa for Rent in Fifth Settlement', $this->egypt->id, $this->newCairo->id, $this->fifth->id], [$request->name_en, $request->country_id, $request->city_id, $request->region_id]);
    }

    public function test_a_place_not_in_the_tree_is_typed_as_a_full_address(): void
    {
        $this->filled()
            ->set(['place.0' => null, 'place.1' => null])
            ->call('send')
            ->assertHasErrors('place.0');

        $this->filled()
            ->set(['manual' => true, 'place.0' => null, 'place.1' => null])
            ->call('send')
            ->assertHasErrors(['fullAddress' => 'required'])
            ->set('fullAddress', '12 Nile Street, Zamalek, Cairo')
            ->call('send')
            ->assertHasNoErrors();

        $request = UnitRequest::sole();
        $this->assertSame(['Villa for Rent', 'Katameya Heights — 12 Nile Street, Zamalek, Cairo', null], [$request->name_en, $request->address, $request->country_id]);
    }

    public function test_a_place_from_another_branch_is_refused(): void
    {
        $other = $this->egypt->parent->children()->create(['slug' => 'uae', 'name_en' => 'UAE']);
        $dubai = $other->children()->create(['slug' => 'dubai', 'name_en' => 'Dubai']);
        Lookup::fixTree();

        $this->filled()->set('place.1', $dubai->id)->call('send')->assertHasErrors('place.1');
        $this->assertSame(0, UnitRequest::count());
    }

    public function test_a_unit_is_saved_as_a_sell_request_with_its_photos_and_sent_to_the_crm(): void
    {
        $this->filled()
            ->set('photos', [UploadedFile::fake()->image('front.jpg', 800, 600), UploadedFile::fake()->image('pool.png', 800, 600)])
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('sent', true)
            ->assertSee('Submitted!');

        $request = UnitRequest::with('lookups')->sole();
        $this->assertSame(
            ['Sara Ali', '+201001234567', 'Villa for Rent in New Cairo', 'فيلا للإيجار في القاهرة الجديدة', $this->newCairo->id, 'Katameya Heights', 4500000, 180, 3, 2, UnitRequest::PENDING],
            [$request->seller_name, $request->phone, $request->name_en, $request->name_ar, $request->city_id, $request->address, $request->price, $request->area, $request->bedrooms, $request->bathrooms, $request->status],
        );
        $this->assertEqualsCanonicalizing(['villa', 'rent'], $request->lookups->pluck('slug')->all());
        $this->assertCount(2, $request->getMedia(UnitRequest::PHOTOS));
        $this->assertNotNull($request->getFirstMedia(UnitRequest::FEATURED_IMAGE));
        // Saved under a random name, never the uploader's.
        $this->assertStringNotContainsString('front', $request->getFirstMedia(UnitRequest::PHOTOS)->file_name);
        Queue::assertPushed(SendLeadToCrm::class, fn (SendLeadToCrm $job) => $job->record->is($request));
    }

    public function test_photos_are_optional_and_for_sale_is_a_resale(): void
    {
        $this->filled()->set('listing', 'sale')->call('send')->assertHasNoErrors();

        $request = UnitRequest::with('lookups')->sole();
        $this->assertSame('Villa for Sale in New Cairo', $request->name_en);
        $this->assertContains('resale', $request->lookups->pluck('slug')->all());
        $this->assertNull($request->getFirstMedia(UnitRequest::FEATURED_IMAGE));
    }

    public function test_required_fields_and_known_choices(): void
    {
        Livewire::test(SubmitProperty::class)
            ->call('open')
            ->set(['type' => 'castle', 'listing' => 'swap', 'phone' => '0100'])
            ->call('send')
            ->assertHasErrors(['fullName' => 'required', 'phone', 'type', 'listing', 'price' => 'required', 'unitArea' => 'required'])
            ->assertSet('sent', false);

        $this->assertSame(0, UnitRequest::count());
    }

    public function test_photos_must_be_images_and_at_most_ten(): void
    {
        $this->filled()
            ->set('photos', [UploadedFile::fake()->create('virus.exe', 10)])
            ->call('send')
            ->assertHasErrors(['photos.0']);

        $this->filled()
            ->set('photos', array_map(fn ($i) => UploadedFile::fake()->image("p{$i}.jpg"), range(1, 11)))
            ->call('send')
            ->assertHasErrors(['photos' => 'max']);

        $this->assertSame(0, UnitRequest::count());
    }

    public function test_submit_another_empties_the_unit_but_keeps_the_contact(): void
    {
        $this->filled()->call('send')->call('again')
            ->assertSet('sent', false)
            ->assertSet('fullName', 'Sara Ali')
            ->assertSet('price', '')
            ->assertSet('place', [null, null, null, null, null]);
    }

    public function test_a_signed_in_owner_gets_their_details_and_the_request_is_linked(): void
    {
        $user = WebsiteUser::factory()->create(['name' => 'Omar', 'email' => 'omar@example.com', 'phone' => '01001234567']);
        $this->actingAs($user, 'website');

        Livewire::test(SubmitProperty::class)
            ->assertSet('fullName', 'Omar')
            ->call('open')
            ->set(['type' => 'villa', 'place.0' => $this->egypt->id, 'place.1' => $this->newCairo->id, 'price' => '100', 'unitArea' => '90'])
            ->call('send')
            ->assertHasNoErrors();

        $request = UnitRequest::sole();
        $this->assertSame([$user->id, 'omar@example.com'], [$request->website_user_id, $request->email]);
    }

    public function test_sends_are_limited_per_visitor(): void
    {
        foreach (range(1, 6) as $i) {
            $form = $this->filled()->set('phone', "0100123456{$i}")->call('send');
            $i <= 5 ? $form->assertHasNoErrors() : $form->assertHasErrors('photos');
        }

        $this->assertSame(5, UnitRequest::count());
    }

    public function test_the_side_panel_shows_the_submit_property_settings(): void
    {
        $settings = app(SubmitPropertySettings::class);
        $settings->why_list_mini_description_en = 'Reach serious buyers.';
        $settings->points = [
            ['icon' => null, 'header_en' => 'Targeted Exposure', 'header_ar' => 'وصول مستهدف', 'body_en' => 'Buyers see your unit.', 'body_ar' => 'المشترون يرون وحدتك.'],
            ['icon' => null, 'header_en' => '', 'header_ar' => '', 'body_en' => 'Empty point', 'body_ar' => ''],
        ];
        $settings->save();

        Livewire::test(SubmitProperty::class)
            ->assertSee(['Why Sell With Us', 'Reach serious buyers.', 'Targeted Exposure', 'Buyers see your unit.'])
            ->assertDontSee('Empty point');
    }
}
