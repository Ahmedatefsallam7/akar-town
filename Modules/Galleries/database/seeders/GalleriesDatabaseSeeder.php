<?php

namespace Modules\Galleries\Database\Seeders;

use Database\Seeders\Concerns\SeedsDemoData;
use Illuminate\Database\Seeder;
use Modules\Galleries\Models\Gallery;
use Modules\Projects\Database\Seeders\ProjectsDatabaseSeeder;
use Modules\Projects\Models\Project;

/**
 * Demo: one full gallery per project (photos, floor plans, 360° images, a PDF, a video), and the existing galleries'
 * empty parts filled. Local only, see SeedsDemoData.
 */
class GalleriesDatabaseSeeder extends Seeder
{
    use SeedsDemoData;

    public function run(): void
    {
        if (! $this->demo()) {
            return;
        }

        $this->needs(ProjectsDatabaseSeeder::class);

        foreach (Project::query()->where('slug', 'not like', 'bulk-%')->get() as $project) {
            Gallery::query()->firstOrCreate(['project_id' => $project->id, 'name_en' => "{$project->title_en} Gallery"], ['name_ar' => 'معرض '.($project->title_ar ?: $project->title_en)]);
        }

        foreach (Gallery::query()->where('name_en', 'not like', 'Bulk %')->get() as $i => $gallery) {
            $this->fillEmpty($gallery, ['video_url' => self::VIDEO, 'name_ar' => 'معرض '.$gallery->name_en]);
            $photos = $this->rotate($i, 4);
            $this->mediaIfEmpty($gallery, Gallery::FEATURED_IMAGE, [$photos[0]]);
            $this->mediaIfEmpty($gallery, Gallery::PHOTOS, $photos);
            $this->mediaIfEmpty($gallery, Gallery::FLOOR_PLANS, [self::PLAN]);
            $this->mediaIfEmpty($gallery, Gallery::IMAGES_360, [$photos[2]]);
            $this->mediaIfEmpty($gallery, Gallery::ATTACHMENTS, [], $this->pdf());
        }

        $this->stamps('galleries');
    }
}
