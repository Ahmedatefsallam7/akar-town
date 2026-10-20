<?php

namespace Modules\WebsiteUsers\Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\Inquiry\Filament\Resources\InquiryResource\Pages\ViewInquiry;
use Modules\Inquiry\Filament\Resources\MeetingResource\Pages\ViewMeeting;
use Modules\Inquiry\Models\Inquiry;
use Modules\Inquiry\Models\Meeting;
use Modules\UnitRequests\Filament\Resources\UnitRequestResource\Pages\ViewUnitRequest;
use Modules\UnitRequests\Models\UnitRequest;
use Modules\Users\Filament\Resources\UserResource\Pages\ListUsers;
use Modules\Users\Support\PermissionModules;
use Modules\WebsiteUsers\Filament\Resources\WebsiteUserResource;
use Modules\WebsiteUsers\Filament\Resources\WebsiteUserResource\Pages\EditWebsiteUser;
use Modules\WebsiteUsers\Filament\Resources\WebsiteUserResource\Pages\ListWebsiteUsers;
use Modules\WebsiteUsers\Filament\Resources\WebsiteUserResource\Pages\ViewWebsiteUser;
use Modules\WebsiteUsers\Filament\Widgets\NewRegistrationsWidget;
use Modules\WebsiteUsers\Models\WebsiteUser;
use Tests\TestCase;

class WebsiteUserResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_without_the_permission_are_forbidden(): void
    {
        $this->actingAs($this->createUserWithPermissions(['contact_us' => true]));

        $this->get(WebsiteUserResource::getUrl('index'))->assertForbidden();
    }

    public function test_admin_can_open_every_page_and_there_is_no_create(): void
    {
        $this->actingAs($this->createAdmin());
        $user = WebsiteUser::factory()->create();

        $this->get(WebsiteUserResource::getUrl('index'))->assertOk()->assertSee($user->name);
        $this->get(WebsiteUserResource::getUrl('view', ['record' => $user]))->assertOk();
        $this->get(WebsiteUserResource::getUrl('edit', ['record' => $user]))->assertOk();
        $this->assertFalse(WebsiteUserResource::canCreate());
        $this->assertArrayNotHasKey('create', WebsiteUserResource::getPages());
    }

    public function test_permission_is_website_users_in_customers_and_crm_without_create(): void
    {
        $module = PermissionModules::all()['website_users'];

        $this->assertSame('inquiries_crm', $module['group']);
        $this->assertSame(['view', 'edit', 'delete', 'bulk'], $module['actions']);
        $this->assertSame('Customers & CRM', __('admin.navigation.inquiries_crm'));
    }

    public function test_website_users_and_admin_users_never_mix(): void
    {
        $this->actingAs($admin = $this->createAdmin());
        $websiteUser = WebsiteUser::factory()->create(['email' => 'visitor@example.com']);

        Livewire::test(ListWebsiteUsers::class)->assertCanSeeTableRecords([$websiteUser])->assertDontSee($admin->email);
        Livewire::test(ListUsers::class)->assertDontSee('visitor@example.com');

        // A website account logs in with its own guard only; the admin guard doesn't know it.
        $this->assertTrue(Auth::guard('website')->attempt(['email' => 'visitor@example.com', 'password' => 'password']));
        $this->assertFalse(Auth::guard('web')->validate(['email' => 'visitor@example.com', 'password' => 'password']));
    }

    public function test_email_is_required_and_email_and_mobile_are_unique(): void
    {
        $user = WebsiteUser::factory()->create(['email' => 'sara@example.com', 'phone' => '+201001112222']);

        foreach ([['email' => null], ['email' => 'sara@example.com'], ['phone' => '+201001112222']] as $invalid) {
            try {
                WebsiteUser::factory()->create($invalid);
                $this->fail('Saved an account with '.json_encode($invalid));
            } catch (QueryException) {
            }
        }

        $this->assertSame(1, WebsiteUser::count());
        $this->assertNotNull($user);
    }

    public function test_the_password_is_never_shown_or_editable(): void
    {
        $this->actingAs($this->createAdmin());
        $user = WebsiteUser::factory()->create();
        $hash = $user->password;

        $this->get(WebsiteUserResource::getUrl('edit', ['record' => $user]))->assertOk()->assertDontSee($hash);
        $this->get(WebsiteUserResource::getUrl('view', ['record' => $user]))->assertOk()->assertDontSee($hash);

        Livewire::test(EditWebsiteUser::class, ['record' => $user->getRouteKey()])
            ->assertFormFieldDoesNotExist('password')
            ->fillForm(['name' => 'Renamed'])
            ->call('save');

        $this->assertSame($hash, $user->fresh()->password);
        $this->assertArrayNotHasKey('password', $user->fresh()->toArray());
    }

    public function test_admin_corrects_name_phone_language_status_and_notes_but_not_the_email(): void
    {
        $this->actingAs($this->createAdmin());
        $user = WebsiteUser::factory()->create(['email' => 'sara@example.com']);
        WebsiteUser::factory()->create(['phone' => '+201000000001']);

        Livewire::test(EditWebsiteUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['phone' => '+201000000001'])
            ->call('save')
            ->assertHasFormErrors(['phone' => 'unique']);

        Livewire::test(EditWebsiteUser::class, ['record' => $user->getRouteKey()])
            ->assertFormFieldIsDisabled('email')
            ->fillForm(['name' => 'Sara Ahmed', 'phone' => '+201009999999', 'preferred_language' => 'ar', 'admin_notes' => 'Asked to fix the name typo.', 'email' => 'hacked@example.com'])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();
        $this->assertSame(['Sara Ahmed', '+201009999999', 'ar', 'Asked to fix the name typo.', 'sara@example.com'], [
            $user->name, $user->phone, $user->preferred_language, $user->admin_notes, $user->email,
        ]);
    }

    public function test_blocking_from_the_view_page_row_and_bulk(): void
    {
        $this->actingAs($this->createAdmin());
        [$a, $b, $c] = WebsiteUser::factory()->count(3)->create();

        Livewire::test(ViewWebsiteUser::class, ['record' => $a->getRouteKey()])->callAction('block');
        $this->assertTrue($a->fresh()->isBlocked());
        Livewire::test(ViewWebsiteUser::class, ['record' => $a->getRouteKey()])
            ->assertActionHidden('block')
            ->callAction('unblock');
        $this->assertFalse($a->fresh()->isBlocked());

        Livewire::test(ListWebsiteUsers::class)->callTableAction('block', $b);
        $this->assertTrue($b->fresh()->isBlocked());

        Livewire::test(ListWebsiteUsers::class)->callTableBulkAction('block', [$a, $c]);
        $this->assertTrue($a->fresh()->isBlocked() && $c->fresh()->isBlocked());
    }

    public function test_a_blocked_user_cannot_log_in_and_is_signed_out_on_the_next_request(): void
    {
        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();
        $user = WebsiteUser::factory()->create(['email' => 'sara@example.com']);
        $rememberToken = $user->remember_token;

        $this->actingAs($user, 'website')->get(route('home'))->assertOk();
        $this->assertTrue(Auth::guard('website')->check());

        $user->block();

        // Next page: signed out, keeps browsing as a guest, with the neutral message.
        $this->get(route('home'))->assertOk()->assertSee(__('admin.website_users.account_unavailable'));
        $this->assertNull(Auth::guard('website')->user());
        // "Remember me" cookies stop working too.
        $this->assertNotSame($rememberToken, $user->fresh()->remember_token);

        // The website's login only lets active accounts in.
        $this->assertFalse(Auth::guard('website')->attempt(['email' => 'sara@example.com', 'password' => 'password', 'status' => WebsiteUser::ACTIVE]));
    }

    public function test_search_filters_and_trash(): void
    {
        $this->actingAs($this->createAdmin());
        $sara = WebsiteUser::factory()->create(['name' => 'Sara Ahmed', 'created_at' => now()->subMonths(2)]);
        $omar = WebsiteUser::factory()->blocked()->create(['name' => 'Omar Ali']);

        Livewire::test(ListWebsiteUsers::class)
            ->searchTable('Sara Ahmed')
            ->assertCanSeeTableRecords([$sara])->assertCanNotSeeTableRecords([$omar]);
        Livewire::test(ListWebsiteUsers::class)
            ->searchTable($omar->phone)
            ->assertCanSeeTableRecords([$omar])->assertCanNotSeeTableRecords([$sara]);

        Livewire::test(ListWebsiteUsers::class)
            ->filterTable('status', WebsiteUser::BLOCKED)
            ->assertCanSeeTableRecords([$omar])->assertCanNotSeeTableRecords([$sara]);
        Livewire::test(ListWebsiteUsers::class)
            ->filterTable('created_at', ['registered_from' => now()->subWeek()->toDateString()])
            ->assertCanSeeTableRecords([$omar])->assertCanNotSeeTableRecords([$sara]);

        $omar->delete();
        Livewire::test(ListWebsiteUsers::class)
            ->assertCanNotSeeTableRecords([$omar])
            ->filterTable('trashed', true)
            ->assertCanSeeTableRecords([$omar]);
    }

    public function test_the_sidebar_badge_counts_website_users(): void
    {
        $this->actingAs($this->createAdmin());
        WebsiteUser::factory()->count(3)->create();

        $this->assertSame('3', WebsiteUserResource::getNavigationBadge());
    }

    public function test_leads_sent_while_logged_in_link_to_the_account_and_guests_stay_unlinked(): void
    {
        $this->actingAs($this->createAdmin());
        $user = WebsiteUser::factory()->create();
        $inquiry = Inquiry::factory()->create(['website_user_id' => $user->id]);
        $meeting = Meeting::factory()->create(['website_user_id' => $user->id]);
        $sellRequest = UnitRequest::factory()->create(['website_user_id' => $user->id]);
        $guest = Inquiry::factory()->create();

        Livewire::test(ViewInquiry::class, ['record' => $inquiry->getRouteKey()])->assertActionVisible('websiteUser');
        Livewire::test(ViewMeeting::class, ['record' => $meeting->getRouteKey()])->assertActionVisible('websiteUser');
        Livewire::test(ViewUnitRequest::class, ['record' => $sellRequest->getRouteKey()])
            ->assertActionVisible('websiteUser')
            ->assertActionHasUrl('websiteUser', WebsiteUserResource::getUrl('view', ['record' => $user]));
        Livewire::test(ViewInquiry::class, ['record' => $guest->getRouteKey()])->assertActionHidden('websiteUser');

        // Deleting the account for good keeps the leads (unlinked).
        $user->forceDelete();
        $this->assertNotNull($inquiry->fresh());
        $this->assertNull($inquiry->fresh()->website_user_id);
    }

    public function test_a_lead_sent_by_a_logged_in_website_user_saves_without_admin_stamps(): void
    {
        $user = WebsiteUser::factory()->create();
        // Logged in on the website (as the website's routes will be), not in the admin.
        $this->actingAs($user, 'website');

        $inquiry = Inquiry::create(['website_user_id' => $user->id, 'full_name' => $user->name, 'phone' => $user->phone, 'message' => 'Call me.']);
        $user->update(['preferred_language' => 'ar']);

        $this->assertSame([$user->id, null], [$inquiry->website_user_id, $inquiry->created_by]);
        $this->assertNull($user->fresh()->updated_by);
    }

    public function test_the_dashboard_counts_this_months_registrations_and_links_to_them(): void
    {
        $this->actingAs($this->createAdmin());
        $this->travelTo('2026-10-20 12:00');
        $thisMonth = WebsiteUser::factory()->count(2)->create();
        $lastMonth = WebsiteUser::factory()->create(['created_at' => '2026-09-28 10:00']);

        $stat = (fn () => $this->getStats()[0])->call(new NewRegistrationsWidget);
        $this->assertSame(['2', __('admin.website_users.new_registrations')], [(string) $stat->getValue(), (string) $stat->getLabel()]);
        $this->assertStringContainsString('registered_from', urldecode($stat->getUrl()));
        Livewire::test(NewRegistrationsWidget::class)->assertSee(__('admin.website_users.new_registrations'));

        Livewire::withQueryParams(['tableFilters' => ['created_at' => ['registered_from' => '2026-10-01']]])
            ->test(ListWebsiteUsers::class)
            ->assertCanSeeTableRecords($thisMonth)
            ->assertCanNotSeeTableRecords([$lastMonth]);

        $this->actingAs($this->createUserWithPermissions(['contact_us' => true]));
        $this->assertFalse(NewRegistrationsWidget::canView());
    }
}
