<?php

namespace Modules\Units\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Galleries\Filament\Resources\GalleryResource;
use Modules\Galleries\Models\Gallery;
use Modules\Lookups\Filament\Resources\LookupResource;
use Modules\Lookups\Models\Lookup;
use Modules\Lookups\Support\LocationTree;
use Modules\Projects\Filament\Resources\ProjectResource;
use Modules\Projects\Filament\Resources\ProjectResource\Pages\ListProjects;
use Modules\Projects\Filament\Resources\ProjectResource\Pages\ViewProject;
use Modules\Projects\Filament\Resources\ProjectResource\RelationManagers\UnitsRelationManager;
use Modules\Projects\Models\Project;
use Modules\Units\Filament\Resources\UnitResource;
use Modules\Units\Filament\Resources\UnitResource\Pages\CreateUnit;
use Modules\Units\Filament\Resources\UnitResource\Pages\EditUnit;
use Modules\Units\Filament\Resources\UnitResource\Pages\ListUnits;
use Modules\Units\Filament\Resources\UnitResource\Pages\ViewUnit;
use Modules\Units\Filament\Resources\UnitResource\RelationManagers\PriceHistoriesRelationManager;
use Modules\Units\Models\Unit;
use Modules\Users\Support\PermissionModules;
use Tests\TestCase;

class UnitResourceTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, Lookup> */
    private array $place = [];

    /** @var array<string, array<string, Lookup>> lookup type => value slug => value */
    private array $value = [];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $root = Lookup::create(['slug' => LocationTree::ROOT_SLUG, 'name_en' => 'Locations', 'name_ar' => 'المواقع']);
        $add = fn (Lookup $parent, string $name) => $parent->children()->create(['slug' => str($name)->slug(), 'name_en' => $name, 'name_ar' => "ع {$name}"]);
        $this->place['egypt'] = $add($root, 'Egypt');
        $this->place['cairo'] = $add($this->place['egypt'], 'Cairo');
        $this->place['giza'] = $add($this->place['egypt'], 'Giza');
        $this->place['new_cairo'] = $add($this->place['cairo'], 'New Cairo');

        $types = [
            'offeringtype' => ['primary', 'resale', 'rent'], 'unittype' => ['apartment', 'villa'], 'propertyusage' => ['residential', 'commercial'],
            'finishingtype' => ['finished', 'core'], 'furnishingstatus' => ['furnished'], 'unitstatus' => ['available', 'sold'],
            'amenities' => ['pool', 'gym'], 'views' => ['sea'], 'currencies' => ['egp', 'usd'], 'ownershiptype' => ['freehold'],
        ];
        foreach ($types as $type => $values) {
            $parent = Lookup::create(['slug' => $type, 'name_en' => ucfirst($type), 'name_ar' => $type, 'is_deletable' => false]);
            foreach ($values as $slug) {
                $this->value[$type][$slug] = $parent->children()->create(['slug' => $slug, 'name_en' => ucfirst($slug), 'name_ar' => "ع {$slug}"]);
            }
        }
    }

    private function id(string $type, string $slug): int
    {
        return $this->value[$type][$slug]->id;
    }

    private function project(array $attributes = []): Project
    {
        return Project::factory()->create($attributes + ['country_id' => $this->place['egypt']->id, 'city_id' => $this->place['cairo']->id, 'region_id' => $this->place['new_cairo']->id]);
    }

    public function test_users_without_the_units_permission_are_forbidden(): void
    {
        $this->actingAs($this->createUserWithPermissions(['projects' => true]));

        $this->get(UnitResource::getUrl('index'))->assertForbidden();
        $this->get(UnitResource::getUrl('create'))->assertForbidden();
    }

    public function test_admin_can_open_every_page(): void
    {
        $this->actingAs($this->createAdmin());
        $unit = Unit::factory()->create();

        $this->get(UnitResource::getUrl('index'))->assertOk();
        $this->get(UnitResource::getUrl('create'))->assertOk();
        $this->get(UnitResource::getUrl('view', ['record' => $unit]))->assertOk();
        $this->get(UnitResource::getUrl('edit', ['record' => $unit]))->assertOk();
    }

    public function test_units_is_a_property_management_permission_module(): void
    {
        $this->assertSame('property_management', PermissionModules::all()['units']['group'] ?? null);
    }

    public function test_admin_can_create_a_standalone_unit_with_every_field(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateUnit::class)
            ->fillForm([
                'name_en' => 'Resale Apartment 12', 'name_ar' => 'شقة إعادة بيع 12',
                'offering_type_id' => $this->id('offeringtype', 'resale'),
                'country_id' => $this->place['egypt']->id, 'city_id' => $this->place['giza']->id,
                'neighbourhood' => 'Dokki', 'address' => '12 Tahrir St', 'latitude' => '30.04', 'longitude' => '31.21',
                'is_featured' => true, 'order' => 3,
                'price' => 4200000, 'area' => 165, 'bedrooms' => 3, 'bathrooms' => 2, 'floor' => -1,
                'parking_spaces' => 2, 'building_floors' => 12, 'building_year' => 2024, 'ownership_type_id' => $this->id('ownershiptype', 'freehold'),
                'down_payment' => 15, 'installment_years_from' => 2, 'installment_years_to' => 5, 'delivery_date' => '2027-03-01',
                'amenities' => [$this->id('amenities', 'pool')], 'views' => [$this->id('views', 'sea')],
                'finishing_type_id' => $this->id('finishingtype', 'finished'), 'furnishing_status_id' => $this->id('furnishingstatus', 'furnished'),
                'unit_status_id' => $this->id('unitstatus', 'available'), 'property_usage_id' => $this->id('propertyusage', 'residential'),
                'property_type_id' => $this->id('unittype', 'apartment'),
                'short_description_en' => 'Card text.', 'description_en' => '<p>Body</p>',
                'featured_image' => [UploadedFile::fake()->image('cover.jpg')],
                'images_360' => [UploadedFile::fake()->image('pano.jpg', 2000, 1000)],
                'floor_plan' => [UploadedFile::fake()->image('plan.png')],
                'master_plan' => [UploadedFile::fake()->image('master.png')],
                'video_url' => 'https://youtu.be/abc',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $unit = Unit::sole();
        $this->assertNull($unit->project_id);
        $this->assertSame('resale-apartment-12', $unit->slug);
        $this->assertSame([4200000, 165, 3, 2, -1], [$unit->price, $unit->area, $unit->bedrooms, $unit->bathrooms, $unit->floor]);
        $this->assertSame([2, 12, 2024, $this->id('ownershiptype', 'freehold')], [$unit->parking_spaces, $unit->building_floors, $unit->building_year, $unit->ownership_type_id]);
        $this->assertSame($this->id('offeringtype', 'resale'), $unit->offering_type_id);
        $this->assertSame($this->id('unittype', 'apartment'), $unit->property_type_id);
        $this->assertSame([$this->id('amenities', 'pool')], $unit->lookupIds('amenities'));
        $this->assertFalse($unit->is_published, 'New units start unpublished, like projects.');
        $this->assertTrue($unit->is_featured);
        $this->assertNotNull($unit->getFirstMedia(Unit::FLOOR_PLAN));
        $this->assertNotNull($unit->getFirstMedia(Unit::MASTER_PLAN));
        $this->assertCount(1, $unit->getMedia(Unit::IMAGES_360));
    }

    public function test_each_image_field_shows_its_own_files_when_editing(): void
    {
        $this->actingAs($this->createAdmin());
        $unit = Unit::factory()->create();
        $floor = $unit->addMedia(UploadedFile::fake()->image('floor.png'))->toMediaCollection(Unit::FLOOR_PLAN);
        $master = $unit->addMedia(UploadedFile::fake()->image('master.png'))->toMediaCollection(Unit::MASTER_PLAN);
        $panos = collect(['a.jpg', 'b.jpg'])->map(fn ($name) => $unit->addMedia(UploadedFile::fake()->image($name, 2000, 1000))->toMediaCollection(Unit::IMAGES_360));

        // The media are read once for all the fields (AppServiceProvider); each field still gets only its collection.
        $state = Livewire::test(EditUnit::class, ['record' => $unit->getRouteKey()])->instance()->form->getRawState();

        $this->assertSame([$floor->uuid], array_values($state['floor_plan']));
        $this->assertSame([$master->uuid], array_values($state['master_plan']));
        $this->assertSame($panos->pluck('uuid')->all(), array_values($state['images_360']));
        $this->assertSame([], array_values($state['featured_image'] ?? []));
    }

    public function test_names_are_required_and_single_choices_only_accept_their_type(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateUnit::class)
            ->fillForm([
                'name_en' => '', 'name_ar' => '',
                'finishing_type_id' => $this->id('unitstatus', 'sold'),
                'offering_type_id' => $this->id('amenities', 'pool'),
                'ownership_type_id' => $this->id('views', 'sea'),
                'building_year' => 1800,
            ])
            ->call('create')
            ->assertHasFormErrors(['name_en' => 'required', 'name_ar' => 'required', 'finishing_type_id', 'offering_type_id', 'ownership_type_id', 'building_year']);
    }

    public function test_picking_a_project_takes_its_location(): void
    {
        $this->actingAs($this->createAdmin());
        $project = $this->project();

        Livewire::test(CreateUnit::class)
            ->fillForm(['project_id' => $project->id])
            ->assertSchemaStateSet(['country_id' => $this->place['egypt']->id, 'city_id' => $this->place['cairo']->id, 'region_id' => $this->place['new_cairo']->id]);

        // Saved without a location (e.g. an import), the unit still takes the project's.
        $unit = Unit::factory()->create(['project_id' => $project->id]);
        $this->assertSame($this->place['new_cairo']->id, $unit->region_id);
    }

    public function test_offering_type_property_type_and_usage_are_limited_to_what_the_project_picked(): void
    {
        $this->actingAs($this->createAdmin());
        $project = $this->project();
        $project->lookupsOfType('offeringtype')->sync([$this->id('offeringtype', 'primary')]);
        $project->lookupsOfType('unittype')->sync([$this->id('unittype', 'villa')]);

        Livewire::test(CreateUnit::class)
            ->fillForm(['name_en' => 'U1', 'name_ar' => 'و1', 'project_id' => $project->id, 'offering_type_id' => $this->id('offeringtype', 'rent'), 'property_type_id' => $this->id('unittype', 'apartment')])
            ->call('create')
            ->assertHasFormErrors(['offering_type_id', 'property_type_id']);

        // Allowed values pass; usage is free because the project picked none.
        Livewire::test(CreateUnit::class)
            ->fillForm(['name_en' => 'U2', 'name_ar' => 'و2', 'project_id' => $project->id, 'offering_type_id' => $this->id('offeringtype', 'primary'),
                'property_type_id' => $this->id('unittype', 'villa'), 'property_usage_id' => $this->id('propertyusage', 'commercial')])
            ->call('create')
            ->assertHasNoFormErrors();

        // A unit without a project can pick any value.
        Livewire::test(CreateUnit::class)
            ->fillForm(['name_en' => 'U3', 'name_ar' => 'و3', 'offering_type_id' => $this->id('offeringtype', 'rent')])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_a_unit_picks_only_its_projects_galleries_or_galleries_with_no_project(): void
    {
        $this->actingAs($this->createAdmin());
        $project = $this->project();
        $other = $this->project();
        $own = Gallery::factory()->create(['project_id' => $project->id]);
        $foreign = Gallery::factory()->create(['project_id' => $other->id]);
        $shared = Gallery::factory()->create();

        Livewire::test(CreateUnit::class)
            ->fillForm(['name_en' => 'U1', 'name_ar' => 'و1', 'project_id' => $project->id, 'gallery_id' => $foreign->id])
            ->call('create')
            ->assertHasFormErrors(['gallery_id']);

        Livewire::test(CreateUnit::class)
            ->fillForm(['name_en' => 'U2', 'name_ar' => 'و2', 'project_id' => $project->id, 'gallery_id' => $own->id])
            ->call('create')
            ->assertHasNoFormErrors();

        Livewire::test(CreateUnit::class)
            ->fillForm(['name_en' => 'U3', 'name_ar' => 'و3', 'gallery_id' => $own->id])
            ->call('create')
            ->assertHasFormErrors(['gallery_id']);

        Livewire::test(CreateUnit::class)
            ->fillForm(['name_en' => 'U4', 'name_ar' => 'و4', 'gallery_id' => $shared->id])
            ->call('create')
            ->assertHasNoFormErrors();

        // Moving the unit to another project drops a gallery that doesn't fit any more.
        Livewire::test(CreateUnit::class)
            ->fillForm(['project_id' => $project->id, 'gallery_id' => $own->id])
            ->fillForm(['project_id' => $other->id])
            ->assertSchemaStateSet(['gallery_id' => null]);
    }

    public function test_every_price_change_is_kept_with_who_made_it(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);
        $unit = Unit::factory()->create(['price' => 3000000]);

        $unit->update(['name_en' => 'Renamed']);
        $unit->update(['price' => 3200000]);
        Unit::factory()->create(['price' => null]);

        $this->assertSame([3200000, 3000000], $unit->priceHistories()->pluck('price')->all());
        $this->assertSame($admin->id, $unit->priceHistories()->value('changed_by'));
        $this->assertSame(2, \DB::table('unit_price_histories')->count());

        Livewire::test(PriceHistoriesRelationManager::class, ['ownerRecord' => $unit, 'pageClass' => ViewUnit::class])
            ->assertSee('3,200,000 EGP')
            ->assertSee('3,000,000 EGP');
    }

    public function test_a_projects_price_range_follows_its_units(): void
    {
        $project = $this->project(['price_from' => 1000000, 'price_to' => 2000000]);
        $alone = $this->project(['price_from' => 7000000, 'price_to' => 8000000]);
        Unit::factory()->create(['project_id' => $alone->id, 'price' => null]);

        $cheap = Unit::factory()->create(['project_id' => $project->id, 'price' => 3000000]);
        $dear = Unit::factory()->create(['project_id' => $project->id, 'price' => 5000000]);
        $this->assertSame([3000000, 5000000], [$project->fresh()->price_from, $project->fresh()->price_to]);

        $dear->delete();
        $this->assertSame([3000000, 3000000], [$project->fresh()->price_from, $project->fresh()->price_to]);

        $cheap->update(['price' => 2500000]);
        $this->assertSame(2500000, $project->fresh()->price_from);

        // Units without a price leave the project's own price alone.
        $this->assertSame([7000000, 8000000], [$alone->fresh()->price_from, $alone->fresh()->price_to]);
    }

    public function test_the_range_clears_when_the_last_priced_unit_leaves_and_a_typed_price_stays_otherwise(): void
    {
        $range = fn (Project $project) => [$project->fresh()->price_from, $project->fresh()->price_to];

        // Deleted: the range came from the unit, so it doesn't stay behind.
        $project = Project::factory()->create();
        $unit = Unit::factory()->create(['project_id' => $project->id, 'price' => 5_000_000]);
        $unit->delete();
        $this->assertSame([null, null], $range($project));

        // Price removed ("price on request").
        $unit->restore();
        $this->assertSame([5_000_000, 5_000_000], $range($project));
        $unit->update(['price' => null]);
        $this->assertSame([null, null], $range($project));

        // Moved to another project.
        $unit->update(['price' => 4_000_000]);
        $other = Project::factory()->create();
        $unit->update(['project_id' => $other->id]);
        $this->assertSame([null, null], $range($project));
        $this->assertSame([4_000_000, 4_000_000], $range($other));

        // A price typed on a project whose units never had one stays.
        $typed = Project::factory()->create(['price_from' => 2_000_000, 'price_to' => 3_000_000]);
        Unit::factory()->create(['project_id' => $typed->id, 'price' => null])->delete();
        $this->assertSame([2_000_000, 3_000_000], $range($typed));
    }

    public function test_prices_show_the_projects_currency_or_egp(): void
    {
        $usdProject = $this->project(['currency_id' => $this->id('currencies', 'usd')]);

        $this->assertSame('100,000 USD', UnitResource::money(Unit::factory()->create(['project_id' => $usdProject->id, 'price' => 100000])));
        $this->assertSame('2,000,000 EGP', UnitResource::money(Unit::factory()->create(['price' => 2000000])));
    }

    public function test_list_shows_the_columns_and_filters_by_everything_the_spec_lists(): void
    {
        $this->actingAs($this->createAdmin());
        $project = $this->project(['title_en' => 'Mountain Park']);
        $small = Unit::factory()->create(['name_en' => 'Studio 1', 'project_id' => $project->id, 'price' => 1500000, 'area' => 60, 'bedrooms' => 1, 'bathrooms' => 1, 'offering_type_id' => $this->id('offeringtype', 'primary')]);
        $big = Unit::factory()->create(['name_en' => 'Villa 9', 'price' => 9000000, 'area' => 400, 'bedrooms' => 5, 'bathrooms' => 4, 'offering_type_id' => $this->id('offeringtype', 'resale')]);

        $this->get(UnitResource::getUrl('index'))
            ->assertSee(['Studio 1', 'Mountain Park', '1,500,000 EGP', 'Primary', __('admin.units.standalone')]);

        $list = fn () => Livewire::test(ListUnits::class);
        $list()->filterTable('price', ['max' => 2000000])->assertCanSeeTableRecords([$small])->assertCanNotSeeTableRecords([$big]);
        $list()->filterTable('area', ['min' => 300])->assertCanSeeTableRecords([$big])->assertCanNotSeeTableRecords([$small]);
        $list()->filterTable('bedrooms', 5)->assertCanSeeTableRecords([$big])->assertCanNotSeeTableRecords([$small]);
        $list()->filterTable('bathrooms', 1)->assertCanSeeTableRecords([$small])->assertCanNotSeeTableRecords([$big]);
        $list()->filterTable('project_id', $project->id)->assertCanSeeTableRecords([$small])->assertCanNotSeeTableRecords([$big]);
        $list()->filterTable('offering_type_id', $this->id('offeringtype', 'resale'))->assertCanSeeTableRecords([$big])->assertCanNotSeeTableRecords([$small]);

        $big->delete();
        $list()->filterTable('trashed', true)->assertCanSeeTableRecords([$big]);
    }

    public function test_search_finds_units_by_the_start_of_the_name_or_slug(): void
    {
        $this->actingAs($this->createAdmin());
        $villa = Unit::factory()->create(['name_en' => 'Villa 12', 'name_ar' => 'فيلا 12']);
        $garden = Unit::factory()->create(['name_en' => 'Garden Villa', 'name_ar' => 'حديقة']);

        Livewire::test(ListUnits::class)->searchTable('Villa 1')->assertCanSeeTableRecords([$villa])->assertCanNotSeeTableRecords([$garden]);
        Livewire::test(ListUnits::class)->searchTable('فيلا')->assertCanSeeTableRecords([$villa])->assertCanNotSeeTableRecords([$garden]);
        Livewire::test(ListUnits::class)->searchTable('garden-villa')->assertCanSeeTableRecords([$garden]);
    }

    public function test_bulk_publish_unpublish_feature_and_unfeature(): void
    {
        $this->actingAs($this->createAdmin());
        $units = Unit::factory()->count(2)->create(['is_published' => false]);
        $state = fn (string $column) => $units->map(fn (Unit $unit) => $unit->fresh()->{$column})->all();
        $list = Livewire::test(ListUnits::class);

        $list->callTableBulkAction('publish', $units);
        $this->assertSame([true, true], $state('is_published'));
        $list->callTableBulkAction('feature', $units);
        $this->assertSame([true, true], $state('is_featured'));
        $list->callTableBulkAction('unfeature', $units);
        $this->assertSame([false, false], $state('is_featured'));
        $list->callTableBulkAction('unpublish', $units);
        $this->assertSame([false, false], $state('is_published'));
    }

    public function test_a_project_with_units_cannot_be_deleted_and_shows_how_many(): void
    {
        $this->actingAs($this->createAdmin());
        $busy = $this->project();
        $free = $this->project();
        Unit::factory()->create(['project_id' => $busy->id]);
        Unit::factory()->create(['project_id' => $busy->id])->delete();

        $this->assertFalse(ProjectResource::canDelete($busy->fresh()));
        $this->assertSame(__('admin.projects.cannot_delete_with_units'), ProjectResource::getAuthorizationResponse('delete', $busy->fresh())->message());
        $this->assertTrue(ProjectResource::canDelete($free->fresh()));

        Livewire::test(ListProjects::class)
            ->assertTableColumnStateSet('units_count', 1, $busy)
            ->assertTableActionDisabled('delete', $busy);
    }

    public function test_a_gallery_used_by_units_cannot_be_deleted(): void
    {
        $this->actingAs($this->createAdmin());
        $used = Gallery::factory()->create();
        $free = Gallery::factory()->create();
        Unit::factory()->create(['gallery_id' => $used->id])->delete();

        $this->assertFalse(GalleryResource::canDelete($used->fresh()));
        $this->assertTrue(GalleryResource::canDelete($free->fresh()));
    }

    public function test_the_project_page_has_a_properties_tab_and_adding_from_it_starts_in_that_project(): void
    {
        $this->actingAs($this->createAdmin());
        $project = $this->project();
        $mine = Unit::factory()->create(['project_id' => $project->id]);
        $other = Unit::factory()->create();

        Livewire::test(UnitsRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class])
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$other]);

        Livewire::withQueryParams(['project' => $project->id])
            ->test(CreateUnit::class)
            ->assertSchemaStateSet(['project_id' => $project->id, 'region_id' => $this->place['new_cairo']->id]);
    }

    public function test_lookup_values_a_unit_uses_cannot_be_deleted(): void
    {
        $this->actingAs($this->createAdmin());
        $unit = Unit::factory()->create(['finishing_type_id' => $this->id('finishingtype', 'core'), 'city_id' => $this->place['giza']->id, 'country_id' => $this->place['egypt']->id]);
        $unit->lookupsOfType('amenities')->sync([$this->id('amenities', 'gym')]);

        $this->assertFalse(LookupResource::canDelete($this->value['finishingtype']['core']->fresh()));
        $this->assertFalse(LookupResource::canDelete($this->value['amenities']['gym']->fresh()));
        $this->assertFalse(LookupResource::canDelete($this->place['giza']->fresh()));
        $this->assertTrue(LookupResource::canDelete($this->value['finishingtype']['finished']->fresh()));
    }

    public function test_a_permanent_delete_removes_picks_media_and_price_history(): void
    {
        $unit = Unit::factory()->create(['price' => 1000000]);
        $unit->lookupsOfType('amenities')->sync([$this->id('amenities', 'pool')]);
        $path = $unit->addMedia(UploadedFile::fake()->image('c.jpg'))->toMediaCollection(Unit::FEATURED_IMAGE)->getPathRelativeToRoot();

        $unit->delete();
        $this->assertDatabaseHas('lookupables', ['lookupable_id' => $unit->id, 'lookupable_type' => Unit::class]);
        Storage::disk('public')->assertExists($path);

        $unit->forceDelete();
        $this->assertDatabaseMissing('lookupables', ['lookupable_id' => $unit->id, 'lookupable_type' => Unit::class]);
        $this->assertDatabaseMissing('unit_price_histories', ['unit_id' => $unit->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_the_view_page_is_read_only_with_edit_delete_and_price_history(): void
    {
        $this->actingAs($this->createAdmin());
        $unit = Unit::factory()->create(['bedrooms' => 3]);

        Livewire::test(ViewUnit::class, ['record' => $unit->getRouteKey()])
            ->assertSchemaStateSet(['bedrooms' => 3])
            ->assertActionVisible('edit')
            ->assertActionVisible('delete');

        $this->assertTrue(PriceHistoriesRelationManager::canViewForRecord($unit, ViewUnit::class));
        $this->assertFalse(PriceHistoriesRelationManager::canViewForRecord($unit, EditUnit::class));
    }

    public function test_numbers_are_checked(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateUnit::class)
            ->fillForm(['name_en' => 'U', 'name_ar' => 'و', 'price' => 9_000_000_000_000_000, 'area' => 99_999_999_999, 'bedrooms' => 500,
                'down_payment' => 150, 'installment_years_from' => 5, 'installment_years_to' => 2, 'video_url' => 'https://evil.example/x'])
            ->call('create')
            ->assertHasFormErrors(['price', 'area', 'bedrooms', 'down_payment', 'installment_years_to', 'video_url']);
    }
}
