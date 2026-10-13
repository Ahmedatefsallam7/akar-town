<?php

namespace Modules\Lookups\Filament\Resources\LookupResource\Pages;

use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Modules\Lookups\Filament\Resources\LookupResource;
use Modules\Lookups\Models\Lookup;

/**
 * Lookup types as cards (with their number of values). The name filter runs in the browser; Super Search looks for
 * values across every type on the server.
 */
class ListLookupTypes extends Page
{
    protected static string $resource = LookupResource::class;

    protected string $view = 'lookups::filament.pages.list-lookup-types';

    /** Super Search term (values in every type). */
    public string $superSearch = '';

    public function getTitle(): string
    {
        return __('admin.lookups.title');
    }

    /** The title already says where you are. */
    public function getBreadcrumbs(): array
    {
        return [];
    }

    /**
     * One query: every type with its number of direct values. User Roles isn't one: roles have their own page.
     *
     * @return Collection<int, array{name: string, search: string, count: int, url: string}>
     */
    public function getTypes(): Collection
    {
        return Lookup::withoutRoles()
            ->whereNull('parent_id')
            ->withCount('children')
            ->orderBy('order')
            ->orderBy(getLocalizedFieldName('name'))
            ->get(['id', 'slug', 'name_en', 'name_ar', 'order'])
            ->map(fn (Lookup $type) => [
                'name' => $type->getLocalized('name'),
                'search' => mb_strtolower($type->name_en.' '.$type->name_ar),
                'count' => $type->children_count,
                'url' => LookupResource::getUrl('values', ['record' => $type]),
            ])
            ->values();
    }

    /**
     * Values (not types) whose English or Arabic name contains the term, from every type; at most 30.
     *
     * @return Collection<int, array{name: string, in: string, url: string}>
     */
    public function getSuperSearchResults(): Collection
    {
        $term = trim($this->superSearch);

        if (mb_strlen($term) < 2) {
            return collect();
        }

        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';

        return Lookup::withoutRoles()
            ->whereNotNull('parent_id')
            ->where(fn ($query) => $query->where('name_en', 'like', $like)->orWhere('name_ar', 'like', $like))
            ->with('parent:id,name_en,name_ar')
            ->orderBy(getLocalizedFieldName('name'))
            ->limit(30)
            ->get(['id', 'parent_id', 'name_en', 'name_ar'])
            ->map(fn (Lookup $value) => [
                'name' => $value->getLocalized('name'),
                'in' => $value->parent?->getLocalized('name'),
                'url' => LookupResource::getUrl('values', ['record' => $value->parent_id]),
            ]);
    }
}
