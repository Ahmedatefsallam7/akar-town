<?php

namespace App\Support\Website;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Image URLs for the website: a WebP size when it has been made ("card" ~900px for cards, "large" 1920px for full
 * views), otherwise the original upload. Photos that are missing (a unit, project or location without any) show the
 * placeholder: the design's villa sketch.
 */
class Image
{
    public const PLACEHOLDER = 'images/website/placeholder-property.svg';

    public static function placeholder(): string
    {
        return asset(self::PLACEHOLDER);
    }

    public static function orPlaceholder(?Media $media, string $conversion = 'large'): string
    {
        return static::url($media, $conversion) ?? static::placeholder();
    }

    /**
     * @param  iterable<Media>  $media
     * @return list<string> the photos' URLs, or just the placeholder when there are none
     */
    public static function photos(iterable $media, string $conversion = 'large'): array
    {
        $urls = collect($media)->map(fn (Media $media) => static::url($media, $conversion))->filter()->values()->all();

        return $urls ?: [static::placeholder()];
    }

    /**
     * The sizes a browser can choose from for an <img srcset> (phones take the card size, big screens the large one);
     * only the sizes already made. Null when there's nothing to choose.
     */
    public static function srcset(?Media $media): ?string
    {
        if (! $media) {
            return null;
        }

        $sizes = array_filter(['card' => '900w', 'large' => '1920w'], fn (string $width, string $size) => $media->hasGeneratedConversion($size), ARRAY_FILTER_USE_BOTH);

        return count($sizes) > 1 ? implode(', ', array_map(fn (string $size, string $width) => $media->getUrl($size).' '.$width, array_keys($sizes), $sizes)) : null;
    }

    /**
     * The asked size, else the large one, else the original (sizes are made in the background after an upload).
     */
    public static function url(?Media $media, string $conversion = 'large'): ?string
    {
        if (! $media) {
            return null;
        }

        foreach (array_unique([$conversion, 'large']) as $size) {
            if ($media->hasGeneratedConversion($size)) {
                return $media->getUrl($size);
            }
        }

        return $media->getUrl();
    }
}
