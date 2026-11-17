<?php

namespace App\Livewire\Website;

use App\Support\Crm\LeadContact;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\WebsiteUsers\Models\PhoneCode;
use Modules\WebsiteUsers\Models\WebsiteUser;
use Modules\WebsiteUsers\Support\PhoneLogin;

/**
 * The website's "Sign in or Register" window (the design's auth-modal): one window for both, opened by the navbar's
 * account button (window event "open-auth"). Steps: WhatsApp number, code, e-mail and password, welcome.
 *
 * WhatsApp (AKAR-49, see PhoneLogin): a number with an account and a password signs in with the password ("forgot
 * password" sends a code); otherwise a code is sent, and once it's right the visitor chooses a password (and a name
 * for a new account). 3 wrong codes lock the number: the window then offers to contact us or another way in.
 *
 * E-mail: an e-mail that has an account signs in with its password; a new one shows the name and password
 * confirmation fields in the same step and creates the account.
 */
class AuthModal extends Component
{
    /** The design's country codes for the WhatsApp number. */
    public const COUNTRIES = [
        ['EG', '🇪🇬', '+20', 'Egypt'],
        ['SA', '🇸🇦', '+966', 'Saudi Arabia'],
        ['AE', '🇦🇪', '+971', 'UAE'],
        ['KW', '🇰🇼', '+965', 'Kuwait'],
        ['QA', '🇶🇦', '+974', 'Qatar'],
        ['GB', '🇬🇧', '+44', 'United Kingdom'],
    ];

    public const GUARD = 'website';

    /**
     * How a mobile number of the country is written (from the phone number library's example one): the first 0 where
     * the country dials one at home ("" otherwise), the digits without it, and the country code.
     *
     * @return array{trunk: string, digits: int, code: string}
     */
    public static function phoneRules(string $country): array
    {
        // Only the countries we offer (the country can be changed from the browser): anything else is Egypt.
        $country = in_array($country, array_column(self::COUNTRIES, 0), true) ? $country : 'EG';

        return once(function () use ($country) {
            $util = PhoneNumberUtil::getInstance();
            $example = $util->getExampleNumberForType($country, PhoneNumberType::MOBILE);
            $digits = strlen($util->getNationalSignificantNumber($example));
            $home = preg_replace('/\D/', '', $util->format($example, PhoneNumberFormat::NATIONAL));

            return ['trunk' => substr($home, 0, strlen($home) - $digits), 'digits' => $digits, 'code' => '+'.$example->getCountryCode()];
        });
    }

    /**
     * A number typed or pasted with another country's code (+966…, 00966…) switches the country to it, when it's one
     * we offer: nobody has to pick it first.
     */
    public function updatedPhone(): void
    {
        $typed = preg_replace('/^00/', '+', PhoneLogin::latinDigits(trim($this->phone)));
        if (! str_starts_with($typed, '+')) {
            return;
        }

        $util = PhoneNumberUtil::getInstance();
        try {
            $region = $util->getRegionCodeForNumber($util->parse($typed, null));
        } catch (NumberParseException) {
            return;
        }
        if ($region && $region !== $this->country && in_array($region, array_column(self::COUNTRIES, 0), true)) {
            $this->country = $region;
        }
    }

    /**
     * The number as the visitor types it (AKAR-49), checked like the sign-in will check it: ok, short (still being
     * typed), long (too many digits), invalid (not a mobile of the country) or empty. The box turns green with a tick
     * once it's ok and red when it's long or invalid.
     *
     * @return array{state: 'empty'|'ok'|'short'|'long'|'invalid', number?: string}
     */
    public function phoneCheck(): array
    {
        $typed = PhoneLogin::latinDigits(trim($this->phone));
        $country = in_array($this->country, array_column(self::COUNTRIES, 0), true) ? $this->country : 'EG';
        if (preg_replace('/\D/', '', $typed) === '') {
            return ['state' => 'empty'];
        }

        $util = PhoneNumberUtil::getInstance();
        try {
            $number = $util->parse($typed, $country);
        } catch (NumberParseException) {
            return ['state' => 'invalid'];
        }
        if ($util->isValidNumber($number)) {
            return ['state' => 'ok', 'number' => $util->format($number, PhoneNumberFormat::INTERNATIONAL)];
        }

        // Digits typed against the length of the country's mobile numbers, in the way it's being written: with the
        // country code, with the first 0, or without either.
        ['trunk' => $trunk, 'digits' => $significant, 'code' => $code] = static::phoneRules($country);
        $digits = preg_replace('/\D/', '', preg_replace('/^00/', '+', $typed));
        $expected = match (true) {
            str_starts_with($typed, '+') || str_starts_with($typed, '00') => strlen($code) - 1 + $significant,
            $trunk !== '' && str_starts_with($digits, $trunk) => strlen($trunk) + $significant,
            default => $significant,
        };
        $length = strlen($digits);

        return match (true) {
            $length < $expected => ['state' => 'short'],
            $length > $expected => ['state' => 'long'],
            default => ['state' => 'invalid'],
        };
    }

    /** Sign-in tries per minute per visitor (IP), as the admin panel. */
    private const MAX_SIGN_INS = 5;

    /** New accounts per hour per visitor (IP). */
    private const MAX_REGISTRATIONS = 5;

    /** "Forgot password" requests per hour per visitor (IP); the broker also waits a minute between links. */
    private const MAX_RESET_LINKS = 5;

    public string $email = '';

    public string $password = '';

    public string $name = '';

    public string $passwordConfirmation = '';

    /** The e-mail has no account yet: the step asks for the name too. Set by the server only. */
    #[Locked]
    public bool $registering = false;

    /** Registering because the e-mail typed to sign in has no account (not from "Create an account"). */
    public bool $newEmail = false;

    /** "Forgot password": the link was asked for (the same message whether the e-mail has an account or not). */
    #[Locked]
    public bool $resetSent = false;

    /** WhatsApp number: the country (ISO, one of COUNTRIES) and the number as typed. */
    public string $country = 'EG';

    public string $phone = '';

    /** The number checked on the server (E.164), for the next steps. */
    #[Locked]
    public string $e164 = '';

    /** Why a code is asked for: "login" (no password yet) or "reset" (forgot the number's password). */
    #[Locked]
    public string $codeFor = 'login';

    /** After a right code: "new" (name and password), "set" (an account without a password) or "reset". */
    #[Locked]
    public string $setup = '';

    /** Tries left on the code, shown after a wrong one. */
    #[Locked]
    public int $attemptsLeft = PhoneLogin::ATTEMPTS;

    /** The last code's delivery (PhoneLogin::delivery), followed for a short while after it's sent. */
    #[Locked]
    public string $delivery = 'pending';

    /** When the followed code was sent (null: nothing to follow, e.g. the log sender). */
    #[Locked]
    public ?int $followSince = null;

    /** WhatsApp couldn't take the code (down, or refused): the number step offers the e-mail instead. */
    #[Locked]
    public bool $sendFailed = false;

    /** How long the code step asks whether the code arrived. */
    private const FOLLOW_SECONDS = 45;

    /**
     * The e-mail step's button: signs in, or (new e-mail) switches the step to "create an account".
     */
    public function signIn(): void
    {
        $this->email = mb_strtolower(trim($this->email));

        $this->validate([
            'email' => ['required', 'max:191', ...LeadContact::emailRules()],
            'password' => ['required', 'string', 'max:191'],
        ], attributes: ['email' => __('Email'), 'password' => __('Password')]);

        if (! $this->allowed('website-sign-in', self::MAX_SIGN_INS, 60)) {
            return;
        }

        $user = WebsiteUser::query()->where('email', $this->email)->first();

        if (! $user) {
            $this->registering = true;
            $this->newEmail = true;
            $this->resetErrorBag();

            return;
        }

        if (! Auth::guard(self::GUARD)->validate(['email' => $this->email, 'password' => $this->password])) {
            $this->addError('password', __('These details are incorrect.'));

            return;
        }

        if ($user->isBlocked()) {
            $this->addError('email', __('admin.website_users.account_unavailable'));

            return;
        }

        $this->login($user);
    }

    /**
     * Creates the account of a new e-mail, then signs it in.
     */
    public function register(): void
    {
        $this->email = mb_strtolower(trim($this->email));

        $this->validate([
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'max:191', ...LeadContact::emailRules(), Rule::unique('website_users', 'email')],
            'password' => ['required', 'string', 'max:191', Password::min(8)],
            'passwordConfirmation' => ['required', 'same:password'],
        ], attributes: [
            'name' => __('Full name'),
            'email' => __('Email'),
            'password' => __('Password'),
            'passwordConfirmation' => __('Confirm password'),
        ]);

        if (! $this->allowed('website-register', self::MAX_REGISTRATIONS, 3600)) {
            return;
        }

        $user = WebsiteUser::query()->create([
            'name' => trim($this->name),
            'email' => $this->email,
            'password' => $this->password,
            'registration_method' => 'email',
            'preferred_language' => app()->getLocale(),
        ]);

        // "Verify your e-mail" link; signing in doesn't wait for it. A mail server problem never stops the sign-up.
        rescue(fn () => $user->sendEmailVerificationNotification());

        $this->login($user);
    }

    /**
     * "Forgot password": e-mails a reset link (valid 60 minutes) to an active account. The answer is the same for any
     * e-mail, so nobody can find out who has an account.
     */
    public function sendResetLink(): void
    {
        $this->email = mb_strtolower(trim($this->email));

        $this->validate(['email' => ['required', 'max:191', ...LeadContact::emailRules()]], attributes: ['email' => __('Email')]);

        if (! $this->allowed('website-reset-link', self::MAX_RESET_LINKS, 3600)) {
            return;
        }

        $user = WebsiteUser::query()->where('email', $this->email)->first();
        if ($user && ! $user->isBlocked()) {
            rescue(fn () => PasswordBroker::broker('website_users')->sendResetLink(['email' => $this->email]));
        }

        $this->resetSent = true;
    }

    /**
     * Leaves "forgot password" (back to e-mail and password).
     */
    public function cancelReset(): void
    {
        $this->resetSent = false;
        $this->resetErrorBag();
    }

    /**
     * "Create an account": the e-mail step with the name and the password twice (register() checks the e-mail is new).
     */
    public function startRegistration(): void
    {
        $this->registering = true;
        $this->newEmail = false;
        $this->resetErrorBag();
    }

    /**
     * Back to e-mail and password only (the "Back" link, "Sign in" or another e-mail).
     */
    public function cancelRegistration(): void
    {
        $this->registering = false;
        $this->newEmail = false;
        $this->reset('name', 'passwordConfirmation');
        $this->resetErrorBag();
    }

    /**
     * The WhatsApp number step's button: a number with an account and a password asks for it, any other gets a code;
     * a locked number shows why and what to do instead.
     */
    public function continueWithPhone(): void
    {
        $this->resetErrorBag();
        $countries = array_column(self::COUNTRIES, 0);
        $e164 = in_array($this->country, $countries, true) ? PhoneLogin::normalize($this->phone, $this->country) : null;
        if (! $e164) {
            // The box already says what's wrong while typing (phoneCheck()); this is for a press on Sign in anyway.
            $this->addError('phone', __('This number isn’t right.'));

            return;
        }
        $this->e164 = $e164;

        $user = WebsiteUser::query()->where('phone', $e164)->first();
        if ($user?->isBlocked()) {
            $this->addError('phone', __('admin.website_users.account_unavailable'));

            return;
        }
        if ($user && $user->password) {
            $this->reset('password');
            $this->dispatch('auth-step', step: 'phone-password');

            return;
        }

        $this->codeFor = 'login';
        $this->sendCode();
    }

    /**
     * "Forgot password" on the WhatsApp password step: a code to the number, then a new password.
     */
    public function forgotPhonePassword(): void
    {
        $this->resetErrorBag();
        $this->codeFor = 'reset';
        $this->sendCode();
    }

    /**
     * Sends a code (or "resend code") and opens the code step; a locked number opens the locked step.
     */
    public function sendCode(): void
    {
        if ($this->e164 === '') {
            $this->dispatch('auth-step', step: 'phone');

            return;
        }

        $result = app(PhoneLogin::class)->send($this->e164, app()->getLocale(), request()->ip());
        $this->attemptsLeft = PhoneLogin::attemptsLeft($this->e164);
        $this->sendFailed = $result['status'] === 'failed';
        if ($result['status'] === 'sent') {
            $this->delivery = 'pending';
            $this->followSince = PhoneCode::query()->where('phone', $this->e164)->value('message_id') ? now()->timestamp : null;
        }

        match ($result['status']) {
            'locked' => $this->dispatch('auth-step', step: 'locked'),
            'sent', 'wait' => $this->dispatch('auth-step', step: 'otp', resendIn: $result['seconds']),
            'too_many' => $this->addError('phone', __('Too many codes asked for. Please try again in :minutes minutes.', ['minutes' => (int) ceil($result['seconds'] / 60)])),
            default => $this->addError('phone', __("WhatsApp sign-in isn't working right now. Please try again in a moment, or sign in with your e-mail.")),
        };
    }

    /**
     * Asked every few seconds by the code step for a while: whether the code arrived. One that didn't (no WhatsApp on
     * the number, or another reason) opens the step that says so, with the other ways in.
     */
    public function checkDelivery(): void
    {
        if ($this->e164 === '') {
            return;
        }

        $this->delivery = PhoneLogin::delivery($this->e164);
        if (in_array($this->delivery, ['no_whatsapp', 'failed'], true)) {
            $this->followSince = null;
            $this->dispatch('auth-step', step: 'undelivered');
        }
    }

    /**
     * Whether the code step still asks if the code arrived.
     */
    public function following(): bool
    {
        return $this->followSince !== null && $this->delivery === 'pending' && now()->timestamp - $this->followSince < self::FOLLOW_SECONDS;
    }

    /**
     * Checks the code: right opens "choose a password", wrong says how many tries are left, the third wrong locks.
     */
    public function verifyCode(string $code): void
    {
        $this->resetErrorBag();
        if (! preg_match('/^\d{6}$/', $code)) {
            $this->addError('code', __('Enter the 6-digit code.'));

            return;
        }

        $result = app(PhoneLogin::class)->verify($this->e164, $code, request()->ip());
        if ($result['status'] === 'locked') {
            $this->dispatch('auth-step', step: 'locked');

            return;
        }
        if ($result['status'] !== 'ok') {
            $this->attemptsLeft = $result['left'] ?? $this->attemptsLeft;
            $this->addError('code', $result['status'] === 'wrong'
                ? trans_choice('Wrong code. :count attempt left.|Wrong code. :count attempts left.', $this->attemptsLeft, ['count' => $this->attemptsLeft])
                : __('This code has expired. Ask for a new one.'));
            $this->dispatch('code-wrong');

            return;
        }

        $user = WebsiteUser::query()->where('phone', $this->e164)->first();
        $this->setup = match (true) {
            $user === null => 'new',
            $this->codeFor === 'reset' => 'reset',
            default => 'set',
        };
        $this->reset('password', 'passwordConfirmation');
        $this->dispatch('auth-step', step: 'phone-setup');
    }

    /**
     * Signs in with the WhatsApp number and its password.
     */
    public function signInWithPhone(): void
    {
        $this->validate(['password' => ['required', 'string', 'max:191']], attributes: ['password' => __('Password')]);
        if (! $this->allowed('website-sign-in', self::MAX_SIGN_INS, 60, 'password')) {
            return;
        }

        $user = WebsiteUser::query()->where('phone', $this->e164)->first();
        if (! $user || ! Auth::guard(self::GUARD)->validate(['phone' => $this->e164, 'password' => $this->password])) {
            $this->addError('password', __('These details are incorrect.'));

            return;
        }
        if ($user->isBlocked()) {
            $this->addError('password', __('admin.website_users.account_unavailable'));

            return;
        }

        $this->login($user);
    }

    /**
     * After a right code: creates the account (name and password) or sets the password, then signs in.
     */
    public function completePhoneSetup(): void
    {
        if ($this->e164 === '' || PhoneLogin::verifiedPhone() !== $this->e164) {
            $this->dispatch('auth-step', step: 'phone');

            return;
        }

        $this->validate([
            'name' => $this->setup === 'new' ? ['required', 'string', 'max:191'] : ['nullable'],
            'password' => ['required', 'string', 'max:191', Password::min(8)],
            'passwordConfirmation' => ['required', 'same:password'],
        ], attributes: ['name' => __('Full name'), 'password' => __('Password'), 'passwordConfirmation' => __('Confirm password')]);

        $user = WebsiteUser::query()->where('phone', $this->e164)->first();
        if ($user?->isBlocked()) {
            $this->addError('password', __('admin.website_users.account_unavailable'));

            return;
        }

        $user ??= new WebsiteUser([
            'name' => trim($this->name),
            'phone' => $this->e164,
            'registration_method' => 'phone',
            'preferred_language' => app()->getLocale(),
        ]);
        $user->password = $this->password;
        $user->phone_verified_at = now();
        $user->save();

        PhoneLogin::forgetVerified();
        $this->login($user);
    }

    private function login(WebsiteUser $user): void
    {
        Auth::guard(self::GUARD)->login($user, remember: true);
        session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        $this->reset('password', 'passwordConfirmation', 'name');
        $this->registering = false;
        // The window shows the welcome step, then the page reloads (navbar, forms) signed in.
        $this->dispatch('signed-in', name: $user->name);
    }

    /**
     * Counts a try; false (with the "too many" message) once the visitor reached the limit.
     */
    private function allowed(string $action, int $max, int $seconds, string $field = 'email'): bool
    {
        $key = $action.':'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, $max)) {
            $this->addError($field, __('Too many attempts. Please try again in :seconds seconds.', ['seconds' => RateLimiter::availableIn($key)]));

            return false;
        }
        RateLimiter::hit($key, $seconds);

        return true;
    }

    public function render(): View
    {
        return view('livewire.website.auth-modal');
    }
}
