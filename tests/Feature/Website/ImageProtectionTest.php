<?php

namespace Tests\Feature\Website;

use App\Support\Images\MediaSizes;
use App\Support\Images\ProtectedImages;
use App\Support\Website\Image;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Careers\Models\Applicant;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\Units\Models\Unit;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

/**
 * AKAR-51: the website's images can't be saved, opened on their own or copied by address.
 */
class ImageProtectionTest extends TestCase
{
    use RefreshDatabase;

    /** What a browser sends for an image shown inside one of our pages. */
    private const IN_A_PAGE = ['Sec-Fetch-Dest' => 'image', 'Sec-Fetch-Site' => 'same-origin'];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(ProtectedImages::DISK);
        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();
    }

    private function photo(): Media
    {
        // Fresh from the database: the sizes made after the upload are on it.
        return Unit::factory()->create()
            ->addMedia(UploadedFile::fake()->image('villa.jpg', 1600, 1200))
            ->toMediaCollection(Unit::FEATURED_IMAGE)
            ->refresh();
    }

    public function test_uploads_go_to_the_private_disk_never_under_storage(): void
    {
        $media = $this->photo();

        $this->assertSame([ProtectedImages::DISK, ProtectedImages::DISK], [$media->disk, $media->conversions_disk]);
        Storage::disk(ProtectedImages::DISK)->assertExists([$media->getPathRelativeToRoot(), $media->getPathRelativeToRoot('large')]);
        $this->assertStringStartsWith('/images/', $media->getUrl('large'));
        $this->assertStringNotContainsString('/storage/', $media->getUrl('large'));
        $this->assertStringContainsString('signature=', $media->getUrl('large'));
    }

    public function test_an_image_shows_inside_a_page_and_stays_in_the_browsers_cache_only(): void
    {
        $url = $this->photo()->getUrl('card');

        $this->withHeaders(self::IN_A_PAGE)->get($url)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/webp')
            ->assertHeader('Cross-Origin-Resource-Policy', 'same-origin')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('private', $this->withHeaders(self::IN_A_PAGE)->get($url)->headers->get('Cache-Control'));
    }

    public function test_an_image_opened_on_its_own_or_by_another_site_shows_nothing(): void
    {
        $url = $this->photo()->getUrl('large');

        // A new tab, the address pasted, "open image in new tab".
        $this->withHeaders(['Sec-Fetch-Dest' => 'document', 'Sec-Fetch-Site' => 'none'])->get($url)->assertForbidden();
        // Another site showing it.
        $this->withHeaders(['Sec-Fetch-Dest' => 'image', 'Sec-Fetch-Site' => 'cross-site'])->get($url)->assertForbidden();
        // Without its signature, or with another file's.
        $this->withHeaders(self::IN_A_PAGE)->get(strtok($url, '?'))->assertForbidden();
        $this->withHeaders(self::IN_A_PAGE)->get(str_replace('-large.webp', '-card.webp', $url))->assertForbidden();
    }

    public function test_a_visitor_never_gets_the_uploaded_original(): void
    {
        $media = $this->photo();
        $original = ProtectedImages::url($media->getPathRelativeToRoot());

        $this->withHeaders(self::IN_A_PAGE)->get($original)->assertNotFound();
        $this->withHeaders(self::IN_A_PAGE)->get(ProtectedImages::url('../.env'))->assertNotFound();
        $this->withHeaders(self::IN_A_PAGE)->get(ProtectedImages::url('settings/other/x.jpg'))->assertNotFound();
    }

    public function test_the_admin_sees_every_file_even_opened_on_its_own_and_with_the_site_off(): void
    {
        $media = $this->photo();
        $site = app(SiteSettings::class);
        $site->is_live = false;
        $site->save();
        $this->actingAs($this->createAdmin());

        $this->withHeaders(['Sec-Fetch-Dest' => 'document'])->get(ProtectedImages::url($media->getPathRelativeToRoot()))->assertOk();
    }

    public function test_a_link_that_ran_out_is_renewed_only_for_our_own_pages(): void
    {
        $path = $this->photo()->getPathRelativeToRoot('card');
        $url = ProtectedImages::url($path);

        $this->travel(ProtectedImages::LIFETIME * 3)->seconds();

        $this->withHeaders(self::IN_A_PAGE)->get($url)->assertRedirect(ProtectedImages::url($path));
        // A pasted / scripted copy (headers set again: withHeaders() keeps them for the next requests).
        $this->flushHeaders()->withHeaders(['Sec-Fetch-Dest' => 'image'])->get($url)->assertForbidden();
    }

    public function test_links_are_shared_for_a_quarter_hour_so_the_browser_reuses_them(): void
    {
        $path = $this->photo()->getPathRelativeToRoot('card');

        $this->assertSame(ProtectedImages::url($path), ProtectedImages::url($path));
        parse_str(parse_url(ProtectedImages::url($path), PHP_URL_QUERY), $query);
        $this->assertGreaterThanOrEqual(time() + ProtectedImages::LIFETIME, (int) $query['expires']);
        $this->assertLessThanOrEqual(time() + ProtectedImages::LIFETIME * 2, (int) $query['expires']);
    }

    public function test_the_watermark_switches_on_and_off_at_once_on_photos_never_on_logos(): void
    {
        $this->assertFileExists(resource_path(MediaSizes::WATERMARK));
        $media = $this->photo();
        $path = $media->getPathRelativeToRoot('large');
        $disk = Storage::disk(ProtectedImages::DISK);

        // Off: the size itself, and its link says so.
        config(['images.watermark' => false]);
        $this->assertSame($disk->path($path), MediaSizes::fileToSend($path));
        $this->assertStringNotContainsString('w&', ProtectedImages::url($path));

        // On: a watermarked copy, made once, with a new link (the browser doesn't keep showing the old image).
        config(['images.watermark' => true]);
        $copy = MediaSizes::fileToSend($path);
        $this->assertNotSame($disk->path($path), $copy);
        $this->assertFileExists($copy);
        $this->assertNotSame(md5_file($disk->path($path)), md5_file($copy));
        $this->assertMatchesRegularExpression('/[?&]v=\d+w&/', ProtectedImages::url($path));
        $this->withHeaders(self::IN_A_PAGE)->get(ProtectedImages::url($path))->assertOk();

        // Never on the admin's thumbnails or on logos ("whole").
        $this->assertFalse(MediaSizes::takesWatermark($media->getPathRelativeToRoot('thumb')));
        $this->assertFalse(MediaSizes::takesWatermark('5/conversions/logo-whole.webp'));
        $this->assertTrue(MediaSizes::takesWatermark('5/conversions/plan-large.webp'));
    }

    public function test_share_previews_get_a_week_long_link_to_the_small_size(): void
    {
        $media = $this->photo();
        parse_str(parse_url(Image::shareUrl($media), PHP_URL_QUERY), $query);

        $this->assertStringContainsString('-card.webp', Image::shareUrl($media));
        $this->assertGreaterThan(time() + 6 * 86400, (int) $query['expires']);
        // Fetched by their servers (no browser headers): allowed.
        $this->get(Image::shareUrl($media))->assertOk();
    }

    public function test_files_on_other_disks_keep_their_usual_links(): void
    {
        Storage::fake(Applicant::RESUME_DISK);
        $applicant = Applicant::factory()->create();
        $cv = $applicant->addMedia(UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf'))->toMediaCollection(Applicant::RESUME);

        $this->assertStringNotContainsString('/images/', $cv->getUrl());
    }

    public function test_pages_make_images_hard_to_save_print_or_screenshot(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        foreach (['[&_img]:select-none', '[&_img]:[-webkit-touch-callout:none]', '[&_img]:[-webkit-user-drag:none]', 'print:[&_img]:invisible', 'data-unfocused:[&_img]:blur-xl'] as $class) {
            $this->assertStringContainsString($class, $html);
        }
        $script = file_get_contents(resource_path('js/website/protect.js'));
        foreach (["'contextmenu'", "'dragstart'", "'PrintScreen'", "'s'", 'data-unfocused'] as $part) {
            $this->assertStringContainsString($part, $script);
        }
        $this->assertStringContainsString("import './website/protect'", file_get_contents(resource_path('js/website.js')));
    }

    public function test_moving_the_old_images_to_the_private_disk(): void
    {
        Storage::fake('public');
        $media = $this->photo();
        $media->forceFill(['disk' => 'public', 'conversions_disk' => 'public'])->saveQuietly();
        Storage::disk('public')->put($media->getKey().'/villa.jpg', 'x');
        Storage::disk('public')->put('settings/images/hero.jpg', 'x');

        $this->artisan('images:protect', ['--files-only' => true])->assertSuccessful();

        $this->assertSame(ProtectedImages::DISK, $media->fresh()->disk);
        Storage::disk(ProtectedImages::DISK)->assertExists([$media->getKey().'/villa.jpg', 'settings/images/hero.jpg']);
        Storage::disk('public')->assertMissing([$media->getKey().'/villa.jpg', 'settings/images/hero.jpg']);
    }
}
