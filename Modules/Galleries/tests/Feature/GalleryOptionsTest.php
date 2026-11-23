<?php

namespace Modules\Galleries\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Once;
use Modules\Galleries\Models\Gallery;
use Modules\Galleries\Support\GalleryOptions;
use Modules\Projects\Models\Project;
use Tests\TestCase;

/**
 * The gallery pickers' choices, shared by a property's gallery and a project's galleries.
 */
class GalleryOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_arabic_letters_written_either_way_still_find_the_gallery(): void
    {
        $summit = Gallery::factory()->create(['name_en' => 'Summit Gallery', 'name_ar' => 'معرض مساكن القمة']);
        $october = Gallery::factory()->create(['name_en' => 'October Gallery', 'name_ar' => 'معرض أكتوبر']);
        $madinaty = Gallery::factory()->create(['name_en' => 'Madinaty Gallery', 'name_ar' => 'معرض مدينتي']);

        foreach (['القمه' => $summit, 'القمة' => $summit, 'اكتوبر' => $october, 'إكتوبر' => $october, 'مدينتى' => $madinaty, 'MADINATY' => $madinaty] as $typed => $gallery) {
            $this->assertSame([$gallery->id], array_keys(GalleryOptions::search(null, $typed)), $typed);
        }
    }

    public function test_labels_come_from_the_list_without_a_query_and_keep_the_chosen_order(): void
    {
        $project = Project::factory()->create(['title_en' => 'The Crown Tower']);
        $crown = Gallery::factory()->create(['project_id' => $project->id, 'name_en' => 'Crown Gallery']);
        $alone = Gallery::factory()->create(['name_en' => 'Alone Gallery']);
        GalleryOptions::search($project->id); // the list the picker shows

        $this->assertSame(0, $this->countQueries(fn () => GalleryOptions::labels([$alone->id, $crown->id], $project->id)));
        $this->assertSame([$alone->id => 'Alone Gallery — Standalone', $crown->id => 'Crown Gallery — The Crown Tower'], GalleryOptions::labels([$alone->id, $crown->id], $project->id));

        // One outside the first page of the list is read; a gallery that doesn't exist is left out.
        Gallery::factory()->count(GalleryOptions::LIMIT)->create(['name_en' => 'Aaa Gallery']);
        $late = Gallery::factory()->create(['name_en' => 'Zzz Gallery']);
        Once::flush();
        $this->assertSame([$late->id => 'Zzz Gallery — Standalone'], GalleryOptions::labels([$late->id, 999999], $project->id));
    }
}
