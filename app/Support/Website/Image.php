<?php

namespace App\Support\Website;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Image URLs for the website: a WebP size when it has been made, otherwise the original upload.
 */
class Image
{
    public static function url(?Media $media, string $conversion = 'large'): ?string
    {
        if (! $media) {
            return null;
        }

        return $media->hasGeneratedConversion($conversion) ? $media->getUrl($conversion) : $media->getUrl();
    }
}
