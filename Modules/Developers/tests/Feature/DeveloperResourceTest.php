<?php

namespace Modules\Developers\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Developers\Database\Seeders\DevelopersDatabaseSeeder;
use Modules\Developers\Filament\Resources\DeveloperResource;
use Modules\Developers\Filament\Resources\DeveloperResource\Pages\CreateDeveloper;
use Modules\Developers\Filament\Resources\DeveloperResource\Pages\EditDeveloper;
use Modules\Developers\Filament\Resources\DeveloperResource\Pages\ListDevelopers;
use Modules\Developers\Filament\Resources\DeveloperResource\Pages\ViewDeveloper;
use Modules\Developers\Models\Developer;
use Modules\Lookups\Filament\Resources\LookupResource;
use Modules\Lookups\Models\Lookup;
use Modules\Lookups\Support\LocationTree;
use Modules\Users\Support\PermissionModules;
use Tests\TestCase;

class DeveloperResourceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * locations › Egypt › Cairo › New Cairo, plus Giza and UAE › Dubai.
     *
     * @return array<string, Lookup>
     */
    private function chain(): array
    {
        $root = Lookup::create(['slug' => LocationTree::ROOT_SLUG, 'name_en' => 'Locations', 'name_ar' => 'المواقع']);
        $add = fn (Lookup $parent, string $name) => $parent->children()->create(['slug' => str($name)->slug('_'), 'name_en' => $name, 'name_ar' => "ع {$name}"]);

        $nodes['egypt'] = $add($root, 'Egypt');
        $nodes['uae'] = $add($root, 'UAE');
        $nodes['cairo'] = $add($nodes['egypt'], 'Cairo');
        $nodes['giza'] = $add($nodes['egypt'], 'Giza');
        $nodes['dubai'] = $add($nodes['uae'], 'Dubai');
        $nodes['new_cairo'] = $add($nodes['cairo'], 'New Cairo');

        return $nodes;
    }

    private function validData(array $overrides = []): array
    {
        return $overrides + ['name_en' => 'Mountain View', 'name_ar' => 'ماونتن فيو'];
    }

    public function test_users_without_the_developers_permission_are_forbidden(): void
    {
        $this->actingAs($this->createUserWithPermissions(['locations' => true]));

        $this->get(DeveloperResource::getUrl('index'))->assertForbidden();
        $this->get(DeveloperResource::getUrl('create'))->assertForbidden();
    }

    public function test_admin_can_open_every_page(): void
    {
        $this->actingAs($this->createAdmin());
        $developer = Developer::factory()->create();

        $this->get(DeveloperResource::getUrl('index'))->assertOk();
        $this->get(DeveloperResource::getUrl('create'))->assertOk();
        $this->get(DeveloperResource::getUrl('view', ['record' => $developer]))->assertOk();
        $this->get(DeveloperResource::getUrl('edit', ['record' => $developer]))->assertOk();
    }

    public function test_developers_is_a_property_management_permission_module(): void
    {
        $this->assertSame('property_management', PermissionModules::all()['developers']['group'] ?? null);
    }

    public function test_list_shows_the_main_columns_and_keeps_the_optional_ones_hidden(): void
    {
        $this->actingAs($this->createAdmin());
        Developer::factory()->create(['name_en' => 'Sodic', 'email' => 'hello@sodic.test', 'tagline_en' => 'Live well']);

        Livewire::test(ListDevelopers::class)
            ->assertTableColumnVisible('featured_image')
            ->assertTableColumnVisible('logo')
            ->assertTableColumnVisible('is_featured')
            ->assertTableColumnVisible('is_published')
            ->assertTableColumnVisible('name_en')
            ->assertTableColumnVisible('name_ar')
            ->assertCanSeeTableRecords(Developer::all())
            ->assertTableColumnExists('email')
            ->assertTableColumnExists('phone')
            ->assertTableColumnExists('location')
            ->assertTableColumnExists('created_at');

        // Optional columns start switched off (the column picker turns them on).
        $this->get(DeveloperResource::getUrl('index'))
            ->assertSee('Sodic')
            ->assertDontSee('hello@sodic.test')
            ->assertDontSee('Live well');
    }

    public function test_admin_can_create_a_developer_with_every_field(): void
    {
        $this->actingAs($this->createAdmin());
        $tree = $this->chain();

        Livewire::test(CreateDeveloper::class)
            ->fillForm($this->validData([
                'is_featured' => true,
                'tagline_en' => 'Building happiness',
                'short_description_en' => 'Card text.',
                'description_en' => 'Full profile.',
                'email' => 'info@mountainview.test',
                'phone' => '+20 16166',
                'country_id' => $tree['egypt']->id,
                'city_id' => $tree['cairo']->id,
                'region_id' => $tree['new_cairo']->id,
                'neighbourhood' => 'Fifth Settlement',
                'meta_title_en' => 'Mountain View projects',
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $developer = Developer::sole();
        $this->assertSame('mountain-view', $developer->slug);
        $this->assertTrue($developer->is_published);
        $this->assertTrue($developer->is_featured);
        $this->assertSame([$tree['egypt']->id, $tree['cairo']->id, $tree['new_cairo']->id, null, null], [$developer->country_id, $developer->city_id, $developer->region_id, $developer->area_id, $developer->sub_area_id]);
        $this->assertSame('Fifth Settlement', $developer->neighbourhood);
        $this->assertSame('+20 16166', $developer->phone);
    }

    public function test_names_are_required_and_email_and_phone_must_be_valid(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateDeveloper::class)
            ->fillForm(['name_en' => '', 'name_ar' => '', 'email' => 'not-an-email', 'phone' => 'call us!'])
            ->call('create')
            ->assertHasFormErrors(['name_en' => 'required', 'name_ar' => 'required', 'email' => 'email', 'phone']);
    }

    public function test_the_head_office_city_must_belong_to_the_chosen_country(): void
    {
        $this->actingAs($this->createAdmin());
        $tree = $this->chain();

        Livewire::test(CreateDeveloper::class)
            ->fillForm($this->validData(['country_id' => $tree['egypt']->id, 'city_id' => $tree['dubai']->id]))
            ->call('create')
            ->assertHasFormErrors(['city_id']);
    }

    public function test_the_list_shows_the_head_office_location_as_text(): void
    {
        $this->actingAs($this->createAdmin());
        $tree = $this->chain();
        $developer = Developer::factory()->create(['country_id' => $tree['egypt']->id, 'city_id' => $tree['cairo']->id]);

        Livewire::test(ListDevelopers::class)
            ->assertTableColumnStateSet('location', 'Egypt › Cairo', $developer);
    }

    public function test_the_slug_is_unique_and_follows_the_name(): void
    {
        $this->actingAs($this->createAdmin());
        Developer::factory()->create(['name_en' => 'Sodic'])->delete();

        $this->assertSame('sodic-2', Developer::factory()->create(['name_en' => 'Sodic'])->slug);

        Livewire::test(CreateDeveloper::class)
            ->fillForm(['name_en' => 'Sodic'])
            ->assertSchemaStateSet(['slug' => 'sodic-3'])
            ->fillForm($this->validData())
            ->fillForm(['slug' => 'sodic'])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);
    }

    public function test_list_filters_by_the_location_chain(): void
    {
        $this->actingAs($this->createAdmin());
        $tree = $this->chain();
        $cairo = Developer::factory()->create(['country_id' => $tree['egypt']->id, 'city_id' => $tree['cairo']->id]);
        $dubai = Developer::factory()->create(['country_id' => $tree['uae']->id, 'city_id' => $tree['dubai']->id]);

        Livewire::test(ListDevelopers::class)
            ->filterTable('country_id', $tree['egypt']->id)
            ->assertCanSeeTableRecords([$cairo])
            ->assertCanNotSeeTableRecords([$dubai]);
    }

    public function test_deleting_is_soft_and_the_trashed_filter_shows_it_again(): void
    {
        $this->actingAs($this->createAdmin());
        $developer = Developer::factory()->create();

        Livewire::test(ListDevelopers::class)
            ->callTableAction('delete', $developer)
            ->assertCanNotSeeTableRecords([$developer])
            ->filterTable('trashed', true)
            ->assertCanSeeTableRecords([$developer])
            ->callTableAction('restore', $developer);

        $this->assertFalse($developer->fresh()->trashed());
    }

    public function test_bulk_publish_unpublish_feature_and_unfeature(): void
    {
        $this->actingAs($this->createAdmin());
        $developers = Developer::factory()->count(2)->create(['is_published' => false, 'is_featured' => false]);
        $state = fn (string $column) => $developers->map(fn (Developer $developer) => $developer->fresh()->{$column})->all();

        $list = Livewire::test(ListDevelopers::class);

        $list->callTableBulkAction('publish', $developers);
        $this->assertSame([true, true], $state('is_published'));

        $list->callTableBulkAction('feature', $developers);
        $this->assertSame([true, true], $state('is_featured'));

        $list->callTableBulkAction('unfeature', $developers);
        $this->assertSame([false, false], $state('is_featured'));

        $list->callTableBulkAction('unpublish', $developers);
        $this->assertSame([false, false], $state('is_published'));
    }

    public function test_bulk_status_actions_need_the_edit_and_bulk_permissions(): void
    {
        $this->actingAs($this->createUserWithPermissions(['developers' => ['view', 'delete', 'bulk']]));
        Developer::factory()->create();

        Livewire::test(ListDevelopers::class)
            ->assertTableBulkActionHidden('publish')
            ->assertTableBulkActionHidden('feature');
    }

    public function test_the_view_page_shows_the_tabs_read_only_with_edit_and_delete(): void
    {
        $this->actingAs($this->createAdmin());
        $developer = Developer::factory()->create(['tagline_en' => 'Building happiness']);

        Livewire::test(ViewDeveloper::class, ['record' => $developer->getRouteKey()])
            ->assertSchemaStateSet(['tagline_en' => 'Building happiness'])
            ->assertActionVisible('edit')
            ->assertActionVisible('delete')
            ->assertSee(__('admin.developers.identity'))
            ->assertSee(__('admin.developers.contact'))
            ->assertSee(__('admin.developers.media'));
    }

    public function test_logo_and_featured_image_are_saved_with_small_webp_copies_for_the_list(): void
    {
        Storage::fake('public');
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateDeveloper::class)
            ->fillForm($this->validData([
                'logo' => [UploadedFile::fake()->image('logo.png', 400, 200)],
                'featured_image' => [UploadedFile::fake()->image('cover.jpg', 1600, 600)],
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $developer = Developer::sole();

        foreach ([Developer::LOGO, Developer::FEATURED_IMAGE] as $collection) {
            $media = $developer->getFirstMedia($collection);
            $this->assertNotNull($media, "{$collection} missing");
            Storage::disk('public')->assertExists($media->getPathRelativeToRoot('thumb'));
            $this->assertStringEndsWith('.webp', $media->getPathRelativeToRoot('thumb'));
        }

        // The logo is never cropped: a wide logo keeps its shape.
        [$width, $height] = getimagesize(Storage::disk('public')->path($developer->getFirstMedia(Developer::LOGO)->getPathRelativeToRoot('thumb')));
        $this->assertSame([160, 80], [$width, $height]);
    }

    public function test_only_images_are_accepted_with_size_limits(): void
    {
        Storage::fake('public');
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateDeveloper::class)
            ->fillForm($this->validData([
                'logo' => [UploadedFile::fake()->image('logo.png')->size(3000)],
                'featured_image' => [UploadedFile::fake()->create('brochure.pdf', 100, 'application/pdf')],
            ]))
            ->call('create')
            ->assertHasFormErrors(['logo', 'featured_image']);
    }

    public function test_images_stay_in_the_trash_and_go_with_a_permanent_delete(): void
    {
        Storage::fake('public');
        $developer = Developer::factory()->create();
        $path = $developer->addMedia(UploadedFile::fake()->image('logo.png'))->toMediaCollection(Developer::LOGO)->getPathRelativeToRoot();

        $developer->delete();
        Storage::disk('public')->assertExists($path);

        $developer->forceDelete();
        Storage::disk('public')->assertMissing($path);
    }

    public function test_editing_updates_fields(): void
    {
        $this->actingAs($this->createAdmin());
        $developer = Developer::factory()->create();

        Livewire::test(EditDeveloper::class, ['record' => $developer->getRouteKey()])
            ->fillForm(['name_en' => 'Renamed', 'phone' => '19777', 'is_featured' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $developer->refresh();
        $this->assertSame(['Renamed', '19777', true], [$developer->name_en, $developer->phone, $developer->is_featured]);
    }

    public function test_a_city_a_developer_uses_cannot_be_deleted_from_lookups(): void
    {
        $this->actingAs($this->createAdmin());
        $tree = $this->chain();
        Developer::factory()->create(['country_id' => $tree['egypt']->id, 'city_id' => $tree['giza']->id]);

        $this->assertFalse(LookupResource::canDelete($tree['giza']->fresh()));
        $this->assertTrue(LookupResource::canDelete($tree['cairo']->fresh()));
    }

    public function test_the_seeder_adds_the_developers_once_by_name_only(): void
    {
        $this->seed(DevelopersDatabaseSeeder::class);
        $count = Developer::count();
        Developer::where('slug', 'mountain-view')->update(['name_en' => 'Mountain View (edited)']);

        $this->seed(DevelopersDatabaseSeeder::class);

        $this->assertSame(18, $count);
        $this->assertSame($count, Developer::count());
        $this->assertSame('Mountain View (edited)', Developer::where('slug', 'mountain-view')->value('name_en'));
        $this->assertNull(Developer::where('slug', 'sodic')->value('email'));
    }
}
