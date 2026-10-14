<?php

namespace Modules\Inquiry\Models;

use App\Jobs\SendLeadToCrm;
use App\Support\Crm\CrmLead;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Inquiry\Database\Factories\InquiryFactory;
use Modules\WebsiteUsers\Models\WebsiteUser;

/**
 * A contact message from the website (or added by hand, e.g. after a phone call). Every new one goes to the CRM
 * as a fresh lead, with where it came from.
 */
class Inquiry extends Model implements CrmLead
{
    /** The website forms that send contact messages: each goes to its own CRM form (or the Contact Us one). */
    public const FORMS = ['contact_us', 'property_enquiry', 'project_enquiry', 'property_matching'];

    use HasFactory, SoftDeletes, Userstamps;

    protected $fillable = [
        'website_user_id',
        'visitor_id',
        'full_name',
        'email',
        'phone',
        'message',
        'form',
        'source',
        'url',
    ];

    protected $attributes = [
        'form' => 'contact_us',
    ];

    protected $casts = [
        'crm_synced_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // After the save is committed, so the CRM never gets a lead whose save was undone.
        static::created(fn (Inquiry $inquiry) => SendLeadToCrm::dispatch($inquiry)->afterCommit());
    }

    /**
     * The website account it was sent from, when the visitor was logged in (guests: none).
     */
    public function websiteUser(): BelongsTo
    {
        return $this->belongsTo(WebsiteUser::class)->withTrashed();
    }

    protected static function newFactory(): InquiryFactory
    {
        return InquiryFactory::new();
    }

    public function isSentToCrm(): bool
    {
        return filled($this->crm_lead_id);
    }

    /**
     * @return array<string, string>
     */
    public static function formOptions(): array
    {
        return collect(self::FORMS)->mapWithKeys(fn (string $form) => [$form => __("admin.contact_us.forms.{$form}")])->all();
    }

    public function crmForm(): string
    {
        return in_array($this->form, self::FORMS, true) ? $this->form : 'contact_us';
    }

    public function toCrmLead(): array
    {
        return [
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'description' => collect([
                $this->message,
                'Form: '.__("admin.contact_us.forms.{$this->crmForm()}", [], 'en'),
                filled($this->source) ? "Source: {$this->source}" : null,
                filled($this->url) ? "URL: {$this->url}" : null,
            ])->filter()->implode("\n\n"),
        ];
    }
}
