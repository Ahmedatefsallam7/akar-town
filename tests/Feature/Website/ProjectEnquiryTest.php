<?php

namespace Tests\Feature\Website;

use App\Jobs\SendLeadToCrm;
use App\Livewire\Website\ProjectEnquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Modules\Inquiry\Models\Inquiry;
use Modules\Lookups\Models\Lookup;
use Modules\PossibleLeads\Models\PossibleLead;
use Modules\PossibleLeads\Support\Visitor;
use Modules\Projects\Models\Project;
use Tests\TestCase;

/**
 * The project page's "Send an Enquiry" form: saved as a Contact Us message and sent to the CRM.
 */
class ProjectEnquiryTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    private Lookup $villa;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        RateLimiter::clear('project-enquiry:127.0.0.1');

        $this->project = Project::factory()->create(['title_en' => 'Azure Heights']);
        $this->villa = Lookup::create(['slug' => 'unittype', 'name_en' => 'Unit Type'])->children()->create(['slug' => 'villa', 'name_en' => 'Villa']);
    }

    private function form(): mixed
    {
        return Livewire::test(ProjectEnquiry::class, [
            'projectId' => $this->project->id,
            'types' => [['id' => $this->villa->id, 'name' => 'Villa', 'area' => '320–450']],
        ]);
    }

    public function test_an_enquiry_is_saved_as_a_contact_message_and_sent_to_the_crm(): void
    {
        PossibleLead::create(['click_type' => 'phone', 'visitor_id' => $visitor = Visitor::newId(), 'project_id' => $this->project->id]);

        Livewire::withCookies([Visitor::COOKIE => $visitor])
            ->test(ProjectEnquiry::class, ['projectId' => $this->project->id, 'types' => [['id' => $this->villa->id, 'name' => 'Villa', 'area' => '320–450']]])
            ->assertSee('Villa (320–450 m²)')
            ->set([
                'fullName' => 'Sara Ali',
                'phone' => '+20 100 123 4567',
                'email' => 'sara@example.com',
                'type' => (string) $this->villa->id,
                'budget' => '5-10',
                'message' => 'Garden view please.',
            ])
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('sent', true)
            ->assertSee("we'll be in touch shortly");

        $inquiry = Inquiry::sole();
        $this->assertSame(['Sara Ali', '+201001234567', 'sara@example.com', 'project_enquiry', $visitor], [$inquiry->full_name, $inquiry->phone, $inquiry->email, $inquiry->form, $inquiry->visitor_id]);
        $this->assertSame("Project: Azure Heights\nUnit type: Villa\nBudget: 5 – 10 Million EGP\n\nGarden view please.", $inquiry->message);
        Queue::assertPushed(SendLeadToCrm::class, fn (SendLeadToCrm $job) => $job->record->is($inquiry));
        $this->assertSame($inquiry->id, PossibleLead::sole()->converted_lead_id);
    }

    public function test_name_phone_and_email_are_required_and_choices_must_be_known(): void
    {
        $this->form()
            ->set(['phone' => '0100000000', 'email' => 'kfjds@fm', 'type' => '999999', 'budget' => 'lots'])
            ->call('send')
            ->assertHasErrors(['fullName' => 'required', 'phone', 'email', 'type', 'budget'])
            ->assertSet('sent', false);

        $this->assertSame(0, Inquiry::count());
    }

    public function test_sends_are_limited_per_visitor(): void
    {
        foreach (range(1, 6) as $i) {
            $form = $this->form()->set(['fullName' => 'Sara', 'phone' => "0100123456{$i}", 'email' => "sara{$i}@example.com"])->call('send');
            $i <= 5 ? $form->assertHasNoErrors() : $form->assertHasErrors('email');
        }

        $this->assertSame(5, Inquiry::count());
    }
}
