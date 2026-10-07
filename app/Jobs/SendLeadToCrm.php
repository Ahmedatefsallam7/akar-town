<?php

namespace App\Jobs;

use App\Support\Crm\CrmLead;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends a lead to the CRM in the background (the visitor never waits for it), retried a few times if the CRM is
 * down. Sent once: a record that already has a CRM lead id is skipped. Without CRM settings nothing is sent.
 */
class SendLeadToCrm implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    /** @var array<int> Seconds between attempts. */
    public array $backoff = [60, 300, 1800];

    public function __construct(public Model&CrmLead $record) {}

    public function handle(): void
    {
        $record = $this->record;
        $formId = static::formId($record->crmForm());

        if (filled($record->crm_lead_id)) {
            return;
        }

        if (blank(config('crm.token')) || blank(config('crm.base_url')) || blank($formId)) {
            Log::warning('CRM lead not sent: CRM settings are missing.', ['record' => $record::class.'#'.$record->getKey()]);

            return;
        }

        $response = Http::baseUrl(config('crm.base_url'))
            ->withToken(config('crm.token'))
            ->acceptJson()
            ->withUserAgent(config('app.name').'/Web')
            ->timeout(config('crm.timeout'))
            ->post('api/v1/lead_generation/web_form_routings/storeLead', $this->payload($record->toCrmLead(), (string) $formId))
            ->throw();

        $record->forceFill([
            'crm_lead_id' => (string) ($response->json('data.id') ?? $response->json('id') ?? 'synced'),
            'crm_synced_at' => now(),
        ])->saveQuietly();
    }

    /**
     * The CRM form a kind of lead goes to; one without its own goes to the Contact Us form (never dropped).
     */
    public static function formId(string $form): ?string
    {
        return config("crm.forms.{$form}") ?: config('crm.forms.contact_us');
    }

    /**
     * Whether leads can be sent at all (token, address and a Contact Us form set).
     */
    public static function isConfigured(): bool
    {
        return filled(config('crm.token')) && filled(config('crm.base_url')) && filled(config('crm.forms.contact_us'));
    }

    public function failed(Throwable $exception): void
    {
        Log::error('CRM lead not sent after all attempts.', [
            'record' => $this->record::class.'#'.$this->record->getKey(),
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * The CRM's web-form lead format (as Dastan sends it).
     *
     * @param  array{full_name: string, phone: ?string, email: ?string, description: string}  $lead
     */
    private function payload(array $lead, string $formId): array
    {
        $fullName = trim($lead['full_name']);
        $parts = preg_split('/\s+/', $fullName, 2, PREG_SPLIT_NO_EMPTY) ?: [];
        $phone = preg_replace('/[^\d+]/', '', (string) $lead['phone']);

        return [
            'title' => '',
            'first_name' => $parts[0] ?? $fullName,
            'middle_name' => '',
            'last_name' => $parts[1] ?? '',
            'full_name' => $fullName,
            'description' => $lead['description'],
            'company' => '',
            'address' => '',
            'zip_code' => '',
            'birthdate' => '',
            'phones' => $phone ? [['phone' => $phone, 'country_code' => config('crm.default_country_code')]] : [],
            'social_accounts' => filled($lead['email'])
                ? [['social_account' => $lead['email'], 'account_type_id' => config('crm.email_account_type_id')]]
                : [],
            'form_id' => $formId,
        ];
    }
}
