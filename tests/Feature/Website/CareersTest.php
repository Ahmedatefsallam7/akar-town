<?php

namespace Tests\Feature\Website;

use App\Livewire\Website\JobApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Careers\Models\Applicant;
use Modules\Careers\Models\Career;
use Modules\GeneralSettings\Settings\BusinessInfoSettings;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\Lookups\Models\Lookup;
use Modules\WebsiteUsers\Models\WebsiteUser;
use Tests\TestCase;

/**
 * The careers page (the design's career.html): the open jobs with their department filter, and the "Apply Now"
 * window, saved as an Applicant with the CV on the private disk.
 */
class CareersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        RateLimiter::clear('job-application:127.0.0.1');

        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();
    }

    private function department(string $slug, string $name): Lookup
    {
        $parent = Lookup::query()->firstOrCreate(['slug' => 'department'], ['name_en' => 'Department']);

        return $parent->children()->create(['slug' => $slug, 'name_en' => $name, 'name_ar' => $name.' ع']);
    }

    private function cv(): UploadedFile
    {
        return UploadedFile::fake()->create('cv.pdf', 200, 'application/pdf');
    }

    public function test_the_page_lists_open_jobs_with_department_type_place_and_summary(): void
    {
        $sales = $this->department('sales', 'Sales');
        $city = Lookup::create(['slug' => 'locations', 'name_en' => 'Locations'])->children()->create(['slug' => 'new-cairo', 'name_en' => 'New Cairo']);
        Career::factory()->create(['title_en' => 'Senior Consultant', 'department_id' => $sales->id, 'type' => 'part_time', 'city_id' => $city->id, 'description_en' => '<p>Guide <b>top</b> clients.</p>']);
        Career::factory()->create(['title_en' => 'Old Job', 'closing_date' => today()->subDay()]);
        Career::factory()->create(['title_en' => 'Hidden Job', 'is_active' => false]);
        Career::factory()->create(['title_en' => 'Last Day Job', 'closing_date' => today()]);

        $this->get(route('careers'))
            ->assertOk()
            ->assertSee(['Build Something<br><em>Remarkable</em>', 'Open Positions', '2 Roles'], false)
            ->assertSee(['Senior Consultant', 'Last Day Job', 'Sales', 'Part Time', 'New Cairo', 'Guide top clients.', 'Apply Now'])
            ->assertDontSee(['Old Job', 'Hidden Job', '<b>top</b>'], false)
            ->assertSeeLivewire(JobApplication::class);
    }

    public function test_the_filter_lists_only_departments_with_open_jobs(): void
    {
        $sales = $this->department('sales', 'Sales');
        $this->department('finance', 'Finance');
        Career::factory()->create(['department_id' => $sales->id]);

        $html = $this->get(route('careers'))->assertOk()->getContent();
        $filter = str($html)->between('Filter by department', '</div>');

        $this->assertTrue($filter->contains(['All', 'Sales']));
        $this->assertFalse($filter->contains('Finance'));
    }

    public function test_the_list_keeps_the_same_queries_with_more_jobs(): void
    {
        $sales = $this->department('sales', 'Sales');
        Career::factory()->create(['department_id' => $sales->id]);
        $this->get(route('careers'));
        DB::enableQueryLog();
        $this->get(route('careers'))->assertOk();
        $few = count(DB::getQueryLog());

        Career::factory()->count(6)->create(['department_id' => $this->department('marketing', 'Marketing')->id]);
        DB::flushQueryLog();
        $this->get(route('careers'))->assertOk();

        $this->assertSame($few, count(DB::getQueryLog()));
    }

    public function test_without_open_jobs_the_page_says_so_and_the_cta_offers_the_cv_by_email(): void
    {
        $business = app(BusinessInfoSettings::class);
        $business->email = 'hr@akartown.test';
        $business->save();

        $this->get(route('careers'))
            ->assertOk()
            ->assertSee(['No open positions right now', 'Email Your CV', 'href="mailto:hr@akartown.test"', 'General Enquiry', 'href="'.route('contact').'"'], false)
            ->assertDontSeeLivewire(JobApplication::class);
    }

    public function test_an_application_is_saved_with_the_cv_on_the_private_disk(): void
    {
        $career = Career::factory()->create();

        Livewire::test(JobApplication::class)
            ->call('choose', $career->id)
            ->set(['fullName' => 'Sara Ali', 'phone' => '+20 100 123 4567', 'email' => 'sara@example.com', 'experience' => '3 years in real estate', 'linkedin' => 'https://www.linkedin.com/in/sara', 'gender' => 'female', 'note' => 'I love sales.'])
            ->set('resume', $this->cv())
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('sent', true)
            ->assertSee('Application sent');

        $applicant = Applicant::sole();
        $this->assertSame([$career->id, 'Sara Ali', '+201001234567', 'sara@example.com', Applicant::PENDING], [$applicant->career_id, $applicant->name, $applicant->phone, $applicant->email, $applicant->fresh()->status]);
        $this->assertSame("Experience: 3 years in real estate\n\nI love sales.", $applicant->cover_letter);
        $this->assertSame(['https://www.linkedin.com/in/sara', 'female'], [$applicant->linkedin_profile, $applicant->gender]);
        $media = $applicant->getFirstMedia(Applicant::RESUME);
        $this->assertSame(Applicant::RESUME_DISK, $media->disk);
        Storage::disk('local')->assertExists($media->getPathRelativeToRoot());
    }

    public function test_name_phone_email_and_a_pdf_or_word_cv_are_required_and_linkedin_must_be_a_web_link(): void
    {
        $career = Career::factory()->create();

        Livewire::test(JobApplication::class)
            ->call('choose', $career->id)
            ->set(['phone' => '0100000000', 'email' => 'kfjds@fm', 'linkedin' => 'javascript:alert(1)', 'gender' => 'other'])
            ->call('send')
            ->assertHasErrors(['fullName' => 'required', 'phone', 'email', 'linkedin', 'gender', 'resume' => 'required'])
            ->set('resume', UploadedFile::fake()->create('cv.exe', 10))
            ->call('send')
            ->assertHasErrors(['resume' => 'mimes'])
            ->set('resume', UploadedFile::fake()->create('cv.pdf', 6000, 'application/pdf'))
            ->call('send')
            ->assertHasErrors(['resume' => 'max']);

        $this->assertSame(0, Applicant::count());
    }

    public function test_a_closed_or_unknown_job_cant_be_applied_for(): void
    {
        $closed = Career::factory()->create(['is_active' => false]);

        foreach ([$closed->id, 999999] as $id) {
            Livewire::test(JobApplication::class)
                ->call('choose', $id)
                ->set(['fullName' => 'Sara', 'phone' => '01001234567', 'email' => 'sara@example.com', 'resume' => $this->cv()])
                ->call('send')
                ->assertHasErrors('resume')
                ->assertSet('sent', false);
        }

        $this->assertSame(0, Applicant::count());
    }

    public function test_a_signed_in_visitor_gets_their_details_filled(): void
    {
        $this->actingAs(WebsiteUser::factory()->create(['name' => 'Omar', 'email' => 'omar@example.com', 'phone' => '01001234567']), 'website');

        Livewire::test(JobApplication::class)->assertSet('fullName', 'Omar')->assertSet('email', 'omar@example.com')->assertSet('phone', '01001234567');
    }

    public function test_applications_are_limited_per_visitor(): void
    {
        $career = Career::factory()->create();

        foreach (range(1, 6) as $i) {
            $form = Livewire::test(JobApplication::class)->call('choose', $career->id)
                ->set(['fullName' => 'Sara', 'phone' => "0100123456{$i}", 'email' => "sara{$i}@example.com", 'resume' => $this->cv()])
                ->call('send');
            $i <= 5 ? $form->assertHasNoErrors() : $form->assertHasErrors('resume');
        }

        $this->assertSame(5, Applicant::count());
    }

    public function test_a_card_opens_its_jobs_page_with_all_the_details(): void
    {
        $sales = $this->department('sales', 'Sales');
        $career = Career::factory()->create([
            'title_en' => 'Senior Consultant',
            'slug' => 'senior-consultant',
            'department_id' => $sales->id,
            'vacancies' => 3,
            'closing_date' => '2099-12-21',
            'description_en' => '<p>Join our team.</p><script>alert(1)</script>',
            'responsibilities' => [['en' => 'Meet clients', 'ar' => 'مقابلة العملاء']],
            'requirements' => [['en' => '2+ years of experience', 'ar' => 'خبرة سنتين']],
            'show_salary' => true,
            'salary_from' => 15000,
            'salary_to' => 25000,
            'salary_currency' => 'EGP',
            'meta_title_en' => 'Senior Consultant Job',
        ]);

        $this->get(route('careers'))->assertSee('href="'.route('careers.show', 'senior-consultant').'"', false);

        $this->get(route('careers.show', 'senior-consultant'))
            ->assertOk()
            ->assertSee(['<title>Senior Consultant Job', '<p>Join our team.</p>'], false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee(['Senior Consultant', 'Open Positions', 'Sales', 'Full Time', '15,000 – 25,000 EGP', '21 December 2099', 'Meet clients', '2+ years of experience', 'Apply Now'])
            ->assertSee('$dispatch(\'apply-job\'', false)
            ->assertSeeLivewire(JobApplication::class);
    }

    public function test_a_hidden_salary_isnt_shown(): void
    {
        Career::factory()->create(['slug' => 'quiet-job', 'show_salary' => false, 'salary_from' => 15000, 'salary_currency' => 'EGP']);

        $this->get(route('careers.show', 'quiet-job'))->assertOk()->assertDontSee(['Salary', '15,000']);
    }

    public function test_a_closed_or_unknown_jobs_page_is_not_found(): void
    {
        Career::factory()->create(['slug' => 'closed-job', 'closing_date' => today()->subDay()]);
        Career::factory()->create(['slug' => 'hidden-job', 'is_active' => false]);

        $this->get(route('careers.show', 'closed-job'))->assertNotFound();
        $this->get(route('careers.show', 'hidden-job'))->assertNotFound();
        $this->get(route('careers.show', 'nobody'))->assertNotFound();
    }

    public function test_arabic(): void
    {

        $career = Career::factory()->create(['title_ar' => 'مستشار مبيعات', 'responsibilities' => [['en' => 'Meet clients', 'ar' => 'مقابلة العملاء']]]);

        $this->withCookie('locale', 'ar')->get(route('careers'))->assertOk()->assertSee(['مستشار مبيعات', 'الوظائف المتاحة', 'قدّم الآن', 'وظيفة واحدة']);
        $this->withCookie('locale', 'ar')->get(route('careers.show', $career->slug))->assertOk()->assertSee(['مستشار مبيعات', 'تفاصيل الوظيفة', 'مقابلة العملاء'])->assertDontSee('Meet clients');
    }
}
