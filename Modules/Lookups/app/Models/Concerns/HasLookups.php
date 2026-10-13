<?php

namespace Modules\Lookups\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Lookups\Models\Lookup;

/**
 * Lookup values picked on a record (amenities, views, finishing types…), stored in "lookupables" with the lookup
 * type's slug. Used with LookupFields::checkboxList().
 */
trait HasLookups
{
    public static function bootHasLookups(): void
    {
        // The links go with the record; a record in the trash keeps them (it can come back).
        static::deleted(function (self $record) {
            if (! in_array(SoftDeletes::class, class_uses_recursive($record), true) || $record->isForceDeleting()) {
                $record->lookups()->detach();
            }
        });
    }

    public function lookups(): MorphToMany
    {
        return $this->morphToMany(Lookup::class, 'lookupable')->withPivot('type');
    }

    /**
     * The values of one lookup type, e.g. "amenities". Saving through it only touches that type.
     */
    public function lookupsOfType(string $type): MorphToMany
    {
        return $this->lookups()->withPivotValue('type', $type);
    }

    /**
     * Picked value ids of one type, from the loaded "lookups" relation (one query for every list on a form).
     *
     * @return array<int>
     */
    public function lookupIds(string $type): array
    {
        return $this->lookups
            ->filter(fn (Lookup $lookup) => $lookup->pivot->type === $type)
            ->pluck('id')
            ->values()
            ->all();
    }
}
