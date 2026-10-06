<?php

namespace Modules\Sliders\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Sliders\Models\Slider;

/**
 * The fixed set of homepage blocks (admins can't add any): Dastan's hero slider plus the team lead's body blocks.
 * Safe to run again: seeds only when there are none, so blocks edited in the admin are never overwritten.
 */
class SlidersDatabaseSeeder extends Seeder
{
    private const BLOCKS = [
        ['title_en' => 'Homepage Hero Slider', 'title_ar' => 'سلايدر الصفحة الرئيسية', 'placement' => Slider::HOME_HERO, 'content_type' => 'projects', 'order' => 1],
        ['title_en' => 'Our Projects', 'title_ar' => 'مشاريعنا', 'placement' => Slider::HOME_BODY, 'content_type' => 'projects', 'order' => 1],
        ['title_en' => 'Featured Properties', 'title_ar' => 'عقارات مميزة', 'placement' => Slider::HOME_BODY, 'content_type' => 'properties', 'order' => 2],
        ['title_en' => 'Prime Destinations', 'title_ar' => 'وجهات مميزة', 'placement' => Slider::HOME_BODY, 'content_type' => 'locations', 'order' => 3],
        ['title_en' => 'CTA Ribbon', 'title_ar' => 'شريط الدعوة لاتخاذ إجراء', 'placement' => Slider::HOME_BODY, 'content_type' => 'cta_ribbon', 'order' => 4],
    ];

    public function run(): void
    {
        if (Slider::query()->exists()) {
            return;
        }

        foreach (self::BLOCKS as $block) {
            Slider::create($block);
        }
    }
}
