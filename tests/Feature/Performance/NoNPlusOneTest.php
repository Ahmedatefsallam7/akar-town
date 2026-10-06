<?php

namespace Tests\Feature\Performance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Developers\Filament\Resources\DeveloperResource;
use Modules\Developers\Models\Developer;
use Modules\Locations\Filament\Resources\LocationResource;
use Modules\Locations\Models\Location;
use Modules\Lookups\Filament\Resources\LookupResource;
use Modules\Lookups\Models\Lookup;
use Modules\Projects\Filament\Resources\ProjectResource;
use Modules\Projects\Models\Project;
use Modules\Users\Filament\Resources\RoleResource;
use Modules\Users\Filament\Resources\UserResource;
use Modules\Users\Models\User;
use Tests\TestCase;

/**
 * List pages must not run a query per row (N+1): the same number of queries for 1 row and for a full page.
 */
class NoNPlusOneTest extends TestCase
{
    use RefreshDatabase;

    private function queriesFor(string $url): int
    {
        Cache::flush();

        return $this->countQueries(fn () => $this->get($url)->assertOk());
    }

    private function assertNoNPlusOne(string $url, callable $addRows): void
    {
        $this->get($url); // warm up (session, translations)
        $few = $this->queriesFor($url);

        $addRows();
        $many = $this->queriesFor($url);

        $this->assertSame($few, $many, "{$url}: {$few} queries with few rows, {$many} with a full page (N+1).");
    }

    public function test_users_list(): void
    {
        $this->actingAs($this->createAdmin());

        $this->assertNoNPlusOne(UserResource::getUrl('index'), fn () => User::factory()->count(9)->create([
            'role_id' => $this->role('user_roles_client')->id,
        ]));
    }

    public function test_users_list_for_a_non_admin_manager(): void
    {
        $this->actingAs($this->createUserWithPermissions(['users' => true]));

        $this->assertNoNPlusOne(UserResource::getUrl('index'), fn () => User::factory()->count(9)->create([
            'role_id' => $this->role('user_roles_client')->id,
        ]));
    }

    public function test_lookups_list(): void
    {
        $this->actingAs($this->createAdmin());
        $category = Lookup::create(['name_en' => 'Views', 'slug' => 'views']);
        $category->children()->create(['name_en' => 'View 0', 'slug' => 'view_0']);

        $this->assertNoNPlusOne(LookupResource::getUrl('values', ['record' => $category]), function () use ($category) {
            foreach (range(1, 8) as $i) {
                $category->children()->create(['name_en' => "View {$i}", 'slug' => "view_{$i}"]);
            }
        });
    }

    public function test_lookup_types_page_and_a_locations_level(): void
    {
        $this->actingAs($this->createAdmin());
        $locations = Lookup::create(['name_en' => 'Locations', 'slug' => 'locations']);
        $egypt = $locations->children()->create(['name_en' => 'Egypt', 'slug' => 'egypt']);
        $egypt->children()->create(['name_en' => 'Cairo', 'slug' => 'cairo'])->children()->create(['name_en' => 'Nasr City', 'slug' => 'nasr']);

        $this->assertNoNPlusOne(LookupResource::getUrl('index'), function () {
            foreach (range(1, 8) as $i) {
                Lookup::create(['name_en' => "Type {$i}", 'slug' => "type_{$i}"])->children()->create(['name_en' => 'Value', 'slug' => 'value']);
            }
        });

        $this->assertNoNPlusOne(LookupResource::getUrl('values', ['record' => $egypt]), function () use ($egypt) {
            foreach (range(1, 8) as $i) {
                $egypt->children()->create(['name_en' => "City {$i}", 'slug' => "city_{$i}"])->children()->create(['name_en' => 'Region', 'slug' => 'region']);
            }
        });
    }

    public function test_locations_list_with_images(): void
    {
        Storage::fake('public');
        $this->actingAs($this->createAdmin());
        $withImage = fn (Location $location) => $location->addMedia(UploadedFile::fake()->image('cover.jpg', 40, 40))->toMediaCollection(Location::FEATURED_IMAGE);
        $withImage(Location::factory()->create(['neighbourhoods' => ['A']]));

        $this->assertNoNPlusOne(LocationResource::getUrl('index'), fn () => Location::factory()->count(9)->create(['neighbourhoods' => ['A', 'B']])->each($withImage));
    }

    public function test_developers_list_with_logos_images_and_locations(): void
    {
        Storage::fake('public');
        $this->actingAs($this->createAdmin());
        $root = Lookup::create(['slug' => 'locations', 'name_en' => 'Locations']);
        $egypt = $root->children()->create(['slug' => 'egypt', 'name_en' => 'Egypt']);
        $withImages = function (Developer $developer) {
            $developer->addMedia(UploadedFile::fake()->image('logo.png', 40, 40))->toMediaCollection(Developer::LOGO);
            $developer->addMedia(UploadedFile::fake()->image('cover.jpg', 40, 40))->toMediaCollection(Developer::FEATURED_IMAGE);
        };
        $withImages(Developer::factory()->create(['country_id' => $egypt->id]));

        $this->assertNoNPlusOne(DeveloperResource::getUrl('index'), fn () => Developer::factory()->count(9)->create(['country_id' => $egypt->id])->each($withImages));
    }

    public function test_projects_list_with_logos_developers_currencies_and_locations(): void
    {
        Storage::fake('public');
        $this->actingAs($this->createAdmin());
        $root = Lookup::create(['slug' => 'locations', 'name_en' => 'Locations']);
        $egypt = $root->children()->create(['slug' => 'egypt', 'name_en' => 'Egypt']);
        $egp = Lookup::create(['slug' => 'currencies', 'name_en' => 'Currencies'])->children()->create(['slug' => 'egp', 'name_en' => 'EGP']);
        $project = fn () => Project::factory()->create(['country_id' => $egypt->id, 'currency_id' => $egp->id, 'price_from' => 1000000])
            ->addMedia(UploadedFile::fake()->image('logo.png', 40, 40))->toMediaCollection(Project::LOGO);
        $project();

        $this->assertNoNPlusOne(ProjectResource::getUrl('index'), fn () => collect(range(1, 9))->each(fn () => $project()));
    }

    public function test_roles_list(): void
    {
        $this->actingAs($this->createAdmin());
        // One deletable role from the start, so the "given to users?" check (run once per page) is in both counts.
        Lookup::create(['parent_id' => $this->role(Lookup::ROLES_SLUG)->id, 'name_en' => 'Sales', 'slug' => 'user_roles_sales']);

        $this->assertNoNPlusOne(RoleResource::getUrl('index'), function () {
            foreach (range(1, 7) as $i) {
                Lookup::create(['parent_id' => $this->role(Lookup::ROLES_SLUG)->id, 'name_en' => "Role {$i}", 'slug' => "user_roles_r{$i}"]);
            }
        });
    }

    public function test_users_list_never_loads_every_user(): void
    {
        $this->actingAs($this->createAdmin());
        User::factory()->count(30)->create();

        DB::enableQueryLog();
        $this->get(UserResource::getUrl('index'))->assertOk();

        // Only a page of users (limit) or a count, never "select * from users" for the whole table.
        $unbounded = collect(DB::getQueryLog())->pluck('query')
            ->filter(fn (string $sql) => str_starts_with($sql, 'select * from "users"') && ! str_contains($sql, 'limit'));

        $this->assertEmpty($unbounded->all(), 'The users list loaded the whole table: '.$unbounded->first());
    }
}
