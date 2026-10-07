<?php

namespace Modules\Careers\Tests\Feature;

use Filament\Tables\Filters\QueryBuilder\Constraints\SelectConstraint\Operators\IsOperator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Careers\Filament\Resources\ApplicantResource;
use Modules\Careers\Filament\Resources\ApplicantResource\Pages\CreateApplicant;
use Modules\Careers\Filament\Resources\ApplicantResource\Pages\EditApplicant;
use Modules\Careers\Filament\Resources\ApplicantResource\Pages\ListApplicants;
use Modules\Careers\Filament\Resources\ApplicantResource\Pages\ViewApplicant;
use Modules\Careers\Filament\Resources\CareerResource\Pages\ListCareers;
use Modules\Careers\Mail\ApplicationStatusChanged;
use Modules\Careers\Models\Applicant;
use Modules\Careers\Models\Career;
use Modules\Users\Support\PermissionModules;
use Tests\TestCase;

class ApplicantResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(Applicant::RESUME_DISK);
        Storage::fake('public');
    }

    public function test_users_without_the_permission_are_forbidden(): void
    {
        $this->actingAs($this->createUserWithPermissions(['careers' => true]));

        $this->get(ApplicantResource::getUrl('index'))->assertForbidden();
    }

    public function test_admin_can_open_every_page(): void
    {
        $this->actingAs($this->createAdmin());
        $applicant = Applicant::factory()->create();

        $this->get(ApplicantResource::getUrl('index'))->assertOk()->assertSee($applicant->name);
        $this->get(ApplicantResource::getUrl('create'))->assertOk();
        $this->get(ApplicantResource::getUrl('view', ['record' => $applicant]))->assertOk();
        $this->get(ApplicantResource::getUrl('edit', ['record' => $applicant]))->assertOk();
    }

    public function test_it_is_an_inquiries_and_crm_permission_module(): void
    {
        $this->assertSame('inquiries_crm', PermissionModules::all()['applicants']['group'] ?? null);
    }

    public function test_required_fields_and_checked_values(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateApplicant::class)
            ->fillForm(['name' => '', 'email' => '', 'phone' => '', 'career_id' => null, 'status' => null])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required', 'email' => 'required', 'phone' => 'required', 'career_id' => 'required', 'status' => 'required'])
            ->assertHasNoFormErrors(['gender', 'linkedin_profile', 'resume', 'cover_letter']);

        Livewire::test(CreateApplicant::class)
            ->fillForm(['name' => 'Sara', 'email' => 'nope', 'phone' => '0100', 'career_id' => Career::factory()->create()->id, 'gender' => 'other', 'linkedin_profile' => 'javascript:alert(1)'])
            ->call('create')
            ->assertHasFormErrors(['email', 'gender', 'linkedin_profile']);
    }

    public function test_hr_adds_an_application_with_a_cv_kept_private(): void
    {
        $this->actingAs($this->createAdmin());
        $career = Career::factory()->create();

        Livewire::test(CreateApplicant::class)
            ->assertSchemaStateSet(['status' => Applicant::PENDING])
            ->fillForm([
                'name' => 'Sara Ahmed',
                'email' => 'sara@example.com',
                'phone' => '+201001234567',
                'career_id' => $career->id,
                'gender' => 'female',
                'linkedin_profile' => 'https://www.linkedin.com/in/sara',
                'resume' => [UploadedFile::fake()->create('sara-cv.pdf', 300, 'application/pdf')],
                'cover_letter' => 'I have five years in real estate sales.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $applicant = Applicant::sole();
        $this->assertSame([$career->id, Applicant::PENDING, 'female'], [$applicant->career_id, $applicant->status, $applicant->gender]);

        $cv = $applicant->getFirstMedia(Applicant::RESUME);
        $this->assertSame(Applicant::RESUME_DISK, $cv->disk);
        Storage::disk(Applicant::RESUME_DISK)->assertExists($cv->getPathRelativeToRoot());
        // Nothing on the public disk, so there is no public link to the CV.
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_only_pdf_or_word_cvs_up_to_5_mb(): void
    {
        $this->actingAs($this->createAdmin());
        $fields = ['name' => 'Sara', 'email' => 's@example.com', 'phone' => '0100', 'career_id' => Career::factory()->create()->id];

        Livewire::test(CreateApplicant::class)
            ->fillForm([...$fields, 'resume' => [UploadedFile::fake()->create('cv.exe', 10, 'application/x-msdownload')]])
            ->call('create')
            ->assertHasFormErrors(['resume']);

        Livewire::test(CreateApplicant::class)
            ->fillForm([...$fields, 'resume' => [UploadedFile::fake()->create('cv.pdf', 6000, 'application/pdf')]])
            ->call('create')
            ->assertHasFormErrors(['resume']);
    }

    public function test_the_cv_downloads_from_the_record(): void
    {
        $this->actingAs($this->createAdmin());
        $applicant = Applicant::factory()->create();
        $applicant->addMedia(UploadedFile::fake()->create('omar-cv.pdf', 100, 'application/pdf'))->toMediaCollection(Applicant::RESUME);
        $withoutCv = Applicant::factory()->create();

        Livewire::test(ViewApplicant::class, ['record' => $applicant->getRouteKey()])
            ->callAction('downloadResume')
            ->assertFileDownloaded('omar-cv.pdf');

        Livewire::test(ViewApplicant::class, ['record' => $withoutCv->getRouteKey()])->assertActionHidden('downloadResume');
    }

    public function test_bulk_status_changes_leave_decisions_alone(): void
    {
        $this->actingAs($this->createAdmin());
        $pending = Applicant::factory()->create();
        $reviewed = Applicant::factory()->create(['status' => Applicant::REVIEWED]);
        $accepted = Applicant::factory()->create(['status' => Applicant::ACCEPTED]);
        $all = [$pending, $reviewed, $accepted];
        $statuses = fn () => array_map(fn (Applicant $applicant) => $applicant->fresh()->status, $all);

        Livewire::test(ListApplicants::class)->callTableBulkAction('mark_reviewed', $all);
        $this->assertSame([Applicant::REVIEWED, Applicant::REVIEWED, Applicant::ACCEPTED], $statuses());

        Livewire::test(ListApplicants::class)->callTableBulkAction('mark_rejected', $all);
        $this->assertSame([Applicant::REJECTED, Applicant::REJECTED, Applicant::ACCEPTED], $statuses());

        Livewire::test(ListApplicants::class)->callTableBulkAction('mark_accepted', $all);
        $this->assertSame([Applicant::REJECTED, Applicant::REJECTED, Applicant::ACCEPTED], $statuses());

        // One by one, HR can still change a decision.
        Livewire::test(EditApplicant::class, ['record' => $accepted->getRouteKey()])
            ->fillForm(['status' => Applicant::REJECTED])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame(Applicant::REJECTED, $accepted->fresh()->status);
    }

    public function test_the_applicant_is_emailed_when_hr_changes_the_status(): void
    {
        Mail::fake();
        $this->actingAs($this->createAdmin());
        $career = Career::factory()->create(['title_en' => 'Sales Consultant', 'title_ar' => 'مستشار مبيعات']);
        $applicant = Applicant::factory()->create(['career_id' => $career->id, 'email' => 'sara@example.com', 'name' => 'Sara']);

        // A new application (pending) sends nothing; editing other details sends nothing.
        $applicant->update(['phone' => '+201000000000']);
        Mail::assertNothingQueued();

        Livewire::test(EditApplicant::class, ['record' => $applicant->getRouteKey()])
            ->fillForm(['status' => Applicant::ACCEPTED])
            ->call('save')
            ->assertHasNoFormErrors();

        Mail::assertQueued(ApplicationStatusChanged::class, fn (ApplicationStatusChanged $mail) => $mail->hasTo('sara@example.com'));
        Mail::assertQueuedCount(1);

        $mail = new ApplicationStatusChanged($applicant->fresh());
        $mail->assertHasSubject('Your application for Sales Consultant | طلبك لوظيفة مستشار مبيعات');
        $mail->assertSeeInHtml('Hello Sara,');
        $mail->assertSeeInHtml('Accepted');
        $mail->assertSeeInHtml('مقبول');
    }

    public function test_bulk_status_changes_email_each_changed_applicant_once(): void
    {
        Mail::fake();
        $this->actingAs($this->createAdmin());
        $pending = Applicant::factory()->create();
        $reviewed = Applicant::factory()->create(['status' => Applicant::REVIEWED]);
        $decided = Applicant::factory()->create(['status' => Applicant::ACCEPTED]);

        Livewire::test(ListApplicants::class)->callTableBulkAction('mark_rejected', [$pending, $reviewed, $decided]);

        // The accepted one is left as is, so it gets no e-mail.
        Mail::assertQueuedCount(2);
        Mail::assertNotQueued(ApplicationStatusChanged::class, fn (ApplicationStatusChanged $mail) => $mail->hasTo($decided->email));
    }

    public function test_moving_back_to_pending_sends_nothing(): void
    {
        Mail::fake();
        Applicant::factory()->create(['status' => Applicant::REVIEWED])->update(['status' => Applicant::PENDING]);

        Mail::assertNothingQueued();
    }

    public function test_filters_search_and_trash(): void
    {
        $this->actingAs($this->createAdmin());
        $sales = Career::factory()->create();
        $sara = Applicant::factory()->create(['name' => 'Sara Ahmed', 'gender' => 'female', 'career_id' => $sales->id]);
        $omar = Applicant::factory()->create(['name' => 'Omar Ali', 'gender' => 'male', 'status' => Applicant::REVIEWED]);

        Livewire::test(ListApplicants::class)
            ->assertTableColumnExists('phone')
            ->assertTableColumnExists('gender')
            ->assertTableColumnExists('linkedin_profile')
            ->searchTable('Sara Ahmed')
            ->assertCanSeeTableRecords([$sara])->assertCanNotSeeTableRecords([$omar]);

        Livewire::test(ListApplicants::class)
            ->filterTable('status', [Applicant::REVIEWED])
            ->assertCanSeeTableRecords([$omar])->assertCanNotSeeTableRecords([$sara]);

        Livewire::test(ListApplicants::class)
            ->filterTable('career_id', $sales->id)
            ->assertCanSeeTableRecords([$sara])->assertCanNotSeeTableRecords([$omar]);

        Livewire::test(ListApplicants::class)
            ->filterTable('queryBuilder', ['rules' => [
                ['type' => 'gender', 'data' => ['operator' => IsOperator::make()->getName(), 'settings' => ['value' => 'male']]],
            ]])
            ->assertCanSeeTableRecords([$omar])->assertCanNotSeeTableRecords([$sara]);

        $omar->delete();
        Livewire::test(ListApplicants::class)
            ->assertCanNotSeeTableRecords([$omar])
            ->filterTable('trashed', true)
            ->assertCanSeeTableRecords([$omar]);
    }

    public function test_the_applicants_count_in_the_jobs_list_opens_that_jobs_applicants(): void
    {
        $this->actingAs($this->createAdmin());
        $career = Career::factory()->create();
        $mine = Applicant::factory()->create(['career_id' => $career->id]);
        $other = Applicant::factory()->create();

        Livewire::withQueryParams(['tableFilters' => ['career_id' => ['value' => $career->id]]])
            ->test(ListApplicants::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_a_job_people_applied_for_goes_to_the_trash_but_is_never_deleted_for_good(): void
    {
        $this->actingAs($this->createAdmin());
        $career = Career::factory()->create();
        $applicant = Applicant::factory()->create(['career_id' => $career->id]);
        $empty = Career::factory()->create();

        Livewire::test(ListCareers::class)->assertTableColumnStateSet('all_applicants_count', 1, $career);

        $career->delete();
        $this->assertSame($career->id, $applicant->fresh()->career->id);
        Livewire::test(ListCareers::class)
            ->filterTable('trashed', true)
            ->assertTableActionDisabled('forceDelete', $career);

        $empty->delete();
        Livewire::test(ListCareers::class)
            ->filterTable('trashed', true)
            ->callTableAction('forceDelete', $empty);
        $this->assertNull(Career::withTrashed()->find($empty->id));
    }

    public function test_a_trashed_job_is_not_offered_for_new_applications(): void
    {
        $this->actingAs($this->createAdmin());
        $open = Career::factory()->create(['title_en' => 'Open Job']);
        $trashed = Career::factory()->create(['title_en' => 'Old Job']);
        $trashed->delete();

        Livewire::test(CreateApplicant::class)
            ->fillForm(['name' => 'Sara', 'email' => 's@example.com', 'phone' => '0100', 'career_id' => $trashed->id])
            ->call('create')
            ->assertHasFormErrors(['career_id']);

        $this->assertNotNull($open);
    }
}
