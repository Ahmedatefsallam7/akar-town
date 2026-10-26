<?php

namespace Modules\UnitRequests\Actions;

use Illuminate\Support\Facades\DB;
use LogicException;
use Modules\Galleries\Models\Gallery;
use Modules\Lookups\Models\Lookup;
use Modules\UnitRequests\Models\UnitRequest;
use Modules\Units\Models\Unit;

/**
 * Turns a sell request into a property (as Dastan's ConvertUnitRequestToIUnitAction): its details, location, picked
 * values, featured image and photos (as the property's gallery), unpublished so staff can check it first. The request is then closed and points at the
 * new property. All or nothing, and only once per request.
 */
class ConvertUnitRequestToUnit
{
    /**
     * Values the request picks as a list but a property holds one of: property column => request lookup type.
     * The first picked one is used.
     */
    private const SINGLE = [
        'offering_type_id' => 'offeringtype',
        'property_type_id' => 'unittype',
        'finishing_type_id' => 'finishingtype',
        'furnishing_status_id' => 'furnishingstatus',
    ];

    /**
     * Used when the request picked none (as in Dastan): a submitted unit is a resale, and available.
     */
    private const FALLBACKS = [
        'offering_type_id' => ['offeringtype', 'resale'],
        'unit_status_id' => ['unitstatus', 'us-available'],
    ];

    public function __invoke(UnitRequest $request): Unit
    {
        return DB::transaction(function () use ($request) {
            // Locked, so two clicks (or two staff members) at once can't make two properties from one request.
            $request = UnitRequest::query()->with('lookups')->lockForUpdate()->findOrFail($request->getKey());

            if (! $request->canBeConverted()) {
                throw new LogicException('This request is already converted or cancelled.');
            }

            $unit = Unit::create([
                ...$request->only([
                    'name_en', 'name_ar', 'country_id', 'city_id', 'region_id', 'area_id', 'sub_area_id', 'address', 'latitude', 'longitude',
                    'price', 'area', 'bedrooms', 'bathrooms', 'down_payment', 'installment_years_from', 'installment_years_to',
                    'short_description_en', 'short_description_ar', 'description_en', 'description_ar',
                ]),
                ...$this->singleChoices($request),
                // A property needs an Arabic name and a request may have none: start with the English one (the
                // property's edit page opens next to correct it).
                'name_ar' => $request->name_ar ?: $request->name_en,
                'is_published' => false,
            ]);

            foreach (array_unique(Unit::MULTI_LOOKUPS) as $type) {
                if ($ids = $request->lookupIds($type)) {
                    $unit->lookupsOfType($type)->sync($ids);
                }
            }

            $request->getFirstMedia(UnitRequest::FEATURED_IMAGE)?->copy($unit, Unit::FEATURED_IMAGE);

            // The request's photos become the property's gallery.
            if (($photos = $request->getMedia(UnitRequest::PHOTOS))->isNotEmpty()) {
                $gallery = Gallery::create(['name_en' => $unit->name_en, 'name_ar' => $unit->name_ar]);
                $photos->each(fn ($photo) => $photo->copy($gallery, Gallery::PHOTOS));
                $unit->forceFill(['gallery_id' => $gallery->getKey()])->save();
            }

            $request->forceFill(['unit_id' => $unit->getKey(), 'status' => UnitRequest::CLOSED])->save();

            return $unit;
        });
    }

    /**
     * @return array<string, ?int>
     */
    private function singleChoices(UnitRequest $request): array
    {
        $choices = [];

        foreach (self::SINGLE as $column => $type) {
            $choices[$column] = $request->lookups
                ->filter(fn (Lookup $lookup) => $lookup->pivot->type === $type)
                ->sortBy('order')
                ->first()?->getKey();
        }

        foreach (self::FALLBACKS as $column => [$type, $slug]) {
            $choices[$column] ??= Lookup::query()
                ->where('slug', $slug)
                ->whereHas('parent', fn ($query) => $query->where('slug', $type))
                ->value('id');
        }

        return $choices;
    }
}
