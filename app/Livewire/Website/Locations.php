<?php

namespace App\Livewire\Website;

use App\Support\Website\LocationCards;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Locations\Models\Location;

/**
 * The locations page (the design's locations.html): header with the number of active locations, the cards (featured
 * first, then the admin's order) and "Load More". A card opens the location's page (its properties).
 */
class Locations extends Component
{
    private const FIRST_PAGE = 9;

    private const MORE = 6;

    /** Most cards one page shows ("Load More" stops here), so one request stays light. */
    private const MAX = 96;

    /** The search box: a name, or a compound's name (AKAR-47). */
    #[Url(except: '')]
    public string $q = '';

    /** Changed only by the server (loadMore), never by the browser. */
    #[Locked]
    public int $shown = self::FIRST_PAGE;

    public function updatedQ(): void
    {
        $this->shown = self::FIRST_PAGE;
    }

    public function loadMore(): void
    {
        $this->shown = min(self::MAX, $this->shown + self::MORE);
    }

    public function render(): View
    {
        $total = $this->q === '' ? Location::query()->where('is_active', true)->count() : LocationCards::search(Location::query()->where('is_active', true), $this->q)->count();
        $cards = LocationCards::from(LocationCards::search(LocationCards::query(), $this->q)->limit($this->shown)->get());

        return view('livewire.website.locations', [
            'total' => $total,
            'cards' => $cards,
            'more' => $total > count($cards) && $this->shown < self::MAX,
        ]);
    }
}
