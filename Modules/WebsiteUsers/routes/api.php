<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteUsers\Http\Controllers\WhatsAppWebhookController;

// Meta's WhatsApp webhook (AKAR-49): the delivery of sign-in codes. Stateless (no session, no CSRF) and up even
// while the website is switched off.
Route::get('webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify'])->name('webhooks.whatsapp.verify');
Route::post('webhooks/whatsapp', [WhatsAppWebhookController::class, 'receive'])
    ->middleware('throttle:600,1')
    ->name('webhooks.whatsapp');
