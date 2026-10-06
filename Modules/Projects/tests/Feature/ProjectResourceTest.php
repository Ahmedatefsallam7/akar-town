<?php

namespace Modules\Projects\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Developers\Filament\Resources\DeveloperResource;
use Modules\Developers\Filament\Resources\DeveloperResource\Pages\ListDevelopers;
use Modules\Developers\Models\Developer;
use Modules\Lookups\Filament\Resources\LookupResource;
use Modules\Lookups\Models\Lookup;
use Modules\Lookups\Support\LocationTree;
use Modules\Projects\Filament\Resources\ProjectResource;
use Modules\Projects\Filament\Resources\ProjectResource\Pages\CreateProject;
use Modules\Projects\Filament\Resources\ProjectResource\Pages\EditProject;
use Modules\Projects\Filament\Resources\ProjectResource\Pages\ListProjects;
use Modules\Projects\Filament\Resources\ProjectResource\Pages\ViewProject;
use Modules\Projects\Models\Project;
use Modules\Users\Support\PermissionModules;
use Tests\TestCase;

class ProjectResourceTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, Lookup> */
    private array $place = [];

    /** @var array<string, array<string, Lookup>> lookup type slug => value slug => value */
    private array $value = [];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $root = Lookup::create(['slug' => LocationTree::ROOT_SLUG, 'name_en' => 'Locations', 'name_ar' => 'المواقع']);
        $add = fn (Lookup $parent, string $name) => $parent->children()->create(['slug' => str($name)->slug(), 'name_en' => $name, 'name_ar' => "ع {$name}"]);
        $this->place['egypt'] = $add($root, 'Egypt');
        $this->place['uae'] = $add($root, 'UAE');
        $this->place['cairo'] = $add($this->place['egypt'], 'Cairo');
        $this->place['dubai'] = $add($this->place['uae'], 'Dubai');
        $this->place['new_cairo'] = $add($this->place['cairo'], 'New Cairo');
        $this->place['marina'] = $add($this->place['dubai'], 'Marina');

        foreach (['amenities' => ['pool', 'gym'], 'views' => ['sea', 'garden'], 'currencies' => ['egp', 'usd'], 'paymentmethod' => ['cash']] as $type => $values) {
            $parent = Lookup::create(['slug' => $type, 'name_en' => ucfirst($type), 'name_ar' => $type, 'is_deletable' => false]);
            foreach ($values as $slug) {
                $this->value[$type][$slug] = $parent->children()->create(['slug' => $slug, 'name_en' => ucfirst($slug), 'name_ar' => "ع {$slug}"]);
            }
        }
    }

    private function images(): array
    {
        return [
            'featured_image' => [UploadedFile::fake()->image('cover.jpg', 800, 600)],
            'logo' => [UploadedFile::fake()->image('logo.png', 300, 100)],
        ];
    }

    private function validData(array $overrides = []): array
    {
        return $overrides + [
            'title_en' => 'Mountain Park',
            'title_ar' => 'ماونتن بارك',
            'developer_id' => Developer::factory()->create()->id,
            'country_id' => $this->place['egypt']->id,
            'city_id' => $this->place['cairo']->id,
            'region_id' => $this->place['new_cairo']->id,
        ] + $this->images();
    }

    private function cairoProject(array $attributes = []): Project
    {
        return Project::factory()->create($attributes + ['country_id' => $this->place['egypt']->id, 'city_id' => $this->place['cairo']->id, 'region_id' => $this->place['new_cairo']->id]);
    }

    public function test_users_without_the_projects_permission_are_forbidden(): void
    {
        $this->actingAs($this->createUserWithPermissions(['developers' => true]));

        $this->get(ProjectResource::getUrl('index'))->assertForbidden();
        $this->get(ProjectResource::getUrl('create'))->assertForbidden();
    }

    public function test_admin_can_open_every_page(): void
    {
        $this->actingAs($this->createAdmin());
        $project = $this->cairoProject();

        $this->get(ProjectResource::getUrl('index'))->assertOk();
        $this->get(ProjectResource::getUrl('create'))->assertOk();
        $this->get(ProjectResource::getUrl('view', ['record' => $project]))->assertOk();
        $this->get(ProjectResource::getUrl('edit', ['record' => $project]))->assertOk();
    }

    public function test_projects_is_a_property_management_permission_module(): void
    {
        $this->assertSame('property_management', PermissionModules::all()['projects']['group'] ?? null);
    }

    public function test_admin_can_create_a_project_with_every_field(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateProject::class)
            ->fillForm($this->validData([
                'project_number' => 'MV-101',
                'short_description_en' => 'Card text.',
                'description_en' => '<p>Project <strong>body</strong>.</p>',
                'area_id' => null,
                'neighbourhood' => 'Fifth Settlement',
                'latitude' => '30.0074',
                'longitude' => '31.4913',
                'address_en' => '90th Street',
                'currency_id' => $this->value['currencies']['egp']->id,
                'price_from' => 3500000,
                'price_to' => 9000000,
                'area_from' => 120,
                'area_to' => 300,
                'down_payment' => 10,
                'installment_years_from' => 6,
                'installment_years_to' => 8,
                'delivery_date' => '2028-06-30',
                'is_featured' => true,
                'amenities' => [$this->value['amenities']['pool']->id, $this->value['amenities']['gym']->id],
                'views' => [$this->value['views']['sea']->id],
                'sales_payment' => [$this->value['paymentmethod']['cash']->id],
                'gallery' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
                'master_plan' => [UploadedFile::fake()->image('plan.png')],
                'video_embed' => '<iframe width="560" height="315" src="https://www.youtube.com/embed/abc123" title="Video" allowfullscreen></iframe>',
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $project = Project::sole();
        $this->assertSame('mountain-park', $project->slug);
        $this->assertSame([3500000, 9000000, 120, 300], [$project->price_from, $project->price_to, $project->area_from, $project->area_to]);
        $this->assertSame([10.0, 6, 8], [$project->down_payment, $project->installment_years_from, $project->installment_years_to]);
        $this->assertSame('2028-06-30', $project->delivery_date->toDateString());
        $this->assertTrue($project->is_published);
        $this->assertTrue($project->is_featured);
        $this->assertEqualsCanonicalizing([$this->value['amenities']['pool']->id, $this->value['amenities']['gym']->id], $project->lookupIds('amenities'));
        $this->assertSame([$this->value['views']['sea']->id], $project->lookupIds('views'));
        $this->assertSame([$this->value['paymentmethod']['cash']->id], $project->lookupIds('paymentmethod'));
        $this->assertCount(2, $project->getMedia(Project::GALLERY));
        $this->assertNotNull($project->getFirstMedia(Project::MASTER_PLAN));
        $this->assertStringEndsWith('.webp', $project->getFirstMedia(Project::LOGO)->getPathRelativeToRoot('thumb'));
    }

    public function test_title_developer_location_featured_image_and_logo_are_required(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateProject::class)
            ->fillForm(['title_en' => '', 'title_ar' => ''])
            ->call('create')
            ->assertHasFormErrors([
                'title_en' => 'required', 'title_ar' => 'required', 'developer_id' => 'required',
                'country_id' => 'required', 'city_id' => 'required', 'region_id' => 'required',
                'featured_image' => 'required', 'logo' => 'required',
            ]);
    }

    public function test_area_and_sub_area_are_optional_but_must_follow_the_chain(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateProject::class)
            ->fillForm($this->validData(['city_id' => $this->place['dubai']->id]))
            ->call('create')
            ->assertHasFormErrors(['city_id']);
    }

    public function test_to_values_cannot_be_below_from_values(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateProject::class)
            ->fillForm($this->validData([
                'price_from' => 5000000, 'price_to' => 1000000,
                'area_from' => 200, 'area_to' => 100,
                'installment_years_from' => 8, 'installment_years_to' => 5,
                'down_payment' => 150,
            ]))
            ->call('create')
            ->assertHasFormErrors(['price_to', 'area_to', 'installment_years_to', 'down_payment']);
    }

    public function test_numbers_too_big_for_the_database_are_rejected(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateProject::class)
            ->fillForm($this->validData(['area_from' => 9_999_999_999, 'price_from' => 9_000_000_000_000_000]))
            ->call('create')
            ->assertHasFormErrors(['area_from', 'price_from']);
    }

    public function test_only_safe_video_embed_codes_are_accepted(): void
    {
        $this->actingAs($this->createAdmin());

        $bad = [
            '<script>alert(1)</script>',
            '<iframe src="http://www.youtube.com/embed/x"></iframe>',
            '<iframe src="https://evil.example/embed/x"></iframe>',
            '<iframe src="https://www.youtube.com/embed/x" onload="alert(1)"></iframe>',
            '<iframe src="https://www.youtube.com/embed/x"></iframe><img src=x onerror=alert(1)>',
        ];

        foreach ($bad as $code) {
            Livewire::test(CreateProject::class)
                ->fillForm($this->validData(['video_embed' => $code]))
                ->call('create')
                ->assertHasFormErrors(['video_embed']);
        }

        Livewire::test(CreateProject::class)
            ->fillForm($this->validData(['video_embed' => '<iframe src="https://player.vimeo.com/video/123" allow="autoplay"></iframe>']))
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_a_checkbox_list_only_accepts_values_of_its_own_type(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateProject::class)
            ->fillForm($this->validData(['amenities' => [$this->value['views']['sea']->id]]))
            ->call('create')
            ->assertHasFormErrors(['amenities']);
    }

    public function test_editing_one_list_keeps_the_others_and_shows_saved_values(): void
    {
        $this->actingAs($this->createAdmin());
        $project = $this->cairoProject();
        $project->lookupsOfType('amenities')->sync([$this->value['amenities']['pool']->id]);
        $project->lookupsOfType('views')->sync([$this->value['views']['garden']->id]);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->assertSchemaStateSet(['amenities' => [$this->value['amenities']['pool']->id], 'views' => [$this->value['views']['garden']->id]])
            ->fillForm(['amenities' => [$this->value['amenities']['gym']->id]] + $this->images())
            ->call('save')
            ->assertHasNoFormErrors();

        $project->unsetRelation('lookups');
        $this->assertSame([$this->value['amenities']['gym']->id], $project->lookupIds('amenities'));
        $this->assertSame([$this->value['views']['garden']->id], $project->lookupIds('views'));
    }

    public function test_a_saved_value_that_was_switched_off_still_shows(): void
    {
        $this->actingAs($this->createAdmin());
        $project = $this->cairoProject();
        $project->lookupsOfType('amenities')->sync([$this->value['amenities']['pool']->id]);
        $this->value['amenities']['pool']->update(['is_active' => false]);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->assertSchemaComponentExists('amenities')
            ->assertSee('Pool');

        Livewire::test(CreateProject::class)->assertDontSee('Pool');
    }

    public function test_list_shows_the_main_columns_with_the_currency_and_hides_the_optional_ones(): void
    {
        $this->actingAs($this->createAdmin());
        $developer = Developer::factory()->create(['name_en' => 'Sodic']);
        $this->cairoProject(['title_en' => 'Villette', 'developer_id' => $developer->id, 'price_from' => 4500000, 'price_to' => 9900000, 'area_from' => 150, 'currency_id' => $this->value['currencies']['egp']->id]);

        $this->get(ProjectResource::getUrl('index'))
            ->assertSee('Villette')
            ->assertSee('Sodic')
            ->assertSee('4,500,000 EGP')
            ->assertSee('150')
            ->assertDontSee('9,900,000 EGP');
    }

    public function test_filters_by_price_and_area_ranges_developer_location_and_delivery_date(): void
    {
        $this->actingAs($this->createAdmin());
        $sodic = Developer::factory()->create();
        $cheap = $this->cairoProject(['price_from' => 1000000, 'price_to' => 2000000, 'area_from' => 80, 'delivery_date' => '2026-12-01']);
        $mid = $this->cairoProject(['price_from' => 3000000, 'price_to' => 6000000, 'area_from' => 150, 'developer_id' => $sodic->id, 'delivery_date' => '2028-01-01']);
        $dubai = Project::factory()->create(['price_from' => 9000000, 'area_from' => 400, 'country_id' => $this->place['uae']->id, 'city_id' => $this->place['dubai']->id, 'region_id' => $this->place['marina']->id]);

        Livewire::test(ListProjects::class)
            ->filterTable('price', ['min' => 2500000, 'max' => 5000000])
            ->assertCanSeeTableRecords([$mid])
            ->assertCanNotSeeTableRecords([$cheap, $dubai]);

        Livewire::test(ListProjects::class)
            ->filterTable('area', ['min' => 100])
            ->assertCanSeeTableRecords([$mid, $dubai])
            ->assertCanNotSeeTableRecords([$cheap]);

        Livewire::test(ListProjects::class)
            ->filterTable('developer_id', $sodic->id)
            ->assertCanSeeTableRecords([$mid])
            ->assertCanNotSeeTableRecords([$cheap, $dubai]);

        Livewire::test(ListProjects::class)
            ->filterTable('country_id', $this->place['uae']->id)
            ->assertCanSeeTableRecords([$dubai])
            ->assertCanNotSeeTableRecords([$cheap, $mid]);

        Livewire::test(ListProjects::class)
            ->filterTable('delivery_date', ['delivery_from' => '2027-01-01'])
            ->assertCanSeeTableRecords([$mid])
            ->assertCanNotSeeTableRecords([$cheap]);
    }

    public function test_bulk_publish_unpublish_feature_and_unfeature(): void
    {
        $this->actingAs($this->createAdmin());
        $projects = collect([$this->cairoProject(['is_published' => false]), $this->cairoProject(['is_published' => false])]);
        $state = fn (string $column) => $projects->map(fn (Project $project) => $project->fresh()->{$column})->all();
        $list = Livewire::test(ListProjects::class);

        $list->callTableBulkAction('publish', $projects);
        $this->assertSame([true, true], $state('is_published'));
        $list->callTableBulkAction('feature', $projects);
        $this->assertSame([true, true], $state('is_featured'));
        $list->callTableBulkAction('unfeature', $projects);
        $this->assertSame([false, false], $state('is_featured'));
        $list->callTableBulkAction('unpublish', $projects);
        $this->assertSame([false, false], $state('is_published'));
    }

    public function test_the_view_page_shows_the_tabs_read_only_with_edit_and_delete(): void
    {
        $this->actingAs($this->createAdmin());
        $project = $this->cairoProject(['project_number' => 'P-7']);

        Livewire::test(ViewProject::class, ['record' => $project->getRouteKey()])
            ->assertSchemaStateSet(['project_number' => 'P-7'])
            ->assertActionVisible('edit')
            ->assertActionVisible('delete')
            ->assertSee(__('admin.tabs.pricing_finance'))
            ->assertSee(__('admin.tabs.associations'));
    }

    public function test_the_trash_keeps_picked_values_and_images_and_a_permanent_delete_removes_them(): void
    {
        $this->actingAs($this->createAdmin());
        $project = $this->cairoProject();
        $project->lookupsOfType('amenities')->sync([$this->value['amenities']['pool']->id]);
        $path = $project->addMedia(UploadedFile::fake()->image('cover.jpg'))->toMediaCollection(Project::FEATURED_IMAGE)->getPathRelativeToRoot();

        Livewire::test(ListProjects::class)
            ->callTableAction('delete', $project)
            ->filterTable('trashed', true)
            ->assertCanSeeTableRecords([$project]);
        $this->assertDatabaseHas('lookupables', ['lookupable_id' => $project->id]);
        Storage::disk('public')->assertExists($path);

        $project->fresh()->forceDelete();
        $this->assertDatabaseMissing('lookupables', ['lookupable_id' => $project->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_lookup_values_a_project_uses_cannot_be_deleted(): void
    {
        $this->actingAs($this->createAdmin());
        $project = $this->cairoProject(['currency_id' => $this->value['currencies']['usd']->id]);
        $project->lookupsOfType('amenities')->sync([$this->value['amenities']['pool']->id]);

        $this->assertFalse(LookupResource::canDelete($this->value['amenities']['pool']->fresh()));
        $this->assertFalse(LookupResource::canDelete($this->value['currencies']['usd']->fresh()));
        $this->assertFalse(LookupResource::canDelete($this->place['new_cairo']->fresh()));
        $this->assertTrue(LookupResource::canDelete($this->value['amenities']['gym']->fresh()));
    }

    public function test_a_developer_with_projects_cannot_be_deleted_and_shows_its_count(): void
    {
        $this->actingAs($this->createAdmin());
        $busy = Developer::factory()->create();
        $free = Developer::factory()->create();
        $this->cairoProject(['developer_id' => $busy->id]);
        $this->cairoProject(['developer_id' => $busy->id])->delete();

        $this->assertFalse(DeveloperResource::canDelete($busy->fresh()));
        $this->assertSame(__('admin.developers.cannot_delete_with_projects'), DeveloperResource::getAuthorizationResponse('delete', $busy->fresh())->message());
        $this->assertTrue(DeveloperResource::canDelete($free->fresh()));

        Livewire::test(ListDevelopers::class)
            ->assertTableColumnStateSet('projects_count', 1, $busy)
            ->assertTableActionDisabled('delete', $busy)
            ->assertTableActionEnabled('delete', $free);
    }

    public function test_the_slug_follows_the_title_and_is_unique(): void
    {
        $this->actingAs($this->createAdmin());
        $this->cairoProject(['title_en' => 'Bloom Fields'])->delete();

        Livewire::test(CreateProject::class)
            ->fillForm(['title_en' => 'Bloom Fields'])
            ->assertSchemaStateSet(['slug' => 'bloom-fields-2']);
    }

    public function test_project_number_is_unique(): void
    {
        $this->actingAs($this->createAdmin());
        $this->cairoProject(['project_number' => 'P-1']);

        Livewire::test(CreateProject::class)
            ->fillForm($this->validData(['project_number' => 'P-1']))
            ->call('create')
            ->assertHasFormErrors(['project_number' => 'unique']);
    }
}
