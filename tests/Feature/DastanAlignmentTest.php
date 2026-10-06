<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Developers\Filament\Resources\DeveloperResource\Pages\EditDeveloper;
use Modules\Developers\Filament\Resources\DeveloperResource\Pages\ListDevelopers;
use Modules\Developers\Filament\Resources\DeveloperResource\Pages\ViewDeveloper;
use Modules\Developers\Filament\Resources\DeveloperResource\RelationManagers\ProjectsRelationManager as DeveloperProjects;
use Modules\Developers\Models\Developer;
use Modules\Galleries\Filament\Resources\GalleryResource\Pages\ListGalleries;
use Modules\Galleries\Models\Gallery;
use Modules\Locations\Filament\Resources\LocationResource\Pages\ListLocations;
use Modules\Locations\Filament\Resources\LocationResource\Pages\ViewLocation;
use Modules\Locations\Filament\Resources\LocationResource\RelationManagers\ProjectsRelationManager as LocationProjects;
use Modules\Locations\Filament\Resources\LocationResource\RelationManagers\UnitsRelationManager as LocationUnits;
use Modules\Locations\Models\Location;
use Modules\Lookups\Models\Lookup;
use Modules\Projects\Filament\Resources\ProjectResource\Pages\CreateProject;
use Modules\Projects\Filament\Resources\ProjectResource\Pages\ListProjects;
use Modules\Projects\Models\Project;
use Modules\Units\Filament\Resources\UnitResource\Pages\ListUnits;
use Modules\Units\Models\Unit;
use Tests\TestCase;

/**
 * Screens and filters added so the finished modules match Dastan (2026-10-06), on top of the team lead's specs.
 */
class DastanAlignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_developers_page_lists_its_projects_and_adds_one_for_it(): void
    {
        $this->actingAs($this->createAdmin());
        $developer = Developer::factory()->create();
        $mine = Project::factory()->create(['developer_id' => $developer->id]);
        $other = Project::factory()->create();

        Livewire::test(DeveloperProjects::class, ['ownerRecord' => $developer, 'pageClass' => ViewDeveloper::class])
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$other]);

        $this->assertFalse(DeveloperProjects::canViewForRecord($developer, EditDeveloper::class));

        Livewire::withQueryParams(['developer' => $developer->id])
            ->test(CreateProject::class)
            ->assertSchemaStateSet(['developer_id' => $developer->id]);
    }

    public function test_a_locations_page_lists_the_projects_and_properties_in_it(): void
    {
        $this->actingAs($this->createAdmin());
        $root = Lookup::create(['slug' => 'locations', 'name_en' => 'Locations']);
        $egypt = $root->children()->create(['slug' => 'egypt', 'name_en' => 'Egypt']);
        $cairo = $egypt->children()->create(['slug' => 'cairo', 'name_en' => 'Cairo']);
        $giza = $egypt->children()->create(['slug' => 'giza', 'name_en' => 'Giza']);
        $page = Location::factory()->create(['country_id' => $egypt->id, 'city_id' => $cairo->id]);

        $inCairo = Project::factory()->create(['country_id' => $egypt->id, 'city_id' => $cairo->id]);
        $inGiza = Project::factory()->create(['country_id' => $egypt->id, 'city_id' => $giza->id]);
        $unitInCairo = Unit::factory()->create(['country_id' => $egypt->id, 'city_id' => $cairo->id]);
        $unitInGiza = Unit::factory()->create(['country_id' => $egypt->id, 'city_id' => $giza->id]);

        Livewire::test(LocationProjects::class, ['ownerRecord' => $page, 'pageClass' => ViewLocation::class])
            ->assertCanSeeTableRecords([$inCairo])->assertCanNotSeeTableRecords([$inGiza]);
        Livewire::test(LocationUnits::class, ['ownerRecord' => $page, 'pageClass' => ViewLocation::class])
            ->assertCanSeeTableRecords([$unitInCairo])->assertCanNotSeeTableRecords([$unitInGiza]);

        // A location page with no chain chosen shows nothing rather than everything.
        $this->assertSame(0, Location::factory()->create()->projects()->count());
    }

    public function test_galleries_show_their_properties_count_and_filter_standalone_and_used(): void
    {
        $this->actingAs($this->createAdmin());
        $standalone = Gallery::factory()->create();
        $linked = Gallery::factory()->create(['project_id' => Project::factory()->create()->id]);
        Unit::factory()->count(2)->create(['gallery_id' => $standalone->id]);

        Livewire::test(ListGalleries::class)
            ->assertTableColumnStateSet('units_count', 2, $standalone)
            ->filterTable('standalone', true)
            ->assertCanSeeTableRecords([$standalone])->assertCanNotSeeTableRecords([$linked]);

        Livewire::test(ListGalleries::class)
            ->filterTable('has_units', false)
            ->assertCanSeeTableRecords([$linked])->assertCanNotSeeTableRecords([$standalone]);
    }

    public function test_published_featured_active_and_has_projects_filters(): void
    {
        $this->actingAs($this->createAdmin());

        $published = Developer::factory()->create(['is_published' => true, 'is_featured' => true]);
        $hidden = Developer::factory()->create(['is_published' => false]);
        Project::factory()->create(['developer_id' => $published->id]);
        Livewire::test(ListDevelopers::class)->filterTable('is_published', true)->assertCanSeeTableRecords([$published])->assertCanNotSeeTableRecords([$hidden]);
        Livewire::test(ListDevelopers::class)->filterTable('is_featured', true)->assertCanSeeTableRecords([$published])->assertCanNotSeeTableRecords([$hidden]);
        Livewire::test(ListDevelopers::class)->filterTable('has_projects', true)->assertCanSeeTableRecords([$published])->assertCanNotSeeTableRecords([$hidden]);

        $active = Location::factory()->create(['is_active' => true, 'is_featured' => true]);
        $inactive = Location::factory()->create(['is_active' => false]);
        Livewire::test(ListLocations::class)->filterTable('is_active', false)->assertCanSeeTableRecords([$inactive])->assertCanNotSeeTableRecords([$active]);
        Livewire::test(ListLocations::class)->filterTable('is_featured', true)->assertCanSeeTableRecords([$active])->assertCanNotSeeTableRecords([$inactive]);

        $live = Project::factory()->create(['is_published' => true, 'is_featured' => true]);
        $draft = Project::factory()->create(['is_published' => false]);
        Livewire::test(ListProjects::class)->filterTable('is_published', true)->assertCanSeeTableRecords([$live])->assertCanNotSeeTableRecords([$draft]);
        Livewire::test(ListProjects::class)->filterTable('is_featured', true)->assertCanSeeTableRecords([$live])->assertCanNotSeeTableRecords([$draft]);

        $shown = Unit::factory()->create(['is_published' => true, 'is_featured' => true]);
        $notShown = Unit::factory()->create(['is_published' => false]);
        Livewire::test(ListUnits::class)->filterTable('is_published', false)->assertCanSeeTableRecords([$notShown])->assertCanNotSeeTableRecords([$shown]);
        Livewire::test(ListUnits::class)->filterTable('is_featured', true)->assertCanSeeTableRecords([$shown])->assertCanNotSeeTableRecords([$notShown]);
    }

    public function test_optional_columns_exist(): void
    {
        $this->actingAs($this->createAdmin());
        Location::factory()->create();
        Project::factory()->create();

        Livewire::test(ListLocations::class)->assertTableColumnExists('code')->assertTableColumnExists('location');
        Livewire::test(ListProjects::class)->assertTableColumnExists('featured_image');
    }
}
