<?php

namespace App\Support\Website;

use Modules\Sliders\Models\Slider;

/**
 * The home page's blocks under the search, from the admin's active "home body" sliders, in their order: what each
 * one lists (projects / properties / locations / CTA ribbon) and its title.
 */
class HomeSections
{
    /**
     * @return list<array{type: string, title: string}>
     */
    public static function get(): array
    {
        return Slider::query()
            ->where('placement', Slider::HOME_BODY)
            ->where('is_active', true)
            ->orderBy('order')
            ->get(['content_type', 'title_en', 'title_ar'])
            ->map(fn (Slider $slider) => ['type' => $slider->content_type, 'title' => $slider->getLocalized('title')])
            ->all();
    }

    /**
     * A title with its last word in the design's accent (e.g. "Featured <em>Properties</em>"), escaped.
     */
    public static function accent(string $title): string
    {
        $words = preg_split('/\s+/u', trim($title));
        $last = array_pop($words);

        return trim(e(implode(' ', $words)).' <em>'.e($last).'</em>');
    }
}
