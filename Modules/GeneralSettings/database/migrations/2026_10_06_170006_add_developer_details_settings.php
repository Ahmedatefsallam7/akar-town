<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Default content (from Dastan, rebranded to Akar Town); the admin edits it in General Settings.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('developer_details.what_to_expect_cards', [
            [
                'header_en' => '',
                'header_ar' => '',
                'body_en' => '',
                'body_ar' => '',
                'icon' => null,
            ],
            [
                'header_en' => '',
                'header_ar' => '',
                'body_en' => '',
                'body_ar' => '',
                'icon' => null,
            ],
            [
                'header_en' => '',
                'header_ar' => '',
                'body_en' => '',
                'body_ar' => '',
                'icon' => null,
            ],
            [
                'header_en' => '',
                'header_ar' => '',
                'body_en' => '',
                'body_ar' => '',
                'icon' => null,
            ],
        ]);
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('developer_details.what_to_expect_cards');
    }
};
