<?php

namespace Modules\Galleries\Models;

use App\Models\Concerns\HasLocalization;
use App\Support\Images\MediaSizes;
use App\Support\Images\ProtectedImages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Galleries\Database\Factories\GalleryFactory;
use Modules\Projects\Models\Project;
use Modules\Units\Models\Unit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A reusable media pack (photos, 360° images, floor plans, attachments, video). Optionally linked to a project
 * (shown on its Galleries tab); units pick one, so 20 identical units share the same media.
 */
class Gallery extends Model implements HasMedia
{
    use HasFactory, HasLocalization, InteractsWithMedia, SoftDeletes, Userstamps;

    public const FEATURED_IMAGE = 'featured_image';

    public const IMAGES_360 = 'images_360';

    public const PHOTOS = 'photos';

    public const FLOOR_PLANS = 'floor_plans';

    public const ATTACHMENTS = 'attachments';

    protected $fillable = [
        'name_en',
        'name_ar',
        'project_id',
        'video_url',
    ];

    private ?bool $usedByUnits = null;

    protected static function newFactory(): GalleryFactory
    {
        return GalleryFactory::new();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    /**
     * Whether any unit (also one in the trash) uses this gallery.
     */
    public function isUsedByUnits(): bool
    {
        if (array_key_exists('all_units_count', $this->attributes)) {
            return $this->attributes['all_units_count'] > 0;
        }

        // Asked once per record (the edit form asks several times).
        return $this->usedByUnits ??= $this->units()->withTrashed()->exists();
    }

    /**
     * Small WebP copies for the admin, plus a large one for the website (made in the background; the original is
     * used until then). 360° images get none: they are very large and only the website's panorama viewer uses them.
     * Files stay in the trash; they go with a permanent delete.
     */
    public function registerMediaCollections(): void
    {
        // Protected images (AKAR-51): the private disk, the website's sizes watermarked (MediaSizes).
        $photo = fn () => MediaSizes::photo($this);
        $disk = ProtectedImages::DISK;

        $this->addMediaCollection(self::FEATURED_IMAGE)->useDisk($disk)->singleFile()->registerMediaConversions($photo);
        $this->addMediaCollection(self::IMAGES_360)->useDisk($disk)
            ->registerMediaConversions(fn () => MediaSizes::panorama($this));
        $this->addMediaCollection(self::PHOTOS)->useDisk($disk)->registerMediaConversions($photo);
        $this->addMediaCollection(self::FLOOR_PLANS)->useDisk($disk)->registerMediaConversions(fn () => MediaSizes::plan($this));
        // Documents for the admin only.
        $this->addMediaCollection(self::ATTACHMENTS)->useDisk($disk);
    }
}
