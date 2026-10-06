<?php

namespace Modules\Projects\Models;

use App\Models\Concerns\HasUniqueSlug;
use App\Traits\HasLocalization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Developers\Models\Developer;
use Modules\Lookups\Models\Concerns\HasLookups;
use Modules\Projects\Database\Factories\ProjectFactory;
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
     * Small WebP copies for the admin (website sizes are added with the frontend). Files stay while the project is
     * in the trash; they go with a permanent delete.
     */
    public function registerMediaCollections(): void
    {
        $crop = fn () => $this->addMediaConversion('thumb')->fit(Fit::Crop, 160, 160)->format('webp')->nonQueued();
        // Logos and plans are shown whole, never cropped.
        $whole = fn () => $this->addMediaConversion('thumb')->fit(Fit::Max, 160, 160)->format('webp')->nonQueued();

        $this->addMediaCollection(self::FEATURED_IMAGE)->useDisk('public')->singleFile()->registerMediaConversions($crop);
        $this->addMediaCollection(self::LOGO)->useDisk('public')->singleFile()->registerMediaConversions($whole);
        $this->addMediaCollection(self::GALLERY)->useDisk('public')->registerMediaConversions($crop);
        $this->addMediaCollection(self::MASTER_PLAN)->useDisk('public')->singleFile()->registerMediaConversions($whole);
        $this->addMediaCollection(self::ABOUT_IMAGE)->useDisk('public')->singleFile()->registerMediaConversions($crop);
    }
}
