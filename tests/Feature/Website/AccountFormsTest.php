<?php

namespace Tests\Feature\Website;

use App\Livewire\Website\ProjectEnquiry;
use App\Livewire\Website\PropertyMatching;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\Inquiry\Models\Inquiry;
use Modules\Projects\Models\Project;
use Modules\WebsiteUsers\Models\WebsiteUser;
use Tests\TestCase;

/**
 * Signed in, the website forms start with the account's name, e-mail and phone (they can still be changed).
 */
class AccountFormsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();
        RateLimiter::clear('project-enquiry:127.0.0.1');
    }

    private function user(): WebsiteUser
    {
        return WebsiteUser::factory()->create(['name' => 'Sara Ali', 'email' => 'sara@example.com', 'phone' => '01001234567']);
    }

    public function test_guests_get_empty_forms(): void
    {
        Livewire::test(PropertyMatching::class)->assertSet('fullName', '')->assertSet('email', '')->assertSet('phone', '');
        Livewire::test(ProjectEnquiry::class, ['projectId' => Project::factory()->create()->id])->assertSet('email', '');
    }

    public function test_signed_in_forms_start_with_the_account_details(): void
    {
        $this->actingAs($this->user(), 'website');

        foreach ([[PropertyMatching::class, []], [ProjectEnquiry::class, ['projectId' => Project::factory()->create()->id]]] as [$form, $params]) {
            Livewire::test($form, $params)
                ->assertSet('fullName', 'Sara Ali')
                ->assertSet('email', 'sara@example.com')
                ->assertSet('phone', '01001234567');
        }
    }

    public function test_changed_details_are_sent_and_the_form_refills_after_sending(): void
    {
        $user = $this->user();
        $this->actingAs($user, 'website');

        Livewire::test(ProjectEnquiry::class, ['projectId' => Project::factory()->create()->id])
            ->set('phone', '01112223334')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('sent', true)
            ->assertSet('phone', '01001234567');

        $inquiry = Inquiry::sole();
        $this->assertSame(['01112223334', 'sara@example.com', $user->id], [$inquiry->phone, $inquiry->email, $inquiry->website_user_id]);
    }

    public function test_an_account_without_a_phone_leaves_it_empty(): void
    {
        $this->actingAs(WebsiteUser::factory()->create(['phone' => null]), 'website');

        Livewire::test(PropertyMatching::class)->assertSet('phone', '');
    }
}
