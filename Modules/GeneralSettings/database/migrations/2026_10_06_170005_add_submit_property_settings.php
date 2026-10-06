<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Default content (from Dastan, rebranded to Akar Town); the admin edits it in General Settings.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('submit_property.why_list_mini_description_en', 'Reach serious buyers and investors actively searching for properties like yours - all under one trusted brand.');
        $this->migrator->add('submit_property.why_list_mini_description_ar', 'تواصل مع مشترين ومستثمرين جادين يبحثون بالفعل عن عقارات مثل عقارك - كل ذلك تحت علامة تجارية موثوقة.');
        $this->migrator->add('submit_property.points', [
            [
                'icon' => null,
                'header_en' => 'Targeted Exposure',
                'header_ar' => 'وصول مستهدف',
                'body_en' => 'Your unit reaches buyers already browsing premium real estate on Akar Town.',
                'body_ar' => 'يصل عقارك إلى مشترين يتصفحون بالفعل العقارات المميزة على عقار تاون.',
            ],
            [
                'icon' => null,
                'header_en' => 'Verified Listings',
                'header_ar' => 'إعلانات موثّقة',
                'body_en' => 'Our team reviews every submission so buyers trust what they see.',
                'body_ar' => 'يراجع فريقنا كل طلب لضمان ثقة المشترين فيما يرونه.',
            ],
            [
                'icon' => null,
                'header_en' => 'Fast Review',
                'header_ar' => 'مراجعة سريعة',
                'body_en' => 'A consultant gets back to you within one business day with next steps.',
                'body_ar' => 'يتواصل معك أحد مستشارينا خلال يوم عمل واحد لتوضيح الخطوات التالية.',
            ],
            [
                'icon' => null,
                'header_en' => 'End-to-End Support',
                'header_ar' => 'دعم متكامل',
                'body_en' => 'From listing to closing, we handle inquiries, viewings, and negotiations.',
                'body_ar' => 'من الإدراج وحتى إتمام الصفقة، نتولى الاستفسارات والمعاينات والتفاوض.',
            ],
        ]);
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('submit_property.why_list_mini_description_en');
        $this->migrator->deleteIfExists('submit_property.why_list_mini_description_ar');
        $this->migrator->deleteIfExists('submit_property.points');
    }
};
