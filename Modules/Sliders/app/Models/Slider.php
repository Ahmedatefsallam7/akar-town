<?php

namespace Modules\Sliders\Models;

use App\Traits\HasLocalization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Sliders\Database\Factories\SliderFactory;

/**
 * A homepage block: where it shows (placement), what it lists (content type), its heading and its order.
 * The set is fixed (made by the seeder); the hero slider's slides are its items.
 */
class Slider extends Model
{
    use HasFactory, HasLocalization, Userstamps;

    public const HOME_HERO = 'home_hero';

    public const HOME_BODY = 'home_body';

    public const NONE = 'none';

    public const PLACEMENTS = [self::HOME_HERO, self::HOME_BODY, self::NONE];

    public const CONTENT_TYPES = ['projects', 'properties', 'locations', 'cta_ribbon'];

    protected $fillable = [
        'title_en',
        'title_ar',
        'placement',
        'content_type',
        'order',
        'is_active',
    ];

    protected $casts = [
        'order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        // The hero's slides are always projects (its slides list only offers projects), whatever was picked.
        static::saving(function (Slider $slider) {
            if ($slider->isHero()) {
                $slider->content_type = 'projects';
            }
        });

        // As in Dastan: giving a block the order of another block in the same placement swaps the two.
        static::updating(function (Slider $slider) {
            if (! $slider->isDirty('order')) {
                return;
            }

            static::query()
                ->where('placement', $slider->placement)
                ->whereKeyNot($slider->getKey())
                ->where('order', $slider->order)
                ->first()
                ?->forceFill(['order' => $slider->getOriginal('order')])
                ->saveQuietly();
        });
    }

    protected static function newFactory(): SliderFactory
    {
        return SliderFactory::new();
    }

    public function items(): HasMany
    {
        return $this->hasMany(SliderItem::class);
    }

    public function isHero(): bool
    {
        return $this->placement === self::HOME_HERO;
    }

    /**
     * @return array<string, string>
     */
    public static function placementOptions(): array
    {
        return collect(self::PLACEMENTS)->mapWithKeys(fn (string $placement) => [$placement => __("admin.sliders.placements.{$placement}")])->all();
    }

    /**
     * @return array<string, string>
     */
    public static function contentTypeOptions(): array
    {
        return collect(self::CONTENT_TYPES)->mapWithKeys(fn (string $type) => [$type => __("admin.sliders.content_types.{$type}")])->all();
    }
}
