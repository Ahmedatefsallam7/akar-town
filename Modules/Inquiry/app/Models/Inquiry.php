<?php

namespace Modules\Inquiry\Models;

use App\Jobs\SendLeadToCrm;
use App\Support\Crm\CrmLead;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Inquiry\Database\Factories\InquiryFactory;

/**
 * A contact message from the website (or added by hand, e.g. after a phone call). Every new one goes to the CRM
 * as a fresh lead, with where it came from.
 */
class Inquiry extends Model implements CrmLead
{
    use HasFactory, SoftDeletes, Userstamps;

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'message',
        'source',
        'url',
    ];

    protected $casts = [
        'crm_synced_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // After the save is committed, so the CRM never gets a lead whose save was undone.
        static::created(fn (Inquiry $inquiry) => SendLeadToCrm::dispatch($inquiry)->afterCommit());
    }

    protected static function newFactory(): InquiryFactory
    {
        return InquiryFactory::new();
    }

    public function isSentToCrm(): bool
    {
        return filled($this->crm_lead_id);
    }

    public function crmForm(): string
    {
        return 'contact_us';
    }

    public function toCrmLead(): array
    {
        return [
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'description' => collect([
                $this->message,
                filled($this->source) ? "Source: {$this->source}" : null,
                filled($this->url) ? "URL: {$this->url}" : null,
            ])->filter()->implode("\n\n"),
        ];
    }
}
