<?php

use Modules\GeneralSettings\Settings\AboutUsSettings;
use Modules\GeneralSettings\Settings\BusinessInfoSettings;
use Modules\GeneralSettings\Settings\CtaRibbonSettings;
use Modules\GeneralSettings\Settings\DeveloperDetailsSettings;
use Modules\GeneralSettings\Settings\HomeSettings;
use Modules\GeneralSettings\Settings\PrivacyPolicySettings;
use Modules\GeneralSettings\Settings\SubmitPropertySettings;
use Modules\GeneralSettings\Settings\TermsOfUseSettings;

/*
 * spatie/laravel-settings: only what differs from the package defaults (merged with them).
 */
return [
    // Every settings class, listed so nothing is scanned on each request.
    'settings' => [
        HomeSettings::class,
        CtaRibbonSettings::class,
        BusinessInfoSettings::class,
        AboutUsSettings::class,
        SubmitPropertySettings::class,
        DeveloperDetailsSettings::class,
        PrivacyPolicySettings::class,
        TermsOfUseSettings::class,
    ],

    'auto_discover_settings' => [],
];
