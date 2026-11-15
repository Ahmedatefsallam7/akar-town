<?php

namespace App\Livewire\Website;

use App\Support\Website\DeveloperCards;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Developers\Models\Developer;

/**
 * The developers page (the design's developers.html): header with the number of published developers, the cards
 * (featured first, then the ones with most projects) and "Load More". A card opens the developer's page.
 */
class Developers extends Component
{
    private const BATCH = 6;

    /** Most cards one page shows ("Load More" stops here), so one request stays light. */
    private const MAX = 96;

    /** The search box: a name, or a compound's name (AKAR-47). */
    #[Url(except: '')]
    public string $q = '';

    /** Changed only by the server (loadMore), never by the browser. */
    #[Locked]
    public int $shown = self::BATCH;

    public function updatedQ(): void
    {
        $this->shown = self::BATCH;
    }

    public function loadMore(): void
    {
        $this->shown = min(self::MAX, $this->shown + self::BATCH);
    }

    public function render(): View
    {
        $total = $this->q === '' ? Developer::query()->where('is_published', true)->count() : DeveloperCards::search(Developer::query()->where('is_published', true), $this->q)->count();
        $cards = DeveloperCards::from(DeveloperCards::search(DeveloperCards::query(), $this->q)->limit($this->shown)->get());

        return view('livewire.website.developers', [
            'total' => $total,
            'cards' => $cards,
            'more' => $total > count($cards) && $this->shown < self::MAX,
        ]);
    }
}
