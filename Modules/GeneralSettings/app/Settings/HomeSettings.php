<?php

namespace Modules\GeneralSettings\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * The homepage "Why Akar Town" block and its cards.
 */
class HomeSettings extends Settings
{
    public string $why_choose_subtitle_en;

    public string $why_choose_subtitle_ar;

    public string $why_choose_header_en;

    public string $why_choose_header_ar;

    public string $why_choose_body_en;

    public string $why_choose_body_ar;

    public string $why_choose_cta_text_en;

    public string $why_choose_cta_text_ar;

    public ?string $why_choose_cta_url;

    // Each card: icon (image path), header_en, header_ar, body_en, body_ar.
    public array $why_choose_cards;

    public static function group(): string
    {
        return 'home';
    }
}
