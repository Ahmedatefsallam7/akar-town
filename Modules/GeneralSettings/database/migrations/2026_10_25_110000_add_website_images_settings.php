<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Settings → Website Images: every photo empty, so the site keeps the design's photos until one is uploaded.
 */
return new class extends SettingsMigration
{
    private const KEYS = ['logo', 'page_header', 'why_background', 'contact_hero', 'careers_hero', 'about_hero', 'footer_background'];

    public function up(): void
    {
        foreach (self::KEYS as $key) {
            $this->migrator->add("website_images.{$key}", null);
        }
    }

    public function down(): void
    {
        foreach (self::KEYS as $key) {
            $this->migrator->deleteIfExists("website_images.{$key}");
        }
    }
};
