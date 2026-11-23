<?php

namespace Modules\Galleries\Support;

use Illuminate\Database\Eloquent\Builder;
use Modules\Galleries\Models\Gallery;
use Modules\Lookups\Support\SearchText;
use Modules\Projects\Models\Project;

/**
 * The gallery pickers' choices (a property's gallery, AKAR-54, and a project's galleries): any gallery, the project's
 * own first (without a project, the galleries with none first), each labelled with its project. Searched on the
 * server by any part of the English or Arabic name, any case, Arabic letters written either way (ة/ه, أ/إ/آ/ا, ى/ي).
 */
class GalleryOptions
{
    /** How many galleries a picker lists at a time (search for the others). */
    public const LIMIT = 50;

    /**
     * @return array<int, string> id => label
     */
    public static function search(?int $projectId, ?string $search = null): array
    {
        $text = SearchText::normalize($search);

        // One query, once per request for the same choices (the label and the list read the same one).
        return once(fn () => static::query()
            ->when($text !== '', function (Builder $query) use ($text) {
                // "%" and "_" typed are letters, not wildcards ("!" escapes them, the same on every database).
                $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $text).'%';
                $query->where(fn (Builder $query) => $query
                    ->whereRaw(static::normalized('galleries.name_en')." like ? escape '!'", [$like])
                    ->orWhereRaw(static::normalized('galleries.name_ar')." like ? escape '!'", [$like]));
            })
            ->orderByRaw($projectId ? 'case when galleries.project_id = ? then 0 else 1 end' : 'case when galleries.project_id is null then 0 else 1 end', $projectId ? [$projectId] : [])
            ->orderBy('galleries.name_en')
            ->limit(self::LIMIT)
            ->get()
            ->mapWithKeys(fn (Gallery $gallery) => [$gallery->id => static::label($gallery)])
            ->all());
    }

    /**
     * The labels of chosen galleries, in their order: from the listed choices when they're there (no query), else read.
     *
     * @param  array<mixed>  $ids
     * @return array<int, string> id => label
     */
    public static function labels(array $ids, ?int $projectId = null): array
    {
        $ids = array_values(array_unique(array_map('intval', array_filter($ids, 'is_numeric'))));
        $labels = array_intersect_key(static::search($projectId), array_flip($ids));

        if ($missing = array_diff($ids, array_keys($labels))) {
            $labels += static::query()->whereIn('galleries.id', $missing)->get()
                ->mapWithKeys(fn (Gallery $gallery) => [$gallery->id => static::label($gallery)])->all();
        }

        return array_filter(array_replace(array_fill_keys($ids, null), $labels));
    }

    /**
     * "The Crown Tower Gallery — The Crown Tower" (or "— Standalone" for a gallery with no project).
     */
    public static function label(?Gallery $gallery): ?string
    {
        if (! $gallery) {
            return null;
        }

        $project = match (true) {
            ! $gallery->project_id => null,
            // Read with the project's title joined (query()): no extra query.
            array_key_exists('project_title_en', $gallery->getAttributes()) => (new Project)->forceFill(['title_en' => $gallery->project_title_en, 'title_ar' => $gallery->project_title_ar]),
            default => $gallery->project,
        };

        return $gallery->getLocalized('name').' — '.($project?->getLocalized('title') ?? __('admin.units.standalone'));
    }

    /**
     * Galleries with their project's title (joined); not the ones in the trash.
     */
    private static function query(): Builder
    {
        return Gallery::query()
            ->leftJoin('projects', 'projects.id', '=', 'galleries.project_id')
            ->select('galleries.id', 'galleries.name_en', 'galleries.name_ar', 'galleries.project_id', 'projects.title_en as project_title_en', 'projects.title_ar as project_title_ar');
    }

    /**
     * A name column as SearchText::normalize() writes text: lower case, one way for each of the Arabic letters written
     * two ways. In SQL (the same on MySQL and SQLite); galleries are a short list, so no index is needed for it.
     */
    private static function normalized(string $column): string
    {
        $sql = "lower({$column})";
        foreach (SearchText::LETTERS as $from => $to) {
            $sql = "replace({$sql}, '{$from}', '{$to}')";
        }

        return $sql;
    }
}
