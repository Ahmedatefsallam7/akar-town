<?php

namespace App\Support\Website;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Developers\Models\Developer;

/**
 * Website developer cards (the design's developers.html .dev-card): published developers, featured first then the
 * ones with most projects, with their photo (or initials), short text, published project count and phone.
 */
class DeveloperCards
{
    /**
     * @return Builder<Developer>
     */
    public static function query(): Builder
    {
        return Developer::query()
            ->where('is_published', true)
            ->with(['media' => fn ($query) => $query->where('collection_name', Developer::FEATURED_IMAGE)])
            ->withCount(['projects' => fn ($query) => $query->where('is_published', true)])
            ->orderByDesc('is_featured')
            ->orderByDesc('projects_count')
            ->orderBy('name_en');
    }

    /**
     * @param  Collection<int, Developer>  $developers
     * @return list<array{slug: string, name: string, initials: string, description: ?string, projects: int, image: ?string, phone: ?string, url: string}>
     */
    public static function from(Collection $developers): array
    {
        return $developers->map(fn (Developer $developer) => [
            'slug' => $developer->slug,
            'name' => $name = $developer->getLocalized('name'),
            'initials' => static::initials($developer->name_en ?: $name),
            'description' => $developer->getLocalized('short_description') ?: $developer->getLocalized('tagline'),
            'projects' => (int) $developer->projects_count,
            'image' => Image::url($developer->getFirstMedia(Developer::FEATURED_IMAGE), 'card'),
            'phone' => $developer->phone,
            'url' => route('developers.show', $developer->slug),
        ])->values()->all();
    }

    /**
     * The design's monogram: the first letters of the first three words (e.g. "Talaat Moustafa Group" → "TMG").
     */
    public static function initials(string $name): string
    {
        return collect(preg_split('/\s+/u', trim($name)))
            ->filter()
            ->take(3)
            ->map(fn (string $word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
    }
}
