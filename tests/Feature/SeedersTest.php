<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Modules\Developers\Models\Developer;
use Modules\Projects\Database\Seeders\ProjectsDatabaseSeeder;
use Modules\Projects\Models\Project;
use Modules\Units\Models\Unit;
use Tests\TestCase;

/**
 * php artisan db:seed: the fixed data everywhere; locally the demo data in every module (and SEED_SCALE more rows);
 * each module's seeder also works alone. Photos are faked (no internet, the real photo cache isn't touched).
 */
class SeedersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // A temporary storage folder: the demo photo cache (storage/app/demo) gets the fake photos, not the real one.
        $this->app->useStoragePath(sys_get_temp_dir().'/seeders-test-'.getmypid());
        Storage::fake('public');
        Storage::fake('local');

        $photo = imagecreatetruecolor(40, 30);
        ob_start();
        imagejpeg($photo);
        Http::fake(['*' => Http::response(ob_get_clean())]);
    }

    private function local(int $scale = 0): void
    {
        $this->app['env'] = 'local';
        config(['app.seed_scale' => $scale]);
    }

    public function test_locally_every_module_gets_demo_data_with_its_fields_filled(): void
    {
        $this->local();

        $this->seed(DatabaseSeeder::class);

        foreach (['projects', 'units', 'galleries', 'careers', 'applicants', 'website_users', 'website_user_favourites', 'inquiries', 'meetings', 'unit_requests', 'possible_leads', 'slider_items'] as $table) {
            $this->assertGreaterThan(0, DB::table($table)->count(), "{$table} is empty");
        }

        foreach (Project::query()->get() as $project) {
            foreach (['project_number', 'developer_id', 'agent_id', 'description_ar', 'country_id', 'price_to', 'video_embed', 'meta_title_en', 'meta_keywords_ar', 'created_by'] as $field) {
                $this->assertNotEmpty($project->{$field}, "Project {$project->title_en}: {$field}");
            }
            foreach ([Project::FEATURED_IMAGE, Project::LOGO, Project::GALLERY, Project::MASTER_PLAN, Project::ABOUT_IMAGE] as $collection) {
                $this->assertTrue($project->hasMedia($collection), "Project {$project->title_en}: {$collection}");
            }
            $this->assertTrue($project->lookupsOfType('amenities')->exists());
        }

        foreach (Unit::query()->get() as $unit) {
            foreach (['agent_id', 'offering_type_id', 'ownership_type_id', 'gallery_id', 'latitude', 'delivery_date', 'description_en', 'meta_description_ar'] as $field) {
                $this->assertNotEmpty($unit->{$field}, "Unit {$unit->name_en}: {$field}");
            }
            $this->assertTrue($unit->hasMedia(Unit::FEATURED_IMAGE));
        }

        $this->assertTrue(Developer::query()->whereNull('tagline_en')->doesntExist());
        $this->assertSame(0, DB::table('units')->where('slug', 'like', 'bulk-%')->count()); // no SEED_SCALE
    }

    public function test_seed_scale_adds_bulk_rows_once(): void
    {
        $this->local(scale: 20);

        $this->seed(DatabaseSeeder::class);
        $counts = fn () => collect(['units' => 'slug', 'projects' => 'slug', 'inquiries' => 'email', 'website_users' => 'email'])
            ->map(fn (string $column, string $table) => DB::table($table)->where($column, 'like', 'bulk-%')->count())
            ->all();

        $this->assertSame(['units' => 20, 'projects' => 4, 'inquiries' => 4, 'website_users' => 4], $counts());
        $this->assertNotNull(DB::table('units')->where('slug', 'bulk-unit-1')->value('meta_title_en'));
        $this->assertTrue(DB::table('lookupables')->where('lookupable_type', Unit::class)->whereIn('lookupable_id', DB::table('units')->where('slug', 'like', 'bulk-%')->select('id'))->exists());

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(['units' => 20, 'projects' => 4, 'inquiries' => 4, 'website_users' => 4], $counts());
    }

    public function test_a_module_seeder_alone_runs_what_it_needs_first(): void
    {
        $this->local();

        $this->seed(ProjectsDatabaseSeeder::class);

        $this->assertGreaterThan(0, Developer::query()->count());
        $this->assertNotNull(Project::query()->where('title_en', 'The Pinnacle Residences')->value('agent_id'));
    }

    public function test_outside_local_only_the_fixed_data_is_seeded(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(0, Developer::query()->count());
        $this->assertSame(0, Project::query()->count());
        $this->assertNull(Developer::query()->value('tagline_en'));
    }
}
