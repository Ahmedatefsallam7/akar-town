<?php

namespace Modules\PossibleLeads\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\PossibleLeads\Database\Factories\PossibleLeadFactory;
use Modules\Projects\Models\Project;
use Modules\Units\Models\Unit;
use Modules\WebsiteUsers\Models\WebsiteUser;

/**
 * A click that shows interest (a form opened / sent, WhatsApp, a phone number) — a possible lead for sales. Marked
 * converted when the same visitor later sends a form (see MarksPossibleLeadsConverted).
 */
class PossibleLead extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    public const FORM = 'form';

    public const WHATSAPP = 'whatsapp';

    public const PHONE = 'phone';

    public const TYPES = [self::FORM, self::WHATSAPP, self::PHONE];

    protected $fillable = [
        'click_type',
        'page_url',
        'unit_id',
        'project_id',
        'visitor_id',
        'website_user_id',
    ];

    protected $casts = [
        'converted_at' => 'datetime',
    ];

    protected static function newFactory(): PossibleLeadFactory
    {
        return PossibleLeadFactory::new();
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class)->withTrashed();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    public function websiteUser(): BelongsTo
    {
        return $this->belongsTo(WebsiteUser::class)->withTrashed();
    }

    /**
     * The lead (contact message, meeting, sell request) this visitor's interest became.
     */
    public function convertedLead(): MorphTo
    {
        return $this->morphTo();
    }

    public function isConverted(): bool
    {
        return $this->converted_at !== null;
    }

    public function scopeConverted(Builder $query, bool $converted = true): Builder
    {
        return $converted ? $query->whereNotNull('converted_at') : $query->whereNull('converted_at');
    }

    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return collect(self::TYPES)->mapWithKeys(fn (string $type) => [$type => __("admin.possible_leads.types.{$type}")])->all();
    }
}
