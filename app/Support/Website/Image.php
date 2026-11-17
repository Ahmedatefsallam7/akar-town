<?php

namespace App\Support\Website;

use App\Support\Images\MediaSizes;
use App\Support\Images\ProtectedImages;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Image URLs for the website: the WebP sizes made for it ("card" ~900px for cards, "large" for full views), with the
 * watermark, through short-lived protected links (AKAR-51, see ProtectedImages). Never the uploaded original: until
 * its sizes are made (a minute after an upload) and for a missing photo, the placeholder (the design's villa sketch).
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
     * The asked size, else the large one; the placeholder while they're being made (never the original).
     */
    public static function url(?Media $media, string $conversion = 'large'): ?string
    {
        if (! $media) {
            return null;
        }

        $size = static::madeSize($media, $conversion);

        return $size ? $media->getUrl($size) : static::placeholder();
    }

    /**
     * The photo shown when a page is shared (social media, chat apps): the cards' size, with a link that works for a
     * week, since their servers fetch it later. Null while it's being made.
     */
    public static function shareUrl(?Media $media): ?string
    {
        $size = $media ? static::madeSize($media, 'card') : null;

        return $size ? ProtectedImages::url($media->getPathRelativeToRoot($size), ProtectedImages::SHARE_LIFETIME) : null;
    }

    /**
     * The file on disk of the same image Image::url() would show (for server-side PDFs); null while it's being made.
     */
    public static function path(?Media $media, string $conversion = 'large'): ?string
    {
        if (! $media) {
            return null;
        }

        $size = static::madeSize($media, $conversion);
        if (! $size) {
            return null;
        }

        // With the watermark on (AKAR-51), the PDF gets it too.
        return $media->conversions_disk === ProtectedImages::DISK ? MediaSizes::fileToSend($media->getPathRelativeToRoot($size)) : $media->getPath($size);
    }

    /**
     * The asked size if it's made, else the large one, else null.
     */
    private static function madeSize(Media $media, string $conversion): ?string
    {
        foreach (array_unique([$conversion, 'large']) as $size) {
            if ($media->hasGeneratedConversion($size)) {
                return $size;
            }
        }

        return null;
    }
}
