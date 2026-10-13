<?php

namespace App\Support\Website;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Galleries\Models\Gallery;
use Modules\Lookups\Models\Lookup;
use Modules\Lookups\Support\LocationTree;
use Modules\Units\Models\Unit;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Units shaped for the website's property card (listings, similar properties): with their photos and lookup names
 * loaded in a fixed number of queries, whatever the number of cards.
 */
class PropertyCards
{
    /** Photos per card's gallery: its featured image, then its gallery's photos. */
    private const PHOTOS = 8;

    /**
     * The relations a card needs; add to a units query before getting the page.
     */
    public static function eagerLoad(Builder $query): Builder
    {
        return $query->with([
            'media' => fn ($query) => $query->where('collection_name', Unit::FEATURED_IMAGE),
            'gallery.media' => fn ($query) => $query->where('collection_name', Gallery::PHOTOS)->orderBy('order_column'),
        ]);
    }

    /**
     * @param  Collection<int, Unit>  $units
     * @return list<array<string, mixed>>
     */
    public static function from(Collection $units): array
    {
        $names = Lookup::query()
            ->whereIn('id', $units->flatMap(fn (Unit $unit) => [$unit->property_type_id, $unit->offering_type_id, $unit->unit_status_id])->filter()->unique())
            ->get(['id', 'slug', 'name_en', 'name_ar'])
            ->keyBy('id');

        return $units->map(function (Unit $unit) use ($names) {
            $photos = collect([$unit->getFirstMedia(Unit::FEATURED_IMAGE)])
                ->merge($unit->gallery?->media ?? [])
                ->filter()
                ->take(self::PHOTOS);
            $image = Image::url($photos->first());

            return [
                'id' => $unit->id,
                'title' => $unit->getLocalized('name'),
                'url' => route('properties.show', $unit->slug),
                'type' => $names->get($unit->property_type_id)?->getLocalized('name'),
                'status' => static::status($names->get($unit->offering_type_id), $names->get($unit->unit_status_id)),
                'featured' => $unit->is_featured,
                'price' => $unit->price ? number_format((float) $unit->price) : null,
                'date' => $unit->created_at?->diffForHumans(),
                'location' => LocationTree::place([$unit->country_id, $unit->city_id, $unit->region_id, $unit->area_id, $unit->sub_area_id]),
                'beds' => $unit->bedrooms,
                'baths' => $unit->bathrooms,
                'area' => $unit->area,
                'floor' => $unit->floor,
                'image' => $image,
                'images' => $photos->map(fn (Media $media) => Image::url($media))->values()->all(),
            ];
        })->values()->all();
    }

    /**
     * The design's status badge: off-plan (red), for rent (dark), for sale (green).
     *
     * @return array{label: string, style: string}|null
     */
    private static function status(?Lookup $offering, ?Lookup $status): ?array
    {
        return match (true) {
            $status?->slug === PropertySearch::OFF_PLAN => ['label' => __('Off Plan'), 'style' => 'red'],
            in_array($offering?->slug, PropertySearch::RENT, true) => ['label' => __('For Rent'), 'style' => 'ink'],
            in_array($offering?->slug, PropertySearch::SALE, true) => ['label' => __('For Sale'), 'style' => 'green'],
            default => null,
        };
    }
}
