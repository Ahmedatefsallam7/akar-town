<?php

namespace Modules\WebsiteUsers\Support\WhatsApp;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Writes the sign-in code to the log instead of sending it (WHATSAPP_DRIVER=log): local work and tests, until the
 * client's WhatsApp Cloud API account is connected. Never used on a live site with real visitors.
 */
class LogCodeSender implements CodeSender
{
    /**
     * Returns a made-up message id, so a delivery can be tried locally by hand (PhoneLogin::recordDelivery()).
     */
    public function send(string $phone, string $code, string $locale): ?string
    {
        $id = 'log.'.Str::lower(Str::random(16));
        Log::info("WhatsApp sign-in code for {$phone}: {$code} (message {$id})");

        return $id;
    }
}
