<?php

namespace App\Support\Website;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Modules\Lookups\Models\Lookup;
use Modules\Lookups\Support\LocationTree;
use Modules\Projects\Models\Project;
use Modules\Units\Models\Unit;

/**
 * Everything the project details page (the design's project-details.html) shows about one published project, read
 * in a fixed number of queries. The unit types are the project's published units grouped by type. Values the admin
 * doesn't have (land area, built-up %, phases, distances, the sales advisor) aren't shown.
 */
class ProjectDetails
{
    /** Feature tags shown for a unit type. */
    private const FEATURES = 5;

    /**
     * @return array<string, mixed>
     */
    public static function for(Project $project): array
    {
        $project->loadMissing([
            'media' => fn ($query) => $query->whereIn('collection_name', [Project::FEATURED_IMAGE, Project::GALLERY])->orderBy('order_column'),
            // Only a published developer is named.
            'developer' => fn ($query) => $query->where('is_published', true)->select('id', 'name_en', 'name_ar'),
            'agent' => Agent::scope(),
            'lookups' => fn ($query) => $query->wherePivotIn('type', ['projectstatus', 'propertyusage', 'amenities', 'facility'])->select('lookups.id', 'name_en', 'name_ar')->orderBy('lookups.order'),
        ]);

        $listOf = fn (string $type) => $project->lookups->where('pivot.type', $type)->map(fn (Lookup $lookup) => $lookup->getLocalized('name'))->values()->all();
        $photos = collect([$project->getFirstMedia(Project::FEATURED_IMAGE)])->merge($project->getMedia(Project::GALLERY))->filter()->values();
        $chain = [$project->country_id, $project->city_id, $project->region_id, $project->area_id, $project->sub_area_id];
        $path = array_filter(explode(' › ', LocationTree::path($chain)));
        $delivery = $project->delivery_date ? Carbon::parse($project->delivery_date) : null;
        $types = static::unitTypes($project);
        $developer = $project->developer?->getLocalized('name');
        $years = array_filter([$project->installment_years_from, $project->installment_years_to]);

        return [
            'id' => $project->id,
            'title' => $project->getLocalized('title'),
            'short' => $project->getLocalized('short_description'),
            'description' => (string) str((string) $project->getLocalized('description'))->sanitizeHtml(),
            'status' => $listOf('projectstatus')[0] ?? null,
            'usage' => implode(' · ', $listOf('propertyusage')) ?: null,
            'developer' => $developer,
            'initials' => $developer ? static::initials($developer) : null,
            'place' => LocationTree::place($chain),
            'city' => end($path) ?: null,
            'address' => collect([$project->getLocalized('address'), implode(app()->getLocale() === 'ar' ? '، ' : ', ', array_reverse($path))])->filter()->implode(' — '),
            'latitude' => $project->latitude,
            'longitude' => $project->longitude,
            'units' => $types['total'],
            'delivery' => $delivery ? ['Q'.$delivery->quarter, (string) $delivery->year] : null,
            'priceShort' => $project->price_from ? Number::abbreviate($project->price_from, maxPrecision: 1) : null,
            'price' => $project->price_from ? number_format($project->price_from) : null,
            'sizes' => static::range($project->area_from, $project->area_to),
            'downPayment' => $project->down_payment !== null ? rtrim(rtrim(number_format($project->down_payment, 2), '0'), '.') : null,
            'installmentYears' => $years ? max($years) : null,
            'agent' => Agent::from($project->agent),
            'types' => $types['list'],
            'amenities' => $listOf('amenities'),
            'nearby' => $listOf('facility'),
            'image' => Image::orPlaceholder($photos->first()),
            // Phones get the card size (a full-screen photo there is under 900px wide).
            'imageSmall' => Image::orPlaceholder($photos->first(), 'card'),
            'thumb' => Image::orPlaceholder($photos->first(), 'thumb'),
            'images' => Image::photos($photos),
            'thumbs' => Image::photos($photos, 'thumb'),
        ];
    }

    /**
     * The project's published units grouped by type (one aggregate query, then the type names and one sample unit
     * per type for its photo and features), in the admin's Lookups order.
     *
     * @return array{total: int, list: list<array<string, mixed>>}
     */
    private static function unitTypes(Project $project): array
    {
        $groups = Unit::query()
            ->where('project_id', $project->id)
            ->where('is_published', true)
            ->groupBy('property_type_id')
            ->selectRaw('property_type_id, count(*) as units, min(area) as area_min, max(area) as area_max, min(price) as price_min,
                min(bedrooms) as beds_min, max(bedrooms) as beds_max, min(bathrooms) as baths_min, max(bathrooms) as baths_max,
                min(floor) as floor_min, max(floor) as floor_max, max(id) as sample_id')
            ->toBase()
            ->get();

        $total = (int) $groups->sum('units');
        $typed = $groups->whereNotNull('property_type_id');
        if ($typed->isEmpty()) {
            return ['total' => $total, 'list' => []];
        }

        $names = Lookup::query()->whereIn('id', $typed->pluck('property_type_id'))->orderBy('order')->get(['id', 'name_en', 'name_ar']);
        /** @var Collection<int, Unit> $samples */
        $samples = Unit::query()
            ->whereIn('id', $typed->pluck('sample_id'))
            ->with([
                'media' => fn ($query) => $query->where('collection_name', Unit::FEATURED_IMAGE),
                'lookups' => fn ($query) => $query->wherePivot('type', 'amenities')->select('lookups.id', 'name_en', 'name_ar')->orderBy('lookups.order'),
            ])
            ->get(['id'])
            ->keyBy('id');
        $groups = $typed->keyBy('property_type_id');

        $list = $names->map(function (Lookup $type) use ($groups, $samples) {
            $group = $groups->get($type->id);
            $sample = $samples->get($group->sample_id);

            return [
                'id' => $type->id,
                'name' => $type->getLocalized('name'),
                'units' => (int) $group->units,
                'area' => static::range($group->area_min, $group->area_max),
                'floors' => static::range($group->floor_min, $group->floor_max),
                'beds' => static::range($group->beds_min, $group->beds_max),
                'baths' => static::range($group->baths_min, $group->baths_max),
                'price' => $group->price_min ? number_format($group->price_min) : null,
                'image' => Image::orPlaceholder($sample?->getFirstMedia(Unit::FEATURED_IMAGE), 'card'),
                'features' => $sample ? $sample->lookups->take(self::FEATURES)->map(fn (Lookup $lookup) => $lookup->getLocalized('name'))->values()->all() : [],
            ];
        })->values()->all();

        return ['total' => $total, 'list' => $list];
    }

    /**
     * "65–85", or "65" when both ends match; null when unknown.
     */
    private static function range(int|string|null $from, int|string|null $to): ?string
    {
        $values = array_values(array_unique(array_filter([$from, $to], fn ($value) => $value !== null)));

        return $values ? implode('–', array_map(fn ($value) => number_format((int) $value), $values)) : null;
    }

    /**
     * The developer's logo letters, e.g. "Emaar Properties" → "EP".
     */
    private static function initials(string $name): string
    {
        return collect(preg_split('/\s+/u', trim($name)))->filter()->take(2)->map(fn (string $word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
    }
}
