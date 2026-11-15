<?php

namespace Modules\WebsiteUsers\Support\WhatsApp;

/**
 * Sends a sign-in code to a WhatsApp number (AKAR-49). Chosen by config('services.whatsapp.driver'): LogCodeSender
 * (local, tests) or MetaCodeSender (the WhatsApp Cloud API).
 */
interface CodeSender
{
    /**
     * @param  string  $phone  E.164, e.g. +201001234567
     * @param  string  $locale  the visitor's site language (en / ar)
     * @return string|null the provider's id for the message (Meta's "wamid"), to follow its delivery; null when there
     *                     is nothing to follow
     */
    public function send(string $phone, string $code, string $locale): ?string;
}
