<?php

namespace Modules\Developers\Models;

use App\Models\Concerns\HasUniqueSlug;
use App\Traits\HasLocalization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Developers\Database\Factories\DeveloperFactory;
use Modules\Projects\Models\Project;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A real-estate developer (e.g. Mountain View). Every project belongs to one, so a developer with projects can't be
 * deleted. Only published developers show on the
 * website; featured ones also appear in the homepage partners slider.
 */
class Developer extends Model implements HasMedia
{
    use HasFactory, HasLocalization, HasUniqueSlug, InteractsWithMedia, SoftDeletes, Userstamps;

    public const LOGO = 'logo';

    public const FEATURED_IMAGE = 'featured_image';

    /** The developer page's social links (social_<network> columns), in the design's order. */
    public const NETWORKS = ['instagram', 'facebook', 'linkedin', 'youtube'];

    protected $fillable = [
        'name_en',
        'name_ar',
        'slug',
        'is_published',
        'is_featured',
        'tagline_en',
        'tagline_ar',
        'short_description_en',
        'short_description_ar',
        'description_en',
        'description_ar',
        'email',
        'phone',
        'country_id',
        'city_id',
        'region_id',
        'area_id',
        'sub_area_id',
        'neighbourhood',
        'social_facebook',
        'social_instagram',
        'social_linkedin',
        'social_youtube',
        'meta_title_en',
        'meta_title_ar',
        'meta_description_en',
        'meta_description_ar',
        'meta_keywords_en',
        'meta_keywords_ar',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'meta_keywords_en' => 'array',
        'meta_keywords_ar' => 'array',
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Whether any project (also one in the trash) belongs to this developer. Lists load it as "all_projects_count".
     */
    public function hasProjects(): bool
    {
        return array_key_exists('all_projects_count', $this->attributes)
            ? $this->attributes['all_projects_count'] > 0
            : $this->projects()->withTrashed()->exists();
    }

    protected static function newFactory(): DeveloperFactory
    {
        return DeveloperFactory::new();
    }

    /**
     * Logo (cards, partners slider) and page header image. Files stay while the developer is in the trash; they go
     * with a permanent delete.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::LOGO)
            ->useDisk('public')
            ->singleFile()
            ->registerMediaConversions(function () {
                // Whole logo, never cropped: the admin list and the developer page's round logo (100 px).
                $this->addMediaConversion('thumb')
                    ->fit(Fit::Max, 160, 160)
                    ->format('webp')
                    ->nonQueued();
            });

        $this->addMediaCollection(self::FEATURED_IMAGE)
            ->useDisk('public')
            ->singleFile()
            ->registerMediaConversions(function () {
                $this->addMediaConversion('thumb')
                    ->fit(Fit::Crop, 160, 160)
                    ->format('webp')
                    ->nonQueued();
                // The website's sizes: the developers page cards, the developer page's story photo.
                $this->addMediaConversion('card')->fit(Fit::Max, 900, 900)->format('webp')->queued();
                $this->addMediaConversion('large')->fit(Fit::Max, 1920, 1920)->format('webp')->queued();
            });
    }
}
