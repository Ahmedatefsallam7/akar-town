<?php

namespace Modules\WebsiteUsers\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A WhatsApp number's current sign-in code (AKAR-49, see Support\PhoneLogin): hashed, valid until expires_at, and
 * the wrong tries since the last right one (a new code doesn't reset them; 3 lock the number).
 */
class PhoneCode extends Model
{
    protected $table = 'website_phone_codes';

    /** Meta's delivery statuses, in order (a late "delivered" never replaces "read"); "failed" ends it. */
    public const DELIVERY_ORDER = ['sent' => 1, 'delivered' => 2, 'read' => 3, 'failed' => 4];

    /** Meta's error code when the number can't get WhatsApp messages (no WhatsApp, or an old app). */
    public const NO_WHATSAPP = '131026';

    protected $fillable = ['phone', 'code', 'expires_at', 'wrong_attempts', 'sent_at', 'message_id', 'delivery', 'delivery_error', 'sends', 'sends_since'];

    protected $hidden = ['code'];

    protected $casts = [
        'expires_at' => 'datetime',
        'sent_at' => 'datetime',
        'wrong_attempts' => 'integer',
        'sends' => 'integer',
        'sends_since' => 'datetime',
    ];
}
