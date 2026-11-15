<?php

namespace Modules\WebsiteUsers\Support\WhatsApp;

use Illuminate\Support\Facades\Http;

/**
 * Sends the sign-in code with the WhatsApp Cloud API (WHATSAPP_DRIVER=meta): the approved authentication template
 * (category "Authentication", a "copy code" button), the code in its body and its button. Needs the client's
 * access token, phone number ID and template name (config/services.php "whatsapp").
 *
 * @see https://developers.facebook.com/docs/whatsapp/business-management-api/authentication-templates
 */
class MetaCodeSender implements CodeSender
{
    /**
     * @param  array{token: ?string, phone_number_id: ?string, template: string, languages: array<string, string>, version: string}  $config
     */
    public function __construct(private readonly array $config) {}

    public function send(string $phone, string $code, string $locale): ?string
    {
        return Http::withToken((string) $this->config['token'])
            ->timeout(10)
            ->post("https://graph.facebook.com/{$this->config['version']}/{$this->config['phone_number_id']}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => ltrim($phone, '+'),
                'type' => 'template',
                'template' => [
                    'name' => $this->config['template'],
                    'language' => ['code' => $this->config['languages'][$locale] ?? $this->config['languages']['en']],
                    'components' => [
                        ['type' => 'body', 'parameters' => [['type' => 'text', 'text' => $code]]],
                        ['type' => 'button', 'sub_type' => 'url', 'index' => '0', 'parameters' => [['type' => 'text', 'text' => $code]]],
                    ],
                ],
            ])
            ->throw()
            // Meta's id for the message: its delivery comes later to the webhook (WhatsAppWebhookController).
            ->json('messages.0.id');
    }
}
