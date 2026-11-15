<?php

namespace Modules\WebsiteUsers\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Users\Models\User;

/**
 * A WhatsApp number locked for sign-in after 3 wrong codes (AKAR-49): a log row each time, open until an admin
 * unlocks it (unlocked_at, unlocked_by). created_at is when it was locked.
 */
class PhoneLock extends Model
{
    protected $table = 'website_phone_locks';

    protected $fillable = ['phone', 'website_user_id', 'attempts', 'ip', 'unlocked_at', 'unlocked_by'];

    protected $casts = [
        'unlocked_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public function websiteUser(): BelongsTo
    {
        return $this->belongsTo(WebsiteUser::class);
    }

    public function unlockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unlocked_by');
    }

    /**
     * Locks still in force.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('unlocked_at');
    }

    public function isActive(): bool
    {
        return $this->unlocked_at === null;
    }
}
