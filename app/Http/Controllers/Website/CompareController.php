<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Support\Website\CompareUnits;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CompareController extends Controller
{
    /**
     * The compare page for the units in "?units=villa-a,flat-b" (their slugs, from the compare bar's "Compare Now").
     */
    public function __invoke(Request $request): View
    {
        $slugs = $request->query('units');

        return view('website.compare', CompareUnits::for(array_filter(explode(',', is_string($slugs) ? $slugs : ''))));
    }
}
