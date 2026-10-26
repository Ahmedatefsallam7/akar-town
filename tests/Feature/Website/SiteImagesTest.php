<?php

namespace Tests\Feature\Website;

use App\Support\Website\SiteImages;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\GeneralSettings\Settings\WebsiteImagesSettings;
use Tests\TestCase;

/**
 * Settings → Website Images on the site: the uploaded photos (phones take their 900 px copy), else the design's.
 */
class SiteImagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();
    }

    private function upload(array $paths): void
    {
        $images = app(WebsiteImagesSettings::class);
        foreach ($paths as $key => $path) {
            $images->{$key} = $path;
            Storage::disk('public')->put($path, 'x');
            Storage::disk('public')->put(SiteImages::smallPath($path), 'x');
        }
        $images->save();
    }

    public function test_without_uploads_the_designs_photos_and_logo_are_used(): void
    {
        $this->assertSame([asset('images/website/contact-hero.webp'), asset('images/website/contact-hero-small.webp')], SiteImages::get('contact_hero'));

        $this->get(route('contact'))
            ->assertOk()
            ->assertSee([asset('images/website/contact-hero.webp'), asset('images/website/footer-bg.webp')], false)
            ->assertSee('viewBox="0 0 2185 380"', false); // the built-in logo
    }

    public function test_uploaded_photos_replace_them_everywhere(): void
    {
        $this->upload([
            'contact_hero' => 'settings/images/contact.jpg',
            'careers_hero' => 'settings/images/careers.jpg',
            'about_hero' => 'settings/images/about.jpg',
            'page_header' => 'settings/images/header.jpg',
            'why_background' => 'settings/images/why.jpg',
            'footer_background' => 'settings/images/footer.jpg',
            'logo' => 'settings/images/logo.png',
        ]);
        $url = fn (string $path) => Storage::disk('public')->url($path);

        $this->get(route('contact'))->assertOk()
            ->assertSee([$url('settings/images/contact.jpg'), $url('settings/images/contact-small.webp'), $url('settings/images/footer.jpg'), $url('settings/images/footer-small.webp'), $url('settings/images/logo.png')], false)
            ->assertDontSee('contact-hero.webp');
        $this->get(route('careers'))->assertOk()->assertSee($url('settings/images/careers.jpg'), false);
        $this->get(route('about'))->assertOk()->assertSee($url('settings/images/about.jpg'), false);
        $this->get(route('projects'))->assertOk()->assertSee($url('settings/images/header.jpg'), false);
    }

    public function test_a_photo_without_its_phone_copy_is_used_on_phones_too(): void
    {
        $images = app(WebsiteImagesSettings::class);
        $images->about_hero = 'settings/images/about.jpg';
        $images->save();
        Storage::disk('public')->put('settings/images/about.jpg', 'x');

        $full = Storage::disk('public')->url('settings/images/about.jpg');
        $this->assertSame([$full, $full], SiteImages::get('about_hero'));
    }
}
