<?php

namespace App\Support\Website;

use Modules\Galleries\Models\Gallery;
use Modules\Lookups\Models\Lookup;
use Modules\Lookups\Support\LocationTree;
use Modules\Units\Models\Unit;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The home page's featured properties (published and featured units, in the admin's order, then newest first),
 * shaped for the slider card.
 */
class FeaturedUnits
{
    private const LIMIT = 8;

    /** Photos per property: its featured image, then its gallery's photos. */
    private const PHOTOS = 5;

    /**
     * @return list<array{id: int, title: string, description: ?string, location: string, beds: ?int, baths: ?int, area: ?string, price: ?string, badges: list<array{label: string, style: string}>, images: list<string>, thumb: ?string, url: string}>
     */
    public static function get(): array
    {
        $units = Unit::query()
            ->where('is_published', true)
            ->where('is_featured', true)
            ->with([
                'media' => fn ($query) => $query->where('collection_name', Unit::FEATURED_IMAGE),
                'gallery.media' => fn ($query) => $query->where('collection_name', Gallery::PHOTOS)->orderBy('order_column'),
            ])
            ->orderByRaw('`order` is null')
            ->orderBy('order')
            ->latest('id')
            ->limit(self::LIMIT)
            ->get();

        $names = Lookup::query()
            ->whereIn('id', $units->pluck('offering_type_id')->merge($units->pluck('unit_status_id'))->filter()->unique())
            ->get(['id', 'name_en', 'name_ar'])
            ->keyBy('id');

        return $units->map(function (Unit $unit) use ($names) {
            $photos = collect([$unit->getFirstMedia(Unit::FEATURED_IMAGE)])
                ->merge($unit->gallery?->media ?? [])
                ->filter()
                ->take(self::PHOTOS);

            return [
                'id' => $unit->id,
                'title' => $unit->getLocalized('name'),
                'description' => $unit->getLocalized('short_description'),
                'location' => LocationTree::place([$unit->country_id, $unit->city_id, $unit->region_id, $unit->area_id, $unit->sub_area_id]),
                'beds' => $unit->bedrooms,
                'baths' => $unit->bathrooms,
                'area' => $unit->area !== null ? rtrim(rtrim(number_format((float) $unit->area, 2, '.', ''), '0'), '.') : null,
                'price' => $unit->price ? __('EGP :price', ['price' => number_format((float) $unit->price)]) : null,
                'badges' => array_values(array_filter([
                    ['label' => __('Featured'), 'style' => 'gold'],
                    ($offering = $names->get($unit->offering_type_id)) ? ['label' => $offering->getLocalized('name'), 'style' => 'red'] : null,
                    ($status = $names->get($unit->unit_status_id)) ? ['label' => $status->getLocalized('name'), 'style' => 'outline'] : null,
                ])),
                'images' => $photos->map(fn (Media $media) => Image::url($media))->values()->all(),
                'thumb' => Image::url($photos->first(), 'thumb'),
                'url' => route('properties'),
            ];
        })->all();
    }
}
