<?php

namespace Modules\Developers\Models;

use App\Traits\HasLocalization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Developers\Database\Factories\DeveloperFactory;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A real-estate developer (e.g. Mountain View). Every project belongs to one. Only published developers show on the
 * website; featured ones also appear in the homepage partners slider.
 */
class Developer extends Model implements HasMedia
{
    use HasFactory, HasLocalization, InteractsWithMedia, SoftDeletes, Userstamps;

    public const LOGO = 'logo';

    public const FEATURED_IMAGE = 'featured_image';

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
                // Whole logo, never cropped, for the admin list. Website sizes are added with the frontend.
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
            });
    }

    /**
     * A URL slug from the English name when none is given, kept unique (also against trashed developers).
     */
    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'developer';
        $slug = $base;

        for ($i = 2; static::withTrashed()->where('slug', $slug)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    protected static function booted(): void
    {
        static::saving(function (Developer $developer) {
            if (blank($developer->slug)) {
                $developer->slug = static::uniqueSlug((string) $developer->name_en, $developer->getKey());
            }
        });
    }
}
