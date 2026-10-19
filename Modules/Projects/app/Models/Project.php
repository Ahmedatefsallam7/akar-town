<?php

namespace Modules\Projects\Models;

use App\Models\Concerns\HasUniqueSlug;
use App\Traits\HasLocalization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Developers\Models\Developer;
use Modules\Galleries\Models\Gallery;
use Modules\Lookups\Models\Concerns\HasLookups;
use Modules\Projects\Database\Factories\ProjectFactory;
use Modules\Sliders\Models\SliderItem;
use Modules\Units\Models\Unit;
use Modules\Users\Models\User;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A compound or development: the core catalogue entity. Belongs to one developer; units, galleries and homepage
 * sliders hang off it. Only published projects show on the website; featured ones go to the homepage slider.
 */
class Project extends Model implements HasMedia
{
    use HasFactory, HasLocalization, HasLookups, HasUniqueSlug, InteractsWithMedia, SoftDeletes, Userstamps;

    public const FEATURED_IMAGE = 'featured_image';

    public const LOGO = 'logo';

    public const GALLERY = 'gallery';

    public const MASTER_PLAN = 'master_plan';

    public const ABOUT_IMAGE = 'about_image';

    /**
     * Values picked from Lookups as checkbox lists: form field => lookup type.
     */
    public const LOOKUP_TYPES = [
        'amenities' => 'amenities',
        'facilities' => 'facility',
        'property_types' => 'unittype',
        'property_usages' => 'propertyusage',
        'services' => 'services',
        'statuses' => 'projectstatus',
        'views' => 'views',
        'finishing_types' => 'finishingtype',
        'sales_payment' => 'paymentmethod',
        'offering_types' => 'offeringtype',
    ];

    protected $fillable = [
        'title_en',
        'title_ar',
        'slug',
        'project_number',
        'developer_id',
        'agent_id',
        'short_description_en',
        'short_description_ar',
        'description_en',
        'description_ar',
        'country_id',
        'city_id',
        'region_id',
        'area_id',
        'sub_area_id',
        'neighbourhood',
        'latitude',
        'longitude',
        'address_en',
        'address_ar',
        'currency_id',
        'price_from',
        'price_to',
        'area_from',
        'area_to',
        'down_payment',
        'installment_years_from',
        'installment_years_to',
        'delivery_date',
        'is_published',
        'is_featured',
        'video_embed',
        'meta_title_en',
        'meta_title_ar',
        'meta_description_en',
        'meta_description_ar',
        'meta_keywords_en',
        'meta_keywords_ar',
    ];

    protected $casts = [
        'price_from' => 'integer',
        'price_to' => 'integer',
        'area_from' => 'integer',
        'area_to' => 'integer',
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

    public static function slugSource(): string
    {
        return 'title_en';
    }

    protected static function newFactory(): ProjectFactory
    {
        return ProjectFactory::new();
    }

    public function developer(): BelongsTo
    {
        return $this->belongsTo(Developer::class);
    }

    /**
     * The admin user shown as the sales advisor on the project page (and on its units without their own agent).
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function galleries(): HasMany
    {
        return $this->hasMany(Gallery::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    /**
     * The homepage slides showing this project (the hero slider's).
     */
    public function sliderItems(): HasMany
    {
        return $this->hasMany(SliderItem::class);
    }

    /**
     * Whether any unit (also one in the trash) belongs to this project. Lists load it as "all_units_count".
     */
    public function hasUnits(): bool
    {
        return array_key_exists('all_units_count', $this->attributes)
            ? $this->attributes['all_units_count'] > 0
            : $this->units()->withTrashed()->exists();
    }

    /**
     * Small WebP copies for the admin, plus a large one for the website (made in the background; the website uses
     * the original until it exists). Files stay while the project is in the trash; they go with a permanent delete.
     */
    public function registerMediaCollections(): void
    {
        $crop = function () {
            $this->addMediaConversion('thumb')->fit(Fit::Crop, 160, 160)->format('webp')->nonQueued();
            $this->addMediaConversion('large')->fit(Fit::Max, 1920, 1920)->format('webp')->queued();
            // The cards' size (lists, home grid, wishlist).
            $this->addMediaConversion('card')->fit(Fit::Max, 900, 900)->format('webp')->queued();
        };
        // Logos and plans are shown whole, never cropped.
        $whole = fn () => $this->addMediaConversion('thumb')->fit(Fit::Max, 160, 160)->format('webp')->nonQueued();

        $this->addMediaCollection(self::FEATURED_IMAGE)->useDisk('public')->singleFile()->registerMediaConversions($crop);
        $this->addMediaCollection(self::LOGO)->useDisk('public')->singleFile()->registerMediaConversions($whole);
        $this->addMediaCollection(self::GALLERY)->useDisk('public')->registerMediaConversions($crop);
        $this->addMediaCollection(self::MASTER_PLAN)->useDisk('public')->singleFile()->registerMediaConversions($whole);
        $this->addMediaCollection(self::ABOUT_IMAGE)->useDisk('public')->singleFile()->registerMediaConversions($crop);
    }
}
