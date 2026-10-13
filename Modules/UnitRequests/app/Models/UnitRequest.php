<?php

namespace Modules\UnitRequests\Models;

use App\Jobs\SendLeadToCrm;
use App\Support\Crm\CrmLead;
use App\Traits\HasLocalization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Lookups\Models\Concerns\HasLookups;
use Modules\UnitRequests\Database\Factories\UnitRequestFactory;
use Modules\Units\Models\Unit;
use Modules\WebsiteUsers\Models\WebsiteUser;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A unit an owner submitted for sale on the website: Pending → Reviewed → Contacted → Closed, or Cancelled. Staff
 * keep private notes and can turn it into a property (see ConvertUnitRequestToUnit).
 */
class UnitRequest extends Model implements CrmLead, HasMedia
{
    use HasFactory, HasLocalization, HasLookups, InteractsWithMedia, SoftDeletes, Userstamps;

    public const FEATURED_IMAGE = 'featured_image';

    public const PENDING = 'pending';

    public const REVIEWED = 'reviewed';

    public const CONTACTED = 'contacted';

    public const CLOSED = 'closed';

    public const CANCELLED = 'cancelled';

    public const STATUSES = [self::PENDING, self::REVIEWED, self::CONTACTED, self::CLOSED, self::CANCELLED];

    /**
     * Values picked from Lookups (checkbox lists): form field => lookup type.
     */
    public const LOOKUPS = [
        'amenities' => 'amenities',
        'offering_types' => 'offeringtype',
        'finishing_types' => 'finishingtype',
        'furnishing_statuses' => 'furnishingstatus',
        'property_types' => 'unittype',
        'services' => 'services',
        'payment_methods' => 'paymentmethod',
    ];

    protected $fillable = [
        'website_user_id',
        'visitor_id',
        'seller_name', 'email', 'phone',
        'name_en', 'name_ar', 'country_id', 'city_id', 'region_id', 'area_id', 'sub_area_id', 'address', 'latitude', 'longitude',
        'price', 'area', 'bedrooms', 'bathrooms', 'down_payment', 'installment_years_from', 'installment_years_to',
        'short_description_en', 'short_description_ar', 'description_en', 'description_ar',
        'status', 'internal_notes',
    ];

    protected $casts = [
        'crm_synced_at' => 'datetime',
        'price' => 'integer',
        'area' => 'integer',
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
        'down_payment' => 'float',
        'installment_years_from' => 'integer',
        'installment_years_to' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    protected $attributes = [
        'status' => self::PENDING,
    ];

    /**
     * The website account it was sent from, when the visitor was logged in (guests: none).
     */
    public function websiteUser(): BelongsTo
    {
        return $this->belongsTo(WebsiteUser::class)->withTrashed();
    }

    protected static function booted(): void
    {
        // A sell request is a lead too: after the save is committed, it goes to the CRM.
        static::created(fn (UnitRequest $request) => SendLeadToCrm::dispatch($request)->afterCommit());
    }

    protected static function newFactory(): UnitRequestFactory
    {
        return UnitRequestFactory::new();
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class)->withTrashed();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::FEATURED_IMAGE)->useDisk('public')->singleFile()
            ->registerMediaConversions(fn () => $this->addMediaConversion('thumb')->fit(Fit::Crop, 160, 160)->format('webp')->nonQueued());
    }

    public function isSentToCrm(): bool
    {
        return filled($this->crm_lead_id);
    }

    public function crmForm(): string
    {
        return 'sell_property';
    }

    /**
     * The owner and the unit they offer (the internal notes stay here).
     */
    public function toCrmLead(): array
    {
        return [
            'full_name' => $this->seller_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'description' => collect([
                'Sell request: '.$this->name_en,
                $this->price ? 'Price: '.number_format($this->price).' EGP' : null,
                $this->area ? "Area: {$this->area} m²" : null,
                $this->bedrooms ? "Bedrooms: {$this->bedrooms}" : null,
                $this->address ? "Address: {$this->address}" : null,
                $this->short_description_en,
            ])->filter()->implode("\n"),
        ];
    }

    public function isConverted(): bool
    {
        return $this->unit_id !== null;
    }

    /**
     * Still open for conversion: not turned into a property yet and not cancelled.
     */
    public function canBeConverted(): bool
    {
        return ! $this->isConverted() && $this->status !== self::CANCELLED;
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return collect(self::STATUSES)->mapWithKeys(fn (string $status) => [$status => __("admin.unit_requests.status_options.{$status}")])->all();
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            self::PENDING => 'warning',
            self::REVIEWED => 'info',
            self::CONTACTED => 'primary',
            self::CLOSED => 'success',
            self::CANCELLED => 'danger',
            default => 'gray',
        };
    }

    /**
     * The statuses a request can move to the given one from (used by the bulk actions): a closed or cancelled
     * request is left alone, and a contacted one isn't moved back to reviewed.
     *
     * @return array<string>
     */
    public static function statusesBefore(string $status): array
    {
        return match ($status) {
            self::REVIEWED => [self::PENDING],
            self::CONTACTED => [self::PENDING, self::REVIEWED],
            default => [],
        };
    }
}
