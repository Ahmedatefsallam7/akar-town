<?php

namespace Modules\GeneralSettings\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * The website's photos that aren't part of a record (public disk paths; empty: the design's photo). Each one also gets
 * a 900 px phone copy when it is saved (see WebsiteImagesSettingsPage).
 */
class WebsiteImagesSettings extends Settings
{
    /** The logo on the dark navbar and footer (empty: the built-in Akar Town logo). */
    public ?string $logo;

    /** The listing pages' header (properties, projects, developers, locations, compare, a job). */
    public ?string $page_header;

    /** The home page's "Why Choose Us" background. */
    public ?string $why_background;

    public ?string $contact_hero;

    public ?string $careers_hero;

    public ?string $about_hero;

    public ?string $footer_background;

    public static function group(): string
    {
        return 'website_images';
    }
}
