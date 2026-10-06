<?php

namespace Modules\GeneralSettings\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Company details for the footer and the Contact Us page.
 */
class BusinessInfoSettings extends Settings
{
    public string $mini_description_en;

    public string $mini_description_ar;

    public ?string $social_facebook;

    public ?string $social_instagram;

    public ?string $social_linkedin;

    public ?string $social_youtube;

    public ?string $social_tiktok;

    public string $headquarters_en;

    public string $headquarters_ar;

    public ?string $branch_1_en;

    public ?string $branch_1_ar;

    public ?string $branch_2_en;

    public ?string $branch_2_ar;

    public ?string $branch_3_en;

    public ?string $branch_3_ar;

    public ?string $branch_4_en;

    public ?string $branch_4_ar;

    public ?string $hotline;

    public ?string $phone_primary;

    public ?string $phone_secondary;

    public ?string $phone_tertiary;

    public ?string $whatsapp;

    public ?string $email;

    public ?string $tax_number;

    public string $working_days_from;

    public string $working_days_to;

    public string $working_hours_from;

    public string $working_hours_to;

    public static function group(): string
    {
        return 'business_info';
    }
}
