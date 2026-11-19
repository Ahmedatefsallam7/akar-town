<?php

namespace Modules\GeneralSettings\Tests\Feature;

use App\Filament\Forms\ImageRules;
use App\Models\ImageSize;
use App\Rules\NotTooBigImage;
use App\Support\Images\ImageSizes;
use App\Support\Images\ImageSizesExport;
use App\Support\Images\ImageText;
use App\Support\Images\ImageValidation;
use App\Support\Images\ProtectedImages;
use App\Support\Website\SiteImages;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Once;
use Livewire\Livewire;
use Modules\GeneralSettings\Filament\Clusters\GeneralSettings\Pages\ImageSizesPage;
use Modules\GeneralSettings\Filament\Clusters\GeneralSettings\Pages\WebsiteImagesSettingsPage;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\GeneralSettings\Settings\WebsiteImagesSettings;
use Modules\Users\Support\PermissionModules;
use ReflectionClass;
use Tests\TestCase;

/**
 * AKAR-50: Settings → Image Sizes, the sizes behind every upload field and the shapes of the website's photos.
 */
class ImageSizesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_defaults_are_consistent_and_within_their_safe_limits(): void
    {
        $kinds = ImageSizes::KINDS;

        foreach (ImageSizes::fields() as $key => $field) {
            $limits = $kinds[$field['kind']];
            $this->assertNotEmpty(ImageSizes::placesOf($key), "{$key} is shown nowhere.");
            $this->assertSame($field['recommended'][0], max($limits['size'][0], min($limits['size'][1], $field['recommended'][0])), "{$key} width");
            $this->assertLessThanOrEqual($field['recommended'][0], $field['min'][0], "{$key} min width");
            $this->assertLessThanOrEqual($field['recommended'][1], $field['min'][1], "{$key} min height");
            $this->assertLessThanOrEqual(20, $limits['mb'][1], 'Above the server upload limit.');
            $this->assertEmpty(array_diff($field['formats'], array_keys(ImageSizes::FORMATS)), "{$key} formats");
            if ($field['crop']) {
                $this->assertSame($field['crop'], ImageText::ratio($field['recommended']), "{$key} is saved in another shape than its crop.");
            }
            // The defaults are already safe: making them safe changes nothing.
            $this->assertSame(ImageSizes::defaults("field:{$key}"), array_intersect_key(ImageSizes::safe("field:{$key}", ImageSizes::defaults("field:{$key}")), ImageSizes::defaults("field:{$key}")));
        }

        foreach (ImageSizes::places() as $key => $place) {
            $this->assertEmpty(array_diff($place['fields'], array_keys(ImageSizes::fields())), "{$key} fields");
            if ($place['display'] === 'shape') {
                $this->assertContains($place['shape'], $place['shapes'], $key);
                $this->assertNotSame('', ImageSizes::shapeClass($key));
                if ($place['phone']) {
                    $this->assertContains($place['phone_shape'], $place['phone_shapes'], $key);
                }
            }
        }
    }

    public function test_every_place_whose_shape_can_change_uses_it_on_the_website(): void
    {
        $views = collect(glob(resource_path('views/{website,components/website,livewire/website}/*.blade.php'), GLOB_BRACE))
            ->map(fn (string $file) => file_get_contents($file))->implode("\n");

        foreach (ImageSizes::places() as $key => $place) {
            if ($place['display'] === 'shape') {
                $this->assertStringContainsString("shapeClass('{$key}')", $views, "{$key} isn't wired to its shape.");
            }
        }
    }

    public function test_every_admin_image_upload_uses_the_image_sizes(): void
    {
        // Files that aren't images (a CV, a gallery's documents) have their own rules.
        $notImages = ["make('resume')", "make('attachments')"];
        $files = [...glob(app_path('Filament/**/*.php')) ?: [], ...glob(base_path('Modules/*/app/Filament/{,*/,*/*/,*/*/*/}*.php'), GLOB_BRACE) ?: []];

        foreach ($files as $file) {
            preg_match_all('/(ImageRules::apply\(\s*)?(?:SpatieMediaLibrary)?FileUpload::make\([^)]*\)/', file_get_contents($file), $uploads, PREG_SET_ORDER);
            foreach ($uploads as $match) {
                [$upload, $wrapped] = [$match[0], $match[1] ?? '']; // no group 1 when the upload isn't wrapped
                if ($wrapped === '' && ! str_contains($upload, $notImages[0]) && ! str_contains($upload, $notImages[1])) {
                    $this->fail("{$upload} in ".basename($file).' has no sizes: wrap it in ImageRules::apply() and add it to ImageSizes.');
                }
            }
        }
        $this->assertNotEmpty($files);
    }

    public function test_changes_are_kept_within_the_safe_limits(): void
    {
        ImageSizes::save('field:unit_featured', ['recommended' => [99999, 10], 'min' => [99999, 99999], 'mb' => 500, 'formats' => ['svg', 'exe']]);

        $field = ImageSizes::field('unit_featured');
        $this->assertSame([4000, 800], $field['recommended']);
        $this->assertSame([3000, 800], $field['min'], 'The minimum never goes above the recommended size.');
        $this->assertSame(10, $field['mb']);
        $this->assertSame(['jpg', 'png', 'webp'], $field['formats'], 'Unknown formats: the default ones.');
        $this->assertTrue($field['changed']);
    }

    public function test_a_cropped_image_keeps_its_shape(): void
    {
        ImageSizes::save('field:share_image', ['recommended' => [1800, 100], 'min' => [900, 1], 'mb' => 3, 'formats' => ['jpg']]);

        $field = ImageSizes::field('share_image');
        $this->assertSame([1800, 945], $field['recommended']);
        $this->assertSame([900, 473], $field['min']);
        $this->assertSame('40:21', ImageText::ratio([1800, 945]));
    }

    public function test_saving_the_default_values_is_no_change(): void
    {
        // The same formats in another order too.
        ImageSizes::save('field:project_logo', ['recommended' => [800, 800], 'min' => [200, 0], 'mb' => 2, 'formats' => ['jpg', 'png', 'webp']]);

        $this->assertSame(0, ImageSize::count());
        $this->assertFalse(ImageSizes::field('project_logo')['changed']);
    }

    public function test_a_change_reaches_the_upload_field_and_back_to_default_restores_it(): void
    {
        $this->actingAs($this->createAdmin());
        $default = ImageRules::hint('unit_featured');
        $this->assertStringContainsString(ImageText::size([1920, 1440]), $default);

        ImageSizes::save('field:unit_featured', ['recommended' => [2400, 1800], 'min' => [1600, 1200], 'mb' => 8, 'formats' => ['jpg', 'webp']]);
        $hint = ImageRules::hint('unit_featured');
        $this->assertStringContainsString(ImageText::size([2400, 1800]), $hint);
        $this->assertStringContainsString(ImageText::dimensions([1600, 1200]), $hint);
        $this->assertStringContainsString('JPG, WebP', $hint);
        $this->assertSame('dimensions:min_width=1600,min_height=1200', ImageValidation::minimumRule('unit_featured'));

        ImageSizes::reset('field:unit_featured');
        $this->assertSame($default, ImageRules::hint('unit_featured'));
    }

    public function test_a_place_takes_only_a_tested_shape(): void
    {
        $this->assertSame('aspect-[4/3]', ImageSizes::shapeClass('property_card'));
        $this->assertSame('aspect-square max-[700px]:aspect-video', ImageSizes::shapeClass('location_card'));

        ImageSizes::save('place:property_card', ['shape' => '1:1']);
        ImageSizes::save('place:location_card', ['shape' => '4:3', 'phone_shape' => '3:2']);
        $this->assertSame('aspect-square', ImageSizes::shapeClass('property_card'));
        $this->assertSame('aspect-[4/3] max-[700px]:aspect-[3/2]', ImageSizes::shapeClass('location_card'));

        // An untested shape is ignored, even written straight to the table.
        ImageSizes::save('place:property_card', ['shape' => '21:9']);
        $this->assertSame('aspect-[4/3]', ImageSizes::shapeClass('property_card'));
        ImageSize::query()->create(['key' => 'place:project_card', 'values' => ['shape' => '9:16']]);
        ImageSizes::reset('place:nothing');
        $this->assertSame('aspect-[16/10]', ImageSizes::shapeClass('project_card'));
    }

    public function test_the_changes_are_read_once_and_cached(): void
    {
        ImageSizes::save('place:property_card', ['shape' => '1:1']);

        $queries = $this->countQueries(function () {
            foreach (range(1, 30) as $i) {
                ImageSizes::shapeClass('property_card');
                ImageRules::hint('unit_featured');
            }
        });

        $this->assertLessThanOrEqual(1, $queries);
    }

    public function test_its_own_permission_with_view_and_edit(): void
    {
        $module = PermissionModules::all()['image_sizes'];
        $this->assertSame('system_configuration', $module['group']);
        $this->assertSame(['view', 'edit'], $module['actions']);

        $this->actingAs($this->createUserWithPermissions(['general_settings' => true]));
        $this->get(ImageSizesPage::getUrl())->assertForbidden();
    }

    public function test_admin_sees_a_card_for_every_image_in_both_languages(): void
    {
        $this->actingAs($this->createAdmin());

        foreach (['en', 'ar'] as $locale) {
            app()->setLocale($locale);
            $response = $this->withCookie('locale', $locale)->get(ImageSizesPage::getUrl())
                ->assertOk()
                ->assertSee(__('admin.image_sizes.actions.export'))
                ->assertSee('1920 × 1440')
                ->assertDontSee('admin.image_sizes.');
            foreach (array_keys(ImageSizes::fields()) as $key) {
                $response->assertSee(__("admin.image_sizes.fields.{$key}"));
            }
        }
    }

    public function test_view_only_users_see_and_export_but_cannot_edit(): void
    {
        $this->actingAs($this->createUserWithPermissions(['image_sizes' => ['view']]));

        Livewire::test(ImageSizesPage::class)
            ->assertOk()
            ->assertSee(__('admin.image_sizes.fields.unit_featured'))
            ->assertActionHidden('edit', ['field' => 'unit_featured'])
            ->assertActionVisible('export');
    }

    public function test_admin_edits_an_image_and_goes_back_to_default(): void
    {
        $this->actingAs($admin = $this->createAdmin());
        $sizes = ['width' => 2400, 'height' => 1800, 'min_width' => 1600, 'min_height' => 1200, 'mb' => 8, 'formats' => ['jpg', 'webp']];

        Livewire::test(ImageSizesPage::class)
            // Shown on every card, but it only works once the image was changed.
            ->assertActionVisible('reset', ['field' => 'unit_featured'])
            ->assertActionDisabled(TestAction::make('reset')->arguments(['field' => 'unit_featured']))
            ->callAction('edit', data: $sizes, arguments: ['field' => 'unit_featured'])
            ->assertHasNoActionErrors();

        $this->assertSame(['recommended' => [2400, 1800], 'min' => [1600, 1200], 'mb' => 8, 'formats' => ['jpg', 'webp']], ImageSize::sole()->values);
        $this->assertSame($admin->id, ImageSize::sole()->updated_by);

        // Out of the safe limits: refused in the form.
        Livewire::test(ImageSizesPage::class)
            ->callAction('edit', data: ['width' => 100, 'mb' => 50, 'formats' => []], arguments: ['field' => 'unit_featured'])
            ->assertHasActionErrors(['width', 'mb', 'formats']);

        // The card shows the change, and Back to default restores it.
        Livewire::test(ImageSizesPage::class)
            ->assertSee('2400 × 1800')
            ->assertSee(__('admin.image_sizes.status.changed'))
            ->assertActionEnabled(TestAction::make('reset')->arguments(['field' => 'unit_featured']))
            ->callAction('reset', arguments: ['field' => 'unit_featured']);
        $this->assertSame(0, ImageSize::count());
    }

    public function test_the_shapes_of_an_images_places_are_changed_in_its_edit_window(): void
    {
        $this->actingAs($this->createAdmin());
        $field = ImageSizes::field('location_featured');
        $sizes = ['width' => $field['recommended'][0], 'height' => $field['recommended'][1], 'min_width' => $field['min'][0], 'min_height' => $field['min'][1], 'mb' => $field['mb'], 'formats' => $field['formats']];

        Livewire::test(ImageSizesPage::class)
            ->mountAction('edit', ['field' => 'location_featured'])
            ->assertSchemaComponentExists('shape_location_card')
            // A place fixed by the layout (the location page header) has no shape to pick.
            ->assertSchemaComponentDoesNotExist('shape_location_header');

        Livewire::test(ImageSizesPage::class)
            ->callAction('edit', data: [...$sizes, 'shape_location_card' => '4:3', 'phone_location_card' => '1:1'], arguments: ['field' => 'location_featured'])
            ->assertHasNoActionErrors();

        $this->assertSame('aspect-[4/3] max-[700px]:aspect-square', ImageSizes::shapeClass('location_card'));
        $this->assertFalse(ImageSizes::field('location_featured')['changed'], 'Only the shape changed.');

        // Back to default restores the shapes too.
        Livewire::test(ImageSizesPage::class)->callAction('reset', arguments: ['field' => 'location_featured']);
        $this->assertSame('aspect-square max-[700px]:aspect-video', ImageSizes::shapeClass('location_card'));
    }

    public function test_the_excel_sheet_has_a_row_for_each_place_and_field_in_the_language_picked(): void
    {
        $expected = array_sum(array_map(fn (array $place) => count($place['fields']), ImageSizes::places()));

        $rows = ImageSizesExport::rows('en');
        $this->assertCount($expected + 1, $rows);
        $this->assertSame('Place on the website', $rows[0][1]);
        $card = collect($rows)->first(fn (array $row) => $row[1] === 'Property card');
        $this->assertSame(['Classic (4:3)', 420, 840, 'Property: featured image', ImageText::dimensions([1920, 1440]), 'No'], [$card[4], $card[6], $card[7], $card[8], $card[9], $card[14]]);

        // All in Arabic: no English left in the names.
        $rows = ImageSizesExport::rows('ar');
        $this->assertCount($expected + 1, $rows);
        $this->assertSame(__('admin.image_sizes.export.columns.place', [], 'ar'), $rows[0][1]);
        $card = collect($rows)->first(fn (array $row) => $row[1] === __('admin.image_sizes.places.property_card', [], 'ar'));
        $this->assertSame([__('admin.image_sizes.shapes.4:3', [], 'ar'), __('admin.image_sizes.fields.unit_featured', [], 'ar'), __('admin.image_sizes.export.no', [], 'ar')], [$card[4], $card[8], $card[14]]);

        $this->actingAs($this->createUserWithPermissions(['image_sizes' => ['view']]));
        Livewire::test(ImageSizesPage::class)
            ->mountAction('export')
            ->assertActionDataSet(['locale' => 'en'])
            ->callMountedAction()
            ->assertFileDownloaded('image-sizes-en-'.now()->format('Y-m-d').'.xlsx');
        Livewire::test(ImageSizesPage::class)
            ->callAction('export', data: ['locale' => 'ar'])
            ->assertFileDownloaded(ImageSizesExport::fileName('ar'));
        // Only English or Arabic.
        Livewire::test(ImageSizesPage::class)
            ->callAction('export', data: ['locale' => 'fr'])
            ->assertHasActionErrors(['locale']);
    }

    public function test_the_share_image_is_uploaded_cropped_and_used_when_a_page_is_shared(): void
    {
        Storage::fake(ProtectedImages::DISK);
        $this->actingAs($this->createAdmin());

        // The website is open (it starts closed in the tests).
        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();

        $this->get(route('home'))->assertOk()->assertSee('og:image', false)->assertSee('images/website/about-hero.webp', false);

        Livewire::test(WebsiteImagesSettingsPage::class)
            ->fillForm(['share_image' => [UploadedFile::fake()->image('share.jpg', 1200, 630)]])
            ->call('save')
            ->assertHasNoFormErrors();
        $path = app(WebsiteImagesSettings::class)->refresh()->share_image;

        $this->get(route('home'))->assertSee(ProtectedImages::url($path, ProtectedImages::SHARE_LIFETIME, forAdmin: false))->assertSee('summary_large_image', false);
        Storage::disk(ProtectedImages::DISK)->assertMissing(SiteImages::smallPath($path));

        // Another shape is refused.
        Livewire::test(WebsiteImagesSettingsPage::class)
            ->fillForm(['share_image' => [UploadedFile::fake()->image('square.jpg', 1200, 1200)]])
            ->call('save')
            ->assertHasFormErrors(['share_image']);
    }

    public function test_a_huge_image_is_refused_even_as_a_small_file(): void
    {
        // Only the header: a PNG that says 10000 × 10000 px (100 megapixels) in a few bytes, like a "pixel bomb".
        $header = 'IHDR'.pack('NN', 10000, 10000)."\x08\x02\x00\x00\x00";
        $path = tempnam(sys_get_temp_dir(), 'bomb');
        file_put_contents($path, "\x89PNG\r\n\x1a\n".pack('N', 13).$header.pack('N', crc32($header)));
        $bomb = new UploadedFile($path, 'bomb.png', 'image/png', null, true);
        $fine = UploadedFile::fake()->image('fine.jpg', 1600, 1200);

        $this->assertTrue(Validator::make(['photo' => $bomb], ['photo' => [new NotTooBigImage]])->fails());
        $this->assertFalse(Validator::make(['photo' => $fine], ['photo' => [new NotTooBigImage]])->fails());
        @unlink($path);
    }

    public function test_values_changed_outside_the_page_are_made_safe_when_read(): void
    {
        // Written straight to the table (not through the page): an SVG, a huge file size, a tiny size.
        ImageSize::query()->create(['key' => 'field:unit_featured', 'values' => ['formats' => ['svg'], 'mb' => 999, 'recommended' => [1, 1], 'min' => [-5, 99999]]]);
        ImageSizes::reset('field:nothing'); // clears the cache

        $field = ImageSizes::field('unit_featured');
        $this->assertSame(['jpg', 'png', 'webp'], $field['formats']);
        $this->assertSame(10, $field['mb']);
        $this->assertSame([800, 800], $field['recommended']);
        $this->assertSame([0, 800], $field['min']);
    }

    public function test_a_card_button_sent_for_an_unknown_image_does_nothing(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(ImageSizesPage::class)
            ->assertActionDisabled(TestAction::make('edit')->arguments(['field' => 'not_an_image']))
            ->assertActionDisabled(TestAction::make('reset')->arguments(['field' => 'not_an_image']))
            ->callAction('edit', data: ['width' => 2000], arguments: ['field' => 'not_an_image'])
            ->assertOk();

        $this->assertSame(0, ImageSize::count());
    }

    public function test_the_site_keeps_working_while_the_table_is_not_made_yet(): void
    {
        // As during an update, before `migrate` has run.
        Schema::drop('image_sizes');
        Cache::flush();
        Once::flush();

        $this->assertSame('aspect-[4/3]', ImageSizes::shapeClass('property_card'));
        $this->assertSame([1920, 1440], ImageSizes::field('unit_featured')['recommended']);
        $this->assertFalse(Cache::has('image_sizes.changed'), 'Nothing cached: the table is read once it exists.');
    }

    public function test_the_page_has_a_search_and_a_summary(): void
    {
        $this->actingAs($this->createAdmin());
        ImageSizes::save('place:property_card', ['shape' => '1:1']);

        $this->get(ImageSizesPage::getUrl())
            ->assertOk()
            ->assertSee(__('admin.image_sizes.search'))
            ->assertSee(__('admin.image_sizes.summary', ['count' => count(ImageSizes::fields())]))
            ->assertSee(__('admin.image_sizes.summary_changed', ['count' => 1]))
            ->assertSee(__('admin.image_sizes.only_changed'));
    }

    public function test_the_registry_has_no_unused_shapes_without_tailwind_classes(): void
    {
        $shapes = (new ReflectionClass(ImageSizes::class))->getConstant('SHAPES');
        $phone = (new ReflectionClass(ImageSizes::class))->getConstant('PHONE_SHAPES');

        foreach (ImageSizes::places() as $place) {
            foreach ($place['shapes'] as $shape) {
                $this->assertArrayHasKey($shape, $shapes);
            }
            foreach ($place['phone_shapes'] as $shape) {
                $this->assertArrayHasKey($shape, $phone[$place['phone']]);
            }
        }
    }
}
