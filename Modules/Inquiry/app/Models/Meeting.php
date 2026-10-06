<?php

namespace Modules\Inquiry\Models;

use App\Jobs\SendLeadToCrm;
use App\Support\Crm\CrmLead;
use App\Traits\HasLocalization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Inquiry\Database\Factories\MeetingFactory;

/**
 * A meeting or property-viewing booking: Pending → Confirmed → Completed, with Rescheduled and Cancelled on the
 * side. From the website's booking forms or added by staff; every new one goes to the CRM as a fresh lead.
 */
class Meeting extends Model implements CrmLead
{
    use HasFactory, HasLocalization, SoftDeletes, Userstamps;

    public const TYPES = ['call', 'in_person', 'online', 'site_visit'];

    public const PENDING = 'pending';

    public const CONFIRMED = 'confirmed';

    public const RESCHEDULED = 'rescheduled';

    public const COMPLETED = 'completed';

    public const CANCELLED = 'cancelled';

    public const STATUSES = [self::PENDING, self::CONFIRMED, self::RESCHEDULED, self::COMPLETED, self::CANCELLED];

    protected $fillable = [
        'name',
        'phone',
        'email',
        'meeting_at',
        'type',
        'status',
        'source',
        'url',
        'notes_en',
        'notes_ar',
        'admin_notes_en',
        'admin_notes_ar',
    ];

    protected $casts = [
        'meeting_at' => 'datetime',
        'crm_synced_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::PENDING,
    ];

    protected static function booted(): void
    {
        // After the save is committed, so the CRM never gets a lead whose save was undone.
        static::created(fn (Meeting $meeting) => SendLeadToCrm::dispatch($meeting)->afterCommit());
    }

    protected static function newFactory(): MeetingFactory
    {
        return MeetingFactory::new();
    }

    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return collect(self::TYPES)->mapWithKeys(fn (string $type) => [$type => __("admin.meetings.types.{$type}")])->all();
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return collect(self::STATUSES)->mapWithKeys(fn (string $status) => [$status => __("admin.meetings.status_options.{$status}")])->all();
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            self::PENDING => 'warning',
            self::CONFIRMED => 'success',
            self::RESCHEDULED => 'info',
            self::COMPLETED => 'primary',
            self::CANCELLED => 'danger',
            default => 'gray',
        };
    }

    /**
     * The statuses a booking can move to the given one from (used by the bulk actions): a completed or
     * cancelled booking is closed and isn't confirmed or completed by mistake.
     *
     * @return array<string>
     */
    public static function statusesBefore(string $status): array
    {
        return match ($status) {
            self::CONFIRMED => [self::PENDING, self::RESCHEDULED],
            self::COMPLETED => [self::PENDING, self::CONFIRMED, self::RESCHEDULED],
            default => [],
        };
    }

    public function isSentToCrm(): bool
    {
        return filled($this->crm_lead_id);
    }

    public function crmForm(): string
    {
        return 'schedule_viewing';
    }

    /**
     * The client's details, the booking and their notes. The agents' internal notes stay here.
     */
    public function toCrmLead(): array
    {
        return [
            'full_name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'description' => collect([
                'Requested '.__("admin.meetings.types.{$this->type}", [], 'en').' at '.$this->meeting_at->format('Y-m-d H:i'),
                $this->notes_en,
                $this->notes_ar,
                filled($this->source) ? "Source: {$this->source}" : null,
                filled($this->url) ? "URL: {$this->url}" : null,
            ])->filter()->implode("\n\n"),
        ];
    }
}
