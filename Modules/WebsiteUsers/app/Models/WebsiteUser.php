<?php

namespace Modules\WebsiteUsers\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\WebsiteUsers\Database\Factories\WebsiteUserFactory;
use Modules\WebsiteUsers\Notifications\ResetWebsitePassword;
use Modules\WebsiteUsers\Notifications\VerifyWebsiteEmail;

/**
 * Someone who registered on the public website (login, favourites…). Logs in with the "website" guard only, never
 * the admin panel. Admins can view, correct and block an account, but never see or set its password.
 */
class WebsiteUser extends Authenticatable implements HasLocalePreference, MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes, Userstamps;

    public const ACTIVE = 'active';

    public const BLOCKED = 'blocked';

    public const STATUSES = [self::ACTIVE, self::BLOCKED];

    /** Final list depends on the chosen login cycle (see docs/website-users-questions.md). */
    public const REGISTRATION_METHODS = ['email', 'phone', 'google', 'apple'];

    public const LANGUAGES = ['en', 'ar'];

    public const AVATAR_DISK = 'public';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
        'registration_method',
        'preferred_language',
        'status',
        'admin_notes',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $attributes = [
        'status' => self::ACTIVE,
        'registration_method' => 'email',
        'preferred_language' => 'en',
    ];

    protected static function newFactory(): WebsiteUserFactory
    {
        return WebsiteUserFactory::new();
    }

    /**
     * The account's e-mails (reset link, verification) are in its language.
     */
    public function preferredLocale(): string
    {
        return $this->preferred_language ?: 'en';
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetWebsitePassword($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyWebsiteEmail);
    }

    public function isBlocked(): bool
    {
        return $this->status === self::BLOCKED;
    }

    public function isVerified(): bool
    {
        return $this->email_verified_at !== null || $this->phone_verified_at !== null;
    }

    /**
     * Blocks the account: it can't log in, and "remember me" logins stop at once (the session itself ends on the next
     * request, see EnsureWebsiteUserIsActive).
     */
    public function block(): void
    {
        $this->forceFill(['status' => self::BLOCKED, 'remember_token' => Str::random(60)])->save();
    }

    public function unblock(): void
    {
        $this->forceFill(['status' => self::ACTIVE])->save();
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar ? Storage::disk(self::AVATAR_DISK)->url($this->avatar) : null;
    }

    /**
     * @return array<string, string>
     */
    public static function options(string $key, array $values): array
    {
        return collect($values)->mapWithKeys(fn (string $value) => [$value => __("admin.website_users.{$key}.{$value}")])->all();
    }
}
