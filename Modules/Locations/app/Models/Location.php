<?php

namespace Modules\Locations\Models;

use App\Models\Concerns\HasUniqueSlug;
use App\Traits\HasLocalization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Locations\Database\Factories\LocationFactory;
use Modules\Lookups\Support\LocationTree;
use Modules\Projects\Models\Project;
use Modules\Units\Models\Unit;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A location landing page for the website's "Explore by Location" section, tied to a point in the location chain.
 * Only active ones show on the website; featured ones also show on the homepage, in display order.
 */
class Location extends Model implements HasMedia
{
    use HasFactory, HasLocalization, HasUniqueSlug, InteractsWithMedia, SoftDeletes, Userstamps;

    public const FEATURED_IMAGE = 'featured_image';

    protected $fillable = [
        'country_id',
        'city_id',
        'region_id',
        'area_id',
        'sub_area_id',
        'name_en',
        'name_ar',
        'slug',
        'code',
        'neighbourhoods',
        'latitude',
        'longitude',
        'order',
        'is_featured',
        'is_active',
        'description_en',
        'description_ar',
        'meta_title_en',
        'meta_title_ar',
        'meta_description_en',
        'meta_description_ar',
        'meta_keywords_en',
        'meta_keywords_ar',
    ];

    protected $casts = [
        'neighbourhoods' => 'array',
        'meta_keywords_en' => 'array',
        'meta_keywords_ar' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'order' => 'integer',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Projects in this location: their chain has the location's deepest chosen level (e.g. its area).
     */
    public function projects(): HasMany
    {
        return $this->inLocation(Project::class);
    }

    /**
     * Properties in this location, matched the same way.
     */
    public function units(): HasMany
    {
        return $this->inLocation(Unit::class);
    }

    /**
     * @param  class-string<Model>  $model  has the country_id … sub_area_id columns
     */
    private function inLocation(string $model): HasMany
    {
        $column = collect(LocationTree::LEVELS)->reverse()->first(fn (string $column) => filled($this->{$column}));

        // A location with no chain chosen matches nothing.
        return $column
            ? $this->hasMany($model, $column, $column)
            : $this->hasMany($model, 'id', 'id')->whereRaw('1 = 0');
    }

    protected static function newFactory(): LocationFactory
    {
        return LocationFactory::new();
    }

    /**
     * Card and header image. Files stay while the location is in the trash; they go with a permanent delete.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::FEATURED_IMAGE)
            ->useDisk('public')
            ->singleFile()
            ->registerMediaConversions(function () {
                // Small WebP for the admin list, and the website's size (made in the background).
                $this->addMediaConversion('thumb')
                    ->fit(Fit::Crop, 160, 160)
                    ->format('webp')
                    ->nonQueued();
                $this->addMediaConversion('large')
                    ->fit(Fit::Max, 1920, 1920)
                    ->format('webp')
                    ->queued();
            });
    }
}
