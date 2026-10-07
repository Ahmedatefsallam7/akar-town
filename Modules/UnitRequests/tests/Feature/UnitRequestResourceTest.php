<?php

namespace Modules\UnitRequests\Tests\Feature;

use Filament\Tables\Filters\QueryBuilder\Constraints\NumberConstraint\Operators\IsMinOperator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Lookups\Models\Lookup;
use Modules\Lookups\Support\LookupUsage;
use Modules\UnitRequests\Actions\ConvertUnitRequestToUnit;
use Modules\UnitRequests\Filament\Resources\UnitRequestResource;
use Modules\UnitRequests\Filament\Resources\UnitRequestResource\Pages\CreateUnitRequest;
use Modules\UnitRequests\Filament\Resources\UnitRequestResource\Pages\EditUnitRequest;
use Modules\UnitRequests\Filament\Resources\UnitRequestResource\Pages\ListUnitRequests;
use Modules\UnitRequests\Filament\Resources\UnitRequestResource\Pages\ViewUnitRequest;
use Modules\UnitRequests\Models\UnitRequest;
use Modules\Units\Filament\Resources\UnitResource;
use Modules\Units\Models\Unit;
use Modules\Users\Support\PermissionModules;
use Tests\TestCase;

class UnitRequestResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * A lookup type with values, e.g. ['offeringtype' => ['primary', 'resale']].
     *
     * @return array<string, Lookup>
     */
    private function lookupValues(string $type, array $slugs): array
    {
        $parent = Lookup::firstOrCreate(['slug' => $type], ['name_en' => ucfirst($type)]);

        return collect($slugs)->mapWithKeys(fn (string $slug, int $i) => [$slug => $parent->children()->create(['slug' => $slug, 'name_en' => ucfirst($slug), 'order' => $i])])->all();
    }

    public function test_users_without_the_permission_are_forbidden(): void
    {
        $this->actingAs($this->createUserWithPermissions(['units' => true]));

        $this->get(UnitRequestResource::getUrl('index'))->assertForbidden();
    }

    public function test_admin_can_open_every_page(): void
    {
        $this->actingAs($this->createAdmin());
        $request = UnitRequest::factory()->create();

        $this->get(UnitRequestResource::getUrl('index'))->assertOk()->assertSee($request->name_en);
        $this->get(UnitRequestResource::getUrl('create'))->assertOk();
        $this->get(UnitRequestResource::getUrl('view', ['record' => $request]))->assertOk();
        $this->get(UnitRequestResource::getUrl('edit', ['record' => $request]))->assertOk();
    }

    public function test_it_is_an_inquiries_and_crm_permission_module(): void
    {
        $this->assertSame('inquiries_crm', PermissionModules::all()['unit_requests']['group'] ?? null);
    }

    public function test_seller_name_phone_unit_name_and_status_are_required(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateUnitRequest::class)
            ->fillForm(['seller_name' => '', 'phone' => '', 'name_en' => '', 'status' => null])
            ->call('create')
            ->assertHasFormErrors(['seller_name' => 'required', 'phone' => 'required', 'name_en' => 'required', 'status' => 'required'])
            ->assertHasNoFormErrors(['email', 'name_ar', 'price', 'area', 'address']);

        Livewire::test(CreateUnitRequest::class)
            ->fillForm(['seller_name' => 'Sara', 'phone' => '0100', 'name_en' => 'Flat', 'email' => 'x', 'status' => 'sold', 'installment_years_from' => 8, 'installment_years_to' => 3, 'down_payment' => 150])
            ->call('create')
            ->assertHasFormErrors(['email', 'status', 'installment_years_to', 'down_payment']);
    }

    public function test_staff_add_a_request_with_choices_and_photo(): void
    {
        $this->actingAs($this->createAdmin());
        $amenities = $this->lookupValues('amenities', ['pool', 'gym']);
        $offering = $this->lookupValues('offeringtype', ['primary', 'resale']);

        Livewire::test(CreateUnitRequest::class)
            ->assertSchemaStateSet(['status' => UnitRequest::PENDING])
            ->fillForm([
                'seller_name' => 'Sara Ahmed',
                'phone' => '+201001234567',
                'name_en' => '3-Bedroom Apartment in New Cairo',
                'price' => 4500000,
                'area' => 180,
                'bedrooms' => 3,
                'amenities' => [$amenities['pool']->id, $amenities['gym']->id],
                'offering_types' => [$offering['resale']->id],
                'featured_image' => [UploadedFile::fake()->image('flat.jpg', 800, 600)],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $request = UnitRequest::sole();
        $this->assertSame([4500000, 180, UnitRequest::PENDING], [$request->price, $request->area, $request->status]);
        $this->assertEqualsCanonicalizing([$amenities['pool']->id, $amenities['gym']->id], $request->lookupIds('amenities'));
        $this->assertSame([$offering['resale']->id], $request->lookupIds('offeringtype'));
        $this->assertNotNull($request->getFirstMedia(UnitRequest::FEATURED_IMAGE));
    }

    public function test_a_value_of_another_lookup_type_is_rejected(): void
    {
        $this->actingAs($this->createAdmin());
        $amenity = $this->lookupValues('amenities', ['pool'])['pool'];
        $this->lookupValues('services', ['cleaning']);

        Livewire::test(CreateUnitRequest::class)
            ->fillForm(['seller_name' => 'Sara', 'phone' => '0100', 'name_en' => 'Flat', 'services' => [$amenity->id]])
            ->call('create')
            ->assertHasFormErrors(['services']);
    }

    public function test_converting_makes_an_unpublished_property_and_closes_the_request(): void
    {
        $this->actingAs($this->createAdmin());
        $root = Lookup::create(['slug' => 'locations', 'name_en' => 'Locations']);
        $egypt = $root->children()->create(['slug' => 'egypt', 'name_en' => 'Egypt']);
        $cairo = $egypt->children()->create(['slug' => 'cairo', 'name_en' => 'Cairo']);
        $offering = $this->lookupValues('offeringtype', ['primary', 'resale']);
        $types = $this->lookupValues('unittype', ['apartment', 'villa']);
        $finishing = $this->lookupValues('finishingtype', ['core', 'finished']);
        $amenities = $this->lookupValues('amenities', ['pool']);
        $status = $this->lookupValues('unitstatus', ['us-available']);

        $request = UnitRequest::factory()->create([
            'name_en' => 'Garden Villa', 'name_ar' => 'فيلا بحديقة', 'country_id' => $egypt->id, 'city_id' => $cairo->id,
            'price' => 9000000, 'area' => 320, 'bedrooms' => 4, 'down_payment' => 10, 'installment_years_to' => 6,
            'description_en' => '<p>Corner villa</p>', 'status' => UnitRequest::CONTACTED,
        ]);
        $request->lookupsOfType('unittype')->sync([$types['villa']->id, $types['apartment']->id]);
        $request->lookupsOfType('finishingtype')->sync([$finishing['finished']->id]);
        $request->lookupsOfType('amenities')->sync([$amenities['pool']->id]);
        $request->addMedia(UploadedFile::fake()->image('villa.jpg', 400, 300))->toMediaCollection(UnitRequest::FEATURED_IMAGE);

        Livewire::test(ViewUnitRequest::class, ['record' => $request->getRouteKey()])
            ->callAction('convertToUnit')
            ->assertRedirect(UnitResource::getUrl('edit', ['record' => Unit::sole()]));

        $unit = Unit::sole();
        $this->assertSame(['Garden Villa', 'فيلا بحديقة', $egypt->id, $cairo->id, 9000000, 320, 4, 6, '<p>Corner villa</p>', false], [
            $unit->name_en, $unit->name_ar, $unit->country_id, $unit->city_id, $unit->price, $unit->area, $unit->bedrooms,
            $unit->installment_years_to, $unit->description_en, $unit->is_published,
        ]);
        // One value where a property holds one: the first picked (by lookup order); defaults when none picked.
        $this->assertSame($types['apartment']->id, $unit->property_type_id);
        $this->assertSame($finishing['finished']->id, $unit->finishing_type_id);
        $this->assertSame($offering['resale']->id, $unit->offering_type_id);
        $this->assertSame($status['us-available']->id, $unit->unit_status_id);
        $this->assertSame([$amenities['pool']->id], $unit->lookupIds('amenities'));
        $this->assertNotNull($unit->getFirstMedia(Unit::FEATURED_IMAGE));
        // The request keeps its own photo.
        $this->assertNotNull($request->fresh()->getFirstMedia(UnitRequest::FEATURED_IMAGE));

        $request->refresh();
        $this->assertSame([UnitRequest::CLOSED, $unit->id], [$request->status, $request->unit_id]);
        Livewire::test(ViewUnitRequest::class, ['record' => $request->getRouteKey()])
            ->assertActionHidden('convertToUnit')
            ->assertActionVisible('viewUnit');
    }

    public function test_a_request_is_converted_only_once(): void
    {
        $request = UnitRequest::factory()->create(['name_en' => 'Sea View Chalet', 'name_ar' => null]);
        $stale = UnitRequest::find($request->id);

        app(ConvertUnitRequestToUnit::class)($request);

        try {
            // A second click from a page opened before the first conversion.
            app(ConvertUnitRequestToUnit::class)($stale);
            $this->fail('A second conversion should be refused.');
        } catch (\LogicException) {
        }

        $this->assertSame(1, Unit::count());
        // No Arabic name on the request: the property starts with the English one.
        $this->assertSame('Sea View Chalet', Unit::sole()->name_ar);
    }

    public function test_cancelled_requests_and_users_who_cannot_add_properties_cannot_convert(): void
    {
        $cancelled = UnitRequest::factory()->create(['status' => UnitRequest::CANCELLED]);
        $open = UnitRequest::factory()->create();

        $this->actingAs($this->createAdmin());
        Livewire::test(ViewUnitRequest::class, ['record' => $cancelled->getRouteKey()])->assertActionHidden('convertToUnit');

        $this->actingAs($this->createUserWithPermissions(['unit_requests' => true, 'units' => ['view']]));
        Livewire::test(ViewUnitRequest::class, ['record' => $open->getRouteKey()])->assertActionHidden('convertToUnit');
        $this->assertSame(0, Unit::count());
    }

    public function test_bulk_mark_reviewed_and_contacted_leave_closed_requests_alone(): void
    {
        $this->actingAs($this->createAdmin());
        $pending = UnitRequest::factory()->create();
        $contacted = UnitRequest::factory()->create(['status' => UnitRequest::CONTACTED]);
        $closed = UnitRequest::factory()->create(['status' => UnitRequest::CLOSED]);
        $all = [$pending, $contacted, $closed];

        Livewire::test(ListUnitRequests::class)->callTableBulkAction('mark_reviewed', $all);
        $this->assertSame([UnitRequest::REVIEWED, UnitRequest::CONTACTED, UnitRequest::CLOSED], array_map(fn ($r) => $r->fresh()->status, $all));

        Livewire::test(ListUnitRequests::class)->callTableBulkAction('mark_contacted', $all);
        $this->assertSame([UnitRequest::CONTACTED, UnitRequest::CONTACTED, UnitRequest::CLOSED], array_map(fn ($r) => $r->fresh()->status, $all));
    }

    public function test_filters_search_columns_and_trash(): void
    {
        $this->actingAs($this->createAdmin());
        $cheap = UnitRequest::factory()->create(['seller_name' => 'Sara Ahmed', 'price' => 1000000]);
        $dear = UnitRequest::factory()->create(['seller_name' => 'Omar Ali', 'price' => 9000000, 'status' => UnitRequest::REVIEWED]);

        Livewire::test(ListUnitRequests::class)
            ->assertTableColumnExists('featured_image')
            ->assertTableColumnExists('area')
            ->assertTableColumnExists('bathrooms')
            ->searchTable('Sara Ahmed')
            ->assertCanSeeTableRecords([$cheap])->assertCanNotSeeTableRecords([$dear]);

        Livewire::test(ListUnitRequests::class)
            ->filterTable('status', [UnitRequest::REVIEWED])
            ->assertCanSeeTableRecords([$dear])->assertCanNotSeeTableRecords([$cheap]);

        Livewire::test(ListUnitRequests::class)
            ->filterTable('queryBuilder', ['rules' => [
                ['type' => 'price', 'data' => ['operator' => IsMinOperator::make()->getName(), 'settings' => ['number' => 5000000]]],
            ]])
            ->assertCanSeeTableRecords([$dear])->assertCanNotSeeTableRecords([$cheap]);

        $dear->delete();
        Livewire::test(ListUnitRequests::class)
            ->assertCanNotSeeTableRecords([$dear])
            ->filterTable('trashed', true)
            ->assertCanSeeTableRecords([$dear]);
    }

    public function test_editing_keeps_internal_notes_and_choices(): void
    {
        $this->actingAs($this->createAdmin());
        $services = $this->lookupValues('services', ['cleaning', 'security']);
        $request = UnitRequest::factory()->create();
        $request->lookupsOfType('services')->sync([$services['cleaning']->id]);

        Livewire::test(EditUnitRequest::class, ['record' => $request->getRouteKey()])
            ->assertSchemaStateSet(['services' => [$services['cleaning']->id]])
            ->fillForm(['internal_notes' => 'Owner prefers calls after 6 pm.', 'status' => UnitRequest::REVIEWED, 'services' => [$services['security']->id]])
            ->call('save')
            ->assertHasNoFormErrors();

        $request->refresh();
        $this->assertSame(['Owner prefers calls after 6 pm.', UnitRequest::REVIEWED], [$request->internal_notes, $request->status]);
        $this->assertSame([$services['security']->id], $request->lookupIds('services'));
    }

    public function test_a_location_a_request_uses_cannot_be_deleted(): void
    {
        $root = Lookup::create(['slug' => 'locations', 'name_en' => 'Locations']);
        $egypt = $root->children()->create(['slug' => 'egypt', 'name_en' => 'Egypt']);
        UnitRequest::factory()->create(['country_id' => $egypt->id]);

        $this->assertTrue(LookupUsage::isUsed($egypt));
    }
}
