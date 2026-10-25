<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * The about page's round story photo, uploaded from Settings → About Us (empty: the design's photo is shown).
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('about_us.story_image', null);
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('about_us.story_image');
    }
};
