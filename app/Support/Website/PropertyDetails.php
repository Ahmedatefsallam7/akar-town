<?php

namespace App\Support\Website;

use Illuminate\Support\Carbon;
use Modules\Galleries\Models\Gallery;
use Modules\Lookups\Models\Lookup;
use Modules\Lookups\Support\LocationTree;
use Modules\Units\Models\Unit;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Everything the property details page (the design's property-details.html) shows about one published unit, read
 * in a fixed number of queries. Values the admin doesn't have (agent, reference, service charge…) aren't shown.
 */
class PropertyDetails
{
    /** Description paragraphs shown before "Read More". */
    private const VISIBLE_PARAGRAPHS = 2;

    /**
     * @return array<string, mixed>
     */
    public static function for(Unit $unit): array
    {
        $unit->loadMissing([
            'media',
            'gallery.media' => fn ($query) => $query->where('collection_name', Gallery::PHOTOS)->orderBy('order_column'),
            'project.developer',
            'lookups:id,name_en,name_ar',
        ]);

        $names = Lookup::query()
            ->whereIn('id', array_filter([$unit->property_type_id, $unit->offering_type_id, $unit->finishing_type_id, $unit->furnishing_status_id, $unit->unit_status_id, $unit->ownership_type_id]))
            ->get(['id', 'slug', 'name_en', 'name_ar'])
            ->keyBy('id');
        $name = fn (?int $id) => $id ? $names->get($id)?->getLocalized('name') : null;
        $listOf = fn (string $type) => $unit->lookups->where('pivot.type', $type)->map(fn (Lookup $lookup) => $lookup->getLocalized('name'))->values()->all();

        $photos = collect([$unit->getFirstMedia(Unit::FEATURED_IMAGE)])->merge($unit->gallery?->media ?? [])->filter()->values();
        $chain = [$unit->country_id, $unit->city_id, $unit->region_id, $unit->area_id, $unit->sub_area_id];
        $place = collect([$unit->address, implode(', ', array_reverse(explode(' › ', LocationTree::path($chain))))])->filter()->implode(', ');
        $delivery = $unit->delivery_date ? Carbon::parse($unit->delivery_date) : null;
        [$intro, $more] = static::paragraphs((string) $unit->getLocalized('description'));

        return [
            'id' => $unit->id,
            'title' => $unit->getLocalized('name'),
            'short' => $unit->getLocalized('short_description'),
            'intro' => $intro,
            'more' => $more,
            'year' => $delivery?->year,
            'featured' => $unit->is_featured,
            'offering' => $name($unit->offering_type_id),
            'finishing' => $name($unit->finishing_type_id),
            'developer' => $unit->project?->developer?->getLocalized('name'),
            'project' => $unit->project?->getLocalized('title'),
            'place' => $place,
            'area' => $unit->area,
            'beds' => $unit->bedrooms,
            'baths' => $unit->bathrooms,
            'parking' => $unit->parking_spaces,
            'floors' => $unit->building_floors !== null ? __('G+:count', ['count' => $unit->building_floors]) : null,
            'price' => $unit->price ? number_format((float) $unit->price) : null,
            'perMeter' => $unit->price && $unit->area ? number_format($unit->price / $unit->area) : null,
            'updated' => $unit->updated_at?->translatedFormat('M Y'),
            'views' => $listOf('views'),
            'details' => array_filter([
                __('Type') => $name($unit->property_type_id),
                __('Finishing') => $name($unit->finishing_type_id),
                __('Furnishing') => $name($unit->furnishing_status_id),
                __('Delivery') => $delivery ? 'Q'.$delivery->quarter.' '.$delivery->year : null,
                __('Developer') => $unit->project?->developer?->getLocalized('name'),
                __('Compound') => $unit->project?->getLocalized('title'),
                __('Ownership') => $name($unit->ownership_type_id),
                __('Building Year') => $unit->building_year,
                __('Status') => $name($unit->unit_status_id),
                __('Floor') => $unit->floor,
                __('Views') => implode(' & ', $listOf('views')) ?: null,
            ], fn ($value) => $value !== null && $value !== ''),
            'amenities' => $listOf('amenities'),
            'facilities' => $listOf('facility'),
            'latitude' => $unit->latitude ?? $unit->project?->latitude,
            'longitude' => $unit->longitude ?? $unit->project?->longitude,
            // The unit's own plans only: a plan it doesn't have isn't shown.
            'floorPlan' => Image::url($unit->getFirstMedia(Unit::FLOOR_PLAN)),
            'masterPlan' => Image::url($unit->getFirstMedia(Unit::MASTER_PLAN)),
            'images' => $photos->map(fn (Media $media) => Image::url($media))->all(),
            'thumbs' => $photos->map(fn (Media $media) => Image::url($media, 'thumb'))->all(),
            'payment' => static::paymentPlan($unit, $delivery),
        ];
    }

    /**
     * The design's payment steps, from the unit's down payment, installment years and delivery date.
     *
     * @return list<array{value: string, name: string, note: string}>
     */
    private static function paymentPlan(Unit $unit, ?Carbon $delivery): array
    {
        $down = $unit->down_payment !== null ? (float) $unit->down_payment : null;
        $years = array_filter([$unit->installment_years_from, $unit->installment_years_to]);

        return array_values(array_filter([
            $down !== null ? ['value' => rtrim(rtrim(number_format($down, 2), '0'), '.').'%', 'name' => __('Down Payment'), 'note' => __('Upon contract signing')] : null,
            $years ? [
                'value' => $down !== null ? rtrim(rtrim(number_format(100 - $down, 2), '0'), '.').'%' : '—',
                'name' => __('Installments'),
                'note' => count(array_unique($years)) > 1 ? __('Over :from – :to years', ['from' => min($years), 'to' => max($years)]) : trans_choice('Over :count year|Over :count years', reset($years), ['count' => reset($years)]),
            ] : null,
            $delivery ? ['value' => 'Q'.$delivery->quarter, 'name' => __('Handover'), 'note' => __('Upon key delivery, :date', ['date' => 'Q'.$delivery->quarter.' '.$delivery->year])] : null,
        ]));
    }

    /**
     * The description's first paragraphs and the rest (shown with "Read More"), sanitized.
     *
     * @return array{0: string, 1: string}
     */
    private static function paragraphs(string $html): array
    {
        $html = (string) str($html)->sanitizeHtml();
        $parts = preg_split('#(?<=</p>)#i', trim($html), -1, PREG_SPLIT_NO_EMPTY);

        if (count($parts) <= self::VISIBLE_PARAGRAPHS) {
            return [$html, ''];
        }

        return [implode('', array_slice($parts, 0, self::VISIBLE_PARAGRAPHS)), implode('', array_slice($parts, self::VISIBLE_PARAGRAPHS))];
    }
}
