<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * A URL slug made from the English name/title when none is given, kept unique (also against records in the trash,
 * so a restored record never clashes). Used with SeoFields::slug() / slugFrom() in the admin.
 */
trait HasUniqueSlug
{
    /**
     * The column the slug is made from.
     */
    public static function slugSource(): string
    {
        return 'name_en';
    }

    public static function uniqueSlug(string $text, ?int $ignoreId = null): string
    {
        // Room for a "-12" suffix in the 191-character column.
        $base = Str::limit(Str::slug($text) ?: Str::slug(class_basename(static::class)), 180, '');
        $slug = $base;
        $query = fn () => in_array(SoftDeletes::class, class_uses_recursive(static::class), true) ? static::withTrashed() : static::query();

        for ($i = 2; $query()->where('slug', $slug)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    public static function bootHasUniqueSlug(): void
    {
        static::saving(function (self $record) {
            if (blank($record->slug)) {
                $record->slug = static::uniqueSlug((string) $record->getAttribute(static::slugSource()), $record->getKey());
            }
        });
    }
}
