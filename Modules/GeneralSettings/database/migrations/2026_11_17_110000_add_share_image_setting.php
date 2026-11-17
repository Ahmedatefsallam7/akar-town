<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Settings → Website Images: the image shown when a page is shared on social media (AKAR-50), empty until uploaded.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('website_images.share_image', null);
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('website_images.share_image');
    }
};
