<?php

namespace Modules\WebsiteUsers\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Users\Models\User;
use Modules\WebsiteUsers\Models\PhoneCode;
use Modules\WebsiteUsers\Models\PhoneLock;
use Modules\WebsiteUsers\Models\WebsiteUser;
use Modules\WebsiteUsers\Support\WhatsApp\CodeSender;
use Propaganistas\LaravelPhone\PhoneNumber;
use Throwable;

/**
 * Signing in with a WhatsApp number (AKAR-49). A 6-digit code is sent to the number (valid 10 minutes, hashed);
 * the number has 3 tries in all to type it: a new code doesn't give more, the third wrong one cancels the code and
 * locks the number for WhatsApp sign-in until an admin unlocks it (each lock is kept as a log). Everything is
 * counted on the server per number, so a refresh or another browser changes nothing. Codes cost money (Meta
 * charges each message): one a minute and three an hour per number, ten an hour per visitor (IP).
 *
 * Delivery: Meta accepts a message before it knows whether the number has WhatsApp. Its webhook then says what
 * happened (recordDelivery); a code that failed (e.g. a number without WhatsApp) is given back and can be asked for
 * again at once, and the window says why (delivery()).
 */
class PhoneLogin
{
    public const ATTEMPTS = 3;

    public const EXPIRES_MINUTES = 10;

    public const RESEND_SECONDS = 60;

    public const SENDS_PER_HOUR = 3;

    public const SENDS_PER_IP_PER_HOUR = 10;

    /** A checked number stays good this long for the next step (choose a password). */
    private const VERIFIED_MINUTES = 15;

    private const SESSION_KEY = 'website_phone_verified';

    public function __construct(private readonly CodeSender $sender) {}

    /**
     * The number in E.164 (+201001234567), or null when it isn't a valid number of that country.
     */
    public static function normalize(string $number, string $country): ?string
    {
        try {
            $phone = new PhoneNumber(static::latinDigits(trim($number)), strtoupper($country));

            return $phone->isValid() ? $phone->formatE164() : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Arabic-Indic and Persian digits (from an Arabic keyboard: ٠١٠…) as 0–9.
     */
    public static function latinDigits(string $text): string
    {
        return strtr($text, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
    }

    public static function isLocked(string $phone): bool
    {
        return PhoneLock::query()->where('phone', $phone)->active()->exists();
    }

    /**
     * Sends a new code. Statuses: sent (seconds = until the next one), wait (one was just sent: seconds left),
     * too_many (seconds = until a code can be asked for again), locked, failed (WhatsApp didn't take it).
     *
     * @return array{status: 'sent'|'wait'|'too_many'|'locked'|'failed', seconds?: int}
     */
    public function send(string $phone, string $locale, ?string $ip = null): array
    {
        if (static::isLocked($phone)) {
            return ['status' => 'locked'];
        }

        $code = PhoneCode::query()->firstOrNew(['phone' => $phone]);
        if ($code->sent_at && ($left = self::RESEND_SECONDS - (int) $code->sent_at->diffInSeconds(now())) > 0) {
            return ['status' => 'wait', 'seconds' => $left];
        }

        // This hour's codes for the number, counted with it (a code that never arrived is given back).
        if (! $code->sends_since || $code->sends_since->lte(now()->subHour())) {
            $code->fill(['sends' => 0, 'sends_since' => now()]);
        }
        if ($code->sends >= self::SENDS_PER_HOUR) {
            return ['status' => 'too_many', 'seconds' => max(1, (int) now()->diffInSeconds($code->sends_since->copy()->addHour()))];
        }
        if (RateLimiter::tooManyAttempts($key = 'phone-code-ip:'.$ip, self::SENDS_PER_IP_PER_HOUR)) {
            return ['status' => 'too_many', 'seconds' => RateLimiter::availableIn($key)];
        }

        $plain = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);
        try {
            // WhatsApp down, a wrong token or a template Meta refuses: nothing was sent, nothing is counted.
            $messageId = $this->sender->send($phone, $plain, $locale);
        } catch (Throwable $e) {
            report($e);

            return ['status' => 'failed'];
        }

        $code->fill([
            'code' => Hash::make($plain),
            'expires_at' => now()->addMinutes(self::EXPIRES_MINUTES),
            'sent_at' => now(),
            'message_id' => $messageId,
            'delivery' => $messageId ? 'sent' : null,
            'delivery_error' => null,
            'sends' => $code->sends + 1,
        ])->save();
        RateLimiter::hit($key, 3600);

        return ['status' => 'sent', 'seconds' => self::RESEND_SECONDS];
    }

    /**
     * Checks a typed code. Statuses: ok (the number is remembered as checked for the next step), wrong (left = tries
     * left), locked (the third wrong one, or already locked), expired (no code, or too old).
     *
     * @return array{status: 'ok'|'wrong'|'locked'|'expired', left?: int}
     */
    public function verify(string $phone, string $typed, ?string $ip = null): array
    {
        if (static::isLocked($phone)) {
            return ['status' => 'locked'];
        }

        // One check at a time per number: two quick tries can't both count as the second.
        return DB::transaction(function () use ($phone, $typed, $ip) {
            $code = PhoneCode::query()->where('phone', $phone)->lockForUpdate()->first();
            if (! $code?->code || $code->expires_at?->isPast()) {
                return ['status' => 'expired'];
            }

            if (Hash::check($typed, $code->code)) {
                $code->update(['code' => null, 'expires_at' => null, 'wrong_attempts' => 0]);
                session()->put(self::SESSION_KEY, ['phone' => $phone, 'at' => now()->timestamp]);

                return ['status' => 'ok'];
            }

            $code->wrong_attempts++;
            if ($code->wrong_attempts < self::ATTEMPTS) {
                $code->save();

                return ['status' => 'wrong', 'left' => self::ATTEMPTS - $code->wrong_attempts];
            }

            $code->fill(['code' => null, 'expires_at' => null])->save();
            PhoneLock::query()->create([
                'phone' => $phone,
                'website_user_id' => WebsiteUser::query()->where('phone', $phone)->value('id'),
                'attempts' => $code->wrong_attempts,
                'ip' => $ip,
            ]);

            return ['status' => 'locked'];
        });
    }

    /**
     * What Meta's webhook says about a message (WhatsAppWebhookController): sent, delivered, read or failed with an
     * error code. Statuses can arrive out of order, so one never goes back (a late "delivered" after "read"). A failed
     * code is given back: it isn't counted in the hour and another can be asked for at once.
     */
    public static function recordDelivery(string $messageId, string $status, ?string $errorCode = null): void
    {
        $order = PhoneCode::DELIVERY_ORDER;
        $code = PhoneCode::query()->where('message_id', $messageId)->first();
        if (! $code || ! isset($order[$status]) || ($order[$code->delivery] ?? 0) >= $order[$status]) {
            return;
        }

        $code->fill(['delivery' => $status, 'delivery_error' => $status === 'failed' ? $errorCode : null]);
        if ($status === 'failed') {
            $code->fill(['sends' => max(0, $code->sends - 1), 'sent_at' => null]);
        }
        $code->save();
    }

    /**
     * Where the number's last code is, for the code step: pending (sent, no news yet, or nothing to follow),
     * delivered (or read), no_whatsapp (the number can't get WhatsApp messages) or failed (any other reason).
     *
     * @return 'pending'|'delivered'|'no_whatsapp'|'failed'
     */
    public static function delivery(string $phone): string
    {
        $code = PhoneCode::query()->where('phone', $phone)->first(['delivery', 'delivery_error']);

        return match ($code?->delivery) {
            'delivered', 'read' => 'delivered',
            'failed' => $code->delivery_error === PhoneCode::NO_WHATSAPP ? 'no_whatsapp' : 'failed',
            default => 'pending',
        };
    }

    /**
     * Tries left on the number's current code (3 for a number that never got one wrong).
     */
    public static function attemptsLeft(string $phone): int
    {
        return max(0, self::ATTEMPTS - (int) PhoneCode::query()->where('phone', $phone)->value('wrong_attempts'));
    }

    /**
     * The number this visitor just checked with a code (for the next few minutes), or null.
     */
    public static function verifiedPhone(): ?string
    {
        $checked = session(self::SESSION_KEY);

        return is_array($checked) && now()->timestamp - (int) $checked['at'] <= self::VERIFIED_MINUTES * 60 ? $checked['phone'] : null;
    }

    public static function forgetVerified(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * An admin unlocks the number: 3 fresh tries, and codes can be asked for again at once.
     */
    public static function unlock(PhoneLock $lock, ?User $admin): void
    {
        $lock->update(['unlocked_at' => now(), 'unlocked_by' => $admin?->getKey()]);
        PhoneCode::query()->where('phone', $lock->phone)->update(['wrong_attempts' => 0, 'code' => null, 'expires_at' => null, 'sent_at' => null, 'sends' => 0, 'sends_since' => null]);
    }
}
