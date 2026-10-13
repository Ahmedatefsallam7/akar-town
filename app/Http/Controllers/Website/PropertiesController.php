<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Support\Website\PropertyCards;
use App\Support\Website\PropertyDetails;
use Illuminate\Contracts\View\View;
use Modules\Units\Models\Unit;

class PropertiesController extends Controller
{
    /**
     * The listing page: header with the number of available properties; the search itself is Livewire.
     */
    public function index(): View
    {
        return view('website.properties', [
            'available' => Unit::query()->where('is_published', true)->count(),
        ]);
    }

    /**
     * One published property, with up to six similar ones (same project or type first, then the latest).
     */
    public function show(string $slug): View
    {
        $unit = Unit::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();

        $similar = PropertyCards::eagerLoad(Unit::query())
            ->where('is_published', true)
            ->whereKeyNot($unit->id)
            // Same project / type first, then the latest, so the slider is never empty.
            ->orderByRaw('(CASE WHEN project_id = ? THEN 1 ELSE 0 END) + (CASE WHEN property_type_id = ? THEN 1 ELSE 0 END) DESC', [$unit->project_id, $unit->property_type_id])
            ->latest()
            ->limit(6)
            ->get();

        return view('website.property', [
            'property' => PropertyDetails::for($unit),
            'slug' => $unit->slug,
            'similar' => PropertyCards::from($similar),
        ]);
    }
}
