<?php

namespace Modules\Units\Models;

use App\Models\Concerns\HasUniqueSlug;
use App\Traits\HasLocalization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Galleries\Models\Gallery;
use Modules\Lookups\Models\Concerns\HasLookups;
use Modules\Lookups\Support\LocationTree;
use Modules\Projects\Models\Project;
use Modules\Units\Database\Factories\UnitFactory;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A listing (apartment, villa, office) for primary, resale or rent: what buyers search on the website. It belongs to
 * a project (and takes its location) or stands alone. Only published units show on the website; featured ones go to
 * "Our Latest Properties", in display order.
 */
class Unit extends Model implements HasMedia
{
    use HasFactory, HasLocalization, HasLookups, HasUniqueSlug, InteractsWithMedia, SoftDeletes, Userstamps;

    public const FEATURED_IMAGE = 'featured_image';

    public const IMAGES_360 = 'images_360';

    public const FLOOR_PLAN = 'floor_plan';

    /**
     * Several values from Lookups (checkbox lists): form field => lookup type.
     */
    public const MULTI_LOOKUPS = [
        'amenities' => 'amenities',
        'views' => 'views',
        'facilities' => 'facility',
        'services' => 'services',
        'payment_methods' => 'paymentmethod',
    ];

    /**
     * One value from Lookups, stored in its own column: column => lookup type.
     */
    public const SINGLE_LOOKUPS = [
        'offering_type_id' => 'offeringtype',
        'property_type_id' => 'unittype',
        'property_usage_id' => 'propertyusage',
        'finishing_type_id' => 'finishingtype',
        'furnishing_status_id' => 'furnishingstatus',
        'unit_status_id' => 'unitstatus',
    ];

    /**
     * Choices limited to what the unit's project picked (as in Dastan): column => the project's checkbox list.
     */
    public const PROJECT_SCOPED = [
        'offering_type_id' => 'offeringtype',
        'property_type_id' => 'unittype',
        'property_usage_id' => 'propertyusage',
    ];

    protected $fillable = [
        'name_en', 'name_ar', 'slug', 'project_id',
        'offering_type_id', 'property_type_id', 'property_usage_id', 'finishing_type_id', 'furnishing_status_id', 'unit_status_id',
        'country_id', 'city_id', 'region_id', 'area_id', 'sub_area_id', 'neighbourhood', 'address', 'latitude', 'longitude',
        'is_published', 'is_featured', 'order',
        'price', 'area', 'bedrooms', 'bathrooms', 'floor', 'down_payment', 'installment_years_from', 'installment_years_to', 'delivery_date',
        'short_description_en', 'short_description_ar', 'description_en', 'description_ar',
        'gallery_id', 'video_url',
        'meta_title_en', 'meta_title_ar', 'meta_description_en', 'meta_description_ar', 'meta_keywords_en', 'meta_keywords_ar',
    ];

    protected $casts = [
        'price' => 'integer',
        'area' => 'integer',
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
        'floor' => 'integer',
        'order' => 'integer',
        'down_payment' => 'float',
        'installment_years_from' => 'integer',
        'installment_years_to' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'delivery_date' => 'date',
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'meta_keywords_en' => 'array',
        'meta_keywords_ar' => 'array',
    ];

    protected static function newFactory(): UnitFactory
    {
        return UnitFactory::new();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function gallery(): BelongsTo
    {
        return $this->belongsTo(Gallery::class);
    }

    public function priceHistories(): HasMany
    {
        return $this->hasMany(UnitPriceHistory::class)->latest('id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::FEATURED_IMAGE)->useDisk('public')->singleFile()
            ->registerMediaConversions(fn () => $this->addMediaConversion('thumb')->fit(Fit::Crop, 160, 160)->format('webp')->nonQueued());
        // 360° images are kept whole (too big to resize on the server); only the website viewer uses them.
        $this->addMediaCollection(self::IMAGES_360)->useDisk('public');
        $this->addMediaCollection(self::FLOOR_PLAN)->useDisk('public')->singleFile()
            ->registerMediaConversions(fn () => $this->addMediaConversion('thumb')->fit(Fit::Max, 160, 160)->format('webp')->nonQueued());
    }

    /**
     * The project's "price from / to" follow its units (as in Dastan): lowest and highest unit price. A project whose
     * units have no price keeps the price typed on it.
     */
    public static function updateProjectPrices(?int $projectId): void
    {
        if (! $projectId) {
            return;
        }

        $prices = static::query()->where('project_id', $projectId)->whereNotNull('price')
            ->selectRaw('min(price) as low, max(price) as high')->first();

        if ($prices?->low !== null) {
            Project::query()->whereKey($projectId)->update(['price_from' => $prices->low, 'price_to' => $prices->high]);
        }
    }

    /**
     * Keeps the current price in the history, with who set it (nothing for "price on request").
     */
    private function recordPrice(): void
    {
        if ($this->price !== null) {
            $this->priceHistories()->create(['price' => $this->price, 'changed_by' => auth()->id()]);
        }
    }

    protected static function booted(): void
    {
        // A unit in a project takes the project's location when it has none of its own.
        static::saving(function (Unit $unit) {
            if ($unit->project_id && blank($unit->country_id) && $project = Project::find($unit->project_id)) {
                foreach (LocationTree::LEVELS as $column) {
                    $unit->{$column} = $project->{$column};
                }
            }
        });

        // Price history and the project's price range. "created" and "updated" are separate on purpose: a model keeps
        // wasRecentlyCreated = true for its whole life, so checking it on later saves would log the price again.
        static::created(function (Unit $unit) {
            $unit->recordPrice();
            static::updateProjectPrices($unit->project_id);
        });

        static::updated(function (Unit $unit) {
            if ($unit->wasChanged('price')) {
                $unit->recordPrice();
            }

            if ($unit->wasChanged(['price', 'project_id'])) {
                static::updateProjectPrices($unit->project_id);
                static::updateProjectPrices($unit->wasChanged('project_id') ? $unit->getOriginal('project_id') : null);
            }
        });

        static::deleted(fn (Unit $unit) => static::updateProjectPrices($unit->project_id));
        static::restored(fn (Unit $unit) => static::updateProjectPrices($unit->project_id));
    }
}
