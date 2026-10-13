<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
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

}
