<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Default content (from Dastan, rebranded to Akar Town); the admin edits it in General Settings.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('cta_ribbon.header_en', 'Your Dream Property Awaits');
        $this->migrator->add('cta_ribbon.header_ar', 'عقار أحلامك في انتظارك');
        $this->migrator->add('cta_ribbon.body_en', 'Connect with our expert team and discover exclusive real estate opportunities tailored for you.');
        $this->migrator->add('cta_ribbon.body_ar', 'تواصل مع فريقنا من الخبراء واكتشف فرصاً عقارية حصرية مصممة خصيصاً لك.');
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('cta_ribbon.header_en');
        $this->migrator->deleteIfExists('cta_ribbon.header_ar');
        $this->migrator->deleteIfExists('cta_ribbon.body_en');
        $this->migrator->deleteIfExists('cta_ribbon.body_ar');
    }
};
