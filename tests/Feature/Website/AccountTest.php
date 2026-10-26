<?php

namespace Tests\Feature\Website;

use App\Livewire\Website\AccountSettings;
use App\Livewire\Website\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\Inquiry\Models\Inquiry;
use Modules\Inquiry\Models\Meeting;
use Modules\UnitRequests\Models\UnitRequest;
use Modules\Units\Models\Unit;
use Modules\WebsiteUsers\Models\WebsiteUser;
use Modules\WebsiteUsers\Notifications\VerifyWebsiteEmail;
use Tests\TestCase;

/**
 * The website account page: the visitor edits their details and password and follows what they sent.
 */
class AccountTest extends TestCase
{
    use RefreshDatabase;

    private WebsiteUser $user;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();

        $this->user = WebsiteUser::factory()->create(['name' => 'Sara Ali', 'email' => 'sara@example.com', 'phone' => '01001234567', 'password' => 'old-password', 'email_verified_at' => now()]);
    }

    public function test_a_guest_is_sent_home_to_sign_in(): void
    {
        $this->get(route('account'))->assertRedirect(route('home'));
    }

    public function test_the_page_and_the_menu_link(): void
    {
        $this->actingAs($this->user, 'website');

        $this->get(route('home'))->assertSee('href="'.route('account').'"', false);
        $this->get(route('account'))
            ->assertOk()
            ->assertSee(['Hello, <em>Sara</em>', 'My details', 'Password', 'My requests'], false)
            ->assertSeeLivewire(AccountSettings::class);
    }

    public function test_details_are_saved(): void
    {
        $this->actingAs($this->user, 'website');

        Livewire::test(AccountSettings::class)
            ->assertSet('name', 'Sara Ali')
            ->set(['name' => 'Sara Mohamed', 'phone' => '0111 234 5678'])
            ->call('saveDetails')
            ->assertHasNoErrors()
            ->assertSet('saved', 'details');

        $this->user->refresh();
        $this->assertSame(['Sara Mohamed', '01112345678'], [$this->user->name, $this->user->phone]);
        $this->assertTrue($this->user->hasVerifiedEmail());
    }

    public function test_a_new_email_must_be_verified_again(): void
    {
        Notification::fake();
        $this->actingAs($this->user, 'website');

        Livewire::test(AccountSettings::class)
            ->set('email', 'New@Example.com')
            ->call('saveDetails')
            ->assertHasNoErrors()
            ->assertSet('saved', 'email');

        $this->user->refresh();
        $this->assertSame('new@example.com', $this->user->email);
        $this->assertFalse($this->user->hasVerifiedEmail());
        Notification::assertSentTo($this->user, VerifyWebsiteEmail::class);
    }

    public function test_email_and_phone_stay_unique_and_valid(): void
    {
        WebsiteUser::factory()->create(['email' => 'taken@example.com', 'phone' => '01223456789']);
        $this->actingAs($this->user, 'website');

        Livewire::test(AccountSettings::class)
            ->set(['name' => '', 'email' => 'taken@example.com', 'phone' => '01223456789'])
            ->call('saveDetails')
            ->assertHasErrors(['name' => 'required', 'email' => 'unique', 'phone' => 'unique']);

        Livewire::test(AccountSettings::class)
            ->set(['email' => 'not-an-email', 'phone' => '0100'])
            ->call('saveDetails')
            ->assertHasErrors(['email', 'phone']);

        $this->assertSame('sara@example.com', $this->user->fresh()->email);
    }

    public function test_the_password_is_changed_with_the_current_one(): void
    {
        $this->actingAs($this->user, 'website');

        Livewire::test(AccountSettings::class)
            ->set(['currentPassword' => 'wrong-password', 'password' => 'new-password-1', 'passwordConfirmation' => 'new-password-1'])
            ->call('savePassword')
            ->assertHasErrors('currentPassword');

        Livewire::test(AccountSettings::class)
            ->set(['currentPassword' => 'old-password', 'password' => 'short', 'passwordConfirmation' => 'other'])
            ->call('savePassword')
            ->assertHasErrors(['password', 'passwordConfirmation']);

        Livewire::test(AccountSettings::class)
            ->set(['currentPassword' => 'old-password', 'password' => 'new-password-1', 'passwordConfirmation' => 'new-password-1'])
            ->call('savePassword')
            ->assertHasNoErrors()
            ->assertSet('saved', 'password')
            ->assertSet('password', '');

        $this->assertTrue(Hash::check('new-password-1', $this->user->fresh()->password));
    }

    public function test_guessing_the_current_password_is_limited(): void
    {
        $this->actingAs($this->user, 'website');

        foreach (range(1, 6) as $i) {
            $form = Livewire::test(AccountSettings::class)
                ->set(['currentPassword' => "wrong-{$i}", 'password' => 'new-password-1', 'passwordConfirmation' => 'new-password-1'])
                ->call('savePassword');
        }

        $form->assertHasErrors('currentPassword')->assertSee('Too many requests');
    }

    public function test_my_requests_lists_what_the_visitor_sent_with_its_status(): void
    {
        Meeting::factory()->create(['website_user_id' => $this->user->id, 'type' => 'online', 'status' => Meeting::CONFIRMED, 'source' => 'Property page: Sky Penthouse', 'meeting_at' => now()->addDay()]);
        UnitRequest::factory()->create(['website_user_id' => $this->user->id, 'name_en' => 'Villa for Sale in New Cairo', 'status' => UnitRequest::CONTACTED]);
        Inquiry::factory()->create(['website_user_id' => $this->user->id, 'form' => 'project_enquiry', 'message' => "Project: Azure Heights\nBudget: 5 – 10 Million EGP"]);
        Inquiry::factory()->create(['website_user_id' => WebsiteUser::factory()->create()->id, 'message' => 'Someone else']);
        $this->actingAs($this->user, 'website');

        $this->get(route('account'))
            ->assertOk()
            ->assertSee(['Sky Penthouse', 'Confirmed', 'Villa for Sale in New Cairo', 'Contacted', 'Project Enquiry', 'Project: Azure Heights', 'Received'])
            ->assertDontSee('Someone else');
    }

    public function test_the_wishlist_tab_shows_the_saved_cards_without_the_wishlist_pages_header(): void
    {
        $unit = Unit::factory()->create(['name_en' => 'Garden Villa', 'is_published' => true]);
        $this->actingAs($this->user, 'website');

        $this->get(route('account'))->assertOk()->assertSee('Wishlist')->assertSeeLivewire(Wishlist::class);

        Livewire::test(Wishlist::class, ['compact' => true])
            ->call('load', [$unit->id])
            ->assertSee('Garden Villa')
            ->assertDontSee('<h1', false);
        // The wishlist page itself keeps its header.
        Livewire::test(Wishlist::class)->call('load', [$unit->id])->assertSee('<h1', false);
    }

    public function test_arabic(): void
    {
        $this->actingAs($this->user, 'website');

        $this->withCookie('locale', 'ar')->get(route('account'))->assertOk()->assertSee(['حسابي', 'بياناتي', 'طلباتي']);
    }
}
