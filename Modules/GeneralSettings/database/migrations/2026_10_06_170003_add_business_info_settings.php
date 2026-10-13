<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Default content (from Dastan, rebranded to Akar Town); the admin edits it in General Settings.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('business_info.mini_description_en', 'Crafting iconic living spaces. Where architecture meets aspiration.');
        $this->migrator->add('business_info.mini_description_ar', 'نصنع مساحات معيشة مميزة، حيث تلتقي الهندسة المعمارية بالطموح.');
        $this->migrator->add('business_info.social_facebook', null);
        $this->migrator->add('business_info.social_instagram', null);
        $this->migrator->add('business_info.social_linkedin', null);
        $this->migrator->add('business_info.social_youtube', null);
        $this->migrator->add('business_info.social_tiktok', null);
        $this->migrator->add('business_info.headquarters_en', '');
        $this->migrator->add('business_info.headquarters_ar', '');
        $this->migrator->add('business_info.branch_1_en', null);
        $this->migrator->add('business_info.branch_1_ar', null);
        $this->migrator->add('business_info.branch_2_en', null);
        $this->migrator->add('business_info.branch_2_ar', null);
        $this->migrator->add('business_info.branch_3_en', null);
        $this->migrator->add('business_info.branch_3_ar', null);
        $this->migrator->add('business_info.branch_4_en', null);
        $this->migrator->add('business_info.branch_4_ar', null);
        $this->migrator->add('business_info.hotline', null);
        $this->migrator->add('business_info.phone_primary', null);
        $this->migrator->add('business_info.phone_secondary', null);
        $this->migrator->add('business_info.phone_tertiary', null);
        $this->migrator->add('business_info.whatsapp', null);
        $this->migrator->add('business_info.email', null);
        $this->migrator->add('business_info.tax_number', null);
        $this->migrator->add('business_info.working_days_from', 'monday');
        $this->migrator->add('business_info.working_days_to', 'saturday');
        $this->migrator->add('business_info.working_hours_from', '09:00');
        $this->migrator->add('business_info.working_hours_to', '18:00');
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('business_info.mini_description_en');
        $this->migrator->deleteIfExists('business_info.mini_description_ar');
        $this->migrator->deleteIfExists('business_info.social_facebook');
        $this->migrator->deleteIfExists('business_info.social_instagram');
        $this->migrator->deleteIfExists('business_info.social_linkedin');
        $this->migrator->deleteIfExists('business_info.social_youtube');
        $this->migrator->deleteIfExists('business_info.social_tiktok');
        $this->migrator->deleteIfExists('business_info.headquarters_en');
        $this->migrator->deleteIfExists('business_info.headquarters_ar');
        $this->migrator->deleteIfExists('business_info.branch_1_en');
        $this->migrator->deleteIfExists('business_info.branch_1_ar');
        $this->migrator->deleteIfExists('business_info.branch_2_en');
        $this->migrator->deleteIfExists('business_info.branch_2_ar');
        $this->migrator->deleteIfExists('business_info.branch_3_en');
        $this->migrator->deleteIfExists('business_info.branch_3_ar');
        $this->migrator->deleteIfExists('business_info.branch_4_en');
        $this->migrator->deleteIfExists('business_info.branch_4_ar');
        $this->migrator->deleteIfExists('business_info.hotline');
        $this->migrator->deleteIfExists('business_info.phone_primary');
        $this->migrator->deleteIfExists('business_info.phone_secondary');
        $this->migrator->deleteIfExists('business_info.phone_tertiary');
        $this->migrator->deleteIfExists('business_info.whatsapp');
        $this->migrator->deleteIfExists('business_info.email');
        $this->migrator->deleteIfExists('business_info.tax_number');
        $this->migrator->deleteIfExists('business_info.working_days_from');
        $this->migrator->deleteIfExists('business_info.working_days_to');
        $this->migrator->deleteIfExists('business_info.working_hours_from');
        $this->migrator->deleteIfExists('business_info.working_hours_to');
    }
};
