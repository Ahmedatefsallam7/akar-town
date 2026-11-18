<?php

namespace App\Livewire\Website;

use App\Support\Website\FooterStats;
use App\Support\Website\PropertyCards;
use App\Support\Website\PropertySearch;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The properties listing (the design's listings.html): filters kept in the URL (so a search can be shared and the
 * home quick search lands here), applied with "Apply" as in the design; search and sort apply at once.
 */
class Properties extends Component
{
    private const FIRST_PAGE = 6;

    private const MORE = 6;

    /** Most cards one page shows ("Load More" stops here), so one request stays light. */
    private const MAX = 96;

    #[Url(except: '')]
    public string $q = '';

    #[Url]
    public array $type = [];

    /** Compound slugs (the locations tree's last level, AKAR-46). */
    #[Url]
    public array $compound = [];

    #[Url]
    public array $price = [];

    #[Url]
    public array $beds = [];

    #[Url]
    public array $baths = [];

    #[Url]
    public array $area = [];

    /** The two sizes (AKAR-52): built-up area (BUA) and land area, each from – to. */
    #[Url(as: 'bua_min', except: '')]
    public string $buaMin = '';

    #[Url(as: 'bua_max', except: '')]
    public string $buaMax = '';

    #[Url(as: 'land_min', except: '')]
    public string $landMin = '';

    #[Url(as: 'land_max', except: '')]
    public string $landMax = '';

    #[Url(as: 'floor_min', except: '')]
    public string $floorMin = '';

    #[Url(as: 'floor_max', except: '')]
    public string $floorMax = '';

    #[Url]
    public array $status = [];

    #[Url]
    public array $amenities = [];

    #[Url]
    public array $finishing = [];

    #[Url(except: '')]
    public string $intent = '';

    #[Url(except: '')]
    public string $category = '';

    #[Url(except: '')]
    public string $location = '';

    #[Url(except: '')]
    public string $project = '';

    /** A location's page (/locations/{slug}): the listing stays in that location. Set by the page only. */
    #[Locked]
    public string $place = '';

    #[Url(except: '')]
    public string $sort = '';

    /** Changed only by the server (loadMore / filters), never by the browser. */
    #[Locked]
    public int $perPage = self::FIRST_PAGE;

    public function updatedQ(): void
    {
        $this->perPage = self::FIRST_PAGE;
    }

    public function updatedSort(): void
    {
        $this->perPage = self::FIRST_PAGE;
    }

    /**
     * The design's "Apply": the filters chosen since the last request are sent with it.
     */
    public function apply(): void
    {
        $this->perPage = self::FIRST_PAGE;
    }

    public function clear(): void
    {
        $this->resetExcept('place');
    }

    public function loadMore(): void
    {
        $this->perPage = min(self::MAX, $this->perPage + self::MORE);
    }

    public function render(): View
    {
        $filters = [
            'q' => $this->q,
            'type' => $this->type,
            'compound' => $this->compound,
            'price' => $this->price,
            'beds' => $this->beds,
            'baths' => $this->baths,
            'area' => $this->area,
            'bua_min' => $this->buaMin,
            'bua_max' => $this->buaMax,
            'land_min' => $this->landMin,
            'land_max' => $this->landMax,
            'floor_min' => $this->floorMin,
            'floor_max' => $this->floorMax,
            'status' => $this->status,
            'amenities' => $this->amenities,
            'finishing' => $this->finishing,
            'intent' => $this->intent,
            'category' => $this->category,
            'location' => $this->place ?: $this->location,
            'project' => $this->project,
            'sort' => $this->sort,
        ];
        $query = PropertySearch::query($filters);

        // Without filters it's every published unit: the footer's cached count (no count over the whole table).
        $total = array_filter(Arr::except($filters, 'sort'), 'filled')
            ? (clone $query)->reorder()->count()
            : FooterStats::get()['listings'];
        $units = PropertyCards::eagerLoad($query)->limit($this->perPage)->get();

        return view('livewire.website.properties', [
            'total' => $total,
            'more' => $total > count($units) && $this->perPage < self::MAX,
            'cards' => PropertyCards::from($units),
            'advancedCount' => count(array_filter([$this->buaMin, $this->buaMax, $this->landMin, $this->landMax, $this->floorMin, $this->floorMax], 'strlen')) + count($this->status) + count($this->amenities),
        ]);
    }
}
