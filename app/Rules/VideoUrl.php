<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A video link the website can play: an HTTPS link to YouTube or Vimeo.
 */
class VideoUrl implements ValidationRule
{
    public const HOSTS = ['www.youtube.com', 'youtube.com', 'm.youtube.com', 'youtu.be', 'vimeo.com', 'www.vimeo.com', 'player.vimeo.com'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $url = parse_url(trim((string) $value));

        if (($url['scheme'] ?? null) !== 'https' || ! in_array(strtolower($url['host'] ?? ''), self::HOSTS, true)) {
            $fail(__('admin.galleries.video_url_invalid'));
        }
    }
}
