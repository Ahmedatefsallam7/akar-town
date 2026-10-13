<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Offline until an admin switches the site on from the dashboard (as in Dastan), so an unfinished site isn't public.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('site.is_live', false);
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('site.is_live');
    }
};
