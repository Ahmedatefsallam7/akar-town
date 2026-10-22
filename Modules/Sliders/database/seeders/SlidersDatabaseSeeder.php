<?php

namespace Modules\Sliders\Database\Seeders;

use Database\Seeders\Concerns\SeedsDemoData;
use Illuminate\Database\Seeder;
use Modules\Projects\Database\Seeders\ProjectsDatabaseSeeder;
use Modules\Projects\Models\Project;
use Modules\Sliders\Models\Slider;

/**
 * The fixed set of homepage blocks (admins can't add any): Dastan's hero slider plus the team lead's body blocks.
 * Safe to run again: adds only the missing blocks, so blocks edited in the admin are never overwritten.
 * Locally it then makes the demo home page: every block on, in the design's order, with the design's hero slides.
 */
class SlidersDatabaseSeeder extends Seeder
{
    use SeedsDemoData;

    private const BLOCKS = [
        ['title_en' => 'Homepage Hero Slider', 'title_ar' => 'سلايدر الصفحة الرئيسية', 'placement' => Slider::HOME_HERO, 'content_type' => 'projects', 'order' => 1],
        ['title_en' => 'Our Projects', 'title_ar' => 'مشاريعنا', 'placement' => Slider::HOME_BODY, 'content_type' => 'projects', 'order' => 1],
        ['title_en' => 'Featured Properties', 'title_ar' => 'عقارات مميزة', 'placement' => Slider::HOME_BODY, 'content_type' => 'properties', 'order' => 2],
        ['title_en' => 'Prime Destinations', 'title_ar' => 'وجهات مميزة', 'placement' => Slider::HOME_BODY, 'content_type' => 'locations', 'order' => 3],
        ['title_en' => 'CTA Ribbon', 'title_ar' => 'شريط الدعوة لاتخاذ إجراء', 'placement' => Slider::HOME_BODY, 'content_type' => 'cta_ribbon', 'order' => 4],
    ];

    /** The design's order of the home blocks (index.html). */
    private const DESIGN_ORDER = ['properties' => 1, 'projects' => 2, 'locations' => 3, 'cta_ribbon' => 4];

    public function run(): void
    {
        foreach (self::BLOCKS as $block) {
            // One hero; one body block of each content type.
            $key = $block['placement'] === Slider::HOME_HERO
                ? ['placement' => Slider::HOME_HERO]
                : ['placement' => $block['placement'], 'content_type' => $block['content_type']];
            Slider::query()->firstOrCreate($key, $block);
        }

        if (app()->isLocal()) {
            $this->demoHome();
        }
    }

    /**
     * Demo: every block on, the design's order, and the design's projects as hero slides.
     */
    private function demoHome(): void
    {
        $this->needs(ProjectsDatabaseSeeder::class);

        Slider::query()->update(['is_active' => true]);
        foreach (self::DESIGN_ORDER as $type => $order) {
            Slider::query()->where('placement', Slider::HOME_BODY)->where('content_type', $type)->update(['order' => $order]);
        }

        $hero = Slider::query()->where('placement', Slider::HOME_HERO)->first();
        $projects = Project::query()->whereIn('title_en', array_column(ProjectsDatabaseSeeder::HERO, 0))->pluck('id', 'title_en');
        foreach (array_column(ProjectsDatabaseSeeder::HERO, 0) as $order => $title) {
            if ($id = $projects[$title] ?? null) {
                $hero->items()->firstOrCreate(['project_id' => $id], ['order' => $order + 1, 'is_active' => true]);
            }
        }

        $this->stamps('sliders', 'slider_items');
    }
}
