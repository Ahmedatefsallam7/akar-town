<?php

namespace App\Jobs;

use App\Support\Crm\CrmLead;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Sends a lead to the CRM in the background (the visitor never waits for it), retried a few times if the CRM is
 * down. Sent once: a record that already has a CRM lead id is skipped. Without CRM settings nothing is sent.
 */
class SendLeadToCrm implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    /** A lead deleted for good before the job runs: drop the job quietly. */
    public bool $deleteWhenMissingModels = true;

    /** @var array<int> Seconds between attempts. */
    public array $backoff = [60, 300, 1800];

    public function __construct(public Model&CrmLead $record) {}

    public function handle(): void
    {
        $record = $this->record;
        $formId = static::formId($record->crmForm());

        // Already in the CRM, or deleted by staff (e.g. spam) before it was sent.
        if (filled($record->crm_lead_id) || (method_exists($record, 'trashed') && $record->trashed())) {
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
            ->withoutRedirecting()
            // A refused send keeps only the HTTP status in its error (the queue saves failed jobs' errors): the
            // CRM's reply may repeat the lead's personal details.
            ->truncateExceptionsAt(0)
            ->post('api/v1/lead_generation/web_form_routings/storeLead', $this->payload($record->toCrmLead(), (string) $formId))
            ->throw();

        // Only a JSON answer from the CRM's API counts: a web page (wrong address, a login redirect) is a 200 too,
        // and must not mark the lead as sent.
        if (! is_array($response->json())) {
            throw new RuntimeException("CRM didn't answer with JSON (HTTP {$response->status()}, {$response->header('Content-Type')}).");
        }

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

    /**
     * Why a send failed, in words staff understand (no CRM reply in it: it may repeat the lead's personal details).
     */
    public static function failureReason(Throwable $exception): string
    {
        if ($exception instanceof ConnectionException) {
            return __('admin.crm.errors.connection');
        }

        if ($exception instanceof RequestException) {
            $status = $exception->response->status();

            return match (true) {
                in_array($status, [401, 403]) => __('admin.crm.errors.token'),
                $status >= 500 => __('admin.crm.errors.server', ['status' => $status]),
                default => __('admin.crm.errors.refused', ['status' => $status]),
            };
        }

        if ($exception instanceof RuntimeException) {
            return __('admin.crm.errors.not_api');
        }

        return __('admin.crm.errors.unknown');
    }

    public function failed(Throwable $exception): void
    {
        Log::error('CRM lead not sent after all attempts.', [
            'record' => $this->record::class.'#'.$this->record->getKey(),
            // Not the message: the CRM's reply in it may repeat the lead's personal details.
            'error' => $exception::class,
            'status' => $exception instanceof RequestException ? $exception->response->status() : null,
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
