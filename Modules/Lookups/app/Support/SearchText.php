<?php

namespace Modules\Lookups\Support;

/**
 * Text as searches compare it (AKAR-47/48): lower case, no Arabic diacritics or tatweel, single spaces, and the Arabic
 * letters people write either way made one (أ إ آ → ا, ى → ي, ة → ه). "Madinaty", "MADINATY", "مدينتى" and
 * "مدينتي" then match. The website's filter lists do the same in the browser (filter-select.blade.php); records keep
 * their names this way in a "search_name" column (App\Models\Concerns\HasSearchName).
 */
class SearchText
{
    private const LETTERS = ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا', 'ى' => 'ي', 'ة' => 'ه'];

    public static function normalize(?string $text): string
    {
        $text = mb_strtolower(trim((string) $text));
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $text) ?? $text; // diacritics, tatweel
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return strtr($text, self::LETTERS);
    }

    /**
     * Whether the name holds the text anywhere (a partial match), both normalized.
     */
    public static function contains(?string $name, string $normalizedText): bool
    {
        return $normalizedText !== '' && str_contains(static::normalize($name), $normalizedText);
    }

    /**
     * Whether the name holds the text with a small typo (AKAR-48): one letter off for 4–7 letters, two from 8. The
     * text is compared with the start of each run of the name's words, so a word still being typed counts too
     * ("madinsty" ≈ "madinaty", "palm hils" ≈ "palm hills"). Both normalized; under 4 letters, never.
     */
    public static function nearly(?string $name, string $normalizedText): bool
    {
        $length = mb_strlen($normalizedText);
        if ($length < 4) {
            return false;
        }
        $allowed = $length >= 8 ? 2 : 1;
        $words = preg_split('/\s+/u', static::normalize($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $span = substr_count($normalizedText, ' ') + 1;

        foreach (array_keys($words) as $start) {
            $run = implode(' ', array_slice($words, $start, $span));
            // The same length as the text, and one letter either way (a letter missed or added).
            foreach ([$length, $length - 1, $length + 1] as $cut) {
                if (static::distance(mb_substr($run, 0, $cut), $normalizedText) <= $allowed) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Letters to change, add or drop to turn one text into the other (Levenshtein, letter by letter, Arabic too).
     * PHP's own levenshtein() counts bytes, so each letter is first given one byte of its own: the same result, and
     * fast enough for every name of a big list (performance review: the typo search over 2,300 names ~500 ms → a few).
     */
    public static function distance(string $a, string $b): int
    {
        if (! preg_match('/[^\x00-\x7F]/', $a.$b)) {
            return levenshtein($a, $b);
        }

        $letters = [];
        $bytes = function (string $text) use (&$letters): string {
            $out = '';
            foreach (mb_str_split($text) as $letter) {
                $out .= $letters[$letter] ??= chr(count($letters));
            }

            return $out;
        };
        [$x, $y] = [$bytes($a), $bytes($b)];
        if (count($letters) <= 256) {
            return levenshtein($x, $y);
        }

        // More than 256 different letters (a very long text): letter by letter.
        $a = mb_str_split($a);
        $b = mb_str_split($b);
        $previous = range(0, count($b));

        foreach ($a as $i => $letter) {
            $current = [$i + 1];
            foreach ($b as $j => $other) {
                $current[] = min($previous[$j + 1] + 1, $current[$j] + 1, $previous[$j] + ($letter === $other ? 0 : 1));
            }
            $previous = $current;
        }

        return $previous[count($b)];
    }
}
