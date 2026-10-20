<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Support\Website\Favourites;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FavouritesController extends Controller
{
    /**
     * Saves the signed-in user's heart clicks ({"add": [12, "project-3"], "remove": [...]}): one click, or on the
     * first page after signing in, the list the browser kept as a guest.
     */
    public function __invoke(Request $request): Response
    {
        $data = $request->validate([
            'add' => ['array', 'max:'.Favourites::MAX],
            'remove' => ['array', 'max:'.Favourites::MAX],
        ]);

        Favourites::update($request->user('website'), $data['add'] ?? [], $data['remove'] ?? []);

        return response()->noContent();
    }
}
