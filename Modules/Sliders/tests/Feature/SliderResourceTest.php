<?php

namespace Modules\Sliders\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Projects\Filament\Resources\ProjectResource\Pages\EditProject;
use Modules\Projects\Filament\Resources\ProjectResource\Pages\ViewProject;
use Modules\Projects\Filament\Resources\ProjectResource\RelationManagers\SlidersRelationManager as ProjectSliders;
use Modules\Projects\Models\Project;
use Modules\Sliders\Database\Seeders\SlidersDatabaseSeeder;
use Modules\Sliders\Filament\Resources\SliderResource;
use Modules\Sliders\Filament\Resources\SliderResource\Pages\EditSlider;
use Modules\Sliders\Filament\Resources\SliderResource\Pages\ListSliders;
use Modules\Sliders\Filament\Resources\SliderResource\Pages\ViewSlider;
use Modules\Sliders\Filament\Resources\SliderResource\RelationManagers\SlidesRelationManager;
use Modules\Sliders\Models\Slider;
use Modules\Sliders\Models\SliderItem;
use Modules\Users\Support\PermissionModules;
use Tests\TestCase;

class SliderResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_without_the_sliders_permission_are_forbidden(): void
    {
        $this->actingAs($this->createUserWithPermissions(['projects' => true]));

        $this->get(SliderResource::getUrl('index'))->assertForbidden();
    }

    public function test_admin_can_open_every_page(): void
    {
        $this->actingAs($this->createAdmin());
        $slider = Slider::factory()->create();

        $this->get(SliderResource::getUrl('index'))->assertOk()->assertSee($slider->title_en);
        $this->get(SliderResource::getUrl('view', ['record' => $slider]))->assertOk();
        $this->get(SliderResource::getUrl('edit', ['record' => $slider]))->assertOk();
    }

    public function test_the_set_is_fixed_so_there_is_no_create_or_delete(): void
    {
        $this->actingAs($this->createAdmin());
        $slider = Slider::factory()->create();

        $this->assertSame(['view', 'edit', 'bulk'], PermissionModules::actionsFor('sliders'));
        $this->assertSame('marketing_content', PermissionModules::all()['sliders']['group']);
        $this->assertFalse(SliderResource::canCreate());
        $this->assertFalse(SliderResource::canDelete($slider));
        $this->assertArrayNotHasKey('create', SliderResource::getPages());

        Livewire::test(ListSliders::class)
            ->assertActionDoesNotExist('create')
            ->assertTableActionDoesNotExist('delete');
    }

    public function test_the_seeder_makes_the_hero_and_the_requested_blocks_once(): void
    {
        $this->seed(SlidersDatabaseSeeder::class);
        $this->seed(SlidersDatabaseSeeder::class);

        $this->assertSame(1, Slider::where('placement', Slider::HOME_HERO)->count());
        $this->assertSame(
            ['Our Projects' => 'projects', 'Featured Properties' => 'properties', 'Prime Destinations' => 'locations', 'CTA Ribbon' => 'cta_ribbon'],
            Slider::where('placement', Slider::HOME_BODY)->orderBy('order')->pluck('content_type', 'title_en')->all(),
        );
    }

    public function test_every_field_is_required_and_choices_must_be_known(): void
    {
        $this->actingAs($this->createAdmin());
        $slider = Slider::factory()->create();

        Livewire::test(EditSlider::class, ['record' => $slider->getRouteKey()])
            ->fillForm(['title_en' => '', 'title_ar' => '', 'placement' => null, 'content_type' => null, 'order' => null])
            ->call('save')
            ->assertHasFormErrors(['title_en' => 'required', 'title_ar' => 'required', 'placement' => 'required', 'content_type' => 'required', 'order' => 'required']);

        Livewire::test(EditSlider::class, ['record' => $slider->getRouteKey()])
            ->fillForm(['placement' => 'sidebar', 'content_type' => 'blogs', 'order' => 70000])
            ->call('save')
            ->assertHasFormErrors(['placement', 'content_type', 'order']);
    }

    public function test_editing_updates_the_block(): void
    {
        $this->actingAs($this->createAdmin());
        $slider = Slider::factory()->create(['is_active' => true]);

        Livewire::test(EditSlider::class, ['record' => $slider->getRouteKey()])
            ->fillForm(['title_en' => 'Prime Destinations', 'title_ar' => 'وجهات مميزة', 'placement' => Slider::NONE, 'content_type' => 'locations', 'is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertRedirect(SliderResource::getUrl('view', ['record' => $slider]));

        $slider->refresh();
        $this->assertSame(['Prime Destinations', 'وجهات مميزة', Slider::NONE, 'locations', false], [$slider->title_en, $slider->title_ar, $slider->placement, $slider->content_type, $slider->is_active]);
    }

    public function test_the_hero_always_shows_projects(): void
    {
        $this->actingAs($this->createAdmin());
        $hero = Slider::factory()->hero()->create();

        Livewire::test(EditSlider::class, ['record' => $hero->getRouteKey()])
            ->assertFormFieldIsDisabled('content_type')
            ->fillForm(['title_en' => 'Hero', 'content_type' => 'locations'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['Hero', 'projects'], [$hero->refresh()->title_en, $hero->content_type]);

        // Not even outside the form.
        $hero->update(['content_type' => 'locations']);
        $this->assertSame('projects', $hero->refresh()->content_type);
    }

    public function test_taking_the_order_of_another_block_in_the_same_placement_swaps_them(): void
    {
        $first = Slider::factory()->create(['order' => 1]);
        $second = Slider::factory()->create(['order' => 2]);
        $hero = Slider::factory()->hero()->create(['order' => 1]);

        $second->update(['order' => 1]);

        $this->assertSame([2, 1, 1], [$first->fresh()->order, $second->fresh()->order, $hero->fresh()->order]);
    }

    public function test_dragging_rows_sets_the_display_order(): void
    {
        $this->actingAs($this->createAdmin());
        [$a, $b, $c] = Slider::factory()->count(3)->sequence(['order' => 1], ['order' => 2], ['order' => 3])->create();

        Livewire::test(ListSliders::class)
            ->call('reorderTable', [(string) $c->id, (string) $a->id, (string) $b->id]);

        $this->assertSame([2, 3, 1], [$a->fresh()->order, $b->fresh()->order, $c->fresh()->order]);
    }

    public function test_view_only_users_cannot_reorder_or_edit(): void
    {
        $this->actingAs($this->createUserWithPermissions(['sliders' => ['view']]));
        $slider = Slider::factory()->create();

        $this->get(SliderResource::getUrl('index'))->assertOk();
        $this->get(SliderResource::getUrl('edit', ['record' => $slider]))->assertForbidden();
        Livewire::test(ListSliders::class)->assertTableActionHidden('edit', $slider);
        $this->assertFalse(SliderResource::canReorder());
    }

    public function test_filters_by_placement_content_type_and_created_date(): void
    {
        $this->actingAs($this->createAdmin());
        $hero = Slider::factory()->hero()->create(['created_at' => now()->subDays(10)]);
        $locations = Slider::factory()->create(['content_type' => 'locations']);

        Livewire::test(ListSliders::class)
            ->filterTable('placement', Slider::HOME_HERO)
            ->assertCanSeeTableRecords([$hero])->assertCanNotSeeTableRecords([$locations]);
        Livewire::test(ListSliders::class)
            ->filterTable('content_type', 'locations')
            ->assertCanSeeTableRecords([$locations])->assertCanNotSeeTableRecords([$hero]);
        Livewire::test(ListSliders::class)
            ->filterTable('created_at', ['created_from' => now()->subDay()->toDateString()])
            ->assertCanSeeTableRecords([$locations])->assertCanNotSeeTableRecords([$hero]);
    }

    public function test_bulk_activate_and_deactivate(): void
    {
        $this->actingAs($this->createAdmin());
        $sliders = Slider::factory()->count(2)->create(['is_active' => true]);

        Livewire::test(ListSliders::class)->callTableBulkAction('deactivate', $sliders);
        $this->assertSame(0, Slider::where('is_active', true)->count());

        Livewire::test(ListSliders::class)->callTableBulkAction('activate', $sliders);
        $this->assertSame(2, Slider::where('is_active', true)->count());
    }

    public function test_only_the_hero_slider_has_a_slides_tab_on_its_view_page(): void
    {
        $hero = Slider::factory()->hero()->create();

        $this->assertTrue(SlidesRelationManager::canViewForRecord($hero, ViewSlider::class));
        $this->assertFalse(SlidesRelationManager::canViewForRecord($hero, EditSlider::class));
        $this->assertFalse(SlidesRelationManager::canViewForRecord(Slider::factory()->create(), ViewSlider::class));
    }

    public function test_admin_adds_a_project_slide_at_the_end_once(): void
    {
        $this->actingAs($this->createAdmin());
        $hero = Slider::factory()->hero()->create();
        SliderItem::factory()->create(['slider_id' => $hero->id, 'order' => 4]);
        $project = Project::factory()->create();

        $slides = fn () => Livewire::test(SlidesRelationManager::class, ['ownerRecord' => $hero, 'pageClass' => ViewSlider::class]);

        $slides()->callTableAction('create', data: ['project_id' => $project->id])->assertHasNoTableActionErrors();
        $this->assertSame(5, $hero->items()->where('project_id', $project->id)->value('order'));

        $slides()->callTableAction('create', data: ['project_id' => $project->id])->assertHasTableActionErrors(['project_id' => 'unique']);
    }

    public function test_slides_list_their_projects_and_can_be_switched_off(): void
    {
        $this->actingAs($this->createAdmin());
        $hero = Slider::factory()->hero()->create();
        $slide = SliderItem::factory()->create(['slider_id' => $hero->id, 'project_id' => Project::factory()->create(['title_en' => 'Mountain Park'])->id]);
        $other = SliderItem::factory()->create();

        Livewire::test(SlidesRelationManager::class, ['ownerRecord' => $hero, 'pageClass' => ViewSlider::class])
            ->assertCanSeeTableRecords([$slide])
            ->assertCanNotSeeTableRecords([$other])
            ->assertSee('Mountain Park')
            ->callTableBulkAction('deactivate', [$slide]);

        $this->assertFalse($slide->fresh()->is_active);
        $this->assertTrue($other->fresh()->is_active);
    }

    public function test_view_only_users_cannot_change_slides(): void
    {
        $this->actingAs($this->createUserWithPermissions(['sliders' => ['view']]));
        $hero = Slider::factory()->hero()->create();
        $slide = SliderItem::factory()->create(['slider_id' => $hero->id]);

        Livewire::test(SlidesRelationManager::class, ['ownerRecord' => $hero, 'pageClass' => ViewSlider::class])
            ->assertCanSeeTableRecords([$slide])
            ->assertTableActionHidden('create')
            ->assertTableActionHidden('edit', $slide)
            ->assertTableActionHidden('delete', $slide)
            ->assertTableBulkActionHidden('deactivate');
    }

    public function test_the_project_view_page_has_a_sliders_tab_that_adds_it_to_the_hero(): void
    {
        $this->actingAs($this->createAdmin());
        $hero = Slider::factory()->hero()->create();
        SliderItem::factory()->create(['slider_id' => $hero->id, 'order' => 3]);
        $project = Project::factory()->create();
        $tab = fn () => Livewire::test(ProjectSliders::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class]);

        $this->assertTrue(ProjectSliders::canViewForRecord($project, ViewProject::class));
        $this->assertFalse(ProjectSliders::canViewForRecord($project, EditProject::class));

        $tab()->callTableAction('addToHero');
        $slide = $project->sliderItems()->sole();
        $this->assertSame([$hero->id, 4], [$slide->slider_id, $slide->order]);

        $tab()->assertCanSeeTableRecords([$slide])
            ->assertTableActionHidden('addToHero')
            ->callTableAction('delete', $slide);
        $this->assertSame(0, $project->sliderItems()->count());
    }

    public function test_the_project_sliders_tab_needs_the_sliders_permissions(): void
    {
        $project = Project::factory()->create();
        Slider::factory()->hero()->create();

        $this->actingAs($this->createUserWithPermissions(['projects' => true]));
        $this->assertFalse(ProjectSliders::canViewForRecord($project, ViewProject::class));

        $this->actingAs($this->createUserWithPermissions(['projects' => true, 'sliders' => ['view']]));
        Livewire::test(ProjectSliders::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class])
            ->assertTableActionHidden('addToHero');
    }

    public function test_a_permanently_deleted_project_takes_its_slide_with_it(): void
    {
        $slide = SliderItem::factory()->create();

        $slide->project->delete();
        $this->assertNotNull($slide->fresh());

        $slide->project->forceDelete();
        $this->assertNull($slide->fresh());
    }
}
