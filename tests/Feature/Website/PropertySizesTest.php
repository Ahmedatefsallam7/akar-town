<?php

namespace Tests\Feature\Website;

use App\Support\Website\CompareUnits;
use App\Support\Website\PropertyCards;
use App\Support\Website\PropertyDetails;
use App\Support\Website\PropertySearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\UnitRequests\Actions\ConvertUnitRequestToUnit;
use Modules\UnitRequests\Models\UnitRequest;
use Modules\Units\Filament\Resources\UnitResource\Pages\CreateUnit;
use Modules\Units\Models\Unit;
use Tests\TestCase;

/**
 * AKAR-52: a property's built-up area (BUA) and land area.
 */
class PropertySizesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();
    }

    public function test_the_old_area_is_the_land_area_and_the_bua_is_new(): void
    {
        $this->assertTrue(Schema::hasColumns('units', ['land_area', 'built_up_area']));
        $this->assertTrue(Schema::hasColumns('unit_requests', ['land_area', 'built_up_area']));
        $this->assertFalse(Schema::hasColumn('units', 'area'));
        $this->assertFalse(Schema::hasColumn('unit_requests', 'area'));
    }

    public function test_the_admin_fills_both_sizes_above_zero_and_the_bua_may_be_larger(): void
    {
        $this->actingAs($this->createAdmin());
        $base = ['name_en' => 'Twin House', 'name_ar' => 'توين هاوس'];

        Livewire::test(CreateUnit::class)
            ->fillForm([...$base, 'built_up_area' => 0, 'land_area' => 0])
            ->call('create')
            ->assertHasFormErrors(['built_up_area', 'land_area']);

        $unit = Unit::factory()->create(['built_up_area' => 300, 'land_area' => 250]);
        $this->assertSame([300, 250, 300], [$unit->built_up_area, $unit->land_area, $unit->shownArea()]);
        // The database keeps the shown size itself (indexed, for the quick ranges and sorting).
        $this->assertSame(300, (int) Unit::query()->whereKey($unit->id)->value(Unit::SHOWN_AREA));
        $unit->update(['built_up_area' => null]);
        $this->assertSame(250, (int) Unit::query()->whereKey($unit->id)->value(Unit::SHOWN_AREA));
    }

    public function test_the_property_page_shows_each_size_and_hides_an_empty_one(): void
    {
        $both = Unit::factory()->create(['is_published' => true, 'built_up_area' => 520, 'land_area' => 650, 'price' => 52_000_000]);
        $landOnly = Unit::factory()->create(['is_published' => true, 'built_up_area' => null, 'land_area' => 300]);

        $this->get(route('properties.show', $both->slug))->assertOk()
            ->assertSee(['Built-Up Area (BUA)', '520 m²', 'Land Area', '650 m²', 'BUA', 'Land'])
            ->assertSee('EGP 100,000 / m²', false); // the price per m² on the BUA
        $this->get(route('properties.show', $landOnly->slug))->assertOk()
            ->assertSee(['Land Area', '300 m²'])
            ->assertDontSee('Built-Up Area (BUA)');

        $this->withCookie('locale', 'ar')->get(route('properties.show', $both->slug))->assertOk()
            ->assertSee(['مساحة البناء (BUA)', 'مساحة الأرض']);

        $details = PropertyDetails::for($landOnly->fresh());
        $this->assertSame([null, 300], [$details['bua'], $details['land']]);
    }

    public function test_a_card_shows_the_bua_else_the_land_area_with_its_label(): void
    {
        Unit::factory()->create(['name_en' => 'A', 'built_up_area' => 200, 'land_area' => 400]);
        Unit::factory()->create(['name_en' => 'B', 'built_up_area' => null, 'land_area' => 150]);

        $cards = collect(PropertyCards::from(PropertyCards::eagerLoad(Unit::query())->orderBy('name_en')->get()));

        $this->assertSame([200, 'BUA m²'], [$cards[0]['area'], $cards[0]['areaLabel']]);
        $this->assertSame([150, 'Land m²'], [$cards[1]['area'], $cards[1]['areaLabel']]);
    }

    public function test_each_size_has_its_own_filter_and_quick_ranges_use_the_shown_one(): void
    {
        Unit::factory()->create(['name_en' => 'Big House', 'is_published' => true, 'built_up_area' => 400, 'land_area' => 600]);
        Unit::factory()->create(['name_en' => 'Flat', 'is_published' => true, 'built_up_area' => 120, 'land_area' => null]);
        Unit::factory()->create(['name_en' => 'Plot', 'is_published' => true, 'built_up_area' => null, 'land_area' => 90]);
        $names = fn (array $filters) => PropertySearch::query($filters)->pluck('name_en')->sort()->values()->all();

        $this->assertSame(['Big House'], $names(['bua_min' => '300']));
        $this->assertSame(['Flat'], $names(['bua_max' => '200']));
        $this->assertSame(['Big House'], $names(['land_min' => '500']));
        $this->assertSame(['Plot'], $names(['land_max' => '100']));
        // The home's quick ranges: the size a card shows (BUA, else land).
        $this->assertSame(['Flat', 'Plot'], $names(['area' => ['80-150']]));
        $this->assertSame(['Big House', 'Flat', 'Plot'], PropertySearch::query(['sort' => 'area-desc'])->pluck('name_en')->all());
        $this->get(route('properties', ['bua_min' => 300, 'land_max' => 800]))->assertOk()->assertSee('Big House')->assertDontSee('Plot');
    }

    public function test_the_comparison_compares_each_size(): void
    {
        $a = Unit::factory()->create(['is_published' => true, 'built_up_area' => 300, 'land_area' => 200]);
        $b = Unit::factory()->create(['is_published' => true, 'built_up_area' => null, 'land_area' => 500]);

        $data = CompareUnits::for([$a->slug, $b->slug]);
        $this->assertSame([[true, false], [false, true]], [$data['best']['bua'], $data['best']['land']]);
        $this->assertSame([300, 500], [$data['maxBua'], $data['maxLand']]);
        $this->get(route('compare', ['units' => $a->slug.','.$b->slug]))->assertOk()->assertSee(['Built-Up Area (BUA)', 'Land Area']);
    }

    public function test_a_sell_request_keeps_both_sizes_when_it_becomes_a_property(): void
    {
        $this->actingAs($this->createAdmin());
        $request = UnitRequest::factory()->create(['built_up_area' => 210, 'land_area' => 180]);

        $unit = app(ConvertUnitRequestToUnit::class)($request);

        $this->assertSame([210, 180], [$unit->built_up_area, $unit->land_area]);
        $this->assertStringContainsString('Built-up area (BUA): 210 m²', $request->toCrmLead()['description']);
    }
}
