<?php

namespace Modules\Lookups\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Lookups\Database\Seeders\LookupsDatabaseSeeder;
use Modules\Lookups\Models\Lookup;
use Tests\TestCase;

class LookupModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_slug_is_built_from_the_english_name_when_empty(): void
    {
        $lookup = Lookup::create(['name_en' => 'Property Types', 'name_ar' => 'أنواع العقارات']);

        $this->assertSame('property-types', $lookup->slug);
    }

    public function test_given_slug_is_kept(): void
    {
        $lookup = Lookup::create(['name_en' => 'Property Types', 'slug' => 'property_types']);

        $this->assertSame('property_types', $lookup->slug);
    }

    public function test_roles_scope_returns_only_roles(): void
    {
        $this->seedRoles();
        $category = Lookup::create(['name_en' => 'Views', 'slug' => 'views']);
        $category->children()->create(['name_en' => 'Sea View', 'slug' => 'sea_view']);

        $this->assertEqualsCanonicalizing(
            ['user_roles_admin', 'user_roles_editor', 'user_roles_client'],
            Lookup::roles()->pluck('slug')->all()
        );
    }

    public function test_without_roles_scope_hides_the_roles_category_and_its_roles(): void
    {
        $this->seedRoles();
        $category = Lookup::create(['name_en' => 'Views', 'slug' => 'views']);
        $category->children()->create(['name_en' => 'Sea View', 'slug' => 'sea_view']);

        $this->assertEqualsCanonicalizing(['views', 'sea_view'], Lookup::withoutRoles()->pluck('slug')->all());
    }

    public function test_deleting_a_category_soft_deletes_its_children(): void
    {
        $category = Lookup::create(['name_en' => 'Views', 'slug' => 'views']);
        $child = $category->children()->create(['name_en' => 'Sea View', 'slug' => 'sea_view']);

        $category->delete();

        $this->assertSoftDeleted($category);
        $this->assertSoftDeleted($child);
    }

    public function test_seeder_creates_categories_with_items_and_can_run_again(): void
    {
        $this->seed(LookupsDatabaseSeeder::class);
        $count = Lookup::count();

        $this->seed(LookupsDatabaseSeeder::class);

        $this->assertGreaterThan(0, $count);
        $this->assertSame($count, Lookup::count());
        $this->assertTrue(Lookup::where('slug', 'amenities')->whereNull('parent_id')->first()->children()->exists());
    }
}
