<?php

namespace Modules\WebsiteUsers\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\WebsiteUsers\Support\PhoneLogin;

/**
 * Meta's WhatsApp webhook (AKAR-49), at /api/webhooks/whatsapp: what happened to each sign-in code (sent, delivered,
 * read, or failed with an error code such as 131026: the number has no WhatsApp).
 *
 * GET: Meta checks the address once, with the verify token chosen when the webhook is added in Meta's dashboard.
 * POST: the statuses, signed by Meta with the app secret (X-Hub-Signature-256); anything not signed is refused, so
 * nobody can pretend a code arrived or failed.
 */
class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $token = (string) config('services.whatsapp.verify_token');

        return $request->query('hub_mode') === 'subscribe' && $token !== '' && hash_equals($token, (string) $request->query('hub_verify_token'))
            ? response((string) $request->query('hub_challenge'))
            : response('', 403);
    }

    public function receive(Request $request): Response
    {
        $secret = (string) config('services.whatsapp.app_secret');
        $signature = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);
        if ($secret === '' || ! hash_equals($signature, (string) $request->header('X-Hub-Signature-256'))) {
            return response('', 403);
        }

        foreach ($request->input('entry', []) as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                foreach ($change['value']['statuses'] ?? [] as $status) {
                    if (isset($status['id'], $status['status'])) {
                        PhoneLogin::recordDelivery((string) $status['id'], (string) $status['status'], isset($status['errors'][0]['code']) ? (string) $status['errors'][0]['code'] : null);
                    }
                }
            }
        }

        // Meta only needs a quick 200; it sends the same status again when it gets anything else.
        return response('', 200);
    }
}
