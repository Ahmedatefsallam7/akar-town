<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Support\Website\FooterStats;
use App\Support\Website\PropertyCards;
use App\Support\Website\PropertyDetails;
use Illuminate\Contracts\View\View;
use Modules\Units\Models\Unit;

class PropertiesController extends Controller
{
    private const SIMILAR = 6;

    /**
     * The listing page: header with the number of available properties; the search itself is Livewire.
     */
    public function index(): View
    {
        return view('website.properties', [
            // The footer's cached count of published properties (the same number): no count query here.
            'available' => FooterStats::get()['listings'],
        ]);
    }

    /**
     * One published property, with up to six similar ones (same project or type first, then the latest).
     */
    public function show(string $slug): View
    {
        $unit = Unit::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();

        $similar = PropertyCards::eagerLoad(Unit::query())
            ->whereIn('id', $ids = static::similarIds($unit))
            ->get()
            ->sortBy(fn (Unit $similar) => array_search($similar->id, $ids, true))
            ->values();

        return view('website.property', [
            'property' => PropertyDetails::for($unit),
            'slug' => $unit->slug,
            'similar' => PropertyCards::from($similar),
        ]);
    }

    /**
     * Up to six other published units: the same project first, then the same type, then the newest (so the slider is
     * never empty). Three small queries on indexes, newest first by id, instead of sorting every unit by a computed
     * value (174 ms on 50,000 units).
     *
     * @return list<int>
     */
    private static function similarIds(Unit $unit): array
    {
        $ids = [];
        $more = fn (?string $column, mixed $value) => Unit::query()
            ->where('is_published', true)
            ->whereKeyNot([$unit->id, ...$ids])
            ->when($column, fn ($query) => $query->where($column, $value))
            ->latest('id')
            ->limit(self::SIMILAR - count($ids))
            ->pluck('id')
            ->all();

        foreach ([['project_id', $unit->project_id], ['property_type_id', $unit->property_type_id], [null, null]] as [$column, $value]) {
            if (count($ids) < self::SIMILAR && ($column === null || $value !== null)) {
                $ids = [...$ids, ...$more($column, $value)];
            }
        }

        return $ids;
    }
}
