<?php

namespace App\Livewire\Website\Concerns;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use Livewire\Attributes\Locked;
use Modules\WebsiteUsers\Models\PhoneCode;
use Modules\WebsiteUsers\Models\WebsiteUser;
use Modules\WebsiteUsers\Support\PhoneLogin;

/**
 * The sign-in window's WhatsApp way in (AKAR-49, App\Livewire\Website\AuthModal; see PhoneLogin): a number with an
 * account and a password signs in with the password ("forgot password" sends a code); otherwise a code is sent, and
 * once it's right the visitor chooses a password (and a name for a new account). 3 wrong codes lock the number. The
 * number is checked as it's typed, and the code's delivery is followed for a short while. Uses the window's login()
 * and allowed() (tries per visitor).
 */
trait SignsInWithWhatsApp
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
}
