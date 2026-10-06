<?php

namespace Modules\Galleries\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Galleries\Filament\Resources\GalleryResource;
use Modules\Galleries\Filament\Resources\GalleryResource\Pages\CreateGallery;
use Modules\Galleries\Filament\Resources\GalleryResource\Pages\EditGallery;
use Modules\Galleries\Filament\Resources\GalleryResource\Pages\ListGalleries;
use Modules\Galleries\Filament\Resources\GalleryResource\Pages\ViewGallery;
use Modules\Galleries\Models\Gallery;
use Modules\Projects\Filament\Resources\ProjectResource\Pages\EditProject;
use Modules\Projects\Filament\Resources\ProjectResource\Pages\ViewProject;
use Modules\Projects\Filament\Resources\ProjectResource\RelationManagers\GalleriesRelationManager;
use Modules\Projects\Models\Project;
use Modules\Users\Support\PermissionModules;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class GalleryResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_users_without_the_galleries_permission_are_forbidden(): void
    {
        $this->actingAs($this->createUserWithPermissions(['projects' => true]));

        $this->get(GalleryResource::getUrl('index'))->assertForbidden();
        $this->get(GalleryResource::getUrl('create'))->assertForbidden();
    }

    public function test_admin_can_open_every_page(): void
    {
        $this->actingAs($this->createAdmin());
        $gallery = Gallery::factory()->create();

        $this->get(GalleryResource::getUrl('index'))->assertOk();
        $this->get(GalleryResource::getUrl('create'))->assertOk();
        $this->get(GalleryResource::getUrl('view', ['record' => $gallery]))->assertOk();
        $this->get(GalleryResource::getUrl('edit', ['record' => $gallery]))->assertOk();
    }

    public function test_galleries_is_a_property_management_permission_module(): void
    {
        $this->assertSame('property_management', PermissionModules::all()['galleries']['group'] ?? null);
    }

    public function test_only_the_english_name_is_required(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateGallery::class)
            ->fillForm(['name_en' => ''])
            ->call('create')
            ->assertHasFormErrors(['name_en' => 'required'])
            ->assertHasNoFormErrors(['name_ar', 'project_id']);

        Livewire::test(CreateGallery::class)
            ->fillForm(['name_en' => 'R3 Apartments'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertNull(Gallery::sole()->name_ar);
    }

    public function test_admin_can_create_a_gallery_with_every_kind_of_media(): void
    {
        $this->actingAs($this->createAdmin());
        $project = Project::factory()->create();

        Livewire::test(CreateGallery::class)
            ->fillForm([
                'name_en' => 'R3 Apartments',
                'name_ar' => 'شقق R3',
                'project_id' => $project->id,
                'featured_image' => [UploadedFile::fake()->image('cover.jpg', 1200, 800)],
                'images_360' => [UploadedFile::fake()->image('pano.jpg', 4000, 2000)],
                'photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
                'floor_plans' => [UploadedFile::fake()->image('plan.png', 1500, 1000)],
                'attachments' => [UploadedFile::fake()->create('brochure.pdf', 500, 'application/pdf')],
                'video_url' => 'https://www.youtube.com/watch?v=abc123',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $gallery = Gallery::sole();
        $this->assertSame($project->id, $gallery->project_id);
        $this->assertSame('https://www.youtube.com/watch?v=abc123', $gallery->video_url);
        $this->assertCount(1, $gallery->getMedia(Gallery::IMAGES_360));
        $this->assertCount(2, $gallery->getMedia(Gallery::PHOTOS));
        $this->assertCount(1, $gallery->getMedia(Gallery::FLOOR_PLANS));
        $this->assertCount(1, $gallery->getMedia(Gallery::ATTACHMENTS));
        $this->assertTrue($gallery->getFirstMedia(Gallery::PHOTOS)->hasGeneratedConversion('thumb'));
        // 360° images are kept whole, with no copies (too big to resize on the server).
        $this->assertFalse($gallery->getFirstMedia(Gallery::IMAGES_360)->hasGeneratedConversion('thumb'));
    }

    public function test_wrong_files_and_sizes_are_rejected(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateGallery::class)
            ->fillForm([
                'name_en' => 'R3',
                'photos' => [UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf')],
                'floor_plans' => [UploadedFile::fake()->image('big.png')->size(11000)],
                'attachments' => [UploadedFile::fake()->create('tool.exe', 10, 'application/x-msdownload')],
                'images_360' => [UploadedFile::fake()->create('pano.pdf', 100, 'application/pdf')],
            ])
            ->call('create')
            ->assertHasFormErrors(['photos', 'floor_plans', 'attachments', 'images_360']);
        // Above 20 MB, Livewire refuses the upload itself (config/livewire.php), and the browser checks first.
    }

    public function test_upload_limits_allow_the_biggest_file_the_admin_accepts(): void
    {
        // 20 MB (360° images, attachments) must pass Livewire's upload and the media library.
        $this->assertContains('max:20480', config('livewire.temporary_file_upload.rules'));
        $this->assertGreaterThanOrEqual(20 * 1024 * 1024, config('media-library.max_file_size'));
    }

    public function test_a_failed_save_leaves_no_half_saved_gallery(): void
    {
        $this->actingAs($this->createAdmin());
        // Saving the attachment fails (as a too-big file once did): the whole save must be undone.
        Media::creating(fn () => throw new \RuntimeException('Attachment could not be saved.'));

        try {
            Livewire::test(CreateGallery::class)
                ->fillForm(['name_en' => 'Half Saved', 'attachments' => [UploadedFile::fake()->create('brochure.pdf', 500, 'application/pdf')]])
                ->call('create');
            $this->fail('The save should have failed.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Attachment could not be saved.', $exception->getMessage());
        }

        $this->assertDatabaseMissing('galleries', ['name_en' => 'Half Saved']);
    }

    public function test_the_video_url_must_be_an_https_youtube_or_vimeo_link(): void
    {
        $this->actingAs($this->createAdmin());

        foreach (['http://www.youtube.com/watch?v=x', 'https://evil.example/video', 'javascript:alert(1)', 'not a url'] as $url) {
            Livewire::test(CreateGallery::class)
                ->fillForm(['name_en' => 'R3', 'video_url' => $url])
                ->call('create')
                ->assertHasFormErrors(['video_url']);
        }

        foreach (['https://youtu.be/abc', 'https://vimeo.com/12345'] as $url) {
            Livewire::test(CreateGallery::class)
                ->fillForm(['name_en' => "R3 {$url}", 'video_url' => $url])
                ->call('create')
                ->assertHasNoFormErrors();
        }
    }

    public function test_list_shows_name_and_project_and_filters_by_project_and_trash(): void
    {
        $this->actingAs($this->createAdmin());
        $project = Project::factory()->create(['title_en' => 'Mountain Park']);
        $linked = Gallery::factory()->create(['name_en' => 'R3 Apartments', 'project_id' => $project->id]);
        $standalone = Gallery::factory()->create(['name_en' => 'Standalone Villas']);

        $this->get(GalleryResource::getUrl('index'))
            ->assertSee('R3 Apartments')
            ->assertSee('Mountain Park')
            ->assertSee(__('admin.galleries.no_project'));

        Livewire::test(ListGalleries::class)
            ->filterTable('project_id', $project->id)
            ->assertCanSeeTableRecords([$linked])
            ->assertCanNotSeeTableRecords([$standalone]);

        $standalone->delete();
        Livewire::test(ListGalleries::class)
            ->assertCanNotSeeTableRecords([$standalone])
            ->filterTable('trashed', true)
            ->assertCanSeeTableRecords([$standalone]);
    }

    public function test_the_arabic_name_falls_back_to_english(): void
    {
        $this->actingAs($this->createAdmin());
        app()->setLocale('ar');
        Gallery::factory()->create(['name_en' => 'Only English', 'name_ar' => null]);

        Livewire::test(ListGalleries::class)->assertSee('Only English');
    }

    public function test_opening_create_from_a_project_starts_with_that_project(): void
    {
        $this->actingAs($this->createAdmin());
        $project = Project::factory()->create();

        Livewire::withQueryParams(['project' => $project->id])
            ->test(CreateGallery::class)
            ->assertSchemaStateSet(['project_id' => $project->id]);
    }

    public function test_the_project_view_page_has_a_galleries_tab(): void
    {
        $this->actingAs($this->createAdmin());
        $project = Project::factory()->create();
        $mine = Gallery::factory()->create(['project_id' => $project->id]);
        $other = Gallery::factory()->create();

        Livewire::test(GalleriesRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class])
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$other])
            ->assertSee(__('admin.actions.create_title', ['name' => __('admin.galleries.singular')]));

        $this->assertTrue(GalleriesRelationManager::canViewForRecord($project, ViewProject::class));
        $this->assertFalse(GalleriesRelationManager::canViewForRecord($project, EditProject::class));
    }

    public function test_the_galleries_tab_is_hidden_without_the_galleries_permission(): void
    {
        $this->actingAs($this->createUserWithPermissions(['projects' => true]));

        $this->assertFalse(GalleriesRelationManager::canViewForRecord(Project::factory()->create(), ViewProject::class));
    }

    public function test_a_permanently_deleted_project_leaves_its_galleries_without_a_project(): void
    {
        $project = Project::factory()->create();
        $gallery = Gallery::factory()->create(['project_id' => $project->id]);

        $project->delete();
        $this->assertSame($project->id, $gallery->fresh()->project_id);

        $project->forceDelete();
        $this->assertNull($gallery->fresh()->project_id);
    }

    public function test_media_stays_in_the_trash_and_goes_with_a_permanent_delete(): void
    {
        $gallery = Gallery::factory()->create();
        $path = $gallery->addMedia(UploadedFile::fake()->create('brochure.pdf', 50, 'application/pdf'))->toMediaCollection(Gallery::ATTACHMENTS)->getPathRelativeToRoot();

        $gallery->delete();
        Storage::disk('public')->assertExists($path);

        $gallery->forceDelete();
        Storage::disk('public')->assertMissing($path);
    }

    public function test_the_view_page_is_read_only_with_edit_and_delete(): void
    {
        $this->actingAs($this->createAdmin());
        $gallery = Gallery::factory()->create(['video_url' => 'https://vimeo.com/1']);

        Livewire::test(ViewGallery::class, ['record' => $gallery->getRouteKey()])
            ->assertSchemaStateSet(['video_url' => 'https://vimeo.com/1'])
            ->assertActionVisible('edit')
            ->assertActionVisible('delete');
    }

    public function test_editing_updates_the_gallery(): void
    {
        $this->actingAs($this->createAdmin());
        $gallery = Gallery::factory()->create();

        Livewire::test(EditGallery::class, ['record' => $gallery->getRouteKey()])
            ->fillForm(['name_en' => 'Renamed', 'name_ar' => 'جديد'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['Renamed', 'جديد'], [$gallery->fresh()->name_en, $gallery->fresh()->name_ar]);
    }
}
