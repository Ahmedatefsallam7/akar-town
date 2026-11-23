<?php

namespace App\Support\Images;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * The website's images are protected (AKAR-51): they live on the private "media" disk (never under /storage), and the
 * pages show them through short-lived signed links (route "images.show", App\Http\Controllers\ProtectedImageController)
 * that stop working once copied or opened in a new tab. The website only gets the sizes made for it (watermarked
 * photos, logos, small thumbnails) and the site's own images, never an uploaded original; the admin sees everything.
 */
class ProtectedImages
{
    /** The private disk holding every uploaded image (media library and settings). */
    public const DISK = 'media';

    /** How long a link works, at least (seconds). */
    public const LIFETIME = 900;

    /** A share preview (social media, chat apps) is fetched later, by their servers: its link works for a week. */
    public const SHARE_LIFETIME = 604800;

    /**
     * An admin's link (any file, originals too) works two hours: admin pages stay open a long time. It is never renewed
     * once it runs out, so a copied one stops working.
     */
    public const ADMIN_LIFETIME = 7200;

    /**
     * Folders of the site's own images (settings, team photos) a visitor may get. Media library files are allowed by
     * their size instead (WEBSITE_SIZES).
     */
    private const WEBSITE_FOLDERS = ['settings/images/', 'settings/icons/', 'avatars/'];

    /**
     * The media library sizes a visitor may get: "large" and "card" (photos and plans, with the watermark when it's
     * on), "whole" (logos), "thumb" (160 px) and "pano" (360° images for the viewer). Never the uploaded original.
     */
    private const WEBSITE_SIZES = ['large', 'card', 'whole', 'thumb', 'pano'];

    /**
     * The signed link of a file on the media disk, e.g. "12/conversions/villa-large.webp".
     *
     * The link's end time is rounded to the next quarter hour (plus one), so a page and the next ones share the same
     * links in that time and the browser shows the images from its cache; the file's version is in it too.
     */
    public static function url(string $path, ?int $lifetime = null, ?bool $forAdmin = null): string
    {
        // An admin's link says so inside its signature ("a"): the image route then needs no session (performance
        // review: 2 queries per image), and nobody can add it to another link.
        $forAdmin ??= auth('web')->check();
        $lifetime = $forAdmin ? max($lifetime ?? 0, self::ADMIN_LIFETIME) : ($lifetime ?? self::LIFETIME);
        $expires = (int) (ceil(now()->getTimestamp() / self::LIFETIME) * self::LIFETIME) + $lifetime;

        $path = ltrim($path, '/');
        // The file's version (when it last changed, and whether it has the watermark): a file made again or the
        // watermark switched on / off gets a new link at once, instead of the browser showing the old one it keeps.
        // A path that leaves the disk ("../") has no version; the image route refuses it.
        $version = rescue(fn () => @filemtime(Storage::disk(self::DISK)->path($path)) ?: 0, 0, report: false)
            .(MediaSizes::watermarkIsOn() && MediaSizes::takesWatermark($path) ? 'w' : '');

        // A date, not a number: a number would be read as seconds from now.
        return URL::temporarySignedRoute('images.show', Carbon::createFromTimestamp($expires), ['path' => $path, 'v' => $version, ...($forAdmin ? ['a' => 1] : [])], absolute: false);
    }

    /**
     * Whether a visitor (not an admin) may get this file: a website size of a media library file, or a site image.
     */
    public static function isForWebsite(string $path): bool
    {
        if (preg_match('#^\d+/conversions/[^/]+-('.implode('|', self::WEBSITE_SIZES).')\.[a-z0-9]+$#i', $path)) {
            return true;
        }

        foreach (self::WEBSITE_FOLDERS as $folder) {
            if (str_starts_with($path, $folder) && ! str_contains(substr($path, strlen($folder)), '/')) {
                return true;
            }
        }

        return false;
    }
}
