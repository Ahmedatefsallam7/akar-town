<?php

namespace Modules\Lookups\Support;

/**
 * Text as searches compare it (AKAR-47): lower case, no Arabic diacritics or tatweel, and the Arabic letters people
 * write either way made one (أ إ آ → ا, ى → ي, ة → ه). "Madinaty", "MADINATY", "مدينتى" and "مدينتي" then match.
 * The website's filter lists do the same in the browser (filter-select.blade.php).
 */
class SearchText
{
    private const LETTERS = ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا', 'ى' => 'ي', 'ة' => 'ه'];

    public static function normalize(?string $text): string
    {
        $text = mb_strtolower(trim((string) $text));
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $text) ?? $text; // diacritics, tatweel

        return strtr($text, self::LETTERS);
    }

    /**
     * Whether the name holds the text anywhere (a partial match), both normalized.
     */
    public static function contains(?string $name, string $normalizedText): bool
    {
        return $normalizedText !== '' && str_contains(static::normalize($name), $normalizedText);
    }
}
