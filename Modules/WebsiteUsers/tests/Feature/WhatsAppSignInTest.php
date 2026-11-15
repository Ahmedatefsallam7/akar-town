<?php

namespace Modules\WebsiteUsers\Tests\Feature;

use App\Livewire\Website\AccountSettings;
use App\Livewire\Website\AuthModal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\WebsiteUsers\Filament\Resources\PhoneLockResource;
use Modules\WebsiteUsers\Filament\Resources\PhoneLockResource\Pages\ListPhoneLocks;
use Modules\WebsiteUsers\Models\PhoneCode;
use Modules\WebsiteUsers\Models\PhoneLock;
use Modules\WebsiteUsers\Models\WebsiteUser;
use Modules\WebsiteUsers\Support\PhoneLogin;
use Modules\WebsiteUsers\Support\WhatsApp\CodeSender;
use Modules\WebsiteUsers\Support\WhatsApp\MetaCodeSender;
use Tests\TestCase;

/**
 * WhatsApp sign-in (AKAR-49): a code to the number, 3 tries in all, then the number is locked until an admin unlocks
 * it; a number with a password signs in with it next time.
 */
class WhatsAppSignInTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = '+201001234567';

    /** The codes "sent" (to the number => code). */
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();

        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();
        foreach (['phone-code:'.self::PHONE, 'phone-code-ip:127.0.0.1', 'website-sign-in:127.0.0.1'] as $key) {
            RateLimiter::clear($key);
        }

        $test = $this;
        $this->app->instance(CodeSender::class, new class($test) implements CodeSender
        {
            public function __construct(private WhatsAppSignInTest $test) {}

            public function send(string $phone, string $code, string $locale): ?string
            {
                return $this->test->recordCode($phone, $code);
            }
        });
    }

    /** Whether the fake WhatsApp is down (the next sends fail). */
    public bool $down = false;

    public function recordCode(string $phone, string $code): string
    {
        if ($this->down) {
            throw new \RuntimeException('WhatsApp is down');
        }
        $this->sent[] = [$phone, $code];

        return 'wamid.test-'.count($this->sent);
    }

    /**
     * A call from Meta's webhook, signed with the app secret as Meta does.
     */
    private function webhook(array $statuses, ?string $secret = 'test-secret')
    {
        config(['services.whatsapp.app_secret' => 'test-secret']);
        $body = json_encode(['object' => 'whatsapp_business_account', 'entry' => [['changes' => [['field' => 'messages', 'value' => ['statuses' => $statuses]]]]]]);

        return $this->call('POST', route('api.webhooks.whatsapp'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, (string) $secret),
        ], $body);
    }

    private function lastCode(): string
    {
        return end($this->sent)[1];
    }

    private function wrongCode(): string
    {
        return $this->lastCode() === '000000' ? '111111' : '000000';
    }

    private function window(string $number = '1001234567')
    {
        return Livewire::test(AuthModal::class)->set('country', 'EG')->set('phone', $number);
    }

    public function test_numbers_are_checked_and_kept_in_one_format(): void
    {
        $this->assertSame(self::PHONE, PhoneLogin::normalize('01001234567', 'EG'));
        $this->assertSame(self::PHONE, PhoneLogin::normalize('100 123 4567', 'EG'));
        $this->assertNull(PhoneLogin::normalize('12345', 'EG'));

        $this->window('12')->call('continueWithPhone')->assertHasErrors('phone');
        $this->window()->set('country', 'XX')->call('continueWithPhone')->assertHasErrors('phone');
        $this->assertSame([], $this->sent);
    }

    public function test_a_number_can_be_written_with_or_without_the_leading_0_or_with_the_country_code(): void
    {
        foreach (['01001234567', '1001234567', '010 0123 4567', '010-0123-4567', '+20 100 123 4567', '00201001234567'] as $typed) {
            $this->assertSame(self::PHONE, PhoneLogin::normalize($typed, 'EG'), $typed);
        }
        // Other countries the same way.
        $this->assertSame('+966512345678', PhoneLogin::normalize('0512345678', 'SA'));
        $this->assertSame('+966512345678', PhoneLogin::normalize('512345678', 'SA'));
        $this->assertSame('+447400123456', PhoneLogin::normalize('07400 123456', 'GB'));
        $this->assertSame('+97433123456', PhoneLogin::normalize('3312 3456', 'QA'));

        $this->window('010 0123 4567')->call('continueWithPhone')->assertHasNoErrors()->assertSet('e164', self::PHONE);
    }

    public function test_the_box_turns_green_once_the_number_is_right_and_red_when_it_cannot_be(): void
    {
        // A guiding line: how many digits a mobile number has in the chosen country.
        $window = Livewire::test(AuthModal::class)->assertSee(['placeholder="Your mobile number"', 'It must have WhatsApp: we’ll send your code there (11 digits).'], false);
        Livewire::test(AuthModal::class)->set('country', 'QA')->assertSee('It must have WhatsApp: we’ll send your code there (8 digits).');

        // Still typing: the guiding line stays, nothing is wrong yet. Counted in the way it's written (with the 0, without it, with the code).
        foreach (['0101', '101', '+2010'] as $typed) {
            $window->set('phone', $typed)->assertDontSee('This number isn’t right.');
            $this->assertSame('short', $window->instance()->phoneCheck()['state'], $typed);
        }

        $window->set('phone', '010 1234 5678')->assertSee('The number is right.')->assertDontSee('aria-invalid', false);
        $this->assertSame('ok', $window->instance()->phoneCheck()['state']);

        $window->set('phone', '010123456789')->assertSee('This number isn’t right.')->assertSee('aria-invalid="true"', false);
        $window->set('phone', '05123456789')->assertSee('This number isn’t right.');

        // Pressing Sign in on a wrong number says the same, once.
        $this->window('0102344556666')->call('continueWithPhone')->assertHasErrors('phone')->assertSeeHtmlInOrder(['This number isn’t right.']);
    }

    public function test_the_window_markup_is_balanced_so_every_step_stays_inside_it(): void
    {
        // An extra </div> once closed the number step early and the e-mail step showed beside the window.
        $html = Livewire::test(AuthModal::class)->html();

        $this->assertSame(substr_count($html, '<div'), substr_count($html, '</div>'));
        $this->assertSame(substr_count($html, '<template'), substr_count($html, '</template>'));
        $this->assertSame(substr_count($html, '<form'), substr_count($html, '</form>'));
    }

    public function test_the_country_list_can_be_searched_in_both_languages(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertSee(['Search a country or code', 'No country found'])
            ->assertSee('x-teleport="body"', false); // above the window, never cut off by it
        $this->withCookie('locale', 'ar')->get(route('home'))->assertOk()
            ->assertSee(['ابحث عن دولة أو كود', 'السعودية', 'بريطانيا']);
    }

    public function test_arabic_digits_are_understood_and_another_country_code_switches_the_country(): void
    {
        $this->assertSame(self::PHONE, PhoneLogin::normalize('٠١٠٠١٢٣٤٥٦٧', 'EG'));
        $this->window('٠١٠٠١٢٣٤٥٦٧')->call('continueWithPhone')->assertHasNoErrors()->assertSet('e164', self::PHONE);

        Livewire::test(AuthModal::class)->set('phone', '+966 51 234 5678')->assertSet('country', 'SA')->assertSee('The number is right.');
        Livewire::test(AuthModal::class)->set('phone', '00971501234567')->assertSet('country', 'AE');
        // A code we don't offer leaves the country as it is.
        Livewire::test(AuthModal::class)->set('phone', '+1 202 555 0123')->assertSet('country', 'EG');
    }

    public function test_a_new_number_gets_a_code_then_chooses_a_name_and_password(): void
    {
        $window = $this->window()->call('continueWithPhone')->assertDispatched('auth-step', step: 'otp', resendIn: 60);
        $this->assertSame(self::PHONE, $this->sent[0][0]);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $this->lastCode());
        $this->assertNotSame($this->lastCode(), PhoneCode::query()->value('code')); // hashed

        $window->call('verifyCode', $this->lastCode())->assertDispatched('auth-step', step: 'phone-setup')->assertSet('setup', 'new')
            ->set('name', 'Omar Ali')->set('password', 'secret-pass-1')->set('passwordConfirmation', 'secret-pass-1')
            ->call('completePhoneSetup')->assertDispatched('signed-in', name: 'Omar Ali');

        $user = WebsiteUser::query()->sole();
        $this->assertSame([self::PHONE, null, 'phone'], [$user->phone, $user->email, $user->registration_method]);
        $this->assertNotNull($user->phone_verified_at);
        $this->assertTrue(Auth::guard('website')->check());
    }

    public function test_after_that_the_number_signs_in_with_its_password_not_a_new_code(): void
    {
        $user = WebsiteUser::factory()->create(['phone' => self::PHONE, 'email' => null, 'password' => 'secret-pass-1', 'registration_method' => 'phone']);

        $window = $this->window('01001234567')->call('continueWithPhone')->assertDispatched('auth-step', step: 'phone-password');
        $this->assertSame([], $this->sent);

        $window->set('password', 'wrong-pass')->call('signInWithPhone')->assertHasErrors('password');
        $window->set('password', 'secret-pass-1')->call('signInWithPhone')->assertDispatched('signed-in');
        $this->assertTrue(Auth::guard('website')->user()->is($user));
    }

    public function test_a_forgotten_password_is_reset_with_a_code(): void
    {
        $user = WebsiteUser::factory()->create(['phone' => self::PHONE, 'email' => null, 'password' => 'old-pass-123', 'registration_method' => 'phone']);

        $this->window()->call('continueWithPhone')->call('forgotPhonePassword')->assertDispatched('auth-step', step: 'otp')
            ->call('verifyCode', $this->lastCode())->assertSet('setup', 'reset')
            ->set('password', 'new-pass-123')->set('passwordConfirmation', 'new-pass-123')->call('completePhoneSetup')->assertDispatched('signed-in');

        $this->assertTrue(Auth::guard('website')->validate(['phone' => self::PHONE, 'password' => 'new-pass-123']));
        $this->assertSame(1, WebsiteUser::query()->count());
        $this->assertTrue(Auth::guard('website')->user()->is($user));
    }

    public function test_each_wrong_code_says_how_many_tries_are_left_and_the_third_locks_the_number(): void
    {
        $window = $this->window()->call('continueWithPhone')->assertSee('3 attempts to enter the code.');
        $right = $this->lastCode();

        $window->call('verifyCode', $this->wrongCode())->assertHasErrors('code')->assertSee('Wrong code. 2 attempts left.')->assertDispatched('code-wrong');
        $window->call('verifyCode', $this->wrongCode())->assertSee('Wrong code. 1 attempt left.');
        $window->call('verifyCode', $this->wrongCode())->assertDispatched('auth-step', step: 'locked');

        $lock = PhoneLock::query()->sole();
        $this->assertSame([self::PHONE, 3, '127.0.0.1', null], [$lock->phone, $lock->attempts, $lock->ip, $lock->unlocked_at]);
        // The code is cancelled: even the right one no longer works.
        $window->call('verifyCode', $right)->assertDispatched('auth-step', step: 'locked');
        $this->assertSame(0, WebsiteUser::query()->count());
    }

    public function test_tries_are_counted_per_number_on_the_server_not_per_browser(): void
    {
        $this->window()->call('continueWithPhone')->call('verifyCode', $this->wrongCode())->call('verifyCode', $this->wrongCode());

        // Another browser (a new window, no session): one try left, and a new code doesn't give more.
        $this->travel(2)->minutes();
        $other = $this->window()->call('continueWithPhone')->assertSee('1 attempt to enter the code.');
        $other->call('verifyCode', $this->wrongCode())->assertDispatched('auth-step', step: 'locked');
        $this->assertTrue(PhoneLogin::isLocked(self::PHONE));
    }

    public function test_a_locked_number_sees_the_same_message_and_gets_no_more_codes_but_its_password_still_works(): void
    {
        WebsiteUser::factory()->create(['phone' => self::PHONE, 'email' => null, 'password' => 'secret-pass-1', 'registration_method' => 'phone']);
        PhoneLock::query()->create(['phone' => self::PHONE, 'attempts' => 3]);

        $this->window()->call('continueWithPhone')->call('forgotPhonePassword')->assertDispatched('auth-step', step: 'locked');
        $this->assertSame([], $this->sent);

        $this->window()->call('continueWithPhone')->set('password', 'secret-pass-1')->call('signInWithPhone')->assertDispatched('signed-in');
    }

    public function test_the_locked_step_offers_to_contact_us_or_another_way_in_both_languages(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertSee(["You can't continue with WhatsApp", 'Contact page', 'Or sign in another way'])
            ->assertSee('href="'.route('contact').'"', false);
        $this->withCookie('locale', 'ar')->get(route('home'))->assertOk()
            ->assertSee(['لا يمكنك المتابعة عبر واتساب', 'صفحة التواصل', 'أو سجّل الدخول بطريقة أخرى']);
    }

    public function test_codes_are_limited_to_one_a_minute_and_three_an_hour(): void
    {
        $window = $this->window()->call('continueWithPhone');
        $window->call('sendCode')->assertDispatched('auth-step', step: 'otp');
        $this->assertCount(1, $this->sent); // within the minute: the same code, nothing sent

        $this->travel(61)->seconds();
        $window->call('sendCode');
        $this->travel(61)->seconds();
        $window->call('sendCode');
        $this->assertCount(3, $this->sent);

        $this->travel(61)->seconds();
        $window->call('sendCode')->assertHasErrors('phone');
        $this->assertCount(3, $this->sent);
    }

    public function test_an_old_code_has_expired(): void
    {
        $window = $this->window()->call('continueWithPhone');
        $this->travel(PhoneLogin::EXPIRES_MINUTES + 1)->minutes();

        $window->call('verifyCode', $this->lastCode())->assertHasErrors('code')->assertSee('This code has expired.');
    }

    public function test_the_password_step_cant_be_reached_without_a_right_code(): void
    {
        $this->window()->call('continueWithPhone')
            ->set('name', 'Intruder')->set('password', 'secret-pass-1')->set('passwordConfirmation', 'secret-pass-1')
            ->call('completePhoneSetup')->assertDispatched('auth-step', step: 'phone');

        $this->assertSame(0, WebsiteUser::query()->count());
    }

    public function test_the_admin_sees_locked_numbers_unlocks_them_and_keeps_the_log(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);
        $user = WebsiteUser::factory()->create(['phone' => self::PHONE, 'name' => 'Omar Ali']);
        $lock = PhoneLock::query()->create(['phone' => self::PHONE, 'website_user_id' => $user->id, 'attempts' => 3, 'ip' => '10.0.0.1']);
        $old = PhoneLock::query()->create(['phone' => '+201112223334', 'attempts' => 3, 'unlocked_at' => now()->subDay()]);
        PhoneCode::query()->create(['phone' => self::PHONE, 'wrong_attempts' => 3]);

        $this->assertSame(1, PhoneLockResource::getNavigationBadgeCount());
        $this->get(PhoneLockResource::getUrl('index'))->assertOk()->assertSee(['WhatsApp Locks', 'Locked now', 'All locks (log)']);

        Livewire::test(ListPhoneLocks::class)
            ->assertCanSeeTableRecords([$lock])->assertCanNotSeeTableRecords([$old])
            ->set('activeTab', 'all')->assertCanSeeTableRecords([$lock, $old])
            ->set('activeTab', 'locked')
            ->callTableAction('unlock', $lock);

        $this->assertNotNull($lock->fresh()->unlocked_at);
        $this->assertTrue($lock->fresh()->unlockedBy->is($admin));
        $this->assertFalse(PhoneLogin::isLocked(self::PHONE));
        $this->assertSame(PhoneLogin::ATTEMPTS, PhoneLogin::attemptsLeft(self::PHONE));
        $this->assertSame(2, PhoneLock::query()->count()); // the log stays
    }

    public function test_the_locks_page_needs_its_permission_and_editing_to_unlock(): void
    {
        $this->actingAs($this->createUserWithPermissions(['website_users' => true]));
        $this->get(PhoneLockResource::getUrl('index'))->assertForbidden();

        $lock = PhoneLock::query()->create(['phone' => self::PHONE, 'attempts' => 3]);
        $this->actingAs($this->createUserWithPermissions(['phone_locks' => ['view']]));
        Livewire::test(ListPhoneLocks::class)->assertTableActionHidden('unlock', $lock);
    }

    public function test_the_cloud_api_sends_the_approved_template_with_the_code(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);

        (new MetaCodeSender([
            'token' => 'test-token', 'phone_number_id' => '123456', 'template' => 'login_code',
            'languages' => ['en' => 'en_US', 'ar' => 'ar'], 'version' => 'v21.0',
        ]))->send(self::PHONE, '482913', 'ar');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://graph.facebook.com/v21.0/123456/messages'
            && $request->hasHeader('Authorization', 'Bearer test-token')
            && $request['to'] === '201001234567'
            && $request['template']['name'] === 'login_code'
            && $request['template']['language']['code'] === 'ar'
            && $request['template']['components'][0]['parameters'][0]['text'] === '482913'
            && $request['template']['components'][1]['parameters'][0]['text'] === '482913');
    }

    public function test_a_whatsapp_account_keeps_its_number_and_may_leave_the_email_empty(): void
    {
        $user = WebsiteUser::factory()->create(['phone' => self::PHONE, 'email' => null, 'registration_method' => 'phone', 'phone_verified_at' => now()]);
        $this->actingAs($user, 'website');

        Livewire::test(AccountSettings::class)
            ->set('name', 'New Name')->set('email', '')->set('phone', '+201999999999')
            ->call('saveDetails')->assertHasNoErrors();

        $this->assertSame(['New Name', null, self::PHONE], [$user->fresh()->name, $user->fresh()->email, $user->fresh()->phone]);
        $this->get(route('account'))->assertOk()->assertSee('Your sign-in number. Contact us to change it.');
    }

    public function test_meta_checks_the_webhook_address_with_the_verify_token(): void
    {
        config(['services.whatsapp.verify_token' => 'my-verify-token']);
        $url = route('api.webhooks.whatsapp.verify');

        $this->get($url.'?hub_mode=subscribe&hub_verify_token=my-verify-token&hub_challenge=12345')->assertOk()->assertSeeText('12345');
        $this->get($url.'?hub_mode=subscribe&hub_verify_token=wrong&hub_challenge=12345')->assertForbidden();
    }

    public function test_the_webhook_refuses_calls_not_signed_by_meta(): void
    {
        $this->window()->call('continueWithPhone');

        $this->webhook([['id' => 'wamid.test-1', 'status' => 'failed', 'errors' => [['code' => 131026]]]], 'not-the-secret')->assertForbidden();
        $this->assertSame('sent', PhoneCode::query()->value('delivery'));
    }

    public function test_the_window_says_when_the_code_was_delivered(): void
    {
        $window = $this->window()->call('continueWithPhone')->assertSee('Sending to WhatsApp…');
        $this->assertSame('wamid.test-1', PhoneCode::query()->value('message_id'));

        $this->webhook([['id' => 'wamid.test-1', 'status' => 'delivered']])->assertOk();
        $window->call('checkDelivery')->assertSet('delivery', 'delivered')->assertSee('Delivered on WhatsApp');

        // A late "sent" never takes it back.
        $this->webhook([['id' => 'wamid.test-1', 'status' => 'sent']]);
        $this->assertSame('delivered', PhoneCode::query()->value('delivery'));
    }

    public function test_a_number_without_whatsapp_is_told_so_and_the_code_is_given_back(): void
    {
        $window = $this->window()->call('continueWithPhone');

        $this->webhook([['id' => 'wamid.test-1', 'status' => 'failed', 'errors' => [['code' => 131026, 'title' => 'Message undeliverable']]]])->assertOk();
        $window->call('checkDelivery')->assertSet('delivery', 'no_whatsapp')->assertDispatched('auth-step', step: 'undelivered')
            ->assertSee(["This number isn't on WhatsApp", 'Change the number']);

        // Not counted, and another can be asked for at once (no minute to wait).
        $this->assertSame(0, PhoneCode::query()->value('sends'));
        $window->call('sendCode')->assertDispatched('auth-step', step: 'otp');
        $this->assertCount(2, $this->sent);
    }

    public function test_a_code_that_failed_for_another_reason_offers_to_send_it_again(): void
    {
        $window = $this->window()->call('continueWithPhone');
        $this->webhook([['id' => 'wamid.test-1', 'status' => 'failed', 'errors' => [['code' => 131049]]]]);

        $window->call('checkDelivery')->assertSet('delivery', 'failed')->assertSee(["The code didn't arrive", 'Send again']);
    }

    public function test_whatsapp_down_says_so_offers_the_email_and_counts_nothing(): void
    {
        $this->down = true;

        $this->window()->call('continueWithPhone')
            ->assertHasErrors('phone')->assertSee("WhatsApp sign-in isn't working right now.")
            ->assertSet('sendFailed', true)->assertSee('Sign in with e-mail instead');
        $this->assertSame(0, (int) PhoneCode::query()->value('sends'));
    }

    public function test_the_cloud_api_gives_back_the_message_id_to_follow(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.HBgM']]])]);

        $id = (new MetaCodeSender([
            'token' => 't', 'phone_number_id' => '1', 'template' => 'login_code', 'languages' => ['en' => 'en'], 'version' => 'v21.0',
        ]))->send(self::PHONE, '123456', 'en');

        $this->assertSame('wamid.HBgM', $id);
    }
}
