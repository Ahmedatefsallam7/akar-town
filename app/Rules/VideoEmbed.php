<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Video embed code shown as-is on the website, so it must be exactly one <iframe> from a known video player over
 * HTTPS, with no scripts or event handlers (keeps a pasted snippet from running code on the website).
 */
class VideoEmbed implements ValidationRule
{
    /**
     * Players that may be embedded.
     */
    public const HOSTS = ['www.youtube.com', 'youtube.com', 'www.youtube-nocookie.com', 'player.vimeo.com'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $code = trim((string) $value);

        $isOneIframe = preg_match('#^<iframe\b[^<>]*>\s*</iframe>$#i', $code) === 1;
        $hasCode = preg_match('#<script|\son[a-z]+\s*=|javascript:#i', $code) === 1;
        preg_match('#\ssrc\s*=\s*(["\'])(.*?)\1#i', $code, $src);
        $url = parse_url($src[2] ?? '');

        if (! $isOneIframe || $hasCode || ($url['scheme'] ?? null) !== 'https' || ! in_array($url['host'] ?? null, self::HOSTS, true)) {
            $fail(__('admin.projects.video_embed_invalid'));
        }
    }
}
