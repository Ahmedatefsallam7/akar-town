<?php

namespace App\Livewire\Website;

use App\Support\Website\PropertyCards;
use App\Support\Website\PropertySearch;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The properties listing (the design's listings.html): filters kept in the URL (so a search can be shared and the
 * home quick search lands here), applied with "Apply" as in the design; search and sort apply at once.
 */
class Properties extends Component
{
    private const FIRST_PAGE = 9;

    private const MORE = 6;

    #[Url(except: '')]
    public string $q = '';

    #[Url]
    public array $type = [];

    #[Url]
    public array $price = [];

    #[Url]
    public array $beds = [];

    #[Url]
    public array $baths = [];

    #[Url]
    public array $area = [];

    #[Url(as: 'area_min', except: '')]
    public string $areaMin = '';

    #[Url(as: 'area_max', except: '')]
    public string $areaMax = '';

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

    #[Url(except: '')]
    public string $sort = '';

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
        $this->reset();
    }

    public function loadMore(): void
    {
        $this->perPage += self::MORE;
    }

    public function render(): View
    {
        $query = PropertySearch::query([
            'q' => $this->q,
            'type' => $this->type,
            'price' => $this->price,
            'beds' => $this->beds,
            'baths' => $this->baths,
            'area' => $this->area,
            'area_min' => $this->areaMin,
            'area_max' => $this->areaMax,
            'floor_min' => $this->floorMin,
            'floor_max' => $this->floorMax,
            'status' => $this->status,
            'amenities' => $this->amenities,
            'finishing' => $this->finishing,
            'intent' => $this->intent,
            'category' => $this->category,
            'location' => $this->location,
            'project' => $this->project,
            'sort' => $this->sort,
        ]);

        $total = (clone $query)->reorder()->count();
        $units = PropertyCards::eagerLoad($query)->limit($this->perPage)->get();

        return view('livewire.website.properties', [
            'total' => $total,
            'cards' => PropertyCards::from($units),
            'advancedCount' => count(array_filter([$this->areaMin, $this->areaMax, $this->floorMin, $this->floorMax], 'strlen')) + count($this->status) + count($this->amenities),
        ]);
    }
}
